<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\ScopesToCaller;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\Ai\AiClient;
use App\Services\Assistant\AssistantContextBuilder;
use App\Services\Assistant\AssistantSafety;
use App\Services\Assistant\RagRetriever;
use App\Services\Ollama\OllamaClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * The assistant over the API.
 *
 * Shares the safety layer and prompt builder with the web controller rather
 * than restating them: a guardrail that only holds on one platform is not a
 * guardrail. The rate limit is applied at the route.
 */
class AssistantController extends Controller
{
    use ScopesToCaller;

    private const HISTORY_MESSAGES = 10;

    public function history(Request $request): JsonResponse
    {
        $member = $this->resolveMember($request->user(), $request->integer('member') ?: null);

        $messages = ChatMessage::where('family_member_id', $member->id)
            ->orderBy('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'data' => $messages->map(fn ($m) => [
                'role' => $m->role,
                'content' => $m->content,
                'created_at' => $m->created_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function send(
        Request $request,
        AiClient $ai,
        AssistantContextBuilder $contextBuilder,
        AssistantSafety $safety,
        OllamaClient $ollama,
        RagRetriever $ragRetriever,
    ): JsonResponse {
        $user = $request->user();

        $validated = $request->validate([
            'family_member_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $member = $this->resolveMember($user, $validated['family_member_id'] ?? null);

        $recent = ChatMessage::where('family_member_id', $member->id)
            ->orderByDesc('created_at')
            ->limit(self::HISTORY_MESSAGES)
            ->get()
            ->reverse()
            ->values();

        ChatMessage::create([
            'family_member_id' => $member->id,
            'asked_by_user_id' => $user->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        // Red-flag symptoms and self-harm are answered before the model is
        // reached, identically to the web app.
        if ($notice = $safety->emergencyNoticeFor($validated['message'])) {
            $reply = $this->store($member->id, $user->id, $notice);

            return response()->json([
                'reply' => $reply->content,
                'urgent' => true,
                'created_at' => $reply->created_at->toIso8601String(),
            ]);
        }

        if (! $ollama->isConfigured() && ! $ai->hasAvailableCredential()) {
            $reply = $this->store($member->id, $user->id, "AI features aren't available right now — please try again later.");

            return response()->json([
                'reply' => $reply->content,
                'urgent' => false,
                'created_at' => $reply->created_at->toIso8601String(),
            ]);
        }

        $inputTokens = null;
        $outputTokens = null;
        $content = null;

        // Same RAG-via-Ollama-first, Gemini/Groq-fallback pattern as the web
        // AiChatController — see its comment for the reasoning.
        if ($ollama->isConfigured()) {
            try {
                $chunks = $ragRetriever->retrieve($member, $validated['message']);
                $prompt = $contextBuilder->buildWithRetrieval($member, $recent, $validated['message'], $chunks);
                $result = $ollama->generate($prompt);
                $content = $result['text'];
                $inputTokens = $result['input_tokens'];
                $outputTokens = $result['output_tokens'];
            } catch (Throwable $e) {
                report($e);
            }
        }

        if ($content === null) {
            try {
                $result = $ai->generate($contextBuilder->build($member, $recent, $validated['message']));
                $content = $result['text'];
                $inputTokens = $result['input_tokens'];
                $outputTokens = $result['output_tokens'];
            } catch (Throwable $e) {
                report($e);
                $content = "Sorry, I couldn't process that just now. Please try again in a moment.";
            }
        }

        $reply = $this->store($member->id, $user->id, $content, $inputTokens, $outputTokens);

        return response()->json([
            'reply' => $reply->content,
            'urgent' => false,
            'created_at' => $reply->created_at->toIso8601String(),
        ]);
    }

    private function store(int $memberId, int $userId, string $content, ?int $in = null, ?int $out = null): ChatMessage
    {
        return ChatMessage::create([
            'family_member_id' => $memberId,
            'asked_by_user_id' => $userId,
            'role' => 'assistant',
            'content' => $content,
            'input_tokens' => $in,
            'output_tokens' => $out,
        ]);
    }
}
