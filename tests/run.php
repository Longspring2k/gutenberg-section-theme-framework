<?php
declare(strict_types=1);

require __DIR__ . '/../demo/bootstrap.php';

use Showcase\SectionRegistry;
use Showcase\VariationStore;

$tests = [];
$tests['discovers valid packages, rejects invalid metadata, and orders names deterministically'] = function (): void {
    $root = (getenv('TMPDIR') ?: sys_get_temp_dir()) . '/section-registry-' . bin2hex(random_bytes(4));
    mkdir($root . '/zeta', 0777, true);
    mkdir($root . '/alpha', 0777, true);
    mkdir($root . '/broken', 0777, true);
    file_put_contents($root . '/zeta/section.json', json_encode(['name' => 'zeta', 'title' => 'Zeta', 'render' => 'render.php']));
    file_put_contents($root . '/alpha/section.json', json_encode(['name' => 'alpha', 'title' => 'Alpha', 'render' => 'render.php']));
    file_put_contents($root . '/broken/section.json', '{not json');
    file_put_contents($root . '/zeta/render.php', '<?php return "z";');
    file_put_contents($root . '/alpha/render.php', '<?php return "a";');

    $registry = SectionRegistry::discover($root);
    assertSame(['alpha', 'zeta'], array_keys($registry->all()));
    assertSame(1, count($registry->errors()));
    assertContains('invalid JSON', $registry->errors()[0]);
};

$tests['rejects duplicates and metadata-selected include paths'] = function (): void {
    $root = (getenv('TMPDIR') ?: sys_get_temp_dir()) . '/section-registry-guard-' . bin2hex(random_bytes(4));
    foreach (['first', 'second', 'escape'] as $directory) { mkdir("{$root}/{$directory}", 0777, true); }
    file_put_contents("{$root}/first/section.json", json_encode(['name' => 'same', 'title' => 'First', 'render' => 'render.php']));
    file_put_contents("{$root}/second/section.json", json_encode(['name' => 'same', 'title' => 'Second', 'render' => 'render.php']));
    file_put_contents("{$root}/escape/section.json", json_encode(['name' => 'escape', 'title' => 'Escape', 'render' => '../outside.php']));
    file_put_contents("{$root}/first/render.php", '<?php echo "first";');
    file_put_contents("{$root}/second/render.php", '<?php echo "second";');
    file_put_contents("{$root}/outside.php", '<?php echo "outside";');

    $registry = SectionRegistry::discover($root);
    assertSame(['same'], array_keys($registry->all()));
    assertSame(2, count($registry->errors()));
    assertContains('render must be exactly render.php', implode("\n", $registry->errors()));
    assertContains('duplicate section name', implode("\n", $registry->errors()));
};

$tests['validates exact attribute fields and types and escapes rendered text'] = function (): void {
    $root = (getenv('TMPDIR') ?: sys_get_temp_dir()) . '/section-render-' . bin2hex(random_bytes(4));
    mkdir("{$root}/hero", 0777, true);
    file_put_contents("{$root}/hero/section.json", json_encode([
        'name' => 'hero', 'title' => 'Hero', 'render' => 'render.php',
        'attributes' => ['heading' => 'string', 'featured' => 'bool'],
    ]));
    file_put_contents("{$root}/hero/render.php", '<h1><?= Showcase\\Escaper::html($attributes["heading"]) ?></h1>');
    $package = SectionRegistry::discover($root)->get('hero');

    assertSame('<h1>&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;</h1>', $package->render([
        'heading' => '<script>alert("x")</script>', 'featured' => true,
    ]));
    assertThrows(fn () => $package->render(['heading' => 'Hi', 'featured' => true, 'extra' => 'no']), 'Unknown attributes: extra');
    assertThrows(fn () => $package->render(['heading' => 'Hi', 'featured' => 'yes']), 'featured must be bool');
};

$tests['linked instances follow updates while overrides win and detach freezes a snapshot'] = function (): void {
    $store = new VariationStore();
    $store->save('primary', ['heading' => 'First', 'featured' => false]);
    $linked = $store->link('hero-a', 'primary');
    $overridden = $store->link('hero-b', 'primary', ['heading' => 'Local']);

    $store->save('primary', ['heading' => 'Second', 'featured' => true]);
    assertSame(['heading' => 'Second', 'featured' => true], $store->resolve($linked));
    assertSame(['heading' => 'Local', 'featured' => true], $store->resolve($overridden));

    $detached = $store->detach($overridden);
    $store->save('primary', ['heading' => 'Third', 'featured' => false]);
    assertSame(['heading' => 'Local', 'featured' => true], $store->resolve($detached));
};

