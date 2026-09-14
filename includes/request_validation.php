<?php

function requestInt(array $input, string $key, int $min = 1, ?int $max = null): ?int
{
    if (!array_key_exists($key, $input) || is_bool($input[$key]) || is_float($input[$key]) || is_array($input[$key]) || is_object($input[$key])) {
        return null;
    }

    $value = filter_var($input[$key], FILTER_VALIDATE_INT);
    if ($value === false || $value < $min || ($max !== null && $value > $max)) {
        return null;
    }

    return $value;
}

function requestOptionalInt(array $input, string $key, int $min = 1, ?int $max = null): ?int
{
    if (!array_key_exists($key, $input) || $input[$key] === '' || $input[$key] === null) {
        return null;
    }

    return requestInt($input, $key, $min, $max);
}

function requestOptionalIntIsValid(array $input, string $key, int $min = 1, ?int $max = null): bool
{
    return !array_key_exists($key, $input)
        || $input[$key] === ''
        || $input[$key] === null
        || requestInt($input, $key, $min, $max) !== null;
}

function requestBoolIsValid(array $input, string $key): bool
{
    return !array_key_exists($key, $input) || requestBool($input, $key) !== null;
}

function requestString(array $input, string $key, int $maxLength, bool $required = false): ?string
{
    if (!array_key_exists($key, $input)) {
        return $required ? null : '';
    }
    if (is_array($input[$key]) || is_object($input[$key])) {
        return null;
    }

    $value = trim((string) $input[$key]);
    if (($required && $value === '') || mb_strlen($value) > $maxLength) {
        return null;
    }

    return $value;
}

function requestEnum(array $input, string $key, array $allowed, bool $required = true): ?string
{
    $value = requestString($input, $key, 50, $required);
    if ($value === null || (!$required && $value === '')) {
        return $value;
    }

    return in_array($value, $allowed, true) ? $value : null;
}

function requestBool(array $input, string $key, bool $default = false): ?bool
{
    if (!array_key_exists($key, $input)) {
        return $default;
    }

    if (is_bool($input[$key])) {
        return $input[$key];
    }

    if ($input[$key] === '1' || $input[$key] === 1) {
        return true;
    }
    if ($input[$key] === '0' || $input[$key] === 0) {
        return false;
    }

    return null;
}

function requestDate(array $input, string $key, ?string $default = null): ?string
{
    if (!array_key_exists($key, $input) || $input[$key] === '') {
        return $default;
    }
    if (!is_string($input[$key])) {
        return null;
    }

    $date = DateTime::createFromFormat('!Y-m-d', $input[$key]);
    return $date && $date->format('Y-m-d') === $input[$key] ? $input[$key] : null;
}

function requestToken(array $input, string $key, int $length = 64): ?string
{
    if (!array_key_exists($key, $input) || !is_string($input[$key])) {
        return null;
    }

    $value = trim($input[$key]);
    return strlen($value) === $length && ctype_xdigit($value) ? $value : null;
}

function requestIntList(array $input, string $key, int $min = 1): ?array
{
    if (!array_key_exists($key, $input) || !is_array($input[$key])) {
        return [];
    }

    $values = [];
    foreach ($input[$key] as $value) {
        if (is_bool($value) || is_float($value) || is_array($value) || is_object($value)) {
            return null;
        }
        $parsed = filter_var($value, FILTER_VALIDATE_INT);
        if ($parsed === false || $parsed < $min) {
            return null;
        }
        $values[] = $parsed;
    }

    return $values;
}

function validName(string $value, int $minLength = 3, int $maxLength = 100): ?string
{
    $value = trim($value);
    if (mb_strlen($value) < $minLength || mb_strlen($value) > $maxLength) {
        return null;
    }
    return preg_match('/^\p{L}+(?: +\p{L}+)*$/u', $value) ? $value : null;
}

function validEmail(string $value, int $maxLength = 100): ?string
{
    $value = strtolower(trim($value));
    if ($value === '' || mb_strlen($value) > $maxLength || preg_match('/\s/u', $value)) {
        return null;
    }
    return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : null;
}

function validUsername(string $value, int $maxLength = 50): ?string
{
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > $maxLength) {
        return null;
    }
    return preg_match('/^[A-Za-z0-9._-]+$/', $value) ? $value : null;
}

function validPassword(string $value, int $minLength = 8, int $maxLength = 255): ?string
{
    $length = strlen($value);
    return $length >= $minLength && $length <= $maxLength ? $value : null;
}

function validServiceName(string $value, int $maxLength = 100): ?string
{
    $value = trim($value);
    if ($value === '' || mb_strlen($value) > $maxLength) {
        return null;
    }
    return preg_match('/^[\p{L}\p{N}][\p{L}\p{N} .,&()\/' . "'" . '\-]*$/u', $value) ? $value : null;
}

function validComment(string $value, int $maxLength = 1000): ?string
{
    $value = trim($value);
    if (mb_strlen($value) > $maxLength || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\p{So}\p{Cs}]/u', $value)) {
        return null;
    }
    return $value;
}

function validTicketNumber(string $value, int $maxLength = 50): ?string
{
    $value = strtoupper(trim($value));
    if ($value === '' || strlen($value) > $maxLength) {
        return null;
    }
    return preg_match('/^[A-Z0-9]+(?:-[A-Z0-9]+)*$/', $value) ? $value : null;
}

function validQueuePrefix(string $value, int $maxLength = 10): ?string
{
    $value = strtoupper(trim($value));
    if ($value === '' || strlen($value) > $maxLength) {
        return null;
    }
    return preg_match('/^[A-Z0-9-]+$/', $value) ? $value : null;
}

function validTime(string $value): ?string
{
    $value = trim($value);
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
        return null;
    }
    return $value;
}
