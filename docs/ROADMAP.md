# Implementation Roadmap

## Phase 1 — Foundation and document repository
- [x] Initialize repository and Laravel/Filament dependency baseline.
- [x] Add domain enums, initial database schema, models, and workflow service.
- [x] Add initial Filament document list/create/edit screens.
- [ ] Verify Composer dependency resolution and run migrations.
- [ ] Finish secure upload and download handling.
- [ ] Add CRUD screens for departments, categories, document types, and review policies.
- [ ] Add factories, seeders, and automated tests.

## Phase 2 — Review and publication
- [x] Snapshot review policy per cycle.
- [x] Implement sequential/parallel review decision processing.
- [x] Preserve current published version until replacement publication succeeds.
- [ ] Add review queue and reviewer decision screens.
- [ ] Add archive action and resubmission UX.

## Phase 3 — Expiry and notifications
- [x] Add reminder event schema and uniqueness constraint.
- [x] Add scheduled expiry reminder command.
- [ ] Add configurable reminder settings, retries, and failure monitor.
- [ ] Add next-review reminders and concurrency tests.

## Phase 4 — Audit, reporting, and hardening
- [x] Add audit log model and workflow audit events.
- [ ] Record downloads and denied access attempts.
- [ ] Add audit browser, expiry dashboard, and reports.
- [ ] Complete permission tests, backup/restore guidance, and deployment docs.
