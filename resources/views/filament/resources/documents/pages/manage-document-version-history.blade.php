<x-filament-panels::page>
    @if (session()->has('status'))
        <x-filament::section>
            <div role="status" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                {{ session('status') }}
            </div>
        </x-filament::section>
    @endif

    <x-filament::section
        heading="{{ $document->reference_number }} · {{ $document->title }}"
        description="Each upload is retained as a separate version. The current published version remains available until an approved replacement is published."
    >
        <div class="space-y-5">
            @forelse ($versions as $version)
                <article wire:key="version-history-{{ $version->id }}" class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
                     </br>
                     </br>
                    <header class="flex flex-wrap items-start justify-between gap-3 bg-gray-50 px-4 py-4 dark:bg-gray-800">
                        <div class="min-w-0">
                            <h2 class="text-base font-semibold">Version {{ $version->version_number }}</h2>
                            <p class="mt-1 break-all text-sm text-gray-600 dark:text-gray-300">{{ $version->original_filename }}</p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <x-filament::badge :color="$version->status->value === 'published' ? 'success' : ($version->status->value === 'rejected' ? 'danger' : 'gray')">
                                {{ str($version->status->value)->replace('_', ' ')->title() }}
                            </x-filament::badge>
                            @if ($document->current_published_version_id === $version->id)
                                <x-filament::badge color="success">Current published version</x-filament::badge>
                            @endif
                        </div>
                    </header>

                    <div class="space-y-4 p-4">
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    <tr><th scope="row" class="w-1/3 bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">File type</th><td class="px-4 py-3">{{ $version->mime_type }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">File size</th><td class="px-4 py-3">{{ number_format($version->file_size / 1024, 1) }} KB ({{ number_format($version->file_size) }} bytes)</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Uploaded by</th><td class="px-4 py-3">{{ $version->uploader?->name ?? 'Unknown user' }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Uploaded at</th><td class="px-4 py-3">{{ $version->created_at?->format('Y-m-d H:i:s') }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Submitted at</th><td class="px-4 py-3">{{ $version->submitted_at?->format('Y-m-d H:i:s') ?? 'Not submitted' }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Approved at</th><td class="px-4 py-3">{{ $version->approved_at?->format('Y-m-d H:i:s') ?? 'Not approved' }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Published at</th><td class="px-4 py-3">{{ $version->published_at?->format('Y-m-d H:i:s') ?? 'Not published' }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Replaces version</th><td class="px-4 py-3">{{ $version->supersedes?->version_number ? 'Version '.$version->supersedes->version_number : 'Initial version / not specified' }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left align-top font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Change notes</th><td class="whitespace-pre-line px-4 py-3">{{ $version->change_notes ?: 'No change notes provided' }}</td></tr>
                                    <tr><th scope="row" class="bg-gray-50 px-4 py-3 text-left align-top font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">SHA-256 checksum</th><td class="break-all px-4 py-3 font-mono text-xs">{{ $version->checksum ?: 'Checksum unavailable' }}</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <section class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-sm font-semibold">Integrity status</h3>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Latest integrity check</span>
                                    @php($latestIntegrityCheck = $version->integrityChecks->first())
                                    @if ($latestIntegrityCheck)
                                        <x-filament::badge :color="match ($latestIntegrityCheck->status) { 'verified' => 'success', 'mismatch', 'missing', 'unreadable' => 'danger', default => 'warning' }">
                                            {{ str($latestIntegrityCheck->status)->replace('_', ' ')->title() }}
                                        </x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray">Not yet verified</x-filament::badge>
                                    @endif
                                </div>
                            </div>

                            @if ($latestIntegrityCheck)
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Checked {{ $latestIntegrityCheck->checked_at?->format('Y-m-d H:i:s') }}
                                    by {{ $latestIntegrityCheck->checker?->name ?? 'Unknown user' }}
                                </p>
                            @endif
                        </section>

                        <div class="flex flex-wrap items-center gap-2">
                            <x-filament::button
                                color="gray"
                                outlined
                                icon="heroicon-m-shield-check"
                                wire:click="verifyIntegrity({{ $version->id }})"
                                wire:loading.attr="disabled"
                                wire:target="verifyIntegrity({{ $version->id }})"
                            >
                                <span wire:loading.remove wire:target="verifyIntegrity({{ $version->id }})">Verify checksum</span>
                                <span wire:loading wire:target="verifyIntegrity({{ $version->id }})">Checking...</span>
                            </x-filament::button>
                            @can('download', $version)
                                <x-filament::button tag="a" :href="route('documents.versions.download', [$document, $version])" color="gray" outlined>
                                    Download version
                                </x-filament::button>
                            @endcan
                        </div>

                        @if ($version->integrityChecks->isNotEmpty())
                            <details class="rounded-lg border border-gray-200 p-4 text-sm dark:border-gray-700">
                                <summary class="cursor-pointer font-medium">Integrity verification history ({{ $version->integrityChecks->count() }})</summary>
                                <div class="mt-3 overflow-x-auto">
                                    <table class="w-full divide-y divide-gray-200 text-left dark:divide-gray-700">
                                        <thead>
                                            <tr class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                <th scope="col" class="px-3 py-2 font-medium">Checked at</th>
                                                <th scope="col" class="px-3 py-2 font-medium">Result</th>
                                                <th scope="col" class="px-3 py-2 font-medium">Checked by</th>
                                                <th scope="col" class="px-3 py-2 font-medium">Calculated checksum</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach ($version->integrityChecks as $check)
                                        <tr wire:key="integrity-check-{{ $check->id }}">
                                            <td class="whitespace-nowrap px-3 py-3 align-top">{{ $check->checked_at?->format('Y-m-d H:i:s') }}</td>
                                            <td class="px-3 py-3 align-top">
                                                <x-filament::badge :color="match ($check->status) { 'verified' => 'success', 'mismatch', 'missing', 'unreadable' => 'danger', default => 'warning' }">
                                                    {{ str($check->status)->replace('_', ' ')->title() }}
                                                </x-filament::badge>
                                            </td>
                                            <td class="px-3 py-3 align-top">{{ $check->checker?->name ?? 'Unknown user' }}</td>
                                            <td class="break-all px-3 py-3 align-top font-mono text-xs">{{ $check->calculated_checksum ?: 'Not available' }}</td>
                                        </tr>
                                    @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </details>
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
</x-filament-panels::page>
