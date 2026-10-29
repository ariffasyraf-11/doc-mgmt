<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Document workflow — {{ $document->title }}</title>
</head>
<body>
<main style="max-width: 900px; margin: 2rem auto; padding: 1rem; font-family: system-ui, sans-serif;">
    <p><a href="{{ url('/admin') }}">Return to document management</a></p>
    <h1>{{ $document->title }}</h1>
    <p><strong>{{ $document->reference_number }}</strong> · {{ $document->workflow_status->value }} · Visibility: {{ $document->visibility->label() }}</p>
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

    <section>
        <h2>Version history</h2>
        @forelse ($document->versions as $version)
            <article style="border:1px solid #ddd; padding:1rem; margin:1rem 0;">
                <h3>Version {{ $version->version_number }} — {{ $version->status->value }}</h3>
                <p>{{ $version->original_filename }} · Uploaded {{ $version->created_at->format('Y-m-d H:i') }}</p>
                @if ($version->change_notes) <p>{{ $version->change_notes }}</p> @endif

                @if ($document->current_published_version_id === $version->id && $document->workflow_status->value !== 'archived')
                    <p><a href="{{ route('documents.versions.download', [$document, $version]) }}">Download current published version</a></p>
                @endif

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
            <form method="post" action="{{ route('documents.archive', $document) }}">
                @csrf
                <button type="submit">Archive document</button>
            </form>
        </section>
    @endif
</main>
</body>
</html>
