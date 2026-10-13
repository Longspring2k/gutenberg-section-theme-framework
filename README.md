# Gutenberg Section Theme Framework

A confidentiality-safe technical showcase of a section-oriented WordPress/Gutenberg architecture. This repository includes a **newly authored, portable PHP mechanism demo** alongside the narrative about original private work.

**Anonymized showcase · Original educational demo · No client source or assets**

PHP 8.3 | JSON metadata | server rendering | reusable variation lifecycle

## What is here

The demo models deterministic discovery of checked-in section packages from strict JSON metadata and reusable variation data with link, local override, update, and detach behavior. It deliberately does **not** pretend to be WordPress: there are no WordPress stubs, fake editor screens, or invented integration APIs.

## Quick start

There are no dependencies or install step. PHP 8.3 is available in the verified environment.

```bash
php tests/run.php
php demo/run.php
```

The current suite ends with `8 passed, 0 failed`. The demo prints discovered package names, one intentional invalid-metadata notice, and rendered HTML showing a linked update, detached snapshot, and escaped markup. A filter that matches no tests exits with an error rather than reporting a false green result.

## Concrete data flow

```text
demo/packages/*/section.json
        │ strict parse + validation
        ▼
SectionRegistry ── deterministic order ──► SectionPackage
        │                                      │
        ▼                                 validated attrs
VariationStore ── link/override/detach ──► render.php ──► escaped HTML
```

Example: `launch` holds shared attributes. `home-hero` remains linked, so an update changes its resolved data. `campaign-hero` overrides only its heading. Detaching it captures the fully resolved data; later shared updates do not affect that snapshot.

## Demonstrated guarantees

- Only immediate child directories with `section.json` are discovered.
- Packages sort by section name; invalid packages are skipped with explicit errors.
- Duplicate names, unknown metadata fields, and arbitrary metadata-selected PHP paths are rejected.
- Render attributes must match declared fields and scalar types exactly.
- Invalid attribute schemas are rejected during discovery; renderer exceptions discard partial output and restore the caller's output buffer.
- Text examples use HTML escaping, including script-like input.
- Linked instances see updates; overrides win; detach freezes a snapshot.
- Missing variation references fail explicitly.
- Resolving or editing a returned value does not mutate the instance description.

## Code map

- `demo/src/SectionRegistry.php` — bounded discovery and metadata contract.
- `demo/src/SectionPackage.php` — validation-to-render boundary.
- `demo/src/AttributeValidator.php` — exact fields and types.
- `demo/src/Escaper.php` — HTML text escaping.
- `demo/src/VariationStore.php` — in-memory reuse lifecycle.
- `demo/packages/` — synthetic packages and one invalid fixture.
- `demo/run.php` — CLI walkthrough.
- `tests/run.php` — dependency-free behavior tests.
- `docs/engineering.md` — contracts, lifecycle, errors, and WordPress boundaries.
- `docs/scope.md` — evidence and trust boundaries.

## Original work versus this demo

The original work informed the architectural narrative: native editor composition, self-contained section packages, server rendering, reusable variations, compatibility boundaries, and integration checks. That source remains private.

The PHP under `demo/` and `tests/` is new synthetic educational code. Passing these tests verifies this portable demo only; it does not retroactively verify the private implementation, a live site, or WordPress/editor behavior.

## Limitations

This is not a plugin or theme and does not demonstrate native block registration, editor UI, persistence, permissions, concurrency, cache invalidation, migrations, asset builds, integrations, or production deployment. See [engineering decisions](docs/engineering.md) and [scope boundaries](docs/scope.md).