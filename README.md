# Document Management System (doc-mgmt)

A Laravel-based internal document management system for one organization, with private document storage, version history, configurable review and approval, publishing, expiry tracking, archive management, audit history, and in-app reminders.

## Status

Implementation is starting. See [`docs/ROADMAP.md`](docs/ROADMAP.md) for the phased delivery plan and [`docs/REQUIREMENTS.md`](docs/REQUIREMENTS.md) for the agreed scope.

## Planned stack

- Laravel 13
- A compatible stable Filament release, verified during setup
- MySQL
- Laravel policies and role/permission authorization
- Private file storage
- Laravel database notifications and scheduled reminder processing

## Security principles

- Documents are stored outside the public web root.
- Every view/download is authorized server-side.
- Only approved versions can be published.
- Replacing a published version does not remove the current version until its replacement is approved and published.
- Expired documents remain published but are clearly marked until an authorized action is taken.
- Historical versions, review decisions, and audit records are retained.

## Setup

Setup instructions will be completed alongside the application foundation. Do not use production credentials in `.env.example`.
