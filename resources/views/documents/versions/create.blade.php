<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Upload version — {{ $document->title }}</title>
</head>
<body>
    <main style="max-width: 760px; margin: 3rem auto; padding: 1rem; font-family: system-ui, sans-serif;">
        <p><a href="{{ url('/admin') }}">Return to document management</a></p>
        <h1>Upload a new version</h1>
        <p><strong>{{ $document->reference_number }}</strong> — {{ $document->title }}</p>

        @if (session('status'))
            <p role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div role="alert">
                <p>Please correct the following:</p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('documents.versions.store', $document) }}" enctype="multipart/form-data">
            @csrf
            <p>
                <label for="file">Document file (PDF, Word, Excel, PowerPoint, or TXT; maximum 20 MB)</label><br>
                <input id="file" name="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" required>
            </p>
            <p>
                <label for="change_notes">Change notes</label><br>
                <textarea id="change_notes" name="change_notes" rows="4" maxlength="5000" style="width:100%">{{ old('change_notes') }}</textarea>
            </p>
            <button type="submit">Upload draft version</button>
        </form>
    </main>
</body>
</html>
