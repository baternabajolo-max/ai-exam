<?php

return [
    'api_base' => env('OPENAI_API_BASE', 'http://127.0.0.1:1234/v1'),
    'api_key' => env('OPENAI_API_KEY'),
    'model' => env('OPENAI_MODEL', 'llama-3.2-3b-instruct'),
    'temperature' => (float) env('AI_TEMPERATURE', 0.7),
    'connect_timeout' => (int) env('AI_CONNECT_TIMEOUT', 3),
    'timeout' => (int) env('AI_TIMEOUT', 20),
];
