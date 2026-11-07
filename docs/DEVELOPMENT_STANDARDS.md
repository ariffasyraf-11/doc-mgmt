# Development Standards and Stack Alignment

This document is the working reference for keeping application code, dependencies, and project documentation aligned with the versions actually used by this repository.

## 1. Source of truth

Use these files in this order when checking the supported stack:

1. `composer.json` — declared dependency constraints.
2. `composer.lock` — exact dependency versions installed by Composer.
3. This document and `README.md` — human-readable project guidance.

Do not assume that the newest available framework release is compatible with this project. Follow the major versions and APIs already selected here. Upgrade a framework or major dependency only as a deliberate change with compatibility review and documentation updates.

## 2. Current technology baseline

The following package versions were read from the repository's `composer.lock` on the Phase 4 development branch.

| Component | Locked version / constraint | Guidance |
|---|---:|---|
| PHP runtime | 8.4.1+ | Use PHP 8.4.1 or later in local and deployment environments. |
| Laravel Framework | 13.39.0 | Laravel 13 APIs and conventions. |
| Filament | 5.11.0 | Filament 5 APIs; do not copy examples written for Filament 3 or 4 without adapting them. |
| Livewire | 4.4.8 | Livewire 4 behavior and APIs. |
| Symfony Console | 8.1.10 | Resolved transitively by Composer. |
| Symfony HttpFoundation | 8.1.10 | Resolved transitively by Composer. |
| PHPUnit | 12.5.42 | PHPUnit 12 test syntax and compatibility. |
| Composer | Composer 2 | Use Composer 2 to install and update dependencies. |
| Database | MySQL | MySQL is the project database target. Confirm the supported server version with the deployment environment; it is not pinned in the manifests. |
| Frontend build tooling | Not declared | There is no root `package.json` in the inspected branch. Do not introduce a Node/Vite build dependency unless the feature requires it. |

The exact package versions above are lock-file observations, not permission to independently upgrade packages. The lock file remains the reproducible dependency set.

## 3. Known version metadata mismatch

At the time this document was written:

- `composer.json` declares PHP `^8.3`.
- `composer.lock` records Symfony 8.1 dependencies that require a newer PHP runtime; the project README already states PHP 8.4.1+.
- The effective documented runtime baseline is therefore PHP 8.4.1+, but the root Composer PHP constraint has not yet been aligned.

Do not treat this mismatch as resolved. Before the next dependency-lock maintenance change:

1. Confirm the minimum PHP version required by every locked package.
2. Update the root PHP constraint in `composer.json` to the agreed minimum (currently expected to be `^8.4.1`).
3. Regenerate the Composer lock metadata using Composer 2 (for example, `composer update --lock` after reviewing the impact).
4. Run `composer validate` and `composer check-platform-reqs` in the supported PHP environment.
5. Review the resulting diff to ensure package versions did not change unintentionally.

Do not edit `composer.lock` manually. Do not change dependency constraints solely to silence a warning.

## 4. General PHP and Laravel coding rules

- Follow PSR-12 formatting and Laravel's established project conventions.
- Use strict, descriptive class, method, variable, route, and database names. Follow the existing namespace and directory structure.
- Keep controllers and Filament pages thin. Put multi-step business operations in focused service classes.
- Use Form Requests or equivalent validated input boundaries where appropriate; do not trust submitted identifiers or hidden UI fields.
- Use Eloquent relationships and query scopes for reusable data access. Avoid duplicated query logic across resources and pages.
- Use database transactions for operations that must succeed or fail together, especially review decisions, publication, version replacement, archiving, and audit recording.
- Use enums/constants for finite domain states where the project already follows that pattern.
- Prefer dependency injection and framework facilities over custom global helpers or duplicated infrastructure.
- Handle expected failures explicitly and provide useful validation feedback. Do not expose stack traces, private paths, or sensitive document metadata to users.
- Avoid adding dependencies when the existing Laravel, Filament, Livewire, or PHP capabilities are sufficient.

## 5. Filament 5 and Livewire 4 rules

- Implement admin UI using Filament 5 components, resources, pages, widgets, actions, forms, tables, and authorization integration.
- Check the installed Filament 5 API before adopting code from tutorials or older projects. Filament 3/4 examples may use incompatible namespaces, method names, or component contracts.
- Keep UI configuration separate from domain rules. A hidden or disabled action is not an authorization boundary.
- Enforce access through Laravel policies, gates, scoped queries, and server-side action handlers.
- Keep Livewire state minimal and validate all state that affects a write operation.
- Use transactions and service methods for consequential actions rather than embedding workflow logic in table callbacks.
- Preserve the existing Admin panel conventions and avoid introducing a second panel or UI framework without an explicit design decision.

