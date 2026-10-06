<?php

use App\Http\Controllers\Api\V1\Admin\AuditLogAdminController;
use App\Http\Controllers\Api\V1\Admin\QrAdminController;
use App\Http\Controllers\Api\V1\Admin\ReportAdminController;
use App\Http\Controllers\Api\V1\Admin\VerificationController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConversationController;
use App\Http\Controllers\Api\V1\DriverController;
use App\Http\Controllers\Api\V1\JourneyController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\QrPublicController;
use App\Http\Controllers\Api\V1\RatingController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SafetyController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\VehicleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ------------------------------------------------------------------------
    // Public QR Resolution (Zero PII, Throttled)
    // ------------------------------------------------------------------------
    Route::get('/qr/{token}', [QrPublicController::class, 'resolve'])
        ->middleware('throttle:30,1');

    // ------------------------------------------------------------------------
    // Public Authentication Endpoints (Phone-first OTP)
    // ------------------------------------------------------------------------
    Route::prefix('auth')->group(function () {
        Route::post('/request-otp', [AuthController::class, 'requestOtp'])
            ->middleware('throttle:6,1');

        Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
            ->middleware('throttle:10,1');

        Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    // ------------------------------------------------------------------------
    // Authenticated Core Endpoints (Protected by Sanctum & Active Account Check)
    // ------------------------------------------------------------------------
    Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
        // Current User Profile
        Route::get('/me', [AuthController::class, 'me']);

        // Profile Management
        Route::get('/profile', [ProfileController::class, 'getProfile']);
        Route::match(['put', 'patch'], '/profile', [ProfileController::class, 'update']);
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
        Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto']);

        // Device Telemetry & Push Tokens (FCM)
        Route::post('/devices', [ProfileController::class, 'updateDevice']);
        Route::delete('/devices/{device_id}', [ProfileController::class, 'deleteDevice']);

        // Privacy Consents
        Route::get('/consents', [ProfileController::class, 'getConsents']);
        Route::post('/consents', [ProfileController::class, 'updateConsent']);

        // --------------------------------------------------------------------
        // Driver Track: Application, KYC Documents & Vehicles
        // --------------------------------------------------------------------
        Route::prefix('driver')->group(function () {
            Route::get('/profile', [DriverController::class, 'getProfile']);
            Route::post('/apply', [DriverController::class, 'apply']);
            Route::get('/documents', [DriverController::class, 'getDocuments']);
            Route::post('/documents', [DriverController::class, 'uploadDocument']);

            // Vehicle Management & Active Assignment
            Route::get('/vehicles', [VehicleController::class, 'index']);
            Route::post('/vehicles', [VehicleController::class, 'store']);
            Route::get('/vehicles/{uuid}', [VehicleController::class, 'show']);
            Route::post('/vehicles/{uuid}/documents', [VehicleController::class, 'uploadDocument']);
            Route::post('/vehicles/{uuid}/assign-active', [VehicleController::class, 'assignActive']);
            Route::post('/vehicles/{uuid}/end-assignment', [VehicleController::class, 'endAssignment']);
        });

        // --------------------------------------------------------------------
        // Journey Track: Ephemeral Connections & Lifecycle
        // --------------------------------------------------------------------
        Route::prefix('journeys')->group(function () {
            Route::post('/', [JourneyController::class, 'start']);
            Route::get('/current', [JourneyController::class, 'current']);
            Route::post('/connect', [JourneyController::class, 'connect']);
            Route::get('/{uuid}', [JourneyController::class, 'show']);
            Route::post('/{uuid}/complete', [JourneyController::class, 'complete']);
            Route::post('/{uuid}/cancel', [JourneyController::class, 'cancel']);
            Route::post('/{uuid}/disconnect', [JourneyController::class, 'disconnect']);
            Route::post('/{uuid}/share-details', [JourneyController::class, 'shareDetails']);
            Route::get('/{uuid}/passengers', [JourneyController::class, 'passengers']);
        });

        // --------------------------------------------------------------------
        // Lost Item Tickets & Recovery Workflow
        // --------------------------------------------------------------------
        Route::prefix('tickets')->group(function () {
            Route::get('/', [TicketController::class, 'index']);
            Route::post('/', [TicketController::class, 'store']);
            Route::get('/{ticketNumber}', [TicketController::class, 'show']);
            Route::post('/{ticketNumber}/searching', [TicketController::class, 'searching']);
            Route::post('/{ticketNumber}/found', [TicketController::class, 'found']);
            Route::post('/{ticketNumber}/not-found', [TicketController::class, 'notFound']);
            Route::post('/{ticketNumber}/handover/driver', [TicketController::class, 'driverHandover']);
            Route::post('/{ticketNumber}/handover/passenger', [TicketController::class, 'passengerReceipt']);
            Route::post('/{ticketNumber}/cancel', [TicketController::class, 'cancel']);
            Route::post('/{ticketNumber}/dispute', [TicketController::class, 'dispute']);
            Route::post('/{ticketNumber}/escalate', [TicketController::class, 'escalate']);
            Route::post('/{ticketNumber}/resolve', [TicketController::class, 'resolve']);
        });

        // --------------------------------------------------------------------
        // Realtime Conversations & Messaging
        // --------------------------------------------------------------------
        Route::prefix('conversations')->group(function () {
            Route::get('/', [ConversationController::class, 'index']);
            Route::get('/{uuid}', [ConversationController::class, 'show']);
            Route::get('/{uuid}/messages', [ConversationController::class, 'messages']);
            Route::post('/{uuid}/messages', [ConversationController::class, 'storeMessage']);
            Route::post('/{uuid}/read', [ConversationController::class, 'markRead']);
        });

        // --------------------------------------------------------------------
        // In-App Notifications Center
        // --------------------------------------------------------------------
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/read-all', [NotificationController::class, 'markAllRead']);
            Route::post('/{id}/read', [NotificationController::class, 'markRead']);
        });

        // --------------------------------------------------------------------
        // Ratings & Reviews
        // --------------------------------------------------------------------
        Route::prefix('ratings')->group(function () {
            Route::get('/', [RatingController::class, 'index']);
            Route::post('/', [RatingController::class, 'store']);
        });

        // --------------------------------------------------------------------
        // Incident & Fraud Reports
        // --------------------------------------------------------------------
        Route::prefix('reports')->group(function () {
            Route::get('/', [ReportController::class, 'index']);
            Route::post('/', [ReportController::class, 'store']);
            Route::get('/{id}', [ReportController::class, 'show']);
        });

        // --------------------------------------------------------------------
        // User Safety & Blocking
        // --------------------------------------------------------------------
        Route::prefix('users')->group(function () {
            Route::get('/blocked', [SafetyController::class, 'blocked']);
            Route::post('/{id}/block', [SafetyController::class, 'block']);
            Route::delete('/{id}/block', [SafetyController::class, 'unblock']);
        });

        // --------------------------------------------------------------------
        // Admin Verification Track (Staff operations)
        // --------------------------------------------------------------------
        Route::prefix('admin')->middleware('role:admin')->group(function () {
            Route::prefix('verification')->group(function () {
                // Driver verification
                Route::get('/drivers', [VerificationController::class, 'pendingDrivers'])
                    ->middleware('permission:drivers.view');
                Route::post('/drivers/{driver_code}/verify', [VerificationController::class, 'verifyDriver'])
                    ->middleware('permission:drivers.verify');
                Route::post('/drivers/{driver_code}/reject', [VerificationController::class, 'rejectDriver'])
                    ->middleware('permission:drivers.reject');

                // Driver document verification
                Route::post('/driver-documents/{id}/verify', [VerificationController::class, 'verifyDriverDocument'])
                    ->middleware('permission:documents.verify');
                Route::post('/driver-documents/{id}/reject', [VerificationController::class, 'rejectDriverDocument'])
                    ->middleware('permission:documents.reject');

                // Vehicle verification
                Route::get('/vehicles', [VerificationController::class, 'pendingVehicles'])
                    ->middleware('permission:vehicles.view');
                Route::post('/vehicles/{vehicle_code}/verify', [VerificationController::class, 'verifyVehicle'])
                    ->middleware('permission:vehicles.verify');
                Route::post('/vehicles/{vehicle_code}/reject', [VerificationController::class, 'rejectVehicle'])
                    ->middleware('permission:vehicles.reject');

                // Vehicle document verification
                Route::post('/vehicle-documents/{id}/verify', [VerificationController::class, 'verifyVehicleDocument'])
                    ->middleware('permission:documents.verify');
                Route::post('/vehicle-documents/{id}/reject', [VerificationController::class, 'rejectVehicleDocument'])
                    ->middleware('permission:documents.reject');

                // Private KYC document inspection stream
                Route::get('/documents/{media_uuid}/view', [VerificationController::class, 'viewDocument'])
                    ->middleware('permission:documents.view');
            });

            // Vehicle status modification (suspend/block/activate)
            Route::patch('/vehicles/{vehicle_code}/status', [VerificationController::class, 'updateVehicleStatus'])
                ->middleware('permission:vehicles.suspend');

            // ----------------------------------------------------------------
            // Admin QR Code Operations
            // ----------------------------------------------------------------
            Route::prefix('qr')->group(function () {
                Route::post('/vehicles/{vehicle_code}/generate', [QrAdminController::class, 'generate'])
                    ->middleware('permission:qr.generate');
                Route::post('/{id}/activate', [QrAdminController::class, 'activate'])
                    ->middleware('permission:qr.activate');
                Route::post('/{id}/disable', [QrAdminController::class, 'disable'])
                    ->middleware('permission:qr.disable');
                Route::post('/{id}/revoke', [QrAdminController::class, 'revoke'])
                    ->middleware('permission:qr.revoke');
                Route::get('/{id}/download', [QrAdminController::class, 'downloadSticker'])
                    ->middleware('permission:qr.download');
                Route::get('/{id}/logs', [QrAdminController::class, 'scanLogs'])
                    ->middleware('permission:qr.view');
            });

            // ----------------------------------------------------------------
            // Admin Incident Reports
            // ----------------------------------------------------------------
            Route::prefix('reports')->group(function () {
                Route::get('/', [ReportAdminController::class, 'index'])
                    ->middleware('permission:reports.view');
                Route::get('/{id}', [ReportAdminController::class, 'show'])
                    ->middleware('permission:reports.view');
                Route::patch('/{id}', [ReportAdminController::class, 'update'])
                    ->middleware('permission:reports.resolve');
            });

            // ----------------------------------------------------------------
            // Admin Forensic Audit Logs
            // ----------------------------------------------------------------
            Route::get('/audit-logs', [AuditLogAdminController::class, 'index'])
                ->middleware('permission:audit_logs.view');
        });
    });
});
