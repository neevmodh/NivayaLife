<?php

use App\Http\Controllers\Admin\AdminAiUsageController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminRecordController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmergencyCardController;
use App\Http\Controllers\FamilyAddController;
use App\Http\Controllers\FamilyController;
use App\Http\Controllers\FamilyDependentController;
use App\Http\Controllers\FamilyInviteController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\MedicationQuickActionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicShareController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\ReportAiController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportUploadController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TimelineController;
use App\Http\Controllers\VaccinationController;
use App\Models\PageView;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    PageView::record('/');

    return view('welcome');
});

Route::get('/terms', fn () => view('legal.terms'))->name('terms');
Route::get('/privacy', fn () => view('legal.privacy'))->name('privacy');

// Reached from the service worker's notification action buttons, not a
// logged-in page — signature is the authorization, scoped to one dose log.
Route::get('/medications/dose/{log}/quick-action/{action}', [MedicationQuickActionController::class, 'handle'])
    ->name('medications.quick-action')
    ->middleware('signed');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/switch/{familyMember}', [DashboardController::class, 'switch'])->name('dashboard.switch');
    Route::post('/dashboard/dismiss-onboarding', [DashboardController::class, 'dismissOnboarding'])->name('dashboard.dismiss-onboarding');

    Route::get('/id-card', [IdCardController::class, 'show'])->name('id-card.show');
    Route::post('/id-card/reissue', [IdCardController::class, 'reissue'])->name('id-card.reissue');
    Route::post('/id-card/{idCard}/toggle-active', [IdCardController::class, 'toggleActive'])->name('id-card.toggle-active');

    // Static sub-paths registered before the dynamic /reports/{report} routes
    // below, for the same route-model-binding-collision reason as /family/add.
    Route::get('/reports/upload', [ReportUploadController::class, 'create'])->name('reports.upload');
    Route::post('/reports/detect', [ReportUploadController::class, 'detect'])->name('reports.detect');
    Route::post('/reports', [ReportUploadController::class, 'store'])->name('reports.store');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/status', [ReportController::class, 'status'])->name('reports.status');
    Route::get('/reports/{report}/file', [ReportController::class, 'file'])->name('reports.file');
    Route::patch('/reports/{report}/ocr-text', [ReportController::class, 'updateOcrText'])->name('reports.ocr-text');
    Route::post('/reports/{report}/reupload', [ReportController::class, 'reupload'])->name('reports.reupload');
    Route::post('/reports/{report}/detailed-explanation', [ReportAiController::class, 'detailedExplanation'])->name('reports.detailed-explanation');
    Route::post('/reports/{report}/retry-summary', [ReportAiController::class, 'retrySummary'])->name('reports.retry-summary');
    Route::post('/reports/{report}/translate', [ReportAiController::class, 'translate'])->name('reports.translate');

    Route::get('/timeline', [TimelineController::class, 'index'])->name('timeline');
    Route::get('/timeline/export', [TimelineController::class, 'exportPdf'])->name('timeline.export');

    Route::get('/assistant', [AiChatController::class, 'index'])->name('assistant');
    Route::post('/assistant/send', [AiChatController::class, 'send'])->name('assistant.send');

    // Static sub-paths before the dynamic /medications/{medication} routes below.
    Route::get('/medications/create', [MedicationController::class, 'create'])->name('medications.create');
    Route::post('/medications', [MedicationController::class, 'store'])->name('medications.store');
    Route::get('/medications', [MedicationController::class, 'index'])->name('medications.index');
    Route::get('/medications/{medication}/edit', [MedicationController::class, 'edit'])->name('medications.edit');
    Route::patch('/medications/{medication}', [MedicationController::class, 'update'])->name('medications.update');
    Route::delete('/medications/{medication}', [MedicationController::class, 'destroy'])->name('medications.destroy');
    Route::post('/medications/{medication}/toggle-dose', [MedicationController::class, 'toggleDose'])->name('medications.toggle-dose');

    Route::get('/vaccinations/create', [VaccinationController::class, 'create'])->name('vaccinations.create');
    Route::post('/vaccinations', [VaccinationController::class, 'store'])->name('vaccinations.store');
    Route::get('/vaccinations', [VaccinationController::class, 'index'])->name('vaccinations.index');
    Route::get('/vaccinations/{vaccination}/edit', [VaccinationController::class, 'edit'])->name('vaccinations.edit');
    Route::patch('/vaccinations/{vaccination}', [VaccinationController::class, 'update'])->name('vaccinations.update');
    Route::delete('/vaccinations/{vaccination}', [VaccinationController::class, 'destroy'])->name('vaccinations.destroy');

    // Static sub-paths before the dynamic /shares/{share} route below.
    Route::get('/shares/create', [ShareController::class, 'create'])->name('shares.create');
    Route::post('/shares', [ShareController::class, 'store'])->name('shares.store');
    Route::get('/shares/history', [ShareController::class, 'history'])->name('shares.history');
    Route::post('/shares/{share}/revoke', [ShareController::class, 'revoke'])->name('shares.revoke');

    // Family management — static sub-paths must be registered before the
    // dynamic /family/{familyMember} show route below, or Laravel would try
    // to route-model-bind "add" as a family member id.
    Route::get('/family/add', [FamilyAddController::class, 'create'])->name('family.add');
    Route::post('/family/invite', [FamilyInviteController::class, 'store'])->name('family.invite.store');
    Route::post('/family/invite/{invitation}/resend', [FamilyInviteController::class, 'resend'])->name('family.invite.resend');
    Route::delete('/family/invite/{invitation}/cancel', [FamilyInviteController::class, 'cancel'])->name('family.invite.cancel');
    Route::post('/family/dependent', [FamilyDependentController::class, 'store'])->name('family.dependent.store');
    Route::post('/family/sharing/{sharingPermission}/revoke', [FamilyController::class, 'revokeSharing'])->name('family.sharing.revoke');

    Route::get('/family', [FamilyController::class, 'index'])->name('family.index');
    Route::get('/family/{familyMember}/edit', [ProfileController::class, 'edit'])->name('family.member.edit');
    Route::get('/family/{familyMember}', [FamilyController::class, 'show'])->name('family.show');
    Route::post('/family/{familyMember}/archive', [FamilyController::class, 'archive'])->name('family.archive');
    Route::post('/family/{familyMember}/restore', [FamilyController::class, 'restore'])->name('family.restore')->withTrashed();
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');

    // Two physical routes per action rather than one with {familyMember?}:
    // Laravel's route() URL generator doesn't reliably omit a non-trailing
    // optional segment (it was emitting "/profile//basic-info" — a literal
    // empty segment — which then 404s, silently breaking every "own profile"
    // tab save). A required-vs-absent pair of named routes sidesteps that
    // entirely; ProfileController's methods still accept ?FamilyMember $familyMember = null.
    Route::post('/profile/basic-info', [ProfileController::class, 'updateBasicInfo'])->name('profile.basic-info');
    Route::post('/profile/{familyMember}/basic-info', [ProfileController::class, 'updateBasicInfo'])->name('profile.basic-info.member');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::post('/profile/{familyMember}/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.member');
    Route::post('/profile/avatar-preset', [ProfileController::class, 'updateAvatarPreset'])->name('profile.avatar-preset');
    Route::post('/profile/{familyMember}/avatar-preset', [ProfileController::class, 'updateAvatarPreset'])->name('profile.avatar-preset.member');
    Route::post('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address');
    Route::post('/profile/{familyMember}/address', [ProfileController::class, 'updateAddress'])->name('profile.address.member');
    Route::post('/profile/health', [ProfileController::class, 'updateHealth'])->name('profile.health');
    Route::post('/profile/{familyMember}/health', [ProfileController::class, 'updateHealth'])->name('profile.health.member');
    Route::post('/profile/emergency-contact', [ProfileController::class, 'updateEmergencyContact'])->name('profile.emergency-contact');
    Route::post('/profile/{familyMember}/emergency-contact', [ProfileController::class, 'updateEmergencyContact'])->name('profile.emergency-contact.member');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::post('/push-subscriptions/test', [PushSubscriptionController::class, 'test'])->name('push-subscriptions.test');

    Route::post('/invite/{token}/permission', [InvitationController::class, 'storePermission'])->name('invite.permission');
    Route::post('/invite/{token}/dismiss', [InvitationController::class, 'dismiss'])->name('invite.dismiss');
});

