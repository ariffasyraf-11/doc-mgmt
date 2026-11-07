<x-filament-panels::page>
    @if ($errors->any())
        <x-filament::section>
            <div role="alert" class="space-y-1 text-sm text-danger-700 dark:text-danger-300">
                <p class="font-medium">Please review the following:</p>
                <ul class="list-inside list-disc">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </x-filament::section>
    @endif

    @if (session()->has('status'))
        <x-filament::section>
            <div role="status" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ session('status') }}
            </div>
        </x-filament::section>
    @endif

    @if ($document->isExpired())
        <x-filament::section>
            <div class="flex items-center gap-2 text-warning-700 dark:text-warning-300">
                <x-heroicon-o-exclamation-triangle class="h-5 w-5" />
                <span class="font-medium">Expired document</span>
                <span class="text-sm">This document remains published until an authorized action is taken.</span>
            </div>
        </x-filament::section>
    @endif

    <x-filament::section heading="Document metadata / reference" description="The document record is the authoritative reference. Use Version history for file metadata, checksums, and integrity checks.">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    <tr><th scope="row" class="w-1/3 py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Reference number</th><td class="py-3 font-semibold">{{ $document->reference_number }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Title</th><td class="py-3 font-semibold">{{ $document->title }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Workflow status</th><td class="py-3">{{ str($document->workflow_status->value)->replace('_', ' ')->title() }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Category</th><td class="py-3">{{ $document->category?->name ?? 'Not set' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Document type</th><td class="py-3">{{ $document->documentType?->name ?? 'Not set' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Department</th><td class="py-3">{{ $document->department?->name ?? 'Not set' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Owner</th><td class="py-3">{{ $document->owner?->name ?? 'Not set' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Effective date</th><td class="py-3">{{ $document->effective_date?->format('Y-m-d') ?? 'Not set' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Expiry date</th><td class="py-3">{{ $document->expiry_date?->format('Y-m-d') ?? 'No expiry date' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left font-medium text-gray-500 dark:text-gray-400">Next review date</th><td class="py-3">{{ $document->next_review_date?->format('Y-m-d') ?? 'Not set' }}</td></tr>
                    <tr><th scope="row" class="py-3 pr-6 text-left align-top font-medium text-gray-500 dark:text-gray-400">Description</th><td class="whitespace-pre-line py-3">{{ $document->description ?: 'Not provided' }}</td></tr>
                </tbody>
            </table>
        </div>
    </x-filament::section>

    @if ($canUpload)
        <x-filament::section heading="Upload a new version" description="Uploading creates a new draft version. Existing versions are never overwritten.">
            <form wire:submit="uploadVersion" class="space-y-4">
                {{ $this->form }}
                <x-filament::button type="submit" wire:loading.attr="disabled" wire:target="uploadVersion">
                    <span wire:loading.remove wire:target="uploadVersion">Upload draft version</span>
                    <span wire:loading wire:target="uploadVersion">Saving version...</span>
                </x-filament::button>
            </form>
        </x-filament::section>
    @endif

    <x-filament::section heading="Review and publication workflow" description="Manage submissions and review decisions here. Detailed file properties and integrity history are in the separate Version history page.">
        <div class="space-y-4">
            @forelse ($versions as $version)
                <article wire:key="workflow-version-{{ $version->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold">Version {{ $version->version_number }}</h3>
                            <p class="mt-1 break-all text-sm text-gray-500 dark:text-gray-400">{{ $version->original_filename }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::badge :color="$version->status->value === 'published' ? 'success' : ($version->status->value === 'rejected' ? 'danger' : 'gray')">
                                {{ str($version->status->value)->replace('_', ' ')->title() }}
                            </x-filament::badge>
                            @if ($document->current_published_version_id === $version->id)
                                <x-filament::badge color="success">Current published version</x-filament::badge>
                            @endif
                        </div>
                    </div>

                    @if ($version->reviews->isNotEmpty())
                        <div class="mt-4 space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <h4 class="font-medium">Review decisions</h4>
                            @foreach ($version->reviews as $review)
                                <div wire:key="version-review-{{ $review->id }}" class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">
                                    <p>
                                        <span class="font-medium">{{ $review->reviewer?->name }}</span>
                                        — {{ str($review->status->value)->replace('_', ' ')->title() }}
                                        @if ($review->comments) · {{ $review->comments }} @endif
                                    </p>
                                    @if ($review->status->value === 'pending' && auth()->id() === $review->reviewer_id)
                                        <div class="mt-3 flex flex-wrap items-end gap-2">
                                            <div class="min-w-64 flex-1">
                                                <label for="comments-{{ $review->id }}" class="mb-1 block text-xs text-gray-500">Decision comment (required when rejecting)</label>
                                                <textarea id="comments-{{ $review->id }}" wire:model="decisionComments.{{ $review->id }}" rows="2" maxlength="5000" class="w-full rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900"></textarea>
                                            </div>
                                            <x-filament::button type="button" color="success" wire:click="decideReview({{ $version->id }}, {{ $review->id }}, 'approved')" wire:loading.attr="disabled" wire:target="decideReview({{ $version->id }}, {{ $review->id }}, 'approved')">Approve</x-filament::button>
                                            <x-filament::button type="button" color="danger" wire:click="decideReview({{ $version->id }}, {{ $review->id }}, 'rejected')" wire:loading.attr="disabled" wire:target="decideReview({{ $version->id }}, {{ $review->id }}, 'rejected')">Reject</x-filament::button>
                                        </div>
                                        @error('comments') <p class="mt-1 text-sm text-danger-600">{{ $message }}</p> @enderror
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mt-4 flex flex-wrap items-center gap-2">
                        @if ($canSubmit && in_array($version->status->value, ['draft', 'rejected'], true))
                            <form wire:submit="submitVersion({{ $version->id }})" class="flex flex-wrap items-center gap-2">
                                <label for="reviewers-{{ $version->id }}" class="text-sm">Assign reviewers</label>
                                <select id="reviewers-{{ $version->id }}" wire:model="reviewerIds.{{ $version->id }}" multiple required class="min-w-48 rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                    @foreach ($reviewers as $reviewer)
                                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }} ({{ $reviewer->email }})</option>
                                    @endforeach
                                </select>
                                <x-filament::button type="submit" :disabled="$reviewers->isEmpty()" wire:loading.attr="disabled" wire:target="submitVersion({{ $version->id }})">
                                    <span wire:loading.remove wire:target="submitVersion({{ $version->id }})">Submit for review</span>
                                    <span wire:loading wire:target="submitVersion({{ $version->id }})">Submitting...</span>
                                </x-filament::button>
                                @error('reviewerIds.'.$version->id) <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                            </form>
                            @if ($reviewers->isEmpty())
                                <p class="text-sm text-gray-500">No active users have the Reviewer role. Add reviewers before submitting.</p>
                            @endif
                        @endif

                        @if ($canPublish && $version->status->value === 'approved')
                            <x-filament::button
                                type="button"
                                color="success"
                                wire:click="publishVersion({{ $version->id }})"
                                wire:loading.attr="disabled"
                                wire:target="publishVersion({{ $version->id }})"
                            >
                                <span wire:loading.remove wire:target="publishVersion({{ $version->id }})">Publish approved version</span>
                                <span wire:loading wire:target="publishVersion({{ $version->id }})">Publishing...</span>
                            </x-filament::button>
                        @endif
                    </div>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700">
                    No file versions have been uploaded yet.
                </div>
            @endforelse
        </div>
    </x-filament::section>

    @if ($canArchive && $document->workflow_status->value === 'published')
        <x-filament::section heading="Archive document" description="Archiving retains document metadata and all historical file versions.">
            <x-filament::button
                type="button"
                color="danger"
                outlined
                wire:click="archiveDocument"
                wire:confirm="Archive this document? The history will be retained."
                wire:loading.attr="disabled"
                wire:target="archiveDocument"
            >
                <span wire:loading.remove wire:target="archiveDocument">Archive document</span>
                <span wire:loading wire:target="archiveDocument">Archiving...</span>
            </x-filament::button>
        </x-filament::section>
    @endif
</x-filament-panels::page>
