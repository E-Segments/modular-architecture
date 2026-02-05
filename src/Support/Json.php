<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Support;

/**
 * JSON encoding utilities with consistent options.
 */
final class Json
{
    /**
     * Standard encoding options for pretty-printed, readable JSON files.
     */
    public const ENCODE_OPTIONS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES;

    /**
     * Encode value with standard options.
     */
    public static function encode(mixed $value): string
    {
        return json_encode($value, self::ENCODE_OPTIONS);
    }

    /**
     * Decode JSON string to array.
     */
    public static function decode(string $json): ?array
    {
        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }
}
