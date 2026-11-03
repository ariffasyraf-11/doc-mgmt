# Document Workflow

## Lifecycle

Draft → Submitted / Under Review → Approved → Published.

A rejection closes the current review cycle and marks the submitted version as rejected. The document owner revises the content (normally by uploading a new version) and starts a new review cycle. Previous decisions remain in history.

Archiving is an authorized action, not an automatic expiry transition.

## Review policy

Each document type may have one active review policy. The policy supports:

- **Sequential review:** reviewers decide in their assigned order. A later reviewer cannot decide until earlier reviewers approve.
- **Parallel review:** assigned reviewers may decide in any order.
- **Minimum reviewer count:** submission must include at least the configured number of eligible reviewers.
- **Owner review:** disabled by default; the owner is excluded unless the policy explicitly permits it.
- **Rejection behavior:** the current supported behavior is to reject the whole review cycle and require revision. A rejection reason is mandatory.

The active policy and assigned reviewer IDs are copied into the review-cycle snapshot. Changes to the document type policy affect future cycles only.

## Assignment and decisions

When a cycle is submitted, each assigned reviewer receives an in-app database notification. Reviewers can see their pending assignments in the dashboard queue and open the document workflow to inspect the file and history. After deciding, assigned reviewers retain read access to the workflow and decision history, but file downloads remain governed by the separate download policy.

Only the assigned active reviewer can decide their own pending assignment. The workflow service re-checks the review/cycle state inside a database transaction to prevent duplicate or stale decisions.

## Publication and replacement

Publishing requires an approved version. The current published-version pointer is updated in a database transaction. When a replacement is published, the previous version is marked superseded and retained in version history. The existing published version remains current while its replacement is under review.

## Expiry and next review

Expiry is independent of workflow status. An expired document stays published and is marked expired until an authorized user takes action.

Administrators configure reminder schedules and recipients on each document type:

- Expiry reminders: 90, 30, and 7 days before expiry, and on the expiry date by default.
- Next-review reminders: 30 and 7 days before the next review date, and on the review date by default.
- Recipient groups: document owner, administrators/document managers, and reviewers assigned to the latest review cycle.

The defaults above apply when a document type has no saved reminder configuration. Administrators may disable individual offsets or recipient groups by clearing the relevant selections. A reminder is created once per document, recipient, reminder type, and due date. Delivery is in-app only, with retry handling for failed or stale processing records.

Next-review reminders are notifications only; they do not automatically create a review cycle or change publication status. Expiry likewise does not automatically unpublish or archive a document.
