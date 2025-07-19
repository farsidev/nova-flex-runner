<?php

namespace Farsi\NovaFlexRunner\Http\Controllers;

use Farsi\NovaFlexRunner\Models\CommandLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class LogViewerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewNovaFlexRunnerLogs');

        $query = CommandLog::with('user')
            ->orderBy('created_at', 'desc');

        if ($request->filled('user_id')) {
            $query->byUser($request->user_id);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }

        if ($request->filled('command_name')) {
            $query->where('command_name', 'like', '%' . $request->command_name . '%');
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $perPage = min($request->get('per_page', 25), 100);
        $logs = $query->paginate($perPage);

        return response()->json([
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'from' => $logs->firstItem(),
                'to' => $logs->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, CommandLog $log): JsonResponse
    {
        $this->authorize('viewNovaFlexRunnerLogs');

        $log->load('user');

        return response()->json([
            'log' => $log,
        ]);
    }

    public function download(Request $request, CommandLog $log): Response
    {
        $this->authorize('viewNovaFlexRunnerLogs');

        $filename = sprintf(
            'command-log-%s-%s.log',
            $log->command_slug,
            $log->created_at->format('Y-m-d-H-i-s')
        );

        $content = $this->formatLogForDownload($log);

        return response($content, 200, [
            'Content-Type' => 'text/plain',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function stats(Request $request): JsonResponse
    {
        $this->authorize('viewNovaFlexRunnerLogs');

        $days = $request->get('days', 30);
        $fromDate = now()->subDays($days);

        $stats = [
            'total_executions' => CommandLog::where('created_at', '>=', $fromDate)->count(),
            'successful_executions' => CommandLog::where('created_at', '>=', $fromDate)
                ->where('status', 'success')->count(),
            'failed_executions' => CommandLog::where('created_at', '>=', $fromDate)
                ->where('status', 'failed')->count(),
            'running_executions' => CommandLog::where('status', 'running')->count(),
            'average_duration' => CommandLog::where('created_at', '>=', $fromDate)
                ->whereNotNull('duration')
                ->avg('duration'),
        ];

        $stats['success_rate'] = $stats['total_executions'] > 0 
            ? round(($stats['successful_executions'] / $stats['total_executions']) * 100, 2)
            : 0;

        $commandStats = CommandLog::where('created_at', '>=', $fromDate)
            ->selectRaw('command_name, command_type, category, count(*) as executions, avg(duration) as avg_duration')
            ->groupBy('command_name', 'command_type', 'category')
            ->orderBy('executions', 'desc')
            ->limit(10)
            ->get();

        $dailyStats = CommandLog::where('created_at', '>=', $fromDate)
            ->selectRaw('DATE(created_at) as date, count(*) as executions')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'overview' => $stats,
            'top_commands' => $commandStats,
            'daily_executions' => $dailyStats,
        ]);
    }

    public function filters(): JsonResponse
    {
        $this->authorize('viewNovaFlexRunnerLogs');

        $users = CommandLog::with('user')
            ->select('user_id')
            ->distinct()
            ->get()
            ->pluck('user')
            ->filter()
            ->map(function ($user) {
                return [
                    'value' => $user->id,
                    'label' => $user->name ?? $user->email,
                ];
            })
            ->values();

        $types = CommandLog::select('command_type')
            ->distinct()
            ->pluck('command_type')
            ->map(function ($type) {
                return [
                    'value' => $type,
                    'label' => ucfirst($type),
                ];
            })
            ->values();

        $categories = CommandLog::select('category')
            ->distinct()
            ->whereNotNull('category')
            ->pluck('category')
            ->map(function ($category) {
                return [
                    'value' => $category,
                    'label' => ucfirst($category),
                ];
            })
            ->values();

        $statuses = collect(['pending', 'running', 'success', 'failed'])
            ->map(function ($status) {
                return [
                    'value' => $status,
                    'label' => ucfirst($status),
                ];
            });

        return response()->json([
            'users' => $users,
            'types' => $types,
            'categories' => $categories,
            'statuses' => $statuses,
        ]);
    }

    protected function formatLogForDownload(CommandLog $log): string
    {
        $content = [];
        $content[] = "Nova Flex Runner - Command Execution Log";
        $content[] = "=====================================";
        $content[] = "";
        $content[] = "Command: {$log->command_name}";
        $content[] = "Slug: {$log->command_slug}";
        $content[] = "Type: {$log->command_type}";
        $content[] = "Category: {$log->category}";
        $content[] = "Status: {$log->status}";
        $content[] = "User: {$log->user->name ?? $log->user->email ?? 'Unknown'}";
        $content[] = "Started: {$log->started_at}";
        $content[] = "Completed: {$log->completed_at}";
        $content[] = "Duration: {$log->formatted_duration}";
        $content[] = "";

        if ($log->inputs) {
            $content[] = "Inputs:";
            $content[] = "-------";
            foreach ($log->inputs as $key => $value) {
                $content[] = "{$key}: " . (is_array($value) ? json_encode($value) : $value);
            }
            $content[] = "";
        }

        if ($log->error_message) {
            $content[] = "Error:";
            $content[] = "------";
            $content[] = $log->error_message;
            $content[] = "";
        }

        if ($log->output) {
            $content[] = "Output:";
            $content[] = "-------";
            $content[] = $log->output;
        }

        return implode("\n", $content);
    }

    protected function authorize(string $ability): void
    {
        if (Gate::has($ability)) {
            Gate::authorize($ability, auth()->user());
        }
    }
}