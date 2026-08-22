<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactEmailController;
use App\Http\Controllers\ContactSequenceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailThreadController;
use App\Http\Controllers\EmailTrackingController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\InteractionController;
use App\Http\Controllers\LeadSearchPresetController;
use App\Http\Controllers\LeadSearchQueryController;
use App\Http\Controllers\OutreachSequenceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SequenceEnrollmentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Route;

Route::get('t/o/{token}', [EmailTrackingController::class, 'open'])
    ->name('email.tracking.open')
    ->where('token', '[A-Za-z0-9]+');

Route::get('/', function (): View|RedirectResponse {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('contacts', ContactController::class);
    Route::post('contacts/bulk-destroy', [ContactController::class, 'bulkDestroy'])->name('contacts.bulk-destroy');
    Route::post('contacts/bulk-status', [ContactController::class, 'bulkStatus'])->name('contacts.bulk-status');
    Route::post('contacts/email/bulk', [ContactEmailController::class, 'createBulk'])->name('contacts.email.bulk.create');
    Route::post('contacts/email/bulk/send', [ContactEmailController::class, 'storeBulk'])->name('contacts.email.bulk.store');
    Route::get('contacts/{contact}/email', [ContactEmailController::class, 'create'])->name('contacts.email.create');
    Route::post('contacts/{contact}/email', [ContactEmailController::class, 'store'])->name('contacts.email.store');
    Route::post('contacts/{contact}/sequence/cancel', [ContactSequenceController::class, 'cancel'])->name('contacts.sequence.cancel');
    Route::get('sequences', [OutreachSequenceController::class, 'index'])->name('sequences.index');
    Route::post('sequences/bulk/cancel', [SequenceEnrollmentController::class, 'bulkCancel'])->name('sequences.bulk-cancel');
    Route::post('sequences/bulk/send-now', [SequenceEnrollmentController::class, 'bulkSendNow'])->name('sequences.bulk-send-now');
    Route::post('sequences/bulk/retry', [SequenceEnrollmentController::class, 'bulkRetry'])->name('sequences.bulk-retry');
    Route::post('sequences/bulk/mark-step', [SequenceEnrollmentController::class, 'bulkMarkStep'])->name('sequences.bulk-mark-step');
    Route::post('sequences/{enrollment}/mark-step', [SequenceEnrollmentController::class, 'markStep'])->name('sequences.mark-step')->whereNumber('enrollment');
    Route::post('sequences/{enrollment}/send-now', [SequenceEnrollmentController::class, 'sendNow'])->name('sequences.send-now')->whereNumber('enrollment');
    Route::post('sequences/{enrollment}/retry', [SequenceEnrollmentController::class, 'retry'])->name('sequences.retry')->whereNumber('enrollment');
    Route::resource('email-templates', EmailTemplateController::class)->except(['show']);
    Route::resource('lead-search-presets', LeadSearchPresetController::class)->except(['show']);
    Route::get('inbox', [EmailThreadController::class, 'index'])->name('email-threads.index');
    Route::get('inbox/trash', [EmailThreadController::class, 'trash'])->name('email-threads.trash');
    Route::post('inbox/bulk-destroy', [EmailThreadController::class, 'bulkDestroy'])->name('email-threads.bulk-destroy');
    Route::post('inbox/trash/bulk-force-destroy', [EmailThreadController::class, 'bulkForceDestroy'])->name('email-threads.bulk-force-destroy');
    Route::get('inbox/{emailThread}', [EmailThreadController::class, 'show'])->name('email-threads.show');
    Route::post('inbox/{emailThread}/reply', [EmailThreadController::class, 'reply'])->name('email-threads.reply');
    Route::delete('inbox/{emailThread}', [EmailThreadController::class, 'destroy'])->name('email-threads.destroy');
    Route::post('inbox/{emailThread}/restore', [EmailThreadController::class, 'restore'])->name('email-threads.restore');
    Route::delete('inbox/{emailThread}/force', [EmailThreadController::class, 'forceDestroy'])->name('email-threads.force-destroy');
    Route::resource('campaigns', CampaignController::class);
    Route::resource('interactions', InteractionController::class)->except(['show']);
    Route::resource('follow-ups', FollowUpController::class)->except(['show']);
    Route::patch('follow-ups/{followUp}/toggle', [FollowUpController::class, 'toggle'])->name('follow-ups.toggle');
    Route::resource('lead-searches', LeadSearchQueryController::class)->parameters([
        'lead-searches' => 'leadSearch',
    ])->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/contacts.csv', [ReportController::class, 'exportContacts'])->name('reports.contacts.export');
    Route::get('reports/interactions.csv', [ReportController::class, 'exportInteractions'])->name('reports.interactions.export');

    Route::resource('users', UserController::class)->except(['show']);
    Route::resource('roles', RoleController::class)->except(['show']);
});
