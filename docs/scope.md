# Scope and evidence boundaries

## Two distinct bodies of work

This repository describes an original private Gutenberg/theme implementation at an architectural level and contains a separate newly authored portable PHP demo.

The narrative is based on retained private evidence and supports architectural claims, not a fresh claim that the original system was executed, deployed, or verified for this publication. The public PHP uses synthetic names and content; its local results apply only to this demo.

## Included publicly

- newly written English documentation;
- original educational PHP for discovery, strict attributes, escaping, and variation behavior;
- synthetic JSON metadata and renderer fixtures;
- a dependency-free test runner and CLI walkthrough.

## Intentionally excluded

- original client code, assets, identifiers, domains, endpoints, filenames, paths, evidence, or inherited history;
- private build/form configuration, content, screenshots, and deployment scripts;
- metrics, endorsements, or claims of whole-site authorship.

## Demonstrated

`php tests/run.php` exercises the portable contracts. `php demo/run.php` exercises discovery and rendering with synthetic data. PHP syntax checks cover included PHP files.

## Not demonstrated

- native WordPress/Gutenberg registration or editor behavior;
- serialization, autosave, revisions, undo/redo, or persistence;
- authentication, permissions, REST, capabilities, or nonces;
- concurrency, locking, transactions, caches, or migrations;
- production visual/accessibility behavior, forms, legacy migration, hosting, release automation, observability, or deployment.

No WordPress stub is used as evidence for those capabilities.

## Trust boundary

The package root is trusted checked-in application code. Discovery is limited to immediate children and metadata cannot select an arbitrary include path. This reduces accidental path expansion; it does not make checked-in PHP safe from a malicious contributor.

Review the demo as an executable explanation of mechanisms and the original-work narrative as an anonymized description. A passing public suite is not a passing private WordPress suite or proof of production behavior.