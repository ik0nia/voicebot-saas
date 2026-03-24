<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class ChatbotRequestLogger
{
    private float $startTime;
    private array $data = [];

    public function start(): self
    {
        $this->startTime = microtime(true);
        return $this;
    }

    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Log the request with structured data.
     */
    public function log(string $level = 'info'): void
    {
        $this->data['latency_ms'] = isset($this->startTime)
            ? (int) ((microtime(true) - $this->startTime) * 1000)
            : null;

        Log::log($level, 'ChatbotRequest', $this->data);
    }

    /**
     * Convenience: log a complete chatbot request.
     */
    public static function logRequest(array $data): void
    {
        Log::info('ChatbotRequest', $data);
    }
}
