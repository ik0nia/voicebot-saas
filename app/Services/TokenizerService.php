<?php

namespace App\Services;

use Yethee\Tiktoken\EncoderProvider;

/**
 * Accurate token counting using tiktoken (cl100k_base encoding).
 * Replaces strlen/4 estimation throughout the codebase.
 *
 * Thread-safe singleton via Laravel service container.
 */
class TokenizerService
{
    private \Yethee\Tiktoken\Encoder $encoder;

    public function __construct()
    {
        $provider = new EncoderProvider();
        $this->encoder = $provider->getForModel('gpt-4o');
    }

    /**
     * Count tokens in a string.
     */
    public function count(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        return count($this->encoder->encode($text));
    }

    /**
     * Count tokens for a words array (used in chunking).
     */
    public function countWords(array $words): int
    {
        if (empty($words)) {
            return 0;
        }

        return $this->count(implode(' ', $words));
    }

    /**
     * Estimate tokens for a messages array (OpenAI chat format).
     * Each message has ~4 tokens overhead for role/separator.
     */
    public function countMessages(array $messages): int
    {
        $tokens = 3; // priming tokens
        foreach ($messages as $msg) {
            $tokens += 4; // role overhead
            $tokens += $this->count($msg['content'] ?? '');
        }
        return $tokens;
    }
}
