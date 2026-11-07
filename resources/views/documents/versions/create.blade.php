<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document workflow — {{ $document->title }}</title>
</head>
<body>
<main style="max-width: 1000px; margin: 2rem auto; padding: 1rem; font-family: system-ui, sans-serif;">
    <p><a href="{{ url('/admin') }}">Return to document management</a></p>

    <h1>{{ $document->title }}</h1>
    <p><strong>Reference:</strong> {{ $document->reference_number }} ·
        <strong>Status:</strong> {{ $document->workflow_status->value }} ·
        <strong>Visibility:</strong> {{ $document->visibility->label() }}</p>

    <section aria-labelledby="metadata-heading">
        <h2 id="metadata-heading">Document metadata / reference</h2>
        <dl>
            <dt>Description</dt><dd>{{ $document->description ?: 'Not provided' }}</dd>
            <dt>Category</dt><dd>{{ $document->category?->name ?? 'Not set' }}</dd>
            <dt>Document type</dt><dd>{{ $document->documentType?->name ?? 'Not set' }}</dd>
            <dt>Department</dt><dd>{{ $document->department?->name ?? 'Not set' }}</dd>
            <dt>Owner</dt><dd>{{ $document->owner?->name ?? 'Not set' }}</dd>
            <dt>Effective date</dt><dd>{{ $document->effective_date?->format('Y-m-d') ?? 'Not set' }}</dd>
            <dt>Expiry date</dt><dd>{{ $document->expiry_date?->format('Y-m-d') ?? 'No expiry date' }}</dd>
            <dt>Next review date</dt><dd>{{ $document->next_review_date?->format('Y-m-d') ?? 'Not set' }}</dd>
        </dl>
        <p>Use this metadata and reference number to identify, classify, search, and report on the document record. File-level properties are recorded separately for each version below.</p>
    </section>

    @if ($document->isExpired())
        <p role="status"><strong>Expired:</strong> This document remains published until an authorized action is taken.</p>
    @endif
    @if (session('status')) <p role="status">{{ session('status') }}</p> @endif
    @if ($errors->any())
        <div role="alert"><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if ($canUpload)
        <section>
            <h2>Upload a new version</h2>
            <form method="post" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data">
                @csrf
                <p><label for="file">Document file (PDF, Word, Excel, PowerPoint, or TXT; maximum 20 MB)</label><br>
                    <input id="file" name="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" required></p>
                <p><label for="change_notes">Change notes</label><br>
                    <textarea id="change_notes" name="change_notes" rows="3" maxlength="5000" style="width:100%">{{ old('change_notes') }}</textarea></p>
                <button type="submit">Upload draft version</button>
            </form>
        </section>
    @endif

    <section aria-labelledby="version-history-heading">
        <h2 id="version-history-heading">File version history</h2>
        <p>Each upload is retained as a separate file version. The published version remains current until an approved replacement is published.</p>
        @forelse ($document->versions as $version)
            <article style="border:1px solid #ddd; padding:1rem; margin:1rem 0;">
                <h3>Version {{ $version->version_number }} — {{ $version->status->value }}
                    @if ($document->current_published_version_id === $version->id)
                        <strong>(Current published version)</strong>
                    @endif
                </h3>
                <dl>
                    <dt>Original filename</dt><dd>{{ $version->original_filename }}</dd>
                    <dt>File type</dt><dd>{{ $version->mime_type }}</dd>
                    <dt>File size</dt><dd>{{ number_format($version->file_size / 1024, 1) }} KB ({{ number_format($version->file_size) }} bytes)</dd>
                    <dt>SHA-256 checksum</dt><dd><code>{{ $version->checksum }}</code></dd>
                    @php($latestIntegrityCheck = $version->integrityChecks->first())
                    <dt>Latest integrity check</dt>
                    <dd>
                        @if ($latestIntegrityCheck)
                            <strong>{{ str($latestIntegrityCheck->status)->replace('_', ' ')->title() }}</strong>
                            — {{ $latestIntegrityCheck->checked_at?->format('Y-m-d H:i:s') }}
                            @if ($latestIntegrityCheck->checker) by {{ $latestIntegrityCheck->checker->name }} @endif
                        @else
                            Not yet verified
                        @endif
                    </dd>
                    <dt>Uploaded by</dt><dd>{{ $version->uploader?->name ?? 'Unknown user' }}</dd>
                    <dt>Uploaded at</dt><dd>{{ $version->created_at?->format('Y-m-d H:i:s') }}</dd>
                    <dt>Submitted at</dt><dd>{{ $version->submitted_at?->format('Y-m-d H:i:s') ?? 'Not submitted' }}</dd>
                    <dt>Approved at</dt><dd>{{ $version->approved_at?->format('Y-m-d H:i:s') ?? 'Not approved' }}</dd>
                    <dt>Published at</dt><dd>{{ $version->published_at?->format('Y-m-d H:i:s') ?? 'Not published' }}</dd>
                    <dt>Replaces version</dt><dd>{{ $version->supersedes?->version_number ? 'Version '.$version->supersedes->version_number : 'Initial version / not specified' }}</dd>
                    <dt>Change notes</dt><dd>{{ $version->change_notes ?: 'No change notes provided' }}</dd>
                </dl>

                <form method="post" action="{{ route('documents.versions.verify-integrity', [$document, $version]) }}">
                    @csrf
                    <button type="submit">Verify Checksum (SHA-256)</button>
                </form>

                @if ($version->integrityChecks->isNotEmpty())
                    <details>
                        <summary>Integrity verification history ({{ $version->integrityChecks->count() }})</summary>
                        <ul>
                            @foreach ($version->integrityChecks as $integrityCheck)
                                <li>
                                    {{ $integrityCheck->checked_at?->format('Y-m-d H:i:s') }} —
                                    {{ str($integrityCheck->status)->replace('_', ' ')->title() }} —
                                    {{ $integrityCheck->checker?->name ?? 'Unknown user' }}
                                    @if ($integrityCheck->calculated_checksum)
                                        <br><code>{{ $integrityCheck->calculated_checksum }}</code>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif

                @can('download', $version)
                    <p><a href="{{ route('documents.versions.download', [$document, $version]) }}">Download this version</a></p>
                @endcan

                @if ($canSubmit && in_array($version->status->value, ['draft', 'rejected'], true))
                    <form method="post" action="{{ route('documents.versions.submit', [$document, $version]) }}">
                        @csrf
                        <fieldset>
                            <legend>Assign reviewers</legend>
                            @forelse ($reviewers as $reviewer)
                                <label style="display:block">
                                    <input type="checkbox" name="reviewer_ids[]" value="{{ $reviewer->id }}">
                                    {{ $reviewer->name }} ({{ $reviewer->email }})
                                </label>
                            @empty
                                <p>No active users have the Reviewer role. Add reviewers before submitting.</p>
                            @endforelse
                        </fieldset>
                        @if ($reviewers->isNotEmpty()) <button type="submit">Submit for review</button> @endif
                    </form>
                @endif

                @if ($version->reviews->isNotEmpty())
                    <h4>Review decisions</h4>
                    @foreach ($version->reviews as $review)
                        <p>{{ $review->reviewer->name }} — {{ $review->status->value }}
                            @if ($review->comments) · {{ $review->comments }} @endif
                        </p>
                        @if ($review->status->value === 'pending' && auth()->id() === $review->reviewer_id)
                            <form method="post" action="{{ route('documents.versions.reviews.decide', [$document, $version, $review]) }}">
                                @csrf
                                <label for="comments-{{ $review->id }}">Decision comment (required when rejecting)</label><br>
                                <textarea id="comments-{{ $review->id }}" name="comments" rows="2" maxlength="5000"></textarea><br>
                                <button type="submit" name="decision" value="approved">Approve</button>
                                <button type="submit" name="decision" value="rejected">Reject</button>
                            </form>
                        @endif
                    @endforeach
                @endif

                @if ($canPublish && $version->status->value === 'approved')
                    <form method="post" action="{{ route('documents.versions.publish', [$document, $version]) }}">
                        @csrf
                        <button type="submit">Publish approved version</button>
                    </form>
                @endif
            </article>
        @empty
            <p>No versions have been uploaded yet.</p>
        @endforelse
    </section>

    @if ($canArchive && $document->workflow_status->value === 'published')
        <section>
            <h2>Archive</h2>
            <form method="post" action="{{ route('documents.archive', $document) }}" onsubmit="return confirm('Archive this document? The history will be retained.');">
                @csrf
                <button type="submit">Archive document</button>
            </form>
        </section>
    @endif
</main>
</body>
</html>
