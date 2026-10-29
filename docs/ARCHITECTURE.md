# Architecture

- Filament panel: authenticated internal UI and resources.
- Eloquent models: documents, versions, reviews, departments, categories, policies, access, audit, and reminders.
- Policies: authorization for viewing, editing, submitting, publishing, archiving, and downloading.
- Services: workflow state changes and audit event recording.
- Scheduled command: reminder generation and delivery.
- Private storage disk: files stored outside the public web root.

## Invariants
- Version number is unique within a document.
- A review cycle belongs to a single document version.
- Policy settings are snapshotted when review starts.
- Publication updates the current version pointer in a database transaction.
- Expiry is independent of workflow status.
- A database uniqueness constraint prevents duplicate reminder events.

A dedicated download controller must authorize the user and requested version before streaming any file. Add upload validation, download auditing, reminder retries, and full permission/concurrency tests before production.
