<?php
declare(strict_types=1);

namespace Showcase;

final class Escaper
{
    public static function html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}
