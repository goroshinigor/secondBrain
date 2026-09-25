<?php

namespace App\Exceptions;

use RuntimeException;

class OllamaException extends RuntimeException
{
    /**
     * Create an exception for a failed connection attempt.
     */
    public static function unreachable(string $url, ?string $reason = null): self
    {
        return new self(
            $reason !== null && $reason !== ''
                ? "Could not reach Ollama at {$url}: {$reason}"
                : "Could not reach Ollama at {$url}. Make sure the server is running.",
        );
    }

    /**
     * Create an exception for a failed generation request.
     */
    public static function failed(string $reason): self
    {
        return new self("Ollama returned an error: {$reason}");
    }

    /**
     * Create an exception for a malformed response.
     */
    public static function malformedResponse(): self
    {
        return new self('Ollama returned an unexpected response.');
    }
}
