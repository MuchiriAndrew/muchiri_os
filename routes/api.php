<?php

use App\Http\Controllers\Api\AgentRunController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\WorkItemController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::get('/projects/{project:slug}/context', [ProjectController::class, 'context']);
    Route::get('/projects/{project:slug}/todoist-tasks', [ProjectController::class, 'todoistTasks']);

    Route::get('/work-items', [WorkItemController::class, 'index']);
    Route::post('/work-items', [WorkItemController::class, 'store']);
    Route::get('/work-items/{workItem}', [WorkItemController::class, 'show']);
    Route::patch('/work-items/{workItem}', [WorkItemController::class, 'update']);

    Route::get('/agent-runs', [AgentRunController::class, 'index']);
    Route::post('/agent-runs', [AgentRunController::class, 'store']);
    Route::get('/agent-runs/summary', [AgentRunController::class, 'summary']);

    Route::get('/settings/{key}', [SettingController::class, 'show']);
});
