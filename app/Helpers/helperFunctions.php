<?php

function formatJsonForPhpDoc ($data, $statusCode) : string {
    if ( is_string($data) ) {
        $data = json_decode($data, true);
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return "// ❌ Invalid JSON: " . json_last_error_msg();
        }
    }


    $formatted = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    $lines          = explode("\n", $formatted);
    $commentedLines = array_map(fn($line) => ' *   ' . $line, $lines);
    $content        = trim(implode("\n", $commentedLines), ' * ');

    return "/**\n * @response $statusCode  " . $content . "\n */";

}

function formatJsonParamsForPhpDoc (array $data, string $method = 'post') : string {

    $paramTag = in_array(strtolower($method), ['get', 'delete']) ? '@urlParam' : '@bodyParam';

    $commentedLines = array_map(function ($key, $value) use ($paramTag) {

        $type = is_int($value) ? 'int' : (is_float($value) ? 'float' : (is_bool($value) ? 'boolean' : 'string'));


        if ( is_scalar($value) ) {
            $example = $value === true ? 'true' : ($value === false ? 'false' : $value);
        } else {
            $example = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return " * {$paramTag} {$key} {$type} Example: {$example}";
    }, array_keys($data), array_values($data));

    $content = implode("\n", $commentedLines);

    return "/**\n{$content}\n */";
}
