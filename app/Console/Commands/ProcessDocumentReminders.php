<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\Document;
use App\Models\DocumentReminder;
use App\Models\User;
use App\Notifications\DocumentReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class ProcessDocumentReminders extends Command
{
    protected $signature = 'dms:process-reminders';
    protected $description = 'Create and deliver configured in-app document reminders.';

    public function handle(): int
    {
        $today = today();

        Document::query()
            ->where('workflow_status', 'published')
            ->where(function ($query) use ($today) {
                $query->whereBetween('expiry_date', [$today->toDateString(), $today->copy()->addYear()->toDateString()])
                    ->orWhereBetween('next_review_date', [$today->toDateString(), $today->copy()->addYear()->toDateString()]);
            })
            ->with(['documentType', 'owner'])
            ->chunkById(100, function ($documents) use ($today) {
                foreach ($documents as $document) {
                    $type = $document->documentType;

                    if (! $type) {
                        continue;
                    }

                    $this->queueDateReminders(
                        $document,
                        'expiry',
                        $document->expiry_date,
                        $type->expiryReminderDays(),
                        $type->expiryReminderRecipients(),
                        $today,
                    );

                    $this->queueDateReminders(
                        $document,
                        'next_review',
                        $document->next_review_date,
                        $type->nextReviewReminderDays(),
                        $type->nextReviewReminderRecipients(),
                        $today,
                    );
                }
            });

        $this->deliverPendingReminders();

        $this->info('Document reminder processing completed.');

        return self::SUCCESS;
    }

    private function queueDateReminders(
        Document $document,
        string $kind,
        ?Carbon $date,
        array $configuredDays,
        array $recipientTypes,
        Carbon $today,
    ): void {
        if (! $date || ! in_array((int) $today->diffInDays($date, false), array_map('intval', $configuredDays), true)) {
            return;
        }

        $days = (int) $today->diffInDays($date, false);
        $reminderType = $kind === 'expiry'
            ? ($days === 0 ? 'expired_today' : "expiry_{$days}_days")
            : ($days === 0 ? 'next_review_due' : "next_review_{$days}_days");

        foreach ($this->resolveRecipients($document, $recipientTypes) as $recipient) {
            DocumentReminder::query()->firstOrCreate(
                [
                    'document_id' => $document->id,
                    'recipient_id' => $recipient->id,
                    'reminder_type' => $reminderType,
                    'due_date' => $date->toDateString(),
                ],
                [
                    'document_version_id' => $document->current_published_version_id,
                    'status' => 'pending',
                    'attempts' => 0,
                ],
            );
        }
    }

    private function resolveRecipients(Document $document, array $recipientTypes): \Illuminate\Support\Collection
    {
        $ids = collect();

        if (in_array('owner', $recipientTypes, true) && $document->owner_id) {
            $ids->push($document->owner_id);
        }

        if (in_array('document_managers', $recipientTypes, true)) {
            $ids = $ids->merge(
                User::query()
                    ->where('is_active', true)
                    ->whereIn('role', [UserRole::Administrator->value, UserRole::DocumentManager->value])
                    ->pluck('id'),
            );
        }

        if (in_array('assigned_reviewers', $recipientTypes, true)) {
            $latestCycle = $document->reviewCycles()->latest('id')->first();

            if ($latestCycle) {
                $ids = $ids->merge(
                    $latestCycle->reviews()
                        ->whereHas('reviewer', fn ($query) => $query->where('is_active', true))
                        ->pluck('reviewer_id'),
                );
            }
        }

        return User::query()
            ->where('is_active', true)
            ->whereIn('id', $ids->unique()->values())
            ->get();
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
                        $retryable = in_array($locked->status, ['pending', 'failed'], true)
                            || ($locked->status === 'processing' && $locked->updated_at?->lte(now()->subMinutes(15)));

                        if (! $retryable || $locked->attempts >= 5) {
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
