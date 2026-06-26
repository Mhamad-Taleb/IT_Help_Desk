<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AiAssistantChat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_open_the_ai_assistant_page(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
            'name' => 'Ali Taleb',
        ]);

        $response = $this->actingAs($employee)->get(route('assistant.index'));

        $response->assertOk();
        $response->assertSeeText('Employee Self-Service Chat');
        $response->assertSeeText('AI Assistant');
    }

    public function test_non_employee_cannot_open_the_ai_assistant_page(): void
    {
        $agent = User::factory()->create([
            'role' => UserRole::SupportAgent,
        ]);

        $response = $this->actingAs($agent)->get(route('assistant.index'));

        $response->assertForbidden();
    }

    public function test_employee_can_start_a_new_chat(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this->actingAs($employee)->post(route('assistant.store'));

        $response->assertRedirect();

        $chat = AiAssistantChat::query()->first();

        $this->assertNotNull($chat);
        $this->assertSame($employee->id, $chat->user_id);
        $this->assertDatabaseHas('ai_assistant_messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
        ]);
    }

    public function test_employee_can_return_to_an_old_chat_and_add_messages(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $chat = AiAssistantChat::query()->create([
            'user_id' => $employee->id,
            'title' => 'VPN Issue',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($employee)->post(route('assistant.messages.store', $chat), [
            'message' => 'I cannot connect to the office VPN from home.',
        ]);

        $response->assertRedirect(route('assistant.show', $chat));
        $this->assertDatabaseHas('ai_assistant_messages', [
            'chat_id' => $chat->id,
            'role' => 'user',
            'content' => 'I cannot connect to the office VPN from home.',
        ]);
        $this->assertDatabaseHas('ai_assistant_messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
        ]);
    }

    public function test_employee_chat_uses_the_openai_responses_api_when_enabled(): void
    {
        config()->set('services.ai.provider', 'openai');
        config()->set('services.openai.api_key', 'test-openai-key');
        config()->set('services.openai.assistant_enabled', true);
        config()->set('services.openai.assistant_model', 'gpt-5.5');

        Http::fake([
            'https://api.openai.com/v1/responses' => Http::response([
                'output_text' => 'Try forgetting the Wi-Fi network, then reconnect and test again.',
            ]),
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $chat = AiAssistantChat::query()->create([
            'user_id' => $employee->id,
            'title' => 'Wi-Fi Issue',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($employee)->post(route('assistant.messages.store', $chat), [
            'message' => 'I cannot connect to the office Wi-Fi.',
        ]);

        $response->assertRedirect(route('assistant.show', $chat));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request->hasHeader('Authorization', 'Bearer test-openai-key')
                && $request['model'] === 'gpt-5.5';
        });

        $this->assertDatabaseHas('ai_assistant_messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Try forgetting the Wi-Fi network, then reconnect and test again.',
        ]);
    }

    public function test_employee_chat_can_use_ollama_when_selected(): void
    {
        config()->set('services.ai.provider', 'ollama');
        config()->set('services.ollama.base_url', 'http://localhost:11434');
        config()->set('services.ollama.model', 'llama3.2:3b');
        config()->set('services.ollama.assistant_enabled', true);

        Http::fake([
            'http://localhost:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Start by checking whether airplane mode is enabled, then reconnect to the office Wi-Fi.',
                ],
            ]),
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $chat = AiAssistantChat::query()->create([
            'user_id' => $employee->id,
            'title' => 'Wi-Fi Issue',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($employee)->post(route('assistant.messages.store', $chat), [
            'message' => 'The office Wi-Fi is not working on my laptop.',
        ]);

        $response->assertRedirect(route('assistant.show', $chat));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'http://localhost:11434/api/chat'
                && $request['model'] === 'llama3.2:3b'
                && $request['stream'] === false;
        });

        $this->assertDatabaseHas('ai_assistant_messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Start by checking whether airplane mode is enabled, then reconnect to the office Wi-Fi.',
        ]);
    }

    public function test_vpn_question_is_treated_as_it_scope_and_sent_to_the_ai_provider(): void
    {
        config()->set('services.ai.provider', 'ollama');
        config()->set('services.ollama.base_url', 'http://localhost:11434');
        config()->set('services.ollama.model', 'llama3.2:3b');
        config()->set('services.ollama.assistant_enabled', true);

        Http::fake([
            'http://localhost:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Check whether the VPN client is installed, then confirm your username, password, and internet connection before reconnecting.',
                ],
            ]),
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $chat = AiAssistantChat::query()->create([
            'user_id' => $employee->id,
            'title' => 'VPN Issue',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $response = $this->actingAs($employee)->post(route('assistant.messages.store', $chat), [
            'message' => "i can't connect VPN",
        ]);

        $response->assertRedirect(route('assistant.show', $chat));

        Http::assertSentCount(1);

        $this->assertDatabaseHas('ai_assistant_messages', [
            'chat_id' => $chat->id,
            'role' => 'assistant',
            'content' => 'Check whether the VPN client is installed, then confirm your username, password, and internet connection before reconnecting.',
        ]);
    }

    public function test_employee_can_send_a_message_with_json_and_receive_chat_payload(): void
    {
        config()->set('services.ai.provider', 'ollama');
        config()->set('services.ollama.base_url', 'http://localhost:11434');
        config()->set('services.ollama.model', 'llama3.2:3b');
        config()->set('services.ollama.assistant_enabled', true);

        Http::fake([
            'http://localhost:11434/api/chat' => Http::response([
                'message' => [
                    'role' => 'assistant',
                    'content' => 'Please restart the Wi-Fi adapter, then try reconnecting to the office network.',
                ],
            ]),
        ]);

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $chat = AiAssistantChat::query()->create([
            'user_id' => $employee->id,
            'title' => 'Network Issue',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $response = $this
            ->actingAs($employee)
            ->postJson(route('assistant.messages.store', $chat), [
                'message' => 'My laptop cannot join the office Wi-Fi.',
            ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'chat' => ['id', 'title', 'messages_count', 'last_message_at', 'show_url'],
            'user_message' => ['id', 'role', 'content', 'time'],
            'assistant_message' => ['id', 'role', 'content', 'time'],
        ]);
    }

    public function test_employee_can_create_a_chat_with_json(): void
    {
        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $response = $this
            ->actingAs($employee)
            ->postJson(route('assistant.store'));

        $response->assertOk();
        $response->assertJsonStructure([
            'status',
            'chat' => ['id', 'title', 'messages_count', 'last_message_at', 'show_url'],
            'messages' => [
                '*' => ['id', 'role', 'content', 'time'],
            ],
        ]);
    }

    public function test_employee_off_topic_question_is_blocked_before_calling_the_ai_provider(): void
    {
        config()->set('services.ai.provider', 'ollama');
        config()->set('services.ollama.base_url', 'http://localhost:11434');
        config()->set('services.ollama.model', 'llama3.2:3b');
        config()->set('services.ollama.assistant_enabled', true);

        Http::fake();

        $employee = User::factory()->create([
            'role' => UserRole::Employee,
        ]);

        $chat = AiAssistantChat::query()->create([
            'user_id' => $employee->id,
            'title' => 'General Chat',
            'status' => 'active',
            'last_message_at' => now(),
        ]);

        $response = $this
            ->actingAs($employee)
            ->postJson(route('assistant.messages.store', $chat), [
                'message' => 'Tell me a joke about football players.',
            ]);

        $response->assertOk();
        $response->assertJsonPath(
            'assistant_message.content',
            'I can only help with IT support topics. Please ask about laptops, printers, software, email, accounts, passwords, Wi-Fi, VPN, network access, or another technical issue at work.'
        );

        Http::assertNothingSent();
    }
}
