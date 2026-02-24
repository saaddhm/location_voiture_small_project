<?php

declare(strict_types=1);

function sanitize_string(string $value, int $maxLength = 0): string
{
    $value = trim($value);
    $value = strip_tags($value);
    if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
        $value = mb_substr($value, 0, $maxLength);
    }
    return $value;
}

function sanitize_email(string $value): string
{
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
}

function sanitize_phone(string $value): string
{
    return preg_replace('/[^\d\s\+\-]/', '', trim($value));
}

function sanitize_int($value): int
{
    return (int) $value;
}

function sanitize_float($value): float
{
    return (float) str_replace(',', '.', (string) $value);
}
