# Permissions

## Roles

- **Administrator:** system administration, review-policy configuration, and audit visibility.
- **Document Manager:** operational document management and audit visibility within the permissions enforced by document policies.
- **Reviewer:** access to documents assigned for review and the ability to decide only their own pending review assignments.
- **Employee:** access to documents according to ownership, organization/department visibility, and explicit grants.

## Document visibility

- **Organization-wide:** available to active employees.
- **Department-only:** available to active employees in the document's department.
- **Restricted:** requires an explicit access grant, except the owner and users assigned to review the document. Assigned reviewers retain read access to workflow and decision history after their review cycle completes; this does not automatically grant file-download access.
- **Confidential:** requires an explicit access grant or assigned review relationship, except the owner.

Confidential access is not automatically granted to every administrator or manager.

## Metadata and version-history access

Document metadata and version-history details are shown only after the document-level view policy authorizes access to the workflow screen. Metadata visibility does not itself grant permission to download a file. Every historical version download is separately checked by the version download policy and recorded in the download log.

## Audit visibility

The dashboard audit-activity browser is available to administrators and document managers. Audit events are read-only through this interface.

## Enforcement

Authorization must be enforced by Laravel policies and every file-download endpoint. UI visibility alone is not authorization. Review decisions must verify the assigned reviewer, active account, pending review status, and active review cycle server-side.
