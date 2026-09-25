<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ollama Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of the Ollama server used to generate responses to the
    | prompts submitted from the application. Inside Docker this is the
    | service name of the Ollama container.
    |
    */

    'url' => env('OLLAMA_URL', 'http://ollama:11434'),

    /*
    |--------------------------------------------------------------------------
    | Model
    |--------------------------------------------------------------------------
    |
    | The model that should be used when generating a response. The model
    | must already be pulled on the Ollama server, otherwise generation
    | will fail and an error will be returned to the user.
    |
    */

    'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for a response from Ollama.
    | Generation on CPU can be slow, so this defaults to two minutes.
    |
    */

    'timeout' => (int) env('OLLAMA_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | An optional system message that is prepended to every conversation
    | to guide the behaviour and tone of the model's responses.
    |
    */

    'system_prompt' => env('OLLAMA_SYSTEM_PROMPT', 'You are a helpful assistant. Answer clearly and concisely.'),

    /*
    |--------------------------------------------------------------------------
    | Generation Options
    |--------------------------------------------------------------------------
    |
    | Additional options passed to Ollama's generation endpoint, such as
    | sampling temperature and the maximum number of tokens to produce.
    |
    */

    'options' => [
        'temperature' => (float) env('OLLAMA_TEMPERATURE', 0.7),
    ],

];
