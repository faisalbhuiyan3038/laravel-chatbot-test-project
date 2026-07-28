<?php

return [
    'embedding' => [
        'driver' => env('AI_EMBEDDING_DRIVER', 'ollama'), // 'ollama' | 'gemini'
    ],
    'chat' => [
        'driver' => env('AI_CHAT_DRIVER', 'ollama'), // 'ollama' | 'gemini'
    ],
    'ollama' => [
        'base_url'        => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
        'embedding_model' => env('OLLAMA_EMBEDDING_MODEL', 'bge-m3'),
        'chat_model'      => env('OLLAMA_CHAT_MODEL', 'qwen3:8b'),
    ],
    'gemini' => [
        'api_key'         => env('GEMINI_API_KEY'),
        'embedding_model' => env('GEMINI_EMBEDDING_MODEL', 'gemini-embedding-001'),
        'chat_model'      => env('GEMINI_CHAT_MODEL', 'gemini-2.5-flash-lite'),
    ],
];