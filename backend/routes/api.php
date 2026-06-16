<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\RepositoryController;
use App\Http\Controllers\Api\V1\AnalysisController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    /*
    |------------------------------------------------------------------
    | Public Auth Routes
    |------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        // Token refresh — requires valid refresh token
        Route::middleware('auth:sanctum')->post('refresh', [AuthController::class, 'refresh']);

        // OAuth routes
        Route::get('{provider}/redirect', [AuthController::class, 'redirectToProvider'])
            ->where('provider', 'github|google');
        Route::get('{provider}/callback', [AuthController::class, 'handleProviderCallback'])
            ->where('provider', 'github|google');
    });

    /*
    |------------------------------------------------------------------
    | Webhook Routes (no auth, signature verification middleware)
    |------------------------------------------------------------------
    */
    Route::prefix('webhooks')->middleware('webhook.verify')->group(function () {
        Route::post('{provider}/{repositoryId}', [WebhookController::class, 'handle'])
            ->where('provider', 'bitbucket|github|gitlab')
            ->name('api.webhooks.handle');
    });

    /*
    |------------------------------------------------------------------
    | Authenticated Routes
    |------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum'])->group(function () {

        // Current user
        Route::get('auth/me',     [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);

        /*
        |--------------------------------------------------------------
        | Organizations
        |--------------------------------------------------------------
        */
        Route::prefix('organizations')->group(function () {
            Route::get('/',    [OrganizationController::class, 'index']);
            Route::post('/',   [OrganizationController::class, 'store']);
            Route::get('/{organization}',    [OrganizationController::class, 'show']);
            Route::patch('/{organization}',  [OrganizationController::class, 'update']);

            // Members
            Route::get('/{organization}/members',          [OrganizationController::class, 'members']);
            Route::post('/{organization}/members',         [OrganizationController::class, 'inviteMember']);
            Route::delete('/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);
        });

        /*
        |--------------------------------------------------------------
        | Organization-scoped Routes
        |--------------------------------------------------------------
        */
        Route::prefix('organizations/{organization}')->middleware('org.member')->scopeBindings()->group(function () {

            // Repositories
            Route::prefix('repositories')->group(function () {
                Route::get('/',        [RepositoryController::class, 'index']);
                Route::post('/',       [RepositoryController::class, 'store']);
                Route::get('/{repo}',  [RepositoryController::class, 'show']);
                Route::delete('/{repo}', [RepositoryController::class, 'destroy']);
                Route::post('/{repo}/sync', [RepositoryController::class, 'sync']);
                Route::get('/{repo}/branches', [RepositoryController::class, 'branches']);
                Route::get('/{repo}/pull-requests', [RepositoryController::class, 'pullRequests']);
            });

            // Projects
            Route::prefix('projects')->group(function () {
                Route::get('/',         [ProjectController::class, 'index']);
                Route::post('/',        [ProjectController::class, 'store']);
                Route::get('/{project}',  [ProjectController::class, 'show']);
                Route::patch('/{project}', [ProjectController::class, 'update']);
                Route::delete('/{project}', [ProjectController::class, 'destroy']);
            });

            // Analyses
            Route::prefix('analyses')->group(function () {
                Route::get('/',          [AnalysisController::class, 'index']);
                Route::post('/',         [AnalysisController::class, 'store']);
                Route::get('/{analysis}', [AnalysisController::class, 'show']);
                Route::post('/{analysis}/cancel', [AnalysisController::class, 'cancel']);

                // Nested resources
                Route::get('/{analysis}/issues',           [AnalysisController::class, 'issues']);
                Route::get('/{analysis}/recommendations',  [AnalysisController::class, 'recommendations']);
                Route::get('/{analysis}/tests',            [AnalysisController::class, 'generatedTests']);
                Route::get('/{analysis}/report',           [AnalysisController::class, 'report']);
                Route::post('/{analysis}/report/generate', [AnalysisController::class, 'generateReport']);

                // Issue management
                Route::patch('/{analysis}/issues/{issue}/dismiss', [AnalysisController::class, 'dismissIssue']);
            });
        });
    });
});
