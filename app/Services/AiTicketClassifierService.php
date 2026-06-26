<?php

namespace App\Services;

use App\Enums\TicketPriority;
use App\Models\Category;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiTicketClassifierService
{
    public function classify(string $title, string $description): array
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->get(['id', 'name', 'description']);

        if ($categories->isEmpty()) {
            throw new RuntimeException('No active categories are configured for AI classification.');
        }

        $priorityValues = array_map(
            static fn (TicketPriority $priority): string => $priority->value,
            TicketPriority::cases(),
        );

        if (! $this->isEnabled() || blank(config('services.openai.api_key'))) {
            return $this->heuristicClassification($title, $description, $categories);
        }

        try {
            $response = Http::withToken(config('services.openai.api_key'))
                ->timeout(20)
                ->acceptJson()
                ->post('https://api.openai.com/v1/responses', [
                    'model' => config('services.openai.model', 'gpt-4.1-mini'),
                    'store' => false,
                    'input' => [
                        [
                            'role' => 'system',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => $this->systemPrompt($categories->map(function (Category $category): array {
                                        return [
                                            'name' => $category->name,
                                            'description' => $category->description,
                                        ];
                                    })->all(), $priorityValues),
                                ],
                            ],
                        ],
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'input_text',
                                    'text' => "Title: {$title}\nDescription: {$description}",
                                ],
                            ],
                        ],
                    ],
                    'text' => [
                        'format' => [
                            'type' => 'json_schema',
                            'name' => 'ticket_classification',
                            'description' => 'Ticket category and priority classification for IT help desk intake.',
                            'strict' => true,
                            'schema' => [
                                'type' => 'object',
                                'additionalProperties' => false,
                                'properties' => [
                                    'category_name' => [
                                        'type' => 'string',
                                        'enum' => $categories->pluck('name')->values()->all(),
                                    ],
                                    'priority' => [
                                        'type' => 'string',
                                        'enum' => $priorityValues,
                                    ],
                                    'reason' => [
                                        'type' => 'string',
                                    ],
                                ],
                                'required' => ['category_name', 'priority', 'reason'],
                            ],
                        ],
                    ],
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('The AI classification request failed.');
            }

            $classification = $this->decodeStructuredOutput($response->json());

            return $this->normalizeClassification(
                categoryName: (string) ($classification['category_name'] ?? ''),
                priorityValue: (string) ($classification['priority'] ?? ''),
                reason: (string) ($classification['reason'] ?? ''),
                categories: $categories,
                fallbackTitle: $title,
                fallbackDescription: $description,
            );
        } catch (Throwable) {
            return $this->heuristicClassification($title, $description, $categories);
        }
    }

    protected function isEnabled(): bool
    {
        return (bool) config('services.openai.ticket_classification_enabled', true);
    }

    protected function decodeStructuredOutput(array $payload): array
    {
        $text = data_get($payload, 'output.0.content.0.text');

        if (! is_string($text) || trim($text) === '') {
            $text = collect($payload['output'] ?? [])
                ->flatMap(fn (array $item): array => $item['content'] ?? [])
                ->map(fn (array $content): ?string => $content['text'] ?? null)
                ->first(fn (?string $candidate): bool => filled($candidate));
        }

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('The AI response did not contain structured text output.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('The AI response JSON could not be decoded.');
        }

        return $decoded;
    }

    protected function normalizeClassification(
        string $categoryName,
        string $priorityValue,
        string $reason,
        $categories,
        string $fallbackTitle,
        string $fallbackDescription
    ): array {
        $category = $categories->first(
            fn (Category $candidate): bool => Str::lower($candidate->name) === Str::lower($categoryName)
        );

        $priority = collect(TicketPriority::cases())
            ->first(fn (TicketPriority $candidate): bool => $candidate->value === $priorityValue);

        if (! $category || ! $priority) {
            return $this->heuristicClassification($fallbackTitle, $fallbackDescription, $categories);
        }

        return [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'priority' => $priority->value,
            'reason' => filled($reason) ? $reason : 'Classified by AI using the provided title and description.',
            'source' => 'ai',
        ];
    }

    protected function heuristicClassification(string $title, string $description, $categories): array
    {
        $haystack = Str::lower(trim($title.' '.$description));
        $category = $this->matchCategoryFromKeywords($haystack, $categories) ?? $categories->first();

        return [
            'category_id' => $category->id,
            'category_name' => $category->name,
            'priority' => $this->detectPriorityFromKeywords($haystack)->value,
            'reason' => 'Fallback classification was applied from internal keyword rules.',
            'source' => 'fallback',
        ];
    }

    protected function matchCategoryFromKeywords(string $haystack, $categories): ?Category
    {
        $keywordMap = [
            'network' => ['wifi', 'wi-fi', 'internet', 'network', 'vpn', 'lan', 'router', 'connection'],
            'hardware' => ['laptop', 'keyboard', 'mouse', 'screen', 'monitor', 'battery', 'charger', 'hardware', 'pc'],
            'software' => ['software', 'application', 'app', 'excel', 'word', 'system', 'install', 'crash'],
            'email' => ['email', 'outlook', 'mailbox', 'send mail', 'receive mail'],
            'access' => ['password', 'login', 'signin', 'sign in', 'permission', 'access', 'account', 'authentication'],
            'printer' => ['printer', 'printing', 'print', 'scanner'],
        ];

        foreach ($keywordMap as $categoryNeedle => $keywords) {
            foreach ($keywords as $keyword) {
                if (! Str::contains($haystack, $keyword)) {
                    continue;
                }

                $matchedCategory = $categories->first(function (Category $category) use ($categoryNeedle): bool {
                    return Str::contains(Str::lower($category->name), $categoryNeedle);
                });

                if ($matchedCategory) {
                    return $matchedCategory;
                }
            }
        }

        return null;
    }

    protected function detectPriorityFromKeywords(string $haystack): TicketPriority
    {
        if (Str::contains($haystack, [
            'all users', 'everyone', 'whole office', 'office down', 'system down', 'urgent', 'critical',
            'cannot work', 'stopped working', 'outage', 'entire floor',
        ])) {
            return TicketPriority::Critical;
        }

        if (Str::contains($haystack, [
            'cannot connect', 'cannot login', 'blocked', 'unable to work', 'unable to access',
            'not working', 'fails', 'error', 'urgent for work',
        ])) {
            return TicketPriority::High;
        }

        if (Str::contains($haystack, [
            'slow', 'issue', 'problem', 'help', 'request', 'need assistance',
        ])) {
            return TicketPriority::Medium;
        }

        return TicketPriority::Low;
    }

    protected function systemPrompt(array $categories, array $priorityValues): string
    {
        $categoryText = collect($categories)
            ->map(function (array $category): string {
                $description = trim((string) ($category['description'] ?? ''));

                return $description !== ''
                    ? "{$category['name']} ({$description})"
                    : $category['name'];
            })
            ->implode(', ');

        return "You classify internal IT help desk tickets.\n"
            ."Choose exactly one category and one priority.\n"
            ."Allowed categories: {$categoryText}.\n"
            .'Allowed priorities: '.implode(', ', $priorityValues).".\n"
            ."Priority guidance:\n"
            ."- critical: major outage, whole team affected, or work completely blocked for many users\n"
            ."- high: a user is blocked from important work\n"
            ."- medium: issue affects work but a workaround may exist\n"
            ."- low: minor inconvenience or low urgency request\n"
            ."Return the best possible classification for the given title and description.";
    }
}
