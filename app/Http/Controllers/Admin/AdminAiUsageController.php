<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiJob;
use App\Models\ChatMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What the AI is actually costing, and who is driving it.
 *
 * Token counts were already being recorded on every job and chat reply and
 * never shown anywhere, so the only way to discover a runaway was to read the
 * provider's bill. The costs here are estimates from a configured per-million
 * rate — the provider is the authority, this is the early-warning signal.
 */
class AdminAiUsageController extends Controller
{
    private const RANGE_DAYS = ['7' => 7, '30' => 30, '90' => 90];

    public function index(Request $request): View
    {
        $range = $request->query('range', '30');
        $days = self::RANGE_DAYS[$range] ?? 30;
        $since = now()->subDays($days - 1)->startOfDay();

        $jobTotals = AiJob::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(SUM(input_tokens),0) as input, COALESCE(SUM(output_tokens),0) as output, COUNT(*) as runs')
            ->first();

        $chatTotals = ChatMessage::where('created_at', '>=', $since)
            ->selectRaw('COALESCE(SUM(input_tokens),0) as input, COALESCE(SUM(output_tokens),0) as output, COUNT(*) as runs')
            ->first();

        $inputTokens = (int) $jobTotals->input + (int) $chatTotals->input;
        $outputTokens = (int) $jobTotals->output + (int) $chatTotals->output;

        return view('admin.ai-usage', [
            'range' => $range,
            'ranges' => array_keys(self::RANGE_DAYS),
            'days' => $days,
            'inputTokens' => $inputTokens,
            'outputTokens' => $outputTokens,
            'estimatedCost' => $this->estimateCost($inputTokens, $outputTokens),
            'reportRuns' => (int) $jobTotals->runs,
            'chatRuns' => (int) $chatTotals->runs,
            'byJobType' => AiJob::where('created_at', '>=', $since)
                ->selectRaw('job_type, COUNT(*) as runs, COALESCE(SUM(input_tokens + output_tokens),0) as tokens')
                ->groupBy('job_type')->orderByDesc('tokens')->get(),
            'byStatus' => AiJob::where('created_at', '>=', $since)
                ->selectRaw('status, COUNT(*) as runs')->groupBy('status')->pluck('runs', 'status'),
            'byProvider' => AiJob::where('created_at', '>=', $since)
                ->whereNotNull('provider')
                ->selectRaw('provider, COUNT(*) as runs, COALESCE(SUM(input_tokens + output_tokens),0) as tokens')
                ->groupBy('provider')->orderByDesc('tokens')->get(),
            'dailySeries' => $this->dailySeries($since),
            'topChatUsers' => $this->topChatUsers($since),
            'recentFailures' => AiJob::where('status', 'failed')
                ->whereNotNull('error_message')
                ->latest()->limit(8)->get(['id', 'job_type', 'provider', 'error_message', 'created_at']),
        ]);
    }

    /**
     * Rough spend, from a per-million-token rate in config. Deliberately an
     * estimate: providers price per model and change rates, so this is a
     * trend line to watch, not an invoice.
     */
    private function estimateCost(int $input, int $output): float
    {
        $inRate = (float) config('services.gemini.input_cost_per_million', 0.10);
        $outRate = (float) config('services.gemini.output_cost_per_million', 0.40);

        return ($input / 1_000_000 * $inRate) + ($output / 1_000_000 * $outRate);
    }

    /** Zero-filled daily token totals across both jobs and chat. */
    private function dailySeries(\Illuminate\Support\Carbon $since): array
    {
        $jobs = AiJob::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(input_tokens + output_tokens),0) as tokens')
            ->groupBy('day')->pluck('tokens', 'day');

        $chat = ChatMessage::where('created_at', '>=', $since)
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(input_tokens + output_tokens),0) as tokens')
            ->groupBy('day')->pluck('tokens', 'day');

        $totalDays = max(1, (int) $since->diffInDays(now()) + 1);

        return collect(range(0, $totalDays - 1))->map(function ($i) use ($since, $jobs, $chat) {
            $date = $since->copy()->addDays($i)->format('Y-m-d');

            return [
                'date' => $date,
                'tokens' => (int) ($jobs[$date] ?? 0) + (int) ($chat[$date] ?? 0),
            ];
        })->all();
    }

    /**
     * Heaviest assistant users. With no rate limit on the assistant, this is
     * the view that would reveal one account burning the shared quota.
     */
    private function topChatUsers(\Illuminate\Support\Carbon $since)
    {
        return ChatMessage::where('chat_messages.created_at', '>=', $since)
            ->whereNotNull('asked_by_user_id')
            ->join('users', 'users.id', '=', 'chat_messages.asked_by_user_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->select('users.name', 'users.email')
            ->selectRaw('COUNT(*) as messages, COALESCE(SUM(chat_messages.input_tokens + chat_messages.output_tokens),0) as tokens')
            ->orderByDesc('tokens')
            ->limit(8)
            ->get();
    }
}
