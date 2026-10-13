<?php
declare(strict_types=1);

namespace Showcase;

use InvalidArgumentException;

final class SectionRegistry
{
    private function __construct(
        private array $packages,
        private array $errors,
    ) {}

    public static function discover(string $root): self
    {
        $packages = [];
        $errors = [];
        $directories = glob(rtrim($root, '/\\') . '/*', GLOB_ONLYDIR) ?: [];
        sort($directories, SORT_STRING);

        foreach ($directories as $directory) {
            $metadataPath = $directory . '/section.json';
            if (!is_file($metadataPath)) {
                continue;
            }
            try {
                $data = json_decode((string) file_get_contents($metadataPath), true, 32, JSON_THROW_ON_ERROR);
                if (!is_array($data)) {
                    throw new InvalidArgumentException('metadata must be an object');
                }
                $allowed = ['name', 'title', 'render', 'attributes'];
                $unknown = array_diff(array_keys($data), $allowed);
                if ($unknown !== []) {
                    throw new InvalidArgumentException('unknown metadata fields: ' . implode(', ', $unknown));
                }
                foreach (['name', 'title', 'render'] as $required) {
                    if (!isset($data[$required]) || !is_string($data[$required]) || $data[$required] === '') {
                        throw new InvalidArgumentException("{$required} must be a non-empty string");
                    }
                }
                if (!preg_match('/^[a-z][a-z0-9-]*$/', $data['name'])) {
                    throw new InvalidArgumentException('name has an invalid format');
                }
                if ($data['render'] !== 'render.php') {
                    throw new InvalidArgumentException('render must be exactly render.php');
                }
                $renderPath = $directory . '/render.php';
                if (!is_file($renderPath)) {
                    throw new InvalidArgumentException('render.php is missing');
                }
                if (isset($packages[$data['name']])) {
                    throw new InvalidArgumentException("duplicate section name: {$data['name']}");
                }
                $attributes = $data['attributes'] ?? [];
                if (!is_array($attributes)) {
                    throw new InvalidArgumentException('attributes must be an object');
                }
                AttributeValidator::validateSchema($attributes);
                $packages[$data['name']] = new SectionPackage(
                    $data['name'], $data['title'], $renderPath, $attributes
                );
            } catch (\JsonException $error) {
                $errors[] = basename($directory) . ': invalid JSON';
            } catch (InvalidArgumentException $error) {
                $errors[] = basename($directory) . ': ' . $error->getMessage();
            }
        }
        ksort($packages, SORT_STRING);
        return new self($packages, $errors);
    }

    public function all(): array { return $this->packages; }
    public function errors(): array { return $this->errors; }

    public function get(string $name): SectionPackage
    {
        if (!isset($this->packages[$name])) {
            throw new InvalidArgumentException("Unknown section package: {$name}");
        }
        return $this->packages[$name];
    }
}
