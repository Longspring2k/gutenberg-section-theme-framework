<?php
declare(strict_types=1);

namespace Showcase;

final class SectionPackage
{
    public function __construct(
        public readonly string $name,
        public readonly string $title,
        private readonly string $renderPath,
        private readonly array $schema,
    ) {}

    public function render(array $attributes): string
    {
        $clean = AttributeValidator::validate($this->schema, $attributes);
        return (static function (string $__path, array $attributes): string {
            $callerLevel = ob_get_level();
            ob_start();
            try {
                require $__path;
                return (string) ob_get_clean();
            } finally {
                while (ob_get_level() > $callerLevel) {
                    ob_end_clean();
                }
            }
        })($this->renderPath, $clean);
    }
}
