# Permissions

Roles: Administrator, Document Manager, Reviewer, Employee.

Visibility:
- Organization-wide: available to active employees.
- Department-only: available to active employees in the document's department.
- Restricted: requires an explicit access grant, except the owner.
- Confidential: requires an explicit access grant or assigned review relationship, except the owner.

Authorization must be enforced by Laravel policies and every file-download endpoint. UI visibility alone is not authorization. Confidential access is not automatically granted to every administrator or manager.
