<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAgentService
{
    protected $apiKey;
    protected $apiUrl = 'https://openrouter.ai/api/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
    }

    /**
     * Professional AI Agent via OpenRouter
     */
    public function chat($message, $user)
    {
        if (!$this->apiKey) {
            return "Please configure the OpenRouter API key to enable the AI Agent.";
        }

        $context = $this->getPlatformContext($user);
        $contextStr = "Current User: {$user->name} (@{$user->username}). " .
                     "Joined Groups: {$context['groups']}. " .
                     "Recent Activity: {$context['recent_posts']}.";

        try {
            // Updated list of verified free models on OpenRouter (May 2026)
            $freeModels = [
                'google/gemini-2.0-flash-lite-preview-02-05:free',
                'meta-llama/llama-3.1-8b-instruct:free',
                'meta-llama/llama-3.2-3b-instruct:free',
                'google/gemma-2-9b-it:free',
                'mistralai/mistral-7b-instruct:free',
                'microsoft/phi-3-mini-128k-instruct:free',
                'openrouter/auto',
            ];

            $systemPrompt = "You are 'Nexus AI', the official intelligent assistant for the Nexus Social Platform. " .
                           "Your goal is to help users navigate the platform, draft engaging posts, find communities, and understand Nexus features. " .
                           "User Information: {$contextStr}. " .
                           "Platform rules: Be helpful, professional, and concise (max 3 sentences). " .
                           "Use a friendly, companion-like tone. If the user asks in Arabic, respond in Arabic.";

            foreach ($freeModels as $model) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => 'https://github.com/awad/socket-nexus-project',
                    'X-Title' => 'Nexus Social Platform',
                ])->timeout(25)->post($this->apiUrl, [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $message],
                    ],
                    'temperature' => 0.75,
                    'max_tokens' => 300,
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    $content = $data['choices'][0]['message']['content'] ?? null;
                    if ($content) {
                        return trim($content);
                    }
                } else {
                    Log::warning("AI Model $model failed or throttled. Status: " . $response->status());
                }
            }

            // FALLBACK: Local AI Simulation
            Log::info("Falling back to Local AI Brain for user {$user->id}");
            return app(\App\Services\LocalAiBrain::class)->generateResponse($message, $user);

        } catch (\Exception $e) {
            Log::error('AI Agent Exception: ' . $e->getMessage());
            return app(\App\Services\LocalAiBrain::class)->generateResponse($message, $user);
        }
    }

    /**
     * Gather brief context about the platform and user
     */
    protected function getPlatformContext($user)
    {
        $groups = $user->groups()->limit(3)->pluck('name')->implode(', ');
        $recentPosts = \App\Models\Post::where('user_id', $user->id)->limit(2)->pluck('content')->map(fn($c) => str($c)->limit(30))->implode(' | ');

        return [
            'groups' => $groups ?: 'No groups joined yet',
            'recent_posts' => $recentPosts ?: 'No recent posts',
        ];
    }
}
