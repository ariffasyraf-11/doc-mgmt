# Requirements

Internal document management for one organization with departments and employees.

## Included in version 1 and Phase 4 work

- Document metadata and private file storage.
- Unique document reference number used for identification and cross-reference.
- Metadata-based document register search and filters.
- Categories, document types, departments, and user accounts as linked reference data.
- Version history with SHA-256 checksums, file properties, uploader, lifecycle timestamps, and change notes.
- Configurable sequential or parallel review per document type.
- Minimum reviewer count and optional owner-review setting.
- Reviewer assignment, in-app assignment notifications, comments, approval, rejection, and resubmission.
- Dashboard queue for a reviewer's pending assignments.
- Publishing, replacement, and authorized archive actions.
- Organization, department, restricted, and confidential visibility.
- Expiry and next-review dates, expiry watchlist, and dashboard expiry metrics.
- In-app database notifications and scheduled expiry reminders.
- Workflow/download audit history and initial recent-activity browser.

## Excluded from current scope

- Email, SMS, and push notifications.
- Electronic signatures.
- OCR or full-text indexing.
- Public/external portals and multi-tenancy.
- Automatic file deletion or automatic unpublishing on expiry.
- Automatic creation of a review cycle from a next-review reminder.

## Lifecycle and data rules

1. A document metadata record has a unique reference number.
2. Category, document type, department, owner, and dates describe the document record, not an individual file version.
3. Ordinary edits to reference and classification metadata are restricted after the document leaves Draft.
4. A version must be uploaded before submission.
5. Each upload creates a new historical file-version record; it does not overwrite the previous file.
6. Version metadata includes filename, MIME type, size, SHA-256 checksum, uploader, timestamps, status, and change notes.
7. Submitted versions are frozen against ordinary editing.
8. Each review cycle snapshots its policy and reviewer assignments.
9. The submitter cannot review their own submission by default.
10. Only assigned active reviewers may decide their own pending review.
11. Sequential review requires earlier reviewers to approve first.
12. A rejection must include a reason; the owner must revise and resubmit.
13. Only approved versions can be published.
14. A published version remains current while its replacement is in review.
15. Expired documents remain published and marked expired until an authorized action is taken.
16. Archiving preserves document and version history.
17. File authorization is checked on every download.
18. Historical workflow and audit records are retained.
