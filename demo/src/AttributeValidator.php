<?php
declare(strict_types=1);

namespace Showcase;

use InvalidArgumentException;

final class AttributeValidator
{
    private const TYPES = ['string', 'bool', 'int'];

    public static function validateSchema(array $schema): void
    {
        foreach ($schema as $name => $type) {
            if (!is_string($name) || !is_string($type) || !in_array($type, self::TYPES, true)) {
                throw new InvalidArgumentException('Invalid attribute schema');
            }
        }
    }

    public static function validate(array $schema, array $attributes): array
    {
        self::validateSchema($schema);
        $unknown = array_diff(array_keys($attributes), array_keys($schema));
        if ($unknown !== []) {
            throw new InvalidArgumentException('Unknown attributes: ' . implode(', ', $unknown));
        }
        foreach ($schema as $name => $type) {
            if (!array_key_exists($name, $attributes)) {
                throw new InvalidArgumentException("Missing attribute: {$name}");
            }
            $valid = match ($type) {
                'string' => is_string($attributes[$name]),
                'bool' => is_bool($attributes[$name]),
                'int' => is_int($attributes[$name]),
            };
            if (!$valid) {
                throw new InvalidArgumentException("{$name} must be {$type}");
            }
        }
        return $attributes;
    }
}
