# Implementation Roadmap

## Phase 1 — Foundation and document repository
- [x] Initialize Laravel 13 / Filament 5 baseline and CI.
- [x] Add domain enums, database schema, models, policies, and workflow services.
- [x] Add document, user, department, category, document type, review policy, and access-grant resources.
- [x] Add private version uploads, authorized downloads, version history, and access/download audit records.
- [x] Add review submission, reviewer decisions, publishing, replacement, and archive workflow UI.
- [x] Add local starter seeder and automated access/workflow tests.
- [ ] Generate and commit a Composer lock file after dependency versions are finalized.
- [ ] Run production-like MySQL migration and security tests.

## Phase 2 — Workflow and permissions hardening
- [x] Snapshot review policy per cycle.
- [x] Enforce sequential and parallel review decisions.
- [x] Preserve the current published version until replacement publication succeeds.
- [x] Enforce restricted/confidential access and grant-level download permissions.
- [ ] Add dedicated review queue and notifications for reviewer assignments.
- [ ] Add audit browser and configurable reviewer/rejection rules.

## Phase 3 — Expiry and notifications
- [x] Add reminder event schema and uniqueness constraint.
- [x] Add scheduled expiry reminders and database notifications.
- [x] Add retry tracking and stale-processing recovery.
- [ ] Add configurable reminder schedules/recipients and next-review reminders.
- [ ] Add reminder dashboard and timezone/concurrency tests.

## Phase 4 — Reporting and release hardening
- [ ] Add expiry dashboard, document reports, and retention review pages.
- [ ] Complete upload/download/workflow permission and regression tests.
- [ ] Add backup/restore, retention operations, and deployment documentation.
- [ ] Validate large-file behavior, production MySQL, scheduler operation, and restore drills.
