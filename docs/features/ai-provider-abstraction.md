# AI Provider Abstraction & Model Configuration Layer

## Purpose
Provides an infrastructure-agnostic abstraction for generative LLM interactions (chat completions, streaming chat, and translation) and vector text embeddings. It decouples the core domain logic (RAG retrieval, issue classification, chat streaming) from specific vendor APIs (Ollama, Gemini, Groq, OpenAI), allowing zero-code provider swaps via environment configuration.

## Entry Points
- **Service Container Bindings**:
  - `App\Services\AI\Contracts\EmbeddingProvider` (bound in `AiServiceProvider`)
  - `App\Services\AI\Contracts\ChatProvider` (bound in `AiServiceProvider`)
  - `'ai.translator'` string binding (bound in `AiServiceProvider` for dedicated translation models)
  - `'ai.intent_detector'` string binding and `App\Services\Issue\IssueIntentDetector` (bound in `AiServiceProvider` for dedicated intent classification)
- **Console Command**:
  - `php artisan ai:test` -> `App\Console\Commands\TestAiProviders` (Smoke tests current embedding, completion, and intent detection configs)

## Key Files & Classes
- `config/ai.php` — Central configuration declaring active drivers, endpoint URLs, API keys, model names, context message window sizes, and issue action toggles.
- `app/Providers/AiServiceProvider.php` — Laravel service provider registering container singletons/factories for `EmbeddingProvider`, `ChatProvider`, and `'ai.translator'`.
- `app/Services/AI/Contracts/EmbeddingProvider.php` — Interface requiring `embed(string $text): array` returning a numeric float array.
- `app/Services/AI/Contracts/ChatProvider.php` — Interface defining synchronous (`complete`, `completeMessages`) and streaming (`completeStream`, `completeMessagesStream`) methods.
- `app/Services/AI/Providers/OpenAiCompatibleEmbeddingProvider.php` — Generic HTTP adapter targeting standard `/v1/embeddings` endpoints.
- `app/Services/AI/Providers/OpenAiCompatibleChatProvider.php` — Generic HTTP/SSE streaming adapter targeting standard `/v1/chat/completions` endpoints.
- `app/Console/Commands/TestAiProviders.php` — Smoke-test command validating live connectivity and response shapes.

## Data Flow

### 1. Provider Resolution
1. A service (e.g., `RagAnswerer` or `KnowledgeIngestCommand`) type-hints `EmbeddingProvider` or `ChatProvider` in its constructor.
2. `AiServiceProvider` reads the active driver key from `config('ai.embedding_provider')` or `config('ai.chat_provider')` (e.g. `'ollama'`, `'gemini'`, `'groq'`).
3. The provider loads the matching configuration profile (`base_url`, `api_key`, model names) from `config('ai.providers.<driver>')`.
4. An instance of `OpenAiCompatibleEmbeddingProvider` or `OpenAiCompatibleChatProvider` is instantiated and injected.

### 2. Synchronous & Streaming Execution
- **Embedding Generation**:
  - `embed($text)` dispatches `POST {base_url}/embeddings` with `['model' => $model, 'input' => $text]`.
  - Extracts and returns `data.0.embedding` as `float[]`.
- **Chat Completion**:
  - `complete($systemPrompt, $userPrompt)` formats messages as `[['role' => 'system', 'content' => ...], ['role' => 'user', 'content' => ...]]`.
  - Dispatches `POST {base_url}/chat/completions` with low temperature (`0.2`) and top_p (`0.8`).
  - Returns `choices.0.message.content`.
- **Streaming Completions**:
  - `completeMessagesStream($messages, callable $onToken)` sends `stream => true` with Guzzle stream options enabled (`'stream' => true` to bypass buffering).
  - Iterates over PSR-7 stream chunks via a 1024-byte buffer, parses Server-Sent Event frames (`data: {...}`), extracts delta tokens (`choices[0].delta.content`), and invokes the `$onToken($token)` callback until `data: [DONE]` is encountered.

