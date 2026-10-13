<?php
declare(strict_types=1);

namespace Showcase;

use InvalidArgumentException;

final class VariationStore
{
    private array $variations = [];

    public function save(string $id, array $attributes): void
    {
        if ($id === '') {
            throw new InvalidArgumentException('Variation id cannot be empty');
        }
        $this->variations[$id] = $attributes;
    }

    public function link(string $instanceId, string $variationId, array $overrides = []): array
    {
        $this->variation($variationId);
        return [
            'instanceId' => $instanceId,
            'variationId' => $variationId,
            'overrides' => $overrides,
            'snapshot' => null,
        ];
    }

    public function resolve(array $instance): array
    {
        if (is_array($instance['snapshot'] ?? null)) {
            return $instance['snapshot'];
        }
        $base = $this->variation((string) ($instance['variationId'] ?? ''));
        $overrides = $instance['overrides'] ?? [];
        if (!is_array($overrides)) {
            throw new InvalidArgumentException('Instance overrides must be an array');
        }
        return array_replace($base, $overrides);
    }

    public function detach(array $instance): array
    {
        return [
            'instanceId' => (string) ($instance['instanceId'] ?? ''),
            'variationId' => null,
            'overrides' => [],
            'snapshot' => $this->resolve($instance),
        ];
    }

    private function variation(string $id): array
    {
        if (!array_key_exists($id, $this->variations)) {
            throw new InvalidArgumentException("Unknown variation: {$id}");
        }
        return $this->variations[$id];
    }
}
