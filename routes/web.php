<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerProcessStepController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleChatSettingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProcessTimelineAdminController;
use App\Http\Controllers\PublicCustomerEntryController;
use App\Http\Controllers\QualificationAdminController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\UserAdminController;
use App\Http\Controllers\UserMentionController;
use App\Http\Controllers\WebCustomerFeeController;
use App\Http\Controllers\WebCustomerEntryController;
use App\Http\Controllers\WebCustomerController;
use App\Http\Controllers\WebDocumentController;
use App\Http\Controllers\WebFollowUpController;
use App\Http\Controllers\WebRemarkController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'home']);
Route::get('/customer-entry', [PublicCustomerEntryController::class, 'create'])->name('public.customer-entry.create');
Route::post('/customer-entry', [PublicCustomerEntryController::class, 'store'])->name('public.customer-entry.store');
Route::get('/customer-entry/thanks', [PublicCustomerEntryController::class, 'thanks'])->name('public.customer-entry.thanks');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/search/customers', [SearchController::class, 'customers'])->name('search.customers');
    Route::get('/users/mention-search', [UserMentionController::class, 'search'])->name('users.mention-search');
    Route::get('/users', [UserAdminController::class, 'index'])->name('users.index')->middleware('role:admin');
    Route::post('/users', [UserAdminController::class, 'store'])->name('users.store')->middleware('role:admin');
    Route::get('/users/{user}/edit', [UserAdminController::class, 'edit'])->name('users.edit')->middleware('role:admin');
    Route::put('/users/{user}', [UserAdminController::class, 'update'])->name('users.update')->middleware('role:admin');
    Route::put('/users/{user}/password', [UserAdminController::class, 'updatePassword'])->name('users.password')->middleware('role:admin');
    Route::get('/process-timelines', [ProcessTimelineAdminController::class, 'index'])->name('process-timelines.index')->middleware('role:admin');
    Route::post('/process-timelines', [ProcessTimelineAdminController::class, 'store'])->name('process-timelines.store')->middleware('role:admin');
    Route::post('/process-timelines/save', [ProcessTimelineAdminController::class, 'saveAll'])->name('process-timelines.save')->middleware('role:admin');
    Route::put('/process-timelines/{processTimeline}', [ProcessTimelineAdminController::class, 'update'])->name('process-timelines.update')->middleware('role:admin');
    Route::get('/qualifications', [QualificationAdminController::class, 'index'])->name('qualifications.index')->middleware('role:admin');
    Route::post('/qualifications', [QualificationAdminController::class, 'store'])->name('qualifications.store')->middleware('role:admin');
    Route::delete('/qualifications/{qualification}', [QualificationAdminController::class, 'destroy'])->name('qualifications.destroy')->middleware('role:admin');
    Route::get('/google-chat-settings', [GoogleChatSettingsController::class, 'edit'])->name('google-chat-settings.edit')->middleware('role:admin');
    Route::put('/google-chat-settings', [GoogleChatSettingsController::class, 'update'])->name('google-chat-settings.update')->middleware('role:admin');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::match(['post', 'patch'], '/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    Route::get('/visiting-clients', [WebCustomerController::class, 'visitingClients'])
        ->name('visiting-clients.index')
        ->middleware('role:receptionist,telecaller');
    Route::get('/tab-entries', [WebCustomerEntryController::class, 'index'])
        ->name('tab-entries.index')
        ->middleware('role:receptionist');
    Route::get('/new-cases', [WebCustomerController::class, 'newCases'])->name('new-cases.index');
    Route::get('/customers', [WebCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [WebCustomerController::class, 'create'])
        ->name('customers.create')
        ->middleware('role:admin,receptionist,director,agent');
    Route::post('/customers', [WebCustomerController::class, 'store'])
        ->name('customers.store')
        ->middleware('role:admin,receptionist,director,agent');
    Route::get('/customers/{customer}/edit', [WebCustomerController::class, 'edit'])
        ->name('customers.edit')
        ->middleware('role:admin,counselor');
    Route::put('/customers/{customer}', [WebCustomerController::class, 'update'])
        ->name('customers.update')
        ->middleware('role:admin,counselor');
    Route::post('/customers/{customer}/process-steps/{stepKey}', [CustomerProcessStepController::class, 'complete'])
        ->name('customers.process-steps.complete')
        ->middleware('role:admin,counselor,director,agent');
    Route::get('/customers/leads/create', [WebCustomerController::class, 'createTelecaller'])
        ->name('customers.create-telecaller')
        ->middleware('role:telecaller');
    Route::post('/customers/leads', [WebCustomerController::class, 'storeTelecaller'])
        ->name('customers.store-telecaller')
        ->middleware('role:telecaller');
    Route::get('/customers/{customer}', [WebCustomerController::class, 'show'])->name('customers.show');

    Route::post('/customers/{customer}/special-remark', [WebCustomerController::class, 'storeSpecialRemark'])
        ->name('customers.special-remark.store')
        ->middleware('role:counselor');
    Route::post('/customers/{customer}/remarks', [WebRemarkController::class, 'store'])->name('customers.remarks.store');
    Route::post('/customers/{customer}/fees', [WebCustomerFeeController::class, 'store'])->name('customers.fees.store');
    Route::get('/customers/{customer}/fees/receipt', [WebCustomerFeeController::class, 'receipt'])->name('customers.fees.receipt');
    Route::post('/customers/{customer}/intake', [WebCustomerController::class, 'updateIntake'])
        ->name('customers.intake.update')
        ->middleware('role:admin,counselor,director,agent');
    Route::post('/customers/{customer}/agent-commercial', [WebCustomerController::class, 'updateAgentCommercial'])
        ->name('customers.agent-commercial.update')
        ->middleware('role:admin');
    Route::post('/customers/{customer}/documents', [WebDocumentController::class, 'store'])->name('customers.documents.store');
    Route::delete('/documents/{document}', [WebDocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/follow-ups', [WebFollowUpController::class, 'index'])->name('follow-ups.index');
});
