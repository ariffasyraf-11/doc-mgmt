# Requirements

Internal document management for one organization with departments and employees.

## Included in version 1

- Document metadata and private file storage.
- Categories, document types, departments, and user accounts.
- Version history, checksums, and change notes.
- Configurable sequential or parallel review per document type.
- Minimum reviewer count and optional owner-review setting.
- Reviewer assignment, in-app assignment notifications, comments, approval, rejection, and resubmission.
- Dashboard queue for a reviewer's pending assignments.
- Publishing, replacement, and authorized archive actions.
- Organization, department, restricted, and confidential visibility.
- Expiry and next-review dates.
- In-app database notifications and scheduled expiry reminders.
- Workflow/download audit history and initial recent-activity browser.

## Excluded from version 1

- Email, SMS, and push notifications.
- Electronic signatures.
- OCR or full-text indexing.
- Public/external portals and multi-tenancy.
- Automatic file deletion or automatic unpublishing on expiry.

## Lifecycle rules

1. A version must be uploaded before submission.
2. Submitted versions are frozen against ordinary editing.
3. Each review cycle snapshots its policy and reviewer assignments.
4. The submitter cannot review their own submission by default.
5. Only assigned active reviewers may decide their own pending review.
6. Sequential review requires earlier reviewers to approve first.
7. All assigned reviewers must approve; any rejection rejects the cycle.
8. A rejection must include a reason; the owner must revise and resubmit.
9. Only approved versions can be published.
10. A published version remains current while its replacement is in review.
11. Expired documents remain published and marked expired until an authorized action is taken.
12. Archiving preserves document and version history.
13. File authorization is checked on every download.
14. Historical workflow and audit records are retained.
