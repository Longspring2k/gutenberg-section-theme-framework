# Engineering decisions

## Purpose

The PHP is a mechanism demo, not a substitute Gutenberg implementation. It isolates two testable questions: how section packages become discoverable render units, and how reusable data remains shared until an instance is detached.

## Section package contract

A package is one immediate child of a trusted root and contains `section.json` plus `render.php`.

| Field | Required | Contract |
| --- | --- | --- |
| `name` | yes | lower-case `[a-z][a-z0-9-]*` identifier |
| `title` | yes | non-empty string |
| `render` | yes | literal `render.php` |
| `attributes` | no | names mapped to `string`, `bool`, or `int` |

Unknown fields fail discovery. Metadata cannot select a path: the registry constructs `<package>/render.php`. Packages are sorted by canonical name. Invalid and duplicate packages stay out of the registry and produce readable errors.

This is **not** a sandbox for untrusted PHP. The root contains checked-in, code-reviewed application code. A contributor who can add PHP there can execute PHP. The fixed path prevents a metadata-only path-selection mistake; it does not provide filesystem isolation.

## Attribute and rendering boundary

`SectionPackage::render()` validates before including the fixed renderer. Data must contain every declared field, no undeclared fields, and exact scalar types. There is no coercion: `"2"` is not an `int` and `"yes"` is not a `bool`.

Schema definitions are validated during discovery, not deferred until the first render. A renderer runs inside an output buffer; `finally` cleans up on failure so partial markup cannot contaminate the next section or leave an extra buffer open. Renderers are trusted code and must not close their caller's buffer.

Renderers own output context. Samples use `Escaper::html()` for text nodes. Production adapters need distinct URL, attribute, JSON, and rich-HTML policies; one generic helper is not enough for every sink.

## Variation lifecycle

An instance contains a variation reference and overrides. Resolution merges shared data with overrides, with overrides winning. Updating shared data changes future resolutions but does not rewrite an instance.

Detach stores the current merged result as a snapshot and removes the live reference. Later updates cannot change it. Tests verify that mutating a resolved result also does not mutate the instance description. Missing references throw instead of becoming misleading empty content.

## Why alternatives fail

- A central hard-coded registry creates merge contention.
- Recursive discovery admits unrelated nested directories.
- Metadata-selected PHP paths expand executable input.
- Ignoring unknown fields hides typos and contract drift.
- Type coercion masks storage/editor defects.
- Copying shared content into instances makes reuse only an insertion shortcut.
- Reference-only instances cannot support local exceptions or stable detach.
- Writing overrides into the shared record changes every linked instance.

## Error policy

| Condition | Result |
| --- | --- |
| invalid JSON/package | skipped; discovery error recorded |
| duplicate name | later duplicate skipped; explicit error |
| unknown package | exception |
| invalid render attributes | exception before renderer inclusion |
| missing variation | exception |
| invalid override shape | exception |

Discovery reports multiple defects in one run. Runtime operations fail fast because partial or guessed content would conceal broken state.

## WordPress adapter boundary

The demo uses no invented WordPress APIs.

| Portable responsibility | Real WordPress binding point | Not demonstrated |
| --- | --- | --- |
| package registration | `block.json`, `register_block_type()`, render callback | hook execution |
| attribute input | block schema and editor components | UI and serialization |
| rendered markup | dynamic callback and WordPress escaping helpers | theme context/assets |
| shared records | chosen persistence model plus capability checks | database, REST, permissions |
| link/override/detach | editor actions persisting attributes/references | revisions, autosave, conflicts |
| discovery cache | plugin/theme lifecycle and invalidation | deployment and multisite |

Posts, custom post types, options, metadata, and custom tables have different revision, query, permission, and concurrency properties. The demo cannot select among them without product requirements.

## Scenario and test matrix

Discovery reads `callout`, `hero`, and an intentionally broken fixture. A linked hero changes after a shared update. Another hero overrides its heading, then detaches and remains unchanged after another update. Renderers escape text.

| Area | Covered behavior |
| --- | --- |
| discovery | valid/invalid packages and deterministic order |
| package safety | duplicates and fixed renderer filename |
| attributes | unknown fields, wrong types, script-like escaping |
| lifecycle | linked updates, override precedence, detach snapshot |
| isolation | missing references and instance non-mutation |
| failure recovery | schema rejection before registration and output-buffer cleanup after a throwing renderer |
| exact values | missing required fields and numeric strings rejected for integer fields |