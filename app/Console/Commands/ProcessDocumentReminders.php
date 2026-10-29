<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentReminder;
use App\Notifications\DocumentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessDocumentReminders extends Command
{
    protected $signature = 'dms:process-reminders';
    protected $description = 'Create and deliver due in-app document expiry reminders.';

    public function handle(): int
    {
        $today = today();

        Document::query()
            ->whereNotNull('expiry_date')
            ->where('workflow_status', 'published')
            ->where(function ($query) use ($today) {
                $query->whereDate('expiry_date', $today->copy()->addDays(90))
                    ->orWhereDate('expiry_date', $today->copy()->addDays(30))
                    ->orWhereDate('expiry_date', $today->copy()->addDays(7))
                    ->orWhereDate('expiry_date', $today);
            })
            ->chunkById(100, function ($documents) use ($today) {
                foreach ($documents as $document) {
                    $days = (int) $today->diffInDays($document->expiry_date, false);
                    $type = match ($days) {
                        90 => 'expiry_90_days',
                        30 => 'expiry_30_days',
                        7 => 'expiry_7_days',
                        0 => 'expired_today',
                        default => null,
                    };

                    if ($type === null || ! $document->owner_id) {
                        continue;
                    }

                    DocumentReminder::query()->firstOrCreate(
                        [
                            'document_id' => $document->id,
                            'recipient_id' => $document->owner_id,
                            'reminder_type' => $type,
                            'due_date' => $document->expiry_date,
                        ],
                        ['status' => 'pending', 'attempts' => 0],
                    );
                }
            });

        $this->deliverPendingReminders();

        $this->info('Document reminder processing completed.');

        return self::SUCCESS;
    }

    private function deliverPendingReminders(): void
    {
        DocumentReminder::query()
            ->where('attempts', '<', 5)
            ->where(function ($query) {
                $query->whereIn('status', ['pending', 'failed'])
                    ->orWhere(function ($stale) {
                        $stale->where('status', 'processing')
                            ->where('updated_at', '<=', now()->subMinutes(15));
                    });
            })
            ->with(['document', 'recipient'])
            ->chunkById(100, function ($reminders) {
                foreach ($reminders as $reminder) {
                    $claimed = DB::transaction(function () use ($reminder) {
                        $locked = DocumentReminder::query()->lockForUpdate()->findOrFail($reminder->id);

                        $isRetryable = in_array($locked->status, ['pending', 'failed'], true)
                            || ($locked->status === 'processing' && $locked->updated_at?->lte(now()->subMinutes(15)));

                        if (! $isRetryable || $locked->attempts >= 5) {
                            return null;
                        }

                        $locked->update([
                            'status' => 'processing',
                            'attempts' => $locked->attempts + 1,
                        ]);

                        return $locked;
                    });

                    if (! $claimed) {
                        continue;
                    }

                    try {
                        if (! $reminder->document || ! $reminder->recipient || ! $reminder->recipient->is_active) {
                            $claimed->update([
                                'status' => 'cancelled',
                                'last_error' => 'Document or active recipient is unavailable.',
                            ]);
                            continue;
                        }

                        $reminder->recipient->notify(
                            new DocumentReminderNotification($reminder->document, $reminder->reminder_type),
                        );

                        $claimed->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
                    } catch (Throwable $exception) {
                        $claimed->update([
                            'status' => 'failed',
                            'last_error' => mb_substr($exception->getMessage(), 0, 1000),
                        ]);
                        report($exception);
                    }
                }
            });
    }
}
