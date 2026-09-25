<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\Fluent\AssertableJson;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class PromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_the_prompt_form()
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Home')
            ->where('model', config('ollama.model'))
        );
    }

    public function test_prompt_is_required()
    {
        $response = $this->postJson(route('prompt.store'), ['prompt' => '']);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('prompt');
    }

    public function test_prompt_must_be_a_string()
    {
        $response = $this->postJson(route('prompt.store'), ['prompt' => ['nested']]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('prompt');
    }

    public function test_prompt_must_not_exceed_the_maximum_length()
    {
        $response = $this->postJson(route('prompt.store'), [
            'prompt' => str_repeat('a', 4001),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('prompt');
    }

    public function test_a_prompt_returns_the_model_answer()
    {
        Http::fake([
            config('ollama.url').'/api/chat' => Http::response([
                'model' => config('ollama.model'),
                'message' => ['role' => 'assistant', 'content' => 'Привет!'],
                'done' => true,
            ]),
        ]);

        $response = $this->postJson(route('prompt.store'), [
            'prompt' => 'Поздоровайся',
        ]);

        $response->assertOk();
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('answer', 'Привет!')
            ->where('model', config('ollama.model'))
            ->etc()
        );

        Http::assertSent(fn ($request) => $request->url() === config('ollama.url').'/api/chat'
            && $request['model'] === config('ollama.model')
            && $request['stream'] === false
            && $request['messages'][1] === ['role' => 'user', 'content' => 'Поздоровайся']
        );
    }

    public function test_the_prompt_is_trimmed_before_being_sent()
    {
        Http::fake([
            config('ollama.url').'/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => 'ok'],
            ]),
        ]);

        $this->postJson(route('prompt.store'), ['prompt' => '  привет  '])
            ->assertOk();

        Http::assertSent(fn ($request) => $request['messages'][1]['content'] === 'привет');
    }

    public function test_an_unreachable_model_returns_a_service_unavailable_error()
    {
        Http::fake([
            '*' => Http::failedConnection(),
        ]);

        $response = $this->postJson(route('prompt.store'), [
            'prompt' => 'Привет',
        ]);

        $response->assertStatus(503);
        $response->assertJsonStructure(['message']);
    }

    public function test_an_error_response_from_the_model_is_handled()
    {
        Http::fake([
            config('ollama.url').'/api/chat' => Http::response([
                'error' => "model 'llama3.2:3b' not found",
            ], 404),
        ]);

        $response = $this->postJson(route('prompt.store'), [
            'prompt' => 'Привет',
        ]);

        $response->assertStatus(503);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Ollama returned an error: model \'llama3.2:3b\' not found')
        );
    }

    public function test_an_empty_model_response_is_handled()
    {
        Http::fake([
            config('ollama.url').'/api/chat' => Http::response([
                'message' => ['role' => 'assistant', 'content' => '   '],
            ]),
        ]);

        $response = $this->postJson(route('prompt.store'), [
            'prompt' => 'Привет',
        ]);

        $response->assertStatus(503);
        $response->assertJson(fn (AssertableJson $json) => $json
            ->where('message', 'Ollama returned an unexpected response.')
        );
    }
}
