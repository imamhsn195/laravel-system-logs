<?php

namespace ImamHasan\SystemLogs\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use ImamHasan\SystemLogs\Services\SystemLogService;

class SystemLogController extends Controller
{
    protected SystemLogService $logService;
    
    public function __construct(SystemLogService $logService)
    {
        $this->logService = $logService;
    }
    
    /**
     * Display log entries.
     */
    public function index(Request $request)
    {
        // Check permission
        $permission = config('system-logs.permissions.view');
        if ($permission && !$request->user()?->can($permission)) {
            abort(403, 'Unauthorized action.');
        }
        
        $filters = $this->getFilters($request);
        $perPage = $this->getPerPage($request);
        
        $result = $this->logService->getEntries(
            array_merge($filters, ['max_files' => $filters['max_files'] ?? config('system-logs.filters.default_max_files', 3)]),
            $perPage
        );
        
        // If AJAX request, return HTML partial or JSON
        if ($request->ajax() || $request->wantsJson()) {
            // Check if requesting HTML (for AJAX filtering)
            if ($request->header('Accept') === 'text/html' || !$request->wantsJson()) {
                // Check delete permission
                $permission = config('system-logs.permissions.delete');
                $canDelete = !$permission || ($request->user() && $request->user()?->can($permission));
                
                // Return full page HTML for AJAX filtering (so we can extract buttons row and filter chips)
                return view('system-logs::index', [
                    'entries' => $result['entries'],
                    'files' => $result['files'],
                    'filters' => $filters,
                    'meta' => $result['meta'],
                    'channels' => $this->logService->availableChannels(),
                    'levels' => $this->logService->availableLevels(),
                ])->render();
            }
            
            // Return JSON
            return response()->json([
                'success' => true,
                'data' => $result['entries'],
                'files' => $result['files'],
                'meta' => $result['meta'],
            ]);
        }
        
        // Return view
        return view('system-logs::index', [
            'entries' => $result['entries'],
            'files' => $result['files'],
            'filters' => $filters,
            'meta' => $result['meta'],
            'channels' => $this->logService->availableChannels(),
            'levels' => $this->logService->availableLevels(),
        ]);
    }
    
    /**
     * Delete a single log entry.
     */
    public function destroy(Request $request): RedirectResponse|JsonResponse
    {
        // Check permission
        $permission = config('system-logs.permissions.delete');
        if ($permission && !$request->user()?->can($permission)) {
            return $this->errorResponse('Unauthorized action.', 403);
        }
        
        $request->validate([
            'file' => 'required|string',
            'timestamp' => 'required|string',
        ]);
        
        $deleted = $this->logService->deleteEntry(
            $request->input('file'),
            $request->input('timestamp')
        );
        
        if ($deleted) {
            return $this->successResponse('Log entry deleted successfully.');
        }
        
        return $this->errorResponse('Failed to delete log entry.', 500);
    }
    
    /**
     * Bulk delete selected entries.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        // Check permission
        $permission = config('system-logs.permissions.delete');
        if ($permission && !$request->user()?->can($permission)) {
            return $this->errorResponse('Unauthorized action.', 403);
        }
        
        $request->validate([
            'entries' => 'required|array',
            'entries.*.file' => 'required|string',
            'entries.*.timestamp' => 'required|string',
        ]);
        
        $deletedCount = 0;
        $failedCount = 0;
        
        foreach ($request->input('entries') as $entry) {
            $deleted = $this->logService->deleteEntry(
                $entry['file'],
                $entry['timestamp']
            );
            
            if ($deleted) {
                $deletedCount++;
            } else {
                $failedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} log entries.",
                'deleted' => $deletedCount,
                'failed' => $failedCount,
            ]);
        }
        
        return $this->errorResponse('Failed to delete log entries.', 500);
    }
    
    /**
     * Bulk delete by filters.
     */
    public function bulkDeleteByFilters(Request $request): RedirectResponse|JsonResponse
    {
        // Check permission
        $permission = config('system-logs.permissions.delete');
        if ($permission && !$request->user()?->can($permission)) {
            return $this->errorResponse('Unauthorized action.', 403);
        }
        
        $request->validate([
            'channel' => 'nullable|string',
            'file' => 'nullable|string',
            'level' => 'nullable|string|in:debug,info,notice,warning,error,critical,alert,emergency',
            'environment' => 'nullable|string',
            'date' => 'nullable|date',
            'search' => 'nullable|string|max:255',
            'confirm' => 'required|accepted',
        ]);
        
        $filters = $request->only(['channel', 'file', 'level', 'environment', 'date', 'search']);
        $filters = array_filter($filters, fn($value) => !empty($value));
        
        // Require at least one filter to prevent accidental deletion of all logs
        if (empty($filters)) {
            return $this->errorResponse(
                'At least one filter must be specified for bulk deletion.',
                400
            );
        }
        
        $result = $this->logService->bulkDeleteByFilters($filters);
        
        return $this->successResponse(
            "Successfully deleted {$result['deleted']} log entries.",
            ['deleted' => $result['deleted'], 'failed' => $result['failed']]
        );
    }
    
    /**
     * Get filters from request.
     */
    protected function getFilters(Request $request): array
    {
        return [
            'channel' => $request->input('channel'),
            'file' => $request->input('file'),
            'level' => $request->input('level'),
            'environment' => $request->input('environment'),
            'date' => $request->input('date'),
            'search' => $request->input('search'),
            'max_files' => $request->input('max_files', config('system-logs.filters.default_max_files', 3)),
        ];
    }
    
    /**
     * Get per page value.
     */
    protected function getPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', config('system-logs.filters.default_per_page', 50));
        $minPerPage = config('system-logs.filters.min_per_page', 10);
        $maxPerPage = config('system-logs.filters.max_per_page', 300);
        
        return max($minPerPage, min($maxPerPage, $perPage));
    }
    
    /**
     * Return success response.
     */
    protected function successResponse(string $message, array $data = []): RedirectResponse|JsonResponse
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $data,
            ]);
        }
        
        return redirect()
            ->route(config('system-logs.route.name_prefix') . 'index', request()->only(['channel', 'file', 'level', 'environment', 'date', 'search', 'per_page', 'max_files']))
            ->with('success', $message);
    }
    
    /**
     * Return error response.
     */
    protected function errorResponse(string $message, int $status = 400): RedirectResponse|JsonResponse
    {
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }
        
        return redirect()
            ->route(config('system-logs.route.name_prefix') . 'index')
            ->with('error', $message);
    }
}
