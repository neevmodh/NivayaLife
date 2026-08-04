<?php

namespace App\Models;

use App\Mail\PendingDeploymentMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Mail;

class PendingDeployment extends Model
{
    protected $fillable = [
        'commit_sha',
        'commit_message',
        'author_name',
        'author_email',
        'branch',
        'pushed_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'railway_deployment_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'pushed_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'railway_deployment_triggered_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Records a new push and supersedes any other still-pending row on the
     * same branch — only the single latest pending commit is ever
     * approvable, since Railway's deploy-latest-commit call has no way to
     * target a specific older SHA. Idempotent on commit_sha so a redelivered
     * webhook (GitHub retries on timeout) doesn't duplicate rows or resend
     * the notification email.
     */
    public static function recordPush(array $data): self
    {
        $existing = static::where('commit_sha', $data['commit_sha'])->first();

        if ($existing) {
            return $existing;
        }

        static::where('branch', $data['branch'] ?? 'main')
            ->where('status', 'pending')
            ->update(['status' => 'superseded']);

        $deployment = static::create($data);

        if (config('admin.email')) {
            Mail::to(config('admin.email'))->queue(new PendingDeploymentMail($deployment));
        }

        return $deployment;
    }
}
