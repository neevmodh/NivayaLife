<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesActiveFamilyMember;
use App\Models\ChatMessage;
use App\Models\FamilyMember;
use App\Services\Ai\AiClient;
use App\Services\Assistant\AssistantContextBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class AiChatController extends Controller
{
    use ResolvesActiveFamilyMember;

    /** Folded into the prompt text as prior turns — the underlying clients only send a single text prompt, so real multi-turn "contents" isn't wired up yet. */
    private const HISTORY_MESSAGES = 10;

    public function index(Request $request): View
    {
        $user = $request->user();
        $active = $this->resolveActiveFamilyMember($request, $user);
        abort_unless($active->hasGrantedAccessTo($user), 403);

        $messages = ChatMessage::where('family_member_id', $active->id)
            ->orderBy('created_at')
            ->get();

        return view('assistant.index', [
            'active' => $active,
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, AiClient $ai, AssistantContextBuilder $contextBuilder): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'family_member_id' => ['required', 'integer', 'exists:family_members,id'],
            'message' => ['required', 'string', 'max:1000'],
        ]);

        $active = FamilyMember::findOrFail($validated['family_member_id']);
        abort_unless($active->hasGrantedAccessTo($user), 403);

        $recentMessages = ChatMessage::where('family_member_id', $active->id)
            ->orderByDesc('created_at')
            ->limit(self::HISTORY_MESSAGES)
            ->get()
            ->reverse()
            ->values();

        ChatMessage::create([
            'family_member_id' => $active->id,
            'asked_by_user_id' => $user->id,
            'role' => 'user',
            'content' => $validated['message'],
        ]);

        if (! $ai->hasAvailableCredential()) {
            $reply = ChatMessage::create([
                'family_member_id' => $active->id,
                'asked_by_user_id' => $user->id,
                'role' => 'assistant',
                'content' => "AI features aren't available right now — please try again later.",
            ]);

            return response()->json(['success' => true, 'reply' => $reply->content, 'created_at' => $reply->created_at->toIso8601String()]);
        }

        $inputTokens = null;
        $outputTokens = null;

        try {
            $prompt = $contextBuilder->build($active, $recentMessages, $validated['message']);
            $result = $ai->generate($prompt);
            $content = $result['text'];
            $inputTokens = $result['input_tokens'];
            $outputTokens = $result['output_tokens'];
        } catch (Throwable $e) {
            report($e);
            $content = "Sorry, I couldn't process that just now. Please try again in a moment.";
        }

        $reply = ChatMessage::create([
            'family_member_id' => $active->id,
            'asked_by_user_id' => $user->id,
            'role' => 'assistant',
            'content' => $content,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
        ]);

        return response()->json(['success' => true, 'reply' => $reply->content, 'created_at' => $reply->created_at->toIso8601String()]);
    }
}
