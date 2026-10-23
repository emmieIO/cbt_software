<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaEmbeddingService
{
    /** @return array<int, float> */
    public function embed(string $text): array
    {
        $text = trim($text);

        if ($text === '') {
            throw new RuntimeException('Ollama cannot embed empty text.');
        }

        $baseUrl = rtrim((string) config('knowledge.embeddings.base_url'), '/');
        $model = (string) config('knowledge.embeddings.model');
        $dimensions = (int) config('knowledge.embeddings.dimensions');

        if ($baseUrl === '' || $model === '' || $dimensions < 1) {
            throw new RuntimeException('Ollama embedding configuration is invalid.');
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout((int) config('knowledge.embeddings.connect_timeout', 5))
                ->timeout((int) config('knowledge.embeddings.timeout', 120))
                ->post($baseUrl.'/api/embed', [
                    'model' => $model,
                    'input' => $text,
                ])
                ->throw();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Could not connect to the local Ollama embedding service.', previous: $exception);
        } catch (RequestException $exception) {
            throw new RuntimeException(
                'The local Ollama embedding service returned HTTP '.$exception->response->status().'.',
                previous: $exception,
            );
        }

        $embedding = $response->json('embeddings.0');

        if (! is_array($embedding) || ! array_is_list($embedding) || count($embedding) !== $dimensions) {
            throw new RuntimeException("Ollama must return exactly {$dimensions} embedding dimensions.");
        }

        foreach ($embedding as $value) {
            if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                throw new RuntimeException('Ollama returned a malformed embedding vector.');
            }
        }

        return array_map(static fn (int|float $value): float => (float) $value, $embedding);
    }
}
