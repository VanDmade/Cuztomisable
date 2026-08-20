# Roles & Permissions

Outline - fill in once `AccessService` is built.

- Two independent resources (`Role`, `Permission`) plus two separate linking layers: role-to-permission (`Roles\Permission`) and user-to-role/user-to-permission direct grants (`Users\Role`, `Users\Permission`) - a user's effective permissions are the union of both, see `getAllPermissions()`.
- `RoleController::save()`'s reconciliation pattern: `firstOrCreate` (restoring if soft-deleted) for every permission id submitted, then delete any link not in that list - additions and removals in one call. `AccessController::save()` does the same thing for a user's roles AND permissions simultaneously.
- Soft-delete toggle pattern shared across both resources (`toggleDelete()` - first call deletes, second call on an already-deleted row restores).
- `RoleController::removePermission()` - detaching one specific permission from a role, distinct 404 for "role not found" vs "that permission isn't linked to this role."
- `list()` endpoints have no permission gate beyond being logged in (unlike `get`/`table`/`save`/`toggleDelete`, which need `manage-roles-permissions`) - worth being explicit about which endpoints are "read for everyone logged in" vs "admin only."
- This is also where **single-organization scoping** (Step 9 of the refactor plan) will land - `Role`/`Permission` are the two models chosen to get `HasOrganization`, since they're org-administered definition catalogs.

## See also

- [Users](05-users.md)
- [Multi-Tenancy](12-multi-tenancy.md) *(not written yet - lands with Step 9)*
