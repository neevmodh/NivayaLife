<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PendingDeployment;
use App\Services\Railway\RailwayClient;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Approve/reject gate in front of Railway deploys. Only the single latest
 * pending (or failed) row on main is ever actionable — see
 * PendingDeployment::recordPush() — since Railway's deploy call always
 * deploys whatever the latest commit on the branch currently is, not a
 * specific SHA.
 */
class AdminDeploymentController extends Controller
{
    public function index(): View
    {
        return view('admin.deployments.index', [
            'deployments' => PendingDeployment::with('reviewer:id,name')->latest('pushed_at')->paginate(25),
            'actionableId' => $this->actionableDeploymentId(),
        ]);
    }

    public function approve(PendingDeployment $deployment): RedirectResponse
    {
        if ($deployment->id !== $this->actionableDeploymentId()) {
            return back()->with('admin_error', 'Only the latest pending commit can be approved.');
        }

        try {
            app(RailwayClient::class)->deployLatestCommit();
        } catch (RuntimeException $e) {
            $deployment->update([
                'status' => 'failed',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
            AuditLog::record('deploy_trigger_failed', 'PendingDeployment', $deployment->id);

            return back()->with('admin_error', 'Railway deploy failed: '.$e->getMessage());
        }

        $deployment->update([
            'status' => 'deployed',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'railway_deployment_triggered_at' => now(),
        ]);
        AuditLog::record('deploy_approved', 'PendingDeployment', $deployment->id);

        return back()->with('admin_status', "Deploy triggered for commit {$deployment->commit_sha}.");
    }

    public function reject(PendingDeployment $deployment): RedirectResponse
    {
        if ($deployment->id !== $this->actionableDeploymentId()) {
            return back()->with('admin_error', 'Only the latest pending commit can be rejected.');
        }

        $deployment->update([
            'status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
        AuditLog::record('deploy_rejected', 'PendingDeployment', $deployment->id);

        return back()->with('admin_status', "Commit {$deployment->commit_sha} rejected.");
    }

    /**
     * The one row currently open for action — the latest push on main that's
     * still pending or previously failed to deploy. Null if nothing's
     * waiting. Recomputed server-side on every approve/reject so a stale
     * button in an old browser tab can never approve a superseded commit.
     */
    private function actionableDeploymentId(): ?int
    {
        return PendingDeployment::where('branch', 'main')
            ->whereIn('status', ['pending', 'failed'])
            ->latest('pushed_at')
            ->value('id');
    }
}
