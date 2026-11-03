# Architecture

- **Filament panel:** authenticated internal UI, CRUD resources, dashboard statistics, reviewer queue, recent-document list, and restricted audit activity table.
- **Eloquent models:** documents, versions, review cycles, reviewer assignments, departments, categories, document types, review policies, access grants, audit events, downloads, and reminders.
- **Policies:** authorization for viewing, editing, submitting, publishing, archiving, and downloading. Private file downloads are authorized server-side.
- **Workflow services:** transactional submission, reviewer decisions, publication, replacement, archiving, and audit event recording.
- **Notifications:** Laravel database notifications for reviewer assignments and expiry reminders. No external notification channel is enabled.
- **Scheduled command:** creates and processes due expiry reminder events.
- **Private storage disk:** document files are stored outside the public web root.

## Workflow invariants

- Version number is unique within a document.
- A review cycle belongs to one document version.
- Review policy and reviewer assignments are snapshotted when review starts.
- A sequential reviewer cannot decide before earlier reviewers approve.
- A reviewer can decide only their own pending assignment while active.
- A rejection requires a reason and closes the cycle.
- Publication updates the current-version pointer in a database transaction.
- The previous published version remains current until replacement publication succeeds.
- Expiry is independent of workflow status.
- A database uniqueness constraint prevents duplicate reminder events.

## Audit and access

Workflow transitions and downloads record audit events. The dashboard audit browser is restricted to administrators and document managers. Document visibility is enforced by query scopes/policies and every file-download endpoint; UI visibility alone is not authorization.

## Operational validation still required

Run migrations and the full test suite against the supported PHP/Composer/MySQL environment. Validate queue behavior, notification persistence, concurrent review decisions, download authorization, scheduler execution, backups, and restore procedures before production use.
