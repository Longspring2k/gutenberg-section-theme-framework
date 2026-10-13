<?php
declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Showcase\SectionRegistry;
use Showcase\VariationStore;

$registry = SectionRegistry::discover(__DIR__ . '/packages');
$store = new VariationStore();
$store->save('launch', [
    'heading' => 'Build <script>alert("unsafe")</script> with reusable sections',
    'featured' => false,
]);

$linked = $store->link('home-hero', 'launch');
$local = $store->link('campaign-hero', 'launch', ['heading' => 'Campaign-specific heading']);
$detached = $store->detach($local);
$store->save('launch', ['heading' => 'Updated shared heading', 'featured' => true]);

echo "Discovered packages: " . implode(', ', array_keys($registry->all())) . PHP_EOL;
echo "Discovery notices: " . implode('; ', $registry->errors()) . PHP_EOL . PHP_EOL;
echo "Linked instance after shared update:" . PHP_EOL;
echo $registry->get('hero')->render($store->resolve($linked));
echo PHP_EOL . "Detached instance after shared update (unchanged snapshot):" . PHP_EOL;
echo $registry->get('hero')->render($store->resolve($detached));
echo PHP_EOL . "Escaped callout:" . PHP_EOL;
echo $registry->get('callout')->render([
    'body' => 'Text with <strong>markup</strong> remains text.',
    'priority' => 2,
]);
