# Engineering decisions

Gutenberg Section Theme Framework

## Native composition

Use the block editor as the composition authority instead of maintaining a parallel page builder.

## Reusable, not permanently coupled

Allow shared sections to be reused or detached so global consistency does not prevent local editing.

## Migration boundary

Preserve older block identities through explicit compatibility code while keeping new packages self-contained.

## Reading the architecture

Native block editor → Section metadata and attributes → PHP render layer → Shared theme assets → Public page

The diagram reduces the implementation to its relevant responsibilities. It does not expose private endpoints, exact deployment layout, resource identifiers or operational configuration.
