# Implementation Roadmap

This roadmap reflects code present on the active Phase 4 development branch. A checked item means implementation is present; it does not mean production validation is complete.

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
- [x] Enable Filament database notification centre with unread indicators and read-state actions.
- [ ] Add tests for notification delivery/read state, review queue authorization, rejection reasons, audit visibility, and reviewer read access after a completed cycle.
- [ ] Complete authorization regression tests for every Filament action and workflow endpoint.

## Phase 3 — Expiry and notifications

- [x] Add reminder event schema and duplicate-prevention constraint.
- [x] Generate scheduled expiry reminders and deliver in-app database notifications.
- [x] Track retry attempts and recover stale processing events.
- [x] Add administrator-configurable expiry reminder schedules and recipient groups per document type.
- [x] Add configurable next-review-date reminders and recipient groups.
- [ ] Add reminder operations dashboard and failure visibility.
- [ ] Add tests for configurable schedules, recipient resolution, duplicate prevention, retries, timezone behavior, and concurrent scheduler execution.

## Phase 4 — Reporting and release hardening

### Implemented on this branch

- [x] Add an expiry dashboard watchlist for accessible documents already expired or expiring within 30 days.
- [x] Add an expired-document dashboard metric alongside the existing expiring-soon metric.
- [x] Improve document metadata as the primary reference for locating and classifying records: reference number, title, category, document type, department, owner, effective date, expiry date, and next-review date.
- [x] Add metadata columns and category/type/department filters to the document register.
- [x] Keep reference numbers unique and prevent reference/metadata edits after the document leaves Draft.
- [x] Expand the version-history view with file name, MIME type, size, SHA-256 checksum, uploader, upload/submission/approval/publication timestamps, superseded-version link, and change notes.
- [x] Explicitly identify the current published file version in the version history.
- [x] Load version metadata and document reference metadata efficiently for the workflow/history screen.

### Remaining

- [ ] Add advanced audit filters and a controlled audit export.
- [ ] Add retention review pages and approved retention execution controls.
- [ ] Complete upload/download/workflow permission and regression tests, including metadata visibility and historical-version access.
- [ ] Document backup/restore, retention operations, and deployment procedures.
- [ ] Validate large-file behavior, production MySQL, scheduler operation, and restore drills.
- [ ] Resolve Composer PHP platform metadata mismatch and verify the lock file using Composer 2.

## Current release constraints

- Notifications are in-app/database only; email, SMS, and push are out of scope.
- Expiry does not automatically unpublish or archive a document.
- Next-review reminders do not automatically create a review cycle.
- A rejected review cycle is closed; the owner must revise and submit another cycle.
- The active review policy is snapshotted per cycle so later policy edits do not change work already in progress.
- The review policy UI currently offers sequential/parallel mode, minimum reviewer count, owner-review permission, and the single supported rejection behavior.
- Document metadata identifies and classifies the document record; file properties and checksum belong to each individual version.
- Historical file versions remain retained; publishing a replacement does not erase the prior version.
