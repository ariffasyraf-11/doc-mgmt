# Implementation Roadmap

This roadmap reflects the repository implementation on the active development branch. Checkboxes describe code present in the repository; they do not imply that production validation has been completed.

## Phase 1 — Foundation and document repository

- [x] Initialize Laravel 13 / Filament 5 application baseline.
- [x] Add domain enums, database schema, Eloquent models, policies, and workflow services.
- [x] Add Filament resources for documents, users, departments, categories, document types, review policies, and access grants.
- [x] Add private document uploads, SHA-256 checksums, version history, and authorized download route.
- [x] Record download and workflow audit events.
- [x] Add review submission, reviewer decisions, publishing, replacement, and archive workflow UI.
- [x] Add starter seeder and initial access/workflow tests.
- [x] Add dashboard statistics and recent-document list.
- [ ] Finalize Composer platform metadata and lock-file consistency.
- [ ] Run the full suite and production-like MySQL migration/security checks.

## Phase 2 — Workflow and permissions hardening

- [x] Snapshot the active document-type review policy when a review cycle starts.
- [x] Support sequential and parallel review decisions.
- [x] Configure minimum reviewer count and whether the document owner may review.
- [x] Keep the current published version accessible until an approved replacement is published.
- [x] Enforce restricted/confidential visibility and grant-level download permissions.
- [x] Add reviewer pending queue to the dashboard.
- [x] Send in-app database notifications when reviewers are assigned.
- [x] Require a reason when a reviewer rejects a submission.
- [x] Add a recent audit-activity browser for administrators and document managers.
- [ ] Add notification links/read-state UX and reviewer workload/overdue indicators.
- [ ] Expand rejection policy choices beyond the current reject-cycle-and-revise behavior.
- [ ] Add dedicated tests for notification delivery, review queue authorization, rejection reasons, audit visibility, and reviewer read access after a completed cycle.
- [ ] Complete authorization regression tests for every Filament action and workflow endpoint.

## Phase 3 — Expiry and notifications

- [x] Add reminder event schema and duplicate-prevention constraint.
- [x] Generate scheduled expiry reminders and deliver in-app database notifications.
- [x] Track retry attempts and recover stale processing events.
- [ ] Add administrator-configurable reminder schedules and recipients.
- [ ] Add next-review-date reminders.
- [ ] Add reminder operations dashboard, failure visibility, and timezone/concurrency tests.

## Phase 4 — Reporting and release hardening

- [ ] Add expiry dashboard, document reports, and retention review pages.
- [ ] Add advanced audit filters, export, and retention controls.
- [ ] Complete upload/download/workflow permission and regression tests.
- [ ] Document backup/restore, retention operations, and deployment procedures.
- [ ] Validate large-file behavior, production MySQL, scheduler operation, and restore drills.

## Current release constraints

- Notifications are in-app/database only; email, SMS, and push are out of scope.
- Expiry does not automatically unpublish or archive a document.
- A rejected review cycle is closed; the owner must revise and submit another cycle.
- The active review policy is snapshotted per cycle so later policy edits do not change work already in progress.
- The review policy UI currently offers sequential/parallel mode, minimum reviewer count, owner-review permission, and the single supported rejection behavior.
