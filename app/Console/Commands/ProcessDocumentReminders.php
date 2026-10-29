<?php

namespace App\Console\Commands;

use App\Models\Document;
use App\Models\DocumentReminder;
use App\Notifications\DocumentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessDocumentReminders extends Command
{
    protected $signature = 'dms:process-reminders';
    protected $description = 'Create due in-app document expiry reminders.';

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
            ->with('owner')
            ->chunkById(100, function ($documents) {
                foreach ($documents as $document) {
                    if (! $document->owner) continue;

                    $days = (int) today()->diffInDays($document->expiry_date, false);
                    $type = match ($days) {
                        90 => 'expiry_90_days',
                        30 => 'expiry_30_days',
                        7 => 'expiry_7_days',
                        0 => 'expired_today',
                        default => null,
                    };
                    if ($type === null) continue;

                    DB::transaction(function () use ($document, $type) {
                        $reminder = DocumentReminder::query()->firstOrCreate(
                            [
                                'document_id' => $document->id,
                                'recipient_id' => $document->owner_id,
                                'reminder_type' => $type,
                                'due_date' => $document->expiry_date,
                            ],
                            ['status' => 'pending', 'attempts' => 0],
                        );
                        if ($reminder->status === 'sent') return;

                        $reminder->update(['status' => 'processing', 'attempts' => $reminder->attempts + 1]);
                        try {
                            $document->owner->notify(new DocumentReminderNotification($document, $type));
                            $reminder->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
                        } catch (\Throwable $exception) {
                            $reminder->update(['status' => 'failed', 'last_error' => mb_substr($exception->getMessage(), 0, 1000)]);
                            report($exception);
                        }
                    });
                }
            });

        $this->info('Document reminder processing completed.');
        return self::SUCCESS;
    }
}
