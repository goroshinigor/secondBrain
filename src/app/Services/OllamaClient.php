<?php

namespace App\Services;

use App\Exceptions\OllamaException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Throwable;

class OllamaClient
{
    /**
     * Send a prompt to Ollama and return the generated answer.
     *
     * @throws OllamaException
     */
    public function generate(string $prompt): string
    {
        $url = rtrim((string) config('ollama.url'), '/');

        try {
            $response = Http::timeout((int) config('ollama.timeout'))
                ->acceptJson()
                ->asJson()
                ->post("{$url}/api/chat", [
                    'model' => config('ollama.model'),
                    'messages' => $this->messages($prompt),
                    'stream' => false,
                    'options' => config('ollama.options'),
                ]);
        } catch (ConnectionException $e) {
            throw OllamaException::unreachable($url, $e->getMessage());
        } catch (Throwable $e) {
            throw OllamaException::unreachable($url, $e->getMessage());
        }

        if ($response->failed()) {
            $message = $response->json('error');

            throw OllamaException::failed(
                is_string($message) ? $message : "HTTP {$response->status()}"
            );
        }

        $content = $response->json('message.content');

        if (! is_string($content) || trim($content) === '') {
            throw OllamaException::malformedResponse();
        }

        return trim($content);
    }

    /**
     * Build the message payload sent to the model.
     *
     * @return array<int, array{role: string, content: string}>
     */
    protected function messages(string $prompt): array
    {
        $messages = [];

        $systemPrompt = config('ollama.system_prompt');

        if (is_string($systemPrompt) && trim($systemPrompt) !== '') {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $messages;
    }
}
