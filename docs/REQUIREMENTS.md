# Requirements

Internal document management for one organization with departments and employees.

## Included in version 1
- Document metadata and private file storage.
- Categories, document types, departments, and users.
- Version history and change notes.
- Configurable sequential or parallel review per document type.
- Reviewer assignment, comments, approval, rejection, and resubmission.
- Publishing, replacement, and authorized archive actions.
- Organization, department, restricted, and confidential visibility.
- Expiry and next-review dates.
- In-app database notifications and scheduled reminders.
- Audit history and operational reports.

## Excluded from version 1
- Email, SMS, and push notifications.
- Electronic signatures.
- OCR or full-text indexing.
- Public/external portals, multi-tenancy, and automatic file deletion.

## Lifecycle rules
1. A version must be uploaded before submission.
2. Submitted versions are frozen against ordinary editing.
3. Each review cycle snapshots its policy.
4. The submitter cannot review their own submission by default.
5. Only approved versions can be published.
6. A published version remains current while its replacement is in review.
7. Expired documents remain published and marked expired until an authorized action is taken.
8. Archiving preserves history.
9. File authorization is checked on every download.
10. Historical workflow and audit records are retained.
