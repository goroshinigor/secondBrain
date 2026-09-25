<?php

namespace App\Http\Controllers;

use App\Exceptions\OllamaException;
use App\Http\Requests\PromptRequest;
use App\Services\OllamaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PromptController extends Controller
{
    public function __construct(
        protected OllamaClient $client,
    ) {}

    /**
     * Display the home page with the prompt form.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Home', [
            'model' => config('ollama.model'),
        ]);
    }

    /**
     * Send the submitted prompt to the model and return its answer.
     */
    public function store(PromptRequest $request): JsonResponse
    {
        try {
            $answer = $this->client->generate($request->validated('prompt'));
        } catch (OllamaException $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage(),
            ], 503);
        }

        return response()->json([
            'prompt' => $request->validated('prompt'),
            'answer' => $answer,
            'model' => config('ollama.model'),
        ]);
    }
}
