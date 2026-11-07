# Architecture

For supported framework/runtime versions and implementation conventions, see [Development Standards](DEVELOPMENT_STANDARDS.md).

## Application components

- **Filament panel:** authenticated internal UI, document register, metadata filters, expiry watchlist, dashboard statistics, reviewer queue, recent-document list, restricted audit activity table, and database notification centre.
- **Eloquent models:** documents, versions, review cycles, reviewer assignments, departments, categories, document types, review policies, access grants, audit events, downloads, and reminders.
- **Policies:** authorization for viewing, editing, submitting, publishing, archiving, and downloading. Private file downloads are authorized server-side.
- **Workflow services:** transactional submission, reviewer decisions, publication, replacement, archiving, and audit event recording.
- **Notifications:** Laravel database notifications for reviewer assignments, expiry reminders, and next-review reminders. No external notification channel is enabled.
- **Scheduled command:** creates and processes configured document reminders.
- **Private storage disk:** document files are stored outside the public web root.

## Document metadata as reference

The `documents` record is the authoritative metadata/reference record. Its reference number is unique and identifies the document independently of its physical file. Metadata includes title, description, category, document type, department, owner, visibility, effective date, expiry date, next-review date, and workflow status.

The document register uses this metadata for searching, filtering, sorting, dashboard reporting, and expiry watchlists. Category, document type, and department are linked reference data. Once a document leaves Draft, the reference number and descriptive/classification metadata are protected from ordinary edits. File-specific properties are stored separately for each version.

## File version history

Each upload creates a separate `document_versions` row and a file in private storage. A version records its sequence number, original filename, MIME type, byte size, SHA-256 checksum, uploader, change notes, workflow status, lifecycle timestamps, and optional superseded-version relationship.

The document version/workflow screen is a Filament 5 resource page backed by Livewire 4. It renders document metadata and version history with Filament UI components; checksum verification is a Livewire action that updates the displayed result without a full-page redirect. The workflow history displays these attributes and identifies the version referenced by `current_published_version_id`. The current published pointer changes only after an approved replacement is successfully published. Earlier versions remain in history and remain subject to the version-specific download policy. The SHA-256 checksum is calculated at upload and can be re-calculated on demand from private storage. Each verification creates an immutable `document_version_integrity_checks` history row and an audit event; it never changes the original checksum, file, or workflow status. Verification requires document-view authorization and does not grant download permission. The checksum supports integrity checking; it is not a substitute for authorization or backup.

## Notification centre

The Admin panel enables Filament's database notification feature in AdminPanelProvider. Filament renders the notification bell/dropdown, unread indicators, and read-state actions. Review assignment notifications are written when reviewers are assigned; expiry and next-review notifications are written by the reminder processor. Document links lead to the workflow route, where normal document policies continue to apply.

## Reminder configuration and processing

Reminder settings are stored on each document type as JSON arrays: expiry_reminder_days, expiry_reminder_recipients, next_review_reminder_days, and next_review_reminder_recipients.

The scheduled command evaluates published documents against expiry and next-review dates. It resolves selected recipient groups, creates idempotent reminder records using the unique document/recipient/type/date key, and delivers database notifications. Failed attempts are retried up to five times; stale processing records can be reclaimed after 15 minutes.

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

Workflow transitions and downloads record audit events. The dashboard audit browser is restricted to administrators and document managers. Document visibility is enforced by policies and every file-download endpoint; UI visibility alone is not authorization.

## Operational validation still required

Run migrations and the full test suite against the supported PHP/Composer/MySQL environment. Validate metadata visibility, historical-version download authorization, notification persistence, concurrent review decisions, scheduler execution, timezone behavior, backups, and restore procedures.
