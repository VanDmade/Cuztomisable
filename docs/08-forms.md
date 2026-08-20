# Forms

Outline - fill in once real test content lands (Step 16 of the refactor plan).

- What this actually is: a generic multi-step form/wizard progress tracker (`to`/`current` step identifiers + JSON param blobs), keyed per authenticated user - not a document/content feature.
- `updateOrCreate` keyed on `(user_id, current)` - saving the same step twice updates one row rather than accumulating history.
- `to_params`/`current_params`/`form` accept either a raw array or an already-JSON-encoded string on the way in (`normalizeJson()`), and decode back out transparently via the model's own `Attribute` casts on read.
- Naming: this was previously a separate absorbed sub-package ("Formora") - now folded in as a native Cuztomisable feature: `Models\Form`, `Services\FormService`, `Http\Controllers\FormController`. The `formora` table name is unchanged (migrations are additive-only; renaming a live table is a data migration, not a naming decision).

## See also

- [Users](05-users.md)
