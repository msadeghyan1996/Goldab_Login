<?php
namespace App\Services\impl;
use Illuminate\Support\Facades\Log;
abstract class BaseService
{
    protected function logInfo(string $message, array $context = []): void
    {
        Log::info($message, $context);
    }
    protected function logError(string $message, array $context = []): void
    {
        Log::error($message, $context);
    }
    protected function logWarning(string $message, array $context = []): void
    {
        Log::warning($message, $context);
    }
    protected function handleException(\Exception $e, string $context = ''): void
    {
        $this->logError(
            sprintf('[%s] %s: %s', $context, get_class($e), $e->getMessage()),
            [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }
    protected function validateRequired(array $data, array $required): bool
    {
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return false;
            }
        }
        return true;
    }
}