$tests['missing references fail explicitly and resolving never mutates instance data'] = function (): void {
    $store = new VariationStore();
    assertThrows(fn () => $store->link('missing', 'unknown'), 'Unknown variation: unknown');
    $store->save('primary', ['heading' => 'Shared', 'featured' => false]);
    $instance = $store->link('hero', 'primary', ['heading' => 'Local']);
    $before = $instance;
    $resolved = $store->resolve($instance);
    $resolved['heading'] = 'Mutated copy';
    assertSame($before, $instance);
    assertSame('Local', $store->resolve($instance)['heading']);
};

$tests['rejects invalid attribute schemas during discovery, not at first render'] = function (): void {
    $root = (getenv('TMPDIR') ?: sys_get_temp_dir()) . '/section-schema-' . bin2hex(random_bytes(4));
    mkdir($root . '/broken', 0777, true);
    file_put_contents($root . '/broken/section.json', json_encode([
        'name' => 'broken', 'title' => 'Broken', 'render' => 'render.php',
        'attributes' => ['heading' => 'unsupported'],
    ]));
    file_put_contents($root . '/broken/render.php', '<?php echo "unreachable";');
    $registry = SectionRegistry::discover($root);
    assertSame([], array_keys($registry->all()));
    assertContains('Invalid attribute schema', implode("\n", $registry->errors()));
};

$tests['renderer failure discards partial output and restores the caller buffer'] = function (): void {
    $root = (getenv('TMPDIR') ?: sys_get_temp_dir()) . '/section-failure-' . bin2hex(random_bytes(4));
    mkdir($root . '/broken', 0777, true);
    file_put_contents($root . '/broken/section.json', json_encode([
        'name' => 'broken', 'title' => 'Broken', 'render' => 'render.php',
    ]));
    file_put_contents($root . '/broken/render.php', '<?php echo "partial"; throw new RuntimeException("renderer failed");');
    $package = SectionRegistry::discover($root)->get('broken');
    $initial = ob_get_level();
    ob_start();
    try {
        $caller = ob_get_level();
        assertThrows(fn () => $package->render([]), 'renderer failed');
        assertSame($caller, ob_get_level());
        assertSame('', ob_get_contents());
    } finally {
        while (ob_get_level() > $initial) { ob_end_clean(); }
    }
};

$tests['missing attributes and numeric strings cannot pass exact scalar contracts'] = function (): void {
    $package = SectionRegistry::discover(__DIR__ . '/../demo/packages')->get('callout');
    assertThrows(fn () => $package->render([]), 'Missing attribute');
    assertThrows(fn () => $package->render(['body' => 'Hello', 'priority' => '2']), 'priority must be int');
};

function assertSame(mixed $expected, mixed $actual): void {
    if ($expected !== $actual) {
        throw new RuntimeException('Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
    }
}
function assertContains(string $needle, string $haystack): void {
    if (!str_contains($haystack, $needle)) {
        throw new RuntimeException("Expected to find '{$needle}' in '{$haystack}'");
    }
}
function assertThrows(callable $callback, string $messageFragment): void {
    try { $callback(); } catch (Throwable $error) {
        assertContains($messageFragment, $error->getMessage());
        return;
    }
    throw new RuntimeException('Expected exception containing: ' . $messageFragment);
}

$filter = $argv[1] ?? null;
$selected = $filter === null ? $tests : array_filter($tests, fn ($name) => str_contains($name, $filter), ARRAY_FILTER_USE_KEY);
if ($selected === []) {
    fwrite(STDERR, "No tests matched the requested filter.\n");
    exit(2);
}
$failures = 0;
foreach ($selected as $name => $test) {
    try { $test(); echo "PASS {$name}\n"; }
    catch (Throwable $error) { $failures++; echo "FAIL {$name}\n  {$error->getMessage()}\n"; }
}
echo sprintf("\n%d passed, %d failed\n", count($selected) - $failures, $failures);
exit($failures === 0 ? 0 : 1);
