<?php
declare(strict_types=1);

function project_root(): string
{
    return dirname(__DIR__, 2);
}

function clean_text(string $value, int $maxLength = 255): string
{
    $value = trim(strip_tags($value));
    return substr($value, 0, $maxLength);
}

function clean_number($value, float $min = 0, float $max = 10000000): float
{
    $number = filter_var($value, FILTER_VALIDATE_FLOAT);
    if ($number === false) {
        return $min;
    }

    return max($min, min((float) $number, $max));
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    return trim($value, '-') ?: 'product-' . time();
}
