<?php

namespace App\Services;

use App\Models\AiAssistantChat;
use App\Models\AiAssistantMessage;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiSupportAssistantService
{
    public function respond(AiAssistantChat $chat): string
    {
        if (! $this->isItScopedConversation($chat)) {
            return $this->outOfScopeReply();
        }

        if (! $this->isEnabled() || ! $this->providerIsConfigured()) {
            return $this->fallbackReply();
        }

        try {
            return match ($this->provider()) {
                'ollama' => $this->respondWithOllama($chat),
                'openai' => $this->respondWithOpenAi($chat),
                default => throw new RuntimeException('Unsupported AI assistant provider.'),
            };
        } catch (Throwable $exception) {
            Log::warning('AI assistant response failed.', [
                'chat_id' => $chat->id,
                'provider' => $this->provider(),
                'message' => $exception->getMessage(),
            ]);

            return $this->fallbackReply();
        }
    }

    public function isReady(): bool
    {
        return $this->isEnabled() && $this->providerIsConfigured();
    }

    protected function isEnabled(): bool
    {
        return match ($this->provider()) {
            'ollama' => (bool) config('services.ollama.assistant_enabled', true),
            'openai' => (bool) config('services.openai.assistant_enabled', true),
            default => false,
        };
    }

    protected function provider(): string
    {
        return (string) config('services.ai.provider', 'openai');
    }

    protected function providerIsConfigured(): bool
    {
        return match ($this->provider()) {
            'ollama' => filled(config('services.ollama.base_url')) && filled(config('services.ollama.model')),
            'openai' => filled(config('services.openai.api_key')),
            default => false,
        };
    }

    protected function respondWithOpenAi(AiAssistantChat $chat): string
    {
        $payload = [
            'model' => config('services.openai.assistant_model', config('services.openai.model', 'gpt-4.1-mini')),
            'store' => false,
            'instructions' => $this->systemInstructions(),
            'input' => $this->buildOpenAiConversationInput($chat),
        ];

        if (filled($vectorStoreId = config('services.openai.assistant_vector_store_id'))) {
            $payload['tools'] = [[
                'type' => 'file_search',
                'vector_store_ids' => [$vectorStoreId],
                'max_num_results' => 4,
            ]];
        }

        $response = Http::withToken(config('services.openai.api_key'))
            ->timeout(45)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('The OpenAI assistant request failed.');
        }

        return $this->extractOpenAiOutputText($response->json());
    }

    protected function respondWithOllama(AiAssistantChat $chat): string
    {
        $response = Http::baseUrl(rtrim((string) config('services.ollama.base_url'), '/'))
            ->timeout(90)
            ->acceptJson()
            ->post('/api/chat', [
                'model' => config('services.ollama.model', 'llama3.2:3b'),
                'stream' => false,
                'options' => [
                    'temperature' => 0.2,
                ],
                'messages' => $this->buildOllamaConversationInput($chat),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('The Ollama assistant request failed.');
        }

        $content = data_get($response->json(), 'message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('The Ollama assistant response did not contain message content.');
        }

        return trim($content);
    }

    protected function buildOpenAiConversationInput(AiAssistantChat $chat): array
    {
        return $chat->messages()
            ->latest('created_at')
            ->limit(14)
            ->get()
            ->reverse()
            ->values()
            ->map(function (AiAssistantMessage $message): array {
                return [
                    'role' => $message->role === 'assistant' ? 'assistant' : 'user',
                    'content' => [[
                        'type' => 'input_text',
                        'text' => $message->content,
                    ]],
                ];
            })
            ->all();
    }

    protected function buildOllamaConversationInput(AiAssistantChat $chat): array
    {
        return collect([[
            'role' => 'system',
            'content' => $this->systemInstructions(),
        ]])->merge(
            $chat->messages()
                ->latest('created_at')
                ->limit(14)
                ->get()
                ->reverse()
                ->values()
                ->map(function (AiAssistantMessage $message): array {
                    return [
                        'role' => $message->role === 'assistant' ? 'assistant' : 'user',
                        'content' => $message->content,
                    ];
                })
        )->values()->all();
    }

    protected function extractOpenAiOutputText(array $payload): string
    {
        $outputText = data_get($payload, 'output_text');

        if (is_string($outputText) && trim($outputText) !== '') {
            return trim($outputText);
        }

        $text = collect($payload['output'] ?? [])
            ->filter(fn (array $item): bool => ($item['type'] ?? null) === 'message')
            ->flatMap(fn (array $item): array => $item['content'] ?? [])
            ->map(function (array $content): ?string {
                return $content['text']
                    ?? data_get($content, 'output_text')
                    ?? data_get($content, 'content.0.text');
            })
            ->first(fn (?string $candidate): bool => filled($candidate));

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('The AI assistant response did not contain text output.');
        }

        return trim($text);
    }

    protected function systemInstructions(): string
    {
        $knowledgeMode = $this->provider() === 'openai' && filled(config('services.openai.assistant_vector_store_id'))
            ? 'Use the connected internal help desk knowledge base first when answering.'
            : 'Answer only with general IT help desk guidance and avoid inventing company-specific policies.';

        $categoryContext = $this->categoryContextText();

        return "You are the IDS Internal IT Help Desk AI Assistant.\n"
            ."Your job is to help employees troubleshoot common IT issues before they open a support ticket.\n"
            ."Only answer questions related to IT help desk topics such as devices, laptops, printers, email, accounts, passwords, software, network, internet, VPN, peripherals, and internal support workflow.\n"
            ."If the user asks about anything outside IT support, politely refuse and tell them you only handle internal IT help desk questions.\n"
            ."If the question seems technical but is missing details, ask one short clarifying question instead of refusing.\n"
            ."When the issue is clear, give direct troubleshooting steps tailored to the exact problem.\n"
            ."Do not claim actions were completed unless the user confirms them.\n"
            ."When appropriate, give numbered troubleshooting steps.\n"
            ."If the issue sounds severe, blocked, risky, or unresolved after basic steps, tell the user to create or follow up on a support ticket.\n"
            ."If you are unsure, say so clearly instead of guessing.\n"
            .$categoryContext
            .$knowledgeMode;
    }

    protected function isItScopedConversation(AiAssistantChat $chat): bool
    {
        $latestUserMessage = $chat->messages()
            ->where('role', 'user')
            ->latest('created_at')
            ->first();

        if (! $latestUserMessage) {
            return true;
        }

        if ($this->messageLooksItRelated($latestUserMessage->content)) {
            return true;
        }

        if ($this->isContextualFollowUp($latestUserMessage->content) && $this->chatHasPriorItContext($chat, $latestUserMessage->id)) {
            return true;
        }

        return false;
    }

    protected function messageLooksItRelated(string $message): bool
    {
        $normalized = $this->normalizeScopeText($message);

        if ($normalized === '') {
            return false;
        }

        $tokens = collect(preg_split('/\s+/', $normalized) ?: [])
            ->filter(fn (?string $token): bool => filled($token));

        $keywords = $this->baseItKeywords()
            ->merge($this->categoryKeywords())
            ->unique()
            ->values();

        if ($keywords->contains(fn (string $keyword): bool => Str::contains($normalized, $keyword))) {
            return true;
        }

        $issueSignals = collect([
            'cant', 'cannot', 'connect', 'connected', 'connection', 'error', 'issue', 'problem',
            'slow', 'broken', 'failed', 'fails', 'failing', 'blocked', 'stuck', 'reset',
            'login', 'signin', 'password', 'access', 'install', 'update', 'print', 'printing',
            'open', 'crash', 'freeze', 'restart',
        ]);

        $technicalObjects = collect([
            'vpn', 'wifi', 'internet', 'network', 'router', 'ethernet', 'laptop', 'desktop', 'computer',
            'device', 'printer', 'scanner', 'monitor', 'screen', 'keyboard', 'mouse', 'software',
            'application', 'app', 'system', 'windows', 'driver', 'email', 'outlook', 'mailbox',
            'account', 'browser', 'server', 'excel', 'word', 'teams',
        ]);

        return $tokens->intersect($issueSignals)->isNotEmpty()
            && $tokens->intersect($technicalObjects)->isNotEmpty();
    }

    protected function isContextualFollowUp(string $message): bool
    {
        $normalized = Str::lower(trim($message));

        if ($normalized === '') {
            return false;
        }

        return collect([
            'yes', 'no', 'ok', 'okay', 'still', 'still not working', 'it did not work', 'not working',
            'same problem', 'same issue', 'what next', 'then what', 'now what', 'continue',
            'can you explain more', 'more details', 'help me more', 'why', 'how', 'i tried that',
            'it works', 'it worked', 'did not help',
        ])->contains(fn (string $phrase): bool => $normalized === $phrase || Str::contains($normalized, $phrase));
    }

    protected function chatHasPriorItContext(AiAssistantChat $chat, int $latestUserMessageId): bool
    {
        return $chat->messages()
            ->where('role', 'user')
            ->where('id', '!=', $latestUserMessageId)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->contains(fn (AiAssistantMessage $message): bool => $this->messageLooksItRelated($message->content));
    }

    protected function outOfScopeReply(): string
    {
        return 'I can only help with IT support topics. Please ask about laptops, printers, software, email, accounts, passwords, Wi-Fi, VPN, network access, or another technical issue at work.';
    }

    protected function normalizeScopeText(string $message): string
    {
        return Str::of($message)
            ->lower()
            ->replace(['wi-fi', 'wi fi'], 'wifi')
            ->replace(['sign in', 'sign-in'], 'signin')
            ->replace(['log in', 'log-in'], 'login')
            ->replace(['e-mail', 'e mail'], 'email')
            ->replace(["’", "'"], '')
            ->replaceMatches('/[^a-z0-9\s\-]+/', ' ')
            ->squish()
            ->value();
    }

    protected function baseItKeywords()
    {
        return collect([
            'help desk', 'technical support', 'support ticket', 'it support', 'troubleshoot',
            'laptop', 'desktop', 'computer', 'pc', 'device', 'monitor', 'screen', 'keyboard', 'mouse',
            'printer', 'scanner', 'headset', 'dock', 'docking station', 'peripheral',
            'wifi', 'wi fi', 'internet', 'network', 'vpn', 'router', 'ethernet', 'connection',
            'software', 'application', 'app', 'install', 'update', 'system', 'windows', 'driver',
            'email', 'outlook', 'mailbox', 'account', 'login', 'sign in', 'signin', 'password',
            'access', 'permission', 'authentication', 'server', 'browser', 'website', 'microsoft',
            'excel', 'word', 'teams', 'slow', 'crash', 'freeze', 'not working',
        ]);
    }

    protected function categoryKeywords()
    {
        return Category::query()
            ->active()
            ->get(['name', 'description'])
            ->flatMap(function (Category $category): array {
                $name = $this->normalizeScopeText((string) $category->name);
                $description = $this->normalizeScopeText((string) $category->description);

                return array_filter([
                    $name,
                    ...preg_split('/[\s\/&()-]+/', $name) ?: [],
                    ...preg_split('/[\s\/&()-]+/', $description) ?: [],
                ], fn (?string $keyword): bool => filled($keyword) && Str::length($keyword) >= 3);
            });
    }

    protected function categoryContextText(): string
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->get(['name', 'description']);

        if ($categories->isEmpty()) {
            return '';
        }

        $summary = $categories
            ->map(function (Category $category): string {
                $description = trim((string) $category->description);

                return $description !== ''
                    ? "{$category->name} ({$description})"
                    : $category->name;
            })
            ->implode(', ');

        return "Current help desk categories in this system: {$summary}.\n";
    }

    protected function fallbackReply(): string
    {
        return 'The AI assistant is temporarily unavailable right now. Please make sure the selected AI provider is running and properly configured, then try again. '
            .'If your issue is urgent or blocking your work, create a help desk ticket so the support team can assist you directly.';
    }
}