// Public invitation accept flow — token-based, not auth-gated (a brand-new
// invitee has no account yet).
Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invite.show');
Route::post('/invite/{token}/register', [InvitationController::class, 'register'])->name('invite.register');

// Public Emergency Card — no auth, a first responder scanning a QR code has
// no account. Static sub-paths registered before the bare /emergency/{card}
// route for the usual route-model-binding-collision reason.
Route::get('/emergency/{cardNumber}/pdf', [EmergencyCardController::class, 'pdfFull'])->name('emergency.pdf.full');
Route::get('/emergency/{cardNumber}/pdf/wallet', [EmergencyCardController::class, 'pdfWallet'])->name('emergency.pdf.wallet');
Route::get('/emergency/{cardNumber}', [EmergencyCardController::class, 'show'])->name('emergency.show');

// Public report-share links — token-based, no account needed to view.
Route::get('/s/{token}', [PublicShareController::class, 'show'])->name('share.public.show');
Route::post('/s/{token}/pin', [PublicShareController::class, 'verifyPin'])->name('share.public.pin');
Route::get('/s/{token}/pdf', [PublicShareController::class, 'pdf'])->name('share.public.pdf');
Route::get('/s/{token}/file/{report}', [PublicShareController::class, 'file'])->name('share.public.file');

// Operational dashboard + database editor — behind the normal account login
// plus the is_admin flag (see EnsureUserIsAdmin), not a separate credential.
Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/tables', [AdminDashboardController::class, 'tables'])->name('tables');
    Route::get('/tables/{table}/create', [AdminRecordController::class, 'create'])->name('tables.create');
    Route::post('/tables/{table}', [AdminRecordController::class, 'store'])->name('tables.store');
    Route::get('/tables/{table}/{id}/edit', [AdminRecordController::class, 'edit'])->name('tables.edit');
    Route::put('/tables/{table}/{id}', [AdminRecordController::class, 'update'])->name('tables.update');
    Route::delete('/tables/{table}/{id}', [AdminRecordController::class, 'destroy'])->name('tables.destroy');
    Route::get('/tables/{table}/export', [AdminDashboardController::class, 'export'])->name('tables.export');
    Route::get('/tables/{table}', [AdminDashboardController::class, 'table'])->name('tables.show');

    Route::get('/ai-usage', [AdminAiUsageController::class, 'index'])->name('ai-usage');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');

    Route::get('/failed-jobs', [AdminUserController::class, 'failedJobs'])->name('failed-jobs');
    Route::post('/failed-jobs/{uuid}/retry', [AdminUserController::class, 'retryFailedJob'])->name('failed-jobs.retry');
    Route::delete('/failed-jobs/{uuid}', [AdminUserController::class, 'deleteFailedJob'])->name('failed-jobs.delete');

    Route::get('/settings', [SiteSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [SiteSettingController::class, 'update'])->name('settings.update');
});

require __DIR__.'/auth.php';