## 6. DMS domain invariants that code must preserve

Any feature or refactor must preserve these established rules unless a requirement is explicitly changed:

1. **Private documents:** uploaded document versions remain on private storage outside the public web root. Do not expose the private documents directory through a public storage link.
2. **Authorized downloads:** downloads must use the authenticated, policy-checked route. UI visibility alone is not authorization.
3. **Version replacement:** the currently published version remains accessible until its replacement has passed review and is successfully published.
4. **Expiry behavior:** an expired document remains published and clearly marked until an authorized user takes action. Expiry must not silently unpublish or archive it.
5. **Review snapshots:** review policy and reviewer assignments are snapshotted when a review cycle starts. Later policy edits must not alter an active cycle.
6. **Review decisions:** reviewers may decide only their own eligible pending assignment; sequential order and minimum reviewer rules must be enforced server-side.
7. **Rejection:** a rejection requires a reason and closes the current cycle.
8. **Auditability:** important workflow transitions and document downloads must continue to create audit records.
9. **Notifications:** notifications are Laravel database/in-app notifications only. Do not add email, SMS, or push delivery without an approved scope change.
10. **Reminder idempotency:** reminder processing must preserve duplicate prevention and safe retry behavior.
11. **Timezone:** scheduled reminder behavior uses the configured application timezone; the current default is `Asia/Kuala_Lumpur`.

## 7. Storage, security, and configuration

- Keep credentials and environment-specific values in `.env`; never commit secrets.
- Use `.env.example` only for safe variable names and non-secret defaults.
- Do not hard-code hostnames, credentials, storage paths, or deployment-specific values.
- Validate file type, size, and authorization on the server. Do not rely only on browser-side validation.
- Continue using the private document disk and checksum/version metadata.
- Keep notification queries scoped to the authenticated notifiable user.
- Use least-privilege authorization and test direct route access, not just menu/action visibility.
- Do not log document contents, passwords, tokens, or other sensitive values.

## 8. Database and migration rules

- MySQL is the target database; review migrations for MySQL compatibility.
- Make schema changes through Laravel migrations. Never rely on manual production-only schema edits.
- Add indexes and unique constraints for query paths and idempotency guarantees where appropriate.
- Make migrations safe for the repository's deployment workflow. Consider existing data before changing or dropping columns.
- Use Eloquent casts for JSON, dates, enums, and other typed attributes where appropriate.
- Avoid duplicating existing tables or columns; inspect current models and migrations before introducing schema changes.

## 9. Testing and verification expectations

For each code change, select checks appropriate to its risk:

- PHP syntax checks for changed PHP files.
- Laravel feature/unit tests for business logic, policies, workflow transitions, notifications, and scheduled commands.
- Migration checks against MySQL for schema-sensitive changes.
- Authorization checks for both permitted and denied direct requests.
- Regression checks for document download, replacement, expiry, reminder idempotency, and reviewer access when those areas are affected.

The repository roadmap records outstanding test coverage. Do not describe a test as passed unless it was actually executed in the target environment. CI workflows and GitHub Actions are not part of routine changes unless separately requested.

## 10. Documentation maintenance

When changing code, update the relevant documentation in the same feature branch:

- Stack or dependency changes: this document and `README.md`.
- Domain or component changes: `ARCHITECTURE.md`.
- Review behavior: `WORKFLOW.md`.
- Roles and access: `PERMISSIONS.md`.
- Notifications or reminder processing: `NOTIFICATIONS.md`.
- Planned work and completion state: `ROADMAP.md`.

Document what is implemented separately from what is planned or merely validated locally. Include new environment variables, commands, migrations, scheduler requirements, and operational steps where applicable.

## 11. Change checklist

Before considering a change ready for review:

- [ ] Confirm the active branch is not `main`.
- [ ] Confirm the implementation uses Laravel 13, Filament 5, and Livewire 4 APIs.
- [ ] Check that dependency changes are intentional and reflected in Composer manifests/lock file.
- [ ] Preserve the DMS domain invariants in section 6.
- [ ] Enforce server-side authorization and private file handling.
- [ ] Add or update relevant tests where appropriate.
- [ ] Run the checks that are available and report any that were not run.
- [ ] Update the relevant documentation.
- [ ] Summarize changed files, commits, and verification status in the pull request.

---
Last reviewed against the repository manifests on the Phase 4 development branch. Re-check package versions after any dependency update.
