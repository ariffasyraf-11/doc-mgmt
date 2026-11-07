# Document Workflow

## Document metadata and reference

Each document has one metadata record identified by a unique reference number. Use this reference in correspondence, searches, reports, and audit follow-up. Category, document type, department, owner, visibility, effective date, expiry date, and next-review date describe the document record, not an individual uploaded file.

Metadata can be prepared while a record is in Draft. Once the document leaves Draft, ordinary editing protects the reference number and descriptive/classification/date metadata to preserve a stable reference for review and publication.

## Lifecycle

Draft → Submitted / Under Review → Approved → Published.

A rejection closes the current review cycle and marks the submitted version as rejected. The document owner revises the content (normally by uploading a new version) and starts a new review cycle. Previous decisions remain in history.

Archiving is an authorized action, not an automatic expiry transition.

## Review policy

Each document type may have one active review policy. The policy supports sequential or parallel review, minimum reviewer count, optional owner review, and mandatory rejection reason. The active policy and assigned reviewer IDs are copied into the review-cycle snapshot. Changes to the document type policy affect future cycles only.

## File version history

Every file upload creates a new version number under the same document reference. Each version retains original filename, MIME type, file size in bytes, SHA-256 checksum, uploader, upload timestamp, change notes, submission/approval/publication timestamps, workflow status, and the version it supersedes.

The version-history screen presents these properties and marks the current published version. A replacement is a separate version; it does not overwrite the previous file. The existing published version remains current while the replacement is under review and changes only when the replacement is approved and published. Historical versions remain stored privately and download permission is checked for each version.

## Assignment and decisions

When a cycle is submitted, each assigned reviewer receives an in-app database notification. Reviewers can see their pending assignments in the dashboard queue and open the document workflow to inspect the file and history. After deciding, assigned reviewers retain read access to the workflow and decision history, but file downloads remain governed by the separate download policy.

Only the assigned active reviewer can decide their own pending assignment. The workflow service re-checks the review/cycle state inside a database transaction to prevent duplicate or stale decisions.

## Publication and replacement

Publishing requires an approved version. The current published-version pointer is updated in a database transaction. When a replacement is published, the previous version is marked superseded and retained in version history. The existing published version remains current while its replacement is under review.

## Expiry and next review

Expiry is independent of workflow status. An expired document stays published and is marked expired until an authorized user takes action. The dashboard shows accessible documents already expired and those expiring within the next 30 days, using document metadata and reference number.

Administrators configure reminder schedules and recipients on each document type. Reminder delivery is in-app only, with retry handling for failed or stale processing records. Next-review reminders do not automatically create a review cycle or change publication status.
