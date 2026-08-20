# Users

Outline - fill in once `UserService` is built (Step 7, the biggest single controller in the package).

- The "self vs admin-acting-on-another-user" branch that runs through nearly every action: a `null` id (or any id from a non-admin caller) always resolves to `Auth::user()`; only an admin passing a real id targets someone else. Every action below needs both sides tested.
- `get`/`save`/`toggleLocked`/`toggleDelete`/`toggleMfa`/`table`/`list` and their individual permission gates (`view-users`, `manage-users`, `toggle-user-mfa`).
- `save()` is also the `/profile` self-update route - name/username/email/timezone/MFA-flag/default phone/default address/profile image, all in one call.
- `toggleDelete()` explicitly forbids deleting your own account - a distinct error from "not found."
- `toggleMfa()` has two entry points: self (`PATCH /mfa`, no permission needed) and admin-on-another-user (`PATCH /user/{id}/mfa`, needs `toggle-user-mfa`).
- **Dead code found:** `verification()` and `unsubscribe()` exist on the controller but have no route in `routes.php` at all - confirmed via a full read of the routes file. Decide whether these come back (routes.php fix) or get dropped during migration, rather than silently porting unreachable code forward.
- **Known config bug carried forward:** `refreshToken()` reads `cuztomisable.mobile.*`, but `config/mobile.php` is never merged into the app (see [Configuration](10-configuration.md)) - so this always evaluates against hardcoded defaults today, never whatever `mobile.php` actually says.

## See also

- [Authentication](01-authentication.md)
- [Roles & Permissions](06-roles-and-permissions.md)
- [IP Addresses & Trusted Devices](07-ip-addresses.md)