## Relevant Code Snippets

```php
// app/Providers/AiServiceProvider.php
public function register(): void
{
    $this->app->bind(EmbeddingProvider::class, function () {
        $cfg = config('ai.providers.' . config('ai.embedding_provider'));
        return new OpenAiCompatibleEmbeddingProvider(
            baseUrl: $cfg['base_url'],
            apiKey:  $cfg['api_key'] ?? '',
            model:   $cfg['embedding_model'],
        );
    });

    $this->app->bind(ChatProvider::class, function () {
        $cfg = config('ai.providers.' . config('ai.chat_provider'));
        return new OpenAiCompatibleChatProvider(
            baseUrl: $cfg['base_url'],
            apiKey:  $cfg['api_key'] ?? '',
            model:   $cfg['chat_model'],
        );
    });
}
```

```php
// app/Services/AI/Providers/OpenAiCompatibleChatProvider.php
// Streaming parser logic
while (! $body->eof()) {
    $buffer .= $body->read(1024);
    while (($pos = strpos($buffer, "\n\n")) !== false) {
        $frame = substr($buffer, 0, $pos);
        $buffer = substr($buffer, $pos + 2);
        foreach (explode("\n", $frame) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, 'data:')) continue;
            $data = trim(substr($line, 5));
            if ($data === '[DONE]') return;
            $token = json_decode($data, true)['choices'][0]['delta']['content'] ?? null;
            if ($token !== null && $token !== '') {
                $onToken($token);
            }
        }
    }
}
```

## Database Involvement
This layer does not directly interact with database tables. However, vector dimensions produced by `EmbeddingProvider` directly correlate with the binary BLOB column sizes stored in the `knowledge_chunks` and `faqs` tables via `VectorCodec`.

## Configuration Matrix

| Provider Key | Default Base URL | Default Embedding Model | Default Chat Model | Default Intent Model | Notes |
|---|---|---|---|---|---|
| `ollama` | `http://localhost:11434/v1` | `bge-m3` (1024 dims) | `qwen3:4b` | `qwen3:4b` | Self-hosted local inference |
| `gemini` | `https://generativelanguage.googleapis.com/v1beta/openai` | `gemini-embedding-001` | `gemini-2.5-flash-lite` | `gemini-2.5-flash-lite` | Google OpenAI-compat endpoint |
| `groq` | `https://api.groq.com/openai/v1` | `null` | `llama-3.3-70b-versatile` | `llama-3.3-70b-versatile` | Ultra-low latency chat (embeddings unsupported) |

## Edge Cases & Conditional Logic
- **SSE Frame Fragmentation**: Network packet fragmentation may cause partial SSE lines. `OpenAiCompatibleChatProvider` buffers incoming bytes and only consumes frames delimited by `\n\n`, preserving leftover bytes in the buffer.
- **Provider Mismatch (e.g. Groq for Embeddings)**: Groq does not offer OpenAI-compatible embedding endpoints (`embedding_model` is null). Setting `AI_EMBEDDING_PROVIDER=groq` without a custom embedding endpoint will cause HTTP 404/400 failures.
- **Missing API Keys**: Ollama requires a dummy string (e.g., `'ollama'`) as the bearer token; cloud providers (Gemini, Groq) require valid tokens passed in `.env`.

## Notes & Concerns
- **Hardcoded Temperature & Top_P**: Hyperparameters (`temperature: 0.2`, `top_p: 0.8`) and HTTP timeout (`120s`) are hardcoded in `OpenAiCompatibleChatProvider` rather than read from `config/ai.php`.
- **Shared Class for Translation & Chat**: Both `ChatProvider` and `'ai.translator'` bind to `OpenAiCompatibleChatProvider`, but `'ai.translator'` is registered with a string alias rather than an interface or separate typed contract.
- **Error Response Masking**: On HTTP failure, raw response bodies are wrapped into a `RuntimeException`. If upstream errors contain sensitive API keys or verbose HTML error pages, these are exposed to server logs.
