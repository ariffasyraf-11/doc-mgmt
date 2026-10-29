# Document Workflow

Draft → Submitted → Under Review → Approved → Published.

Rejection returns the version to a rejected state so the owner can revise and submit a new review cycle. Previous decisions remain in history. Archiving is an authorized action, not an automatic expiry transition.

Sequential review prevents later reviewers from deciding before earlier reviewers approve. Parallel review allows decisions in any order, but all assigned reviewers must approve in the initial implementation. Any rejection rejects the cycle.

The policy is copied into the review cycle snapshot. Publishing requires an approved version and updates the current version pointer in a database transaction. The previous published file is retained.

Expiry does not change workflow status. An expired document stays published and is marked expired until an authorized user takes action.
