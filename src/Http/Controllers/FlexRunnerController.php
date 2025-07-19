<?php

namespace Farsi\NovaFlexRunner\Http\Controllers;

use Farsi\NovaFlexRunner\Models\CommandLog;
use Farsi\NovaFlexRunner\Services\ArtisanExecutorService;
use Farsi\NovaFlexRunner\Services\CustomServiceExecutor;
use Farsi\NovaFlexRunner\Services\JobExecutorService;
use Farsi\NovaFlexRunner\Services\ShellExecutorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;

class FlexRunnerController extends Controller
{
    protected array $executors;

    public function __construct()
    {
        $this->executors = [
            'artisan' => app(ArtisanExecutorService::class),
            'job' => app(JobExecutorService::class),
            'shell' => app(ShellExecutorService::class),
            'service' => app(CustomServiceExecutor::class),
        ];
    }

    public function index(): JsonResponse
    {
        $this->authorize('viewNovaFlexRunner');

        $commands = config('nova-flex-runner.commands', []);
        
        return response()->json([
            'commands' => $commands,
            'settings' => [
                'require_confirmation' => config('nova-flex-runner.security.require_confirmation', true),
                'shell_enabled' => config('nova-flex-runner.shell.enabled', false),
            ],
        ]);
    }

    public function execute(Request $request): JsonResponse
    {
        $this->authorize('executeNovaFlexRunner');

        $request->validate([
            'command_slug' => 'required|string',
            'category' => 'required|string',
            'inputs' => 'array',
        ]);

        $command = $this->findCommand($request->command_slug, $request->category);
        
        if (!$command) {
            return response()->json([
                'success' => false,
                'error' => 'Command not found',
            ], 404);
        }

        $executor = $this->executors[$command['type']] ?? null;
        
        if (!$executor) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid command type',
            ], 400);
        }

        if (!$executor->validateCommand($command)) {
            return response()->json([
                'success' => false,
                'error' => 'Command validation failed',
            ], 400);
        }

        $log = CommandLog::create([
            'user_id' => $request->user()->id,
            'command_name' => $command['name'],
            'command_slug' => $command['slug'],
            'command_type' => $command['type'],
            'category' => $request->category,
            'inputs' => $request->inputs,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $result = $executor->execute($command, $request->inputs ?? [], $log);
            
            return response()->json(array_merge($result, [
                'log_id' => $log->id,
            ]));
        } catch (\Exception $e) {
            $log->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'log_id' => $log->id,
            ], 500);
        }
    }

    public function status(Request $request, CommandLog $log): JsonResponse
    {
        $this->authorize('viewNovaFlexRunner');

        if ($log->user_id !== $request->user()->id) {
            abort(403);
        }

        return response()->json([
            'id' => $log->id,
            'status' => $log->status,
            'output' => $log->output,
            'duration' => $log->duration,
            'error_message' => $log->error_message,
            'started_at' => $log->started_at,
            'completed_at' => $log->completed_at,
        ]);
    }

    public function validateInputs(Request $request): JsonResponse
    {
        $this->authorize('viewNovaFlexRunner');

        $request->validate([
            'command_slug' => 'required|string',
            'category' => 'required|string',
            'inputs' => 'array',
        ]);

        $command = $this->findCommand($request->command_slug, $request->category);
        
        if (!$command) {
            return response()->json([
                'valid' => false,
                'errors' => ['command' => 'Command not found'],
            ], 404);
        }

        $executor = $this->executors[$command['type']] ?? null;
        
        if (!$executor) {
            return response()->json([
                'valid' => false,
                'errors' => ['command' => 'Invalid command type'],
            ], 400);
        }

        $inputs = $request->inputs ?? [];
        $inputDefinitions = $command['inputs'] ?? [];
        
        $errors = $executor instanceof \Farsi\NovaFlexRunner\Services\BaseExecutorService
            ? $executor->validateInputs($inputs, $inputDefinitions)
            : [];

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors,
        ]);
    }

    protected function findCommand(string $slug, string $category): ?array
    {
        $commands = config('nova-flex-runner.commands', []);
        
        if (!isset($commands[$category])) {
            return null;
        }

        foreach ($commands[$category]['commands'] as $command) {
            if ($command['slug'] === $slug) {
                return $command;
            }
        }

        return null;
    }

    protected function authorize(string $ability): void
    {
        if (Gate::has($ability)) {
            Gate::authorize($ability, auth()->user());
        }
    }
}