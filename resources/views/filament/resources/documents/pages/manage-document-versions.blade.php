<x-filament-panels::page>
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

    <x-filament::section heading="Document metadata / reference" description="The document record is the authoritative reference. File properties are retained separately for each uploaded version.">
        <dl class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2 xl:grid-cols-3">
            <div><dt class="text-gray-500 dark:text-gray-400">Reference number</dt><dd class="mt-1 font-semibold">{{ $document->reference_number }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Title</dt><dd class="mt-1 font-semibold">{{ $document->title }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Workflow status</dt><dd class="mt-1">{{ str($document->workflow_status->value)->replace('_', ' ')->title() }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Category</dt><dd class="mt-1">{{ $document->category?->name ?? 'Not set' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Document type</dt><dd class="mt-1">{{ $document->documentType?->name ?? 'Not set' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Department</dt><dd class="mt-1">{{ $document->department?->name ?? 'Not set' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Owner</dt><dd class="mt-1">{{ $document->owner?->name ?? 'Not set' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Effective date</dt><dd class="mt-1">{{ $document->effective_date?->format('Y-m-d') ?? 'Not set' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Expiry date</dt><dd class="mt-1">{{ $document->expiry_date?->format('Y-m-d') ?? 'No expiry date' }}</dd></div>
            <div><dt class="text-gray-500 dark:text-gray-400">Next review date</dt><dd class="mt-1">{{ $document->next_review_date?->format('Y-m-d') ?? 'Not set' }}</dd></div>
            <div class="md:col-span-2 xl:col-span-3"><dt class="text-gray-500 dark:text-gray-400">Description</dt><dd class="mt-1">{{ $document->description ?: 'Not provided' }}</dd></div>
        </dl>
    </x-filament::section>

    @if ($canUpload)
        <x-filament::section heading="Upload a new version" description="Uploading creates a new draft version. Existing versions are never overwritten.">
            <form method="post" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <label for="file" class="mb-1 block text-sm font-medium">Document file (PDF, Word, Excel, PowerPoint, or TXT; maximum 20 MB)</label>
                    <input id="file" name="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" required class="block w-full rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                </div>
                <div>
                    <label for="change_notes" class="mb-1 block text-sm font-medium">Change notes</label>
                    <textarea id="change_notes" name="change_notes" rows="3" maxlength="5000" class="w-full rounded-lg border border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('change_notes') }}</textarea>
                </div>
                <x-filament::button type="submit">Upload draft version</x-filament::button>
            </form>
        </x-filament::section>
    @endif

    <x-filament::section heading="File version history" description="Each upload is retained as a separate version. The current published version remains available until an approved replacement is published.">
        <div class="space-y-5">
            @forelse ($versions as $version)
                <article wire:key="document-version-{{ $version->id }}" class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold">Version {{ $version->version_number }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $version->original_filename }}</p>
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

                    <dl class="mt-4 grid grid-cols-1 gap-3 text-sm md:grid-cols-2 xl:grid-cols-3">
                        <div><dt class="text-gray-500 dark:text-gray-400">File type</dt><dd class="mt-1">{{ $version->mime_type }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">File size</dt><dd class="mt-1">{{ number_format($version->file_size / 1024, 1) }} KB ({{ number_format($version->file_size) }} bytes)</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Uploaded by</dt><dd class="mt-1">{{ $version->uploader?->name ?? 'Unknown user' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Uploaded at</dt><dd class="mt-1">{{ $version->created_at?->format('Y-m-d H:i:s') }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Submitted at</dt><dd class="mt-1">{{ $version->submitted_at?->format('Y-m-d H:i:s') ?? 'Not submitted' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Approved at</dt><dd class="mt-1">{{ $version->approved_at?->format('Y-m-d H:i:s') ?? 'Not approved' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Published at</dt><dd class="mt-1">{{ $version->published_at?->format('Y-m-d H:i:s') ?? 'Not published' }}</dd></div>
                        <div><dt class="text-gray-500 dark:text-gray-400">Replaces version</dt><dd class="mt-1">{{ $version->supersedes?->version_number ? 'Version '.$version->supersedes->version_number : 'Initial version / not specified' }}</dd></div>
                        <div class="md:col-span-2 xl:col-span-3"><dt class="text-gray-500 dark:text-gray-400">Change notes</dt><dd class="mt-1">{{ $version->change_notes ?: 'No change notes provided' }}</dd></div>
                    </dl>

                    <div class="mt-4 rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">SHA-256 checksum</div>
                        <code class="mt-1 block break-all text-xs">{{ $version->checksum ?: 'Checksum unavailable' }}</code>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Latest integrity check:</span>
                                @php($latestIntegrityCheck = $version->integrityChecks->first())
                                @if ($latestIntegrityCheck)
                                    <x-filament::badge :color="match ($latestIntegrityCheck->status) { 'verified' => 'success', 'mismatch', 'missing', 'unreadable' => 'danger', default => 'warning' }">
                                        {{ str($latestIntegrityCheck->status)->replace('_', ' ')->title() }}
                                    </x-filament::badge>
                                    <span class="text-xs text-gray-500">{{ $latestIntegrityCheck->checked_at?->format('Y-m-d H:i:s') }}</span>
                                @else
                                    <x-filament::badge color="gray">Not yet verified</x-filament::badge>
                                @endif
                            </div>
                            <x-filament::button
                                color="gray"
                                outlined
                                icon="heroicon-m-shield-check"
                                wire:click="verifyIntegrity({{ $version->id }})"
                                wire:loading.attr="disabled"
                                wire:target="verifyIntegrity({{ $version->id }})"
                            >
                                <span wire:loading.remove wire:target="verifyIntegrity({{ $version->id }})">Verify Checksum</span>
                                <span wire:loading wire:target="verifyIntegrity({{ $version->id }})">Checking...</span>
                            </x-filament::button>
                        </div>
                    </div>

                    @if ($version->integrityChecks->isNotEmpty())
                        <details class="mt-3 rounded-lg border border-gray-200 p-3 text-sm dark:border-gray-700">
                            <summary class="cursor-pointer font-medium">Integrity verification history ({{ $version->integrityChecks->count() }})</summary>
                            <div class="mt-3 space-y-3">
                                @foreach ($version->integrityChecks as $check)
                                    <div wire:key="integrity-check-{{ $check->id }}" class="border-t border-gray-100 pt-3 dark:border-gray-700">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-medium">{{ $check->checked_at?->format('Y-m-d H:i:s') }}</span>
                                            <x-filament::badge :color="match ($check->status) { 'verified' => 'success', 'mismatch', 'missing', 'unreadable' => 'danger', default => 'warning' }">
                                                {{ str($check->status)->replace('_', ' ')->title() }}
                                            </x-filament::badge>
                                            <span class="text-gray-500 dark:text-gray-400">{{ $check->checker?->name ?? 'Unknown user' }}</span>
                                        </div>
                                        @if ($check->calculated_checksum)
                                            <code class="mt-1 block break-all text-xs text-gray-500">{{ $check->calculated_checksum }}</code>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    @endif

                    <div class="mt-4 flex flex-wrap gap-2">
                        @can('download', $version)
                            <x-filament::button tag="a" :href="route('documents.versions.download', [$document, $version])" color="gray" outlined>Download version</x-filament::button>
                        @endcan

                        @if ($canSubmit && in_array($version->status->value, ['draft', 'rejected'], true))
                            <form method="post" action="{{ route('documents.versions.submit', [$document, $version]) }}" class="flex flex-wrap items-center gap-2">
                                @csrf
                                <label for="reviewers-{{ $version->id }}" class="text-sm">Reviewers</label>
                                <select id="reviewers-{{ $version->id }}" name="reviewer_ids[]" multiple required class="min-w-48 rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900">
                                    @foreach ($reviewers as $reviewer)
                                        <option value="{{ $reviewer->id }}">{{ $reviewer->name }} ({{ $reviewer->email }})</option>
                                    @endforeach
                                </select>
                                <x-filament::button type="submit" :disabled="$reviewers->isEmpty()">Submit for review</x-filament::button>
                            </form>
                        @endif

                        @if ($canPublish && $version->status->value === 'approved')
                            <form method="post" action="{{ route('documents.versions.publish', [$document, $version]) }}">
                                @csrf
                                <x-filament::button type="submit" color="success">Publish approved version</x-filament::button>
                            </form>
                        @endif
                    </div>

                    @if ($version->reviews->isNotEmpty())
                        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-gray-700">
                            <h4 class="font-medium">Review decisions</h4>
                            <div class="mt-2 space-y-3">
                                @foreach ($version->reviews as $review)
                                    <div wire:key="version-review-{{ $review->id }}" class="text-sm">
                                        <p>{{ $review->reviewer?->name }} — {{ str($review->status->value)->replace('_', ' ')->title() }}
                                            @if ($review->comments) · {{ $review->comments }} @endif
                                        </p>
                                        @if ($review->status->value === 'pending' && auth()->id() === $review->reviewer_id)
                                            <form method="post" action="{{ route('documents.versions.reviews.decide', [$document, $version, $review]) }}" class="mt-2 flex flex-wrap items-end gap-2">
                                                @csrf
                                                <div class="min-w-64 flex-1">
                                                    <label for="comments-{{ $review->id }}" class="mb-1 block text-xs text-gray-500">Decision comment (required when rejecting)</label>
                                                    <textarea id="comments-{{ $review->id }}" name="comments" rows="2" maxlength="5000" class="w-full rounded-lg border border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900"></textarea>
                                                </div>
                                                <x-filament::button type="submit" name="decision" value="approved" color="success">Approve</x-filament::button>
                                                <x-filament::button type="submit" name="decision" value="rejected" color="danger">Reject</x-filament::button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
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
            <form method="post" action="{{ route('documents.archive', $document) }}">
                @csrf
                <x-filament::button type="submit" color="danger" outlined>Archive document</x-filament::button>
            </form>
        </x-filament::section>
    @endif
</x-filament-panels::page>
