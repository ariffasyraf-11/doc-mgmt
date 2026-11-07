# Architecture

For the supported framework/runtime versions and implementation conventions, see [Development Standards](DEVELOPMENT_STANDARDS.md). New application code must follow that baseline and preserve the domain invariants below.

- **Filament panel:** authenticated internal UI, CRUD resources, dashboard statistics, reviewer queue, recent-document list, restricted audit activity table, and database notification centre in the top navigation.
- **Eloquent models:** documents, versions, review cycles, reviewer assignments, departments, categories, document types, review policies, access grants, audit events, downloads, and reminders.
- **Policies:** authorization for viewing, editing, submitting, publishing, archiving, and downloading. Private file downloads are authorized server-side.
- **Workflow services:** transactional submission, reviewer decisions, publication, replacement, archiving, and audit event recording.
- **Notifications:** Laravel database notifications for reviewer assignments, expiry reminders, and next-review reminders. User uses Laravel's Notifiable trait. The Filament panel enables its database notification bell and polls for updates every 30 seconds. Notification records are scoped to the authenticated user by Laravel's notifiable relationship. No external notification channel is enabled.
- **Scheduled command:** creates and processes configured document reminders.
- **Private storage disk:** document files are stored outside the public web root.

## Notification centre

The Admin panel enables Filament's database notification feature in AdminPanelProvider. Filament renders the notification bell/dropdown, unread indicators, and read-state actions. The notification storage uses Laravel's standard notifications table. Review assignment notifications are written when reviewers are assigned; expiry and next-review notifications are written by the reminder processor. Document links in notification payloads lead to the workflow route, where the normal document policies continue to apply.

## Reminder configuration and processing

Reminder settings are stored on each document type as JSON arrays: expiry_reminder_days, expiry_reminder_recipients, next_review_reminder_days, and next_review_reminder_recipients.

The scheduled command evaluates published documents against their expiry and next-review dates. It resolves selected recipient groups, creates idempotent reminder records using the unique document/recipient/type/date key, and delivers database notifications. Failed attempts are retried up to five times; stale processing records can be reclaimed after 15 minutes.

Recipient groups are resolved at processing time: owner, active administrators/document managers, and active reviewers assigned to the latest review cycle. Clearing all selections disables that reminder category.

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

Run migrations and the full test suite against the supported PHP/Composer/MySQL environment. Validate notification persistence and read-state behavior, concurrent review decisions, download authorization, scheduler execution, reminder recipient resolution, timezone behavior, backups, and restore procedures before production use.
