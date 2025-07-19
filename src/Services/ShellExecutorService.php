<?php

namespace Farsi\NovaFlexRunner\Services;

use Farsi\NovaFlexRunner\Models\CommandLog;
use Symfony\Component\Process\Process;

class ShellExecutorService extends BaseExecutorService
{
    protected array $allowedCommands = [];
    protected array $blockedCommands = [
        'rm', 'rmdir', 'del', 'format', 'fdisk', 'mkfs',
        'dd', 'shutdown', 'reboot', 'halt', 'poweroff',
        'passwd', 'su', 'sudo', 'chmod', 'chown',
        'kill', 'killall', 'pkill',
    ];

    public function __construct()
    {
        $this->allowedCommands = config('nova-flex-runner.shell.allowed_commands', []);
        $this->blockedCommands = array_merge(
            $this->blockedCommands,
            config('nova-flex-runner.shell.blocked_commands', [])
        );
    }

    public function execute(array $command, array $inputs = [], ?CommandLog $log = null): array
    {
        $shellCommand = $this->buildCommand($command, $inputs);
        
        if (!$this->isCommandAllowed($shellCommand)) {
            $result = [
                'success' => false,
                'output' => '',
                'error' => 'Command not allowed or contains blocked operations.',
                'duration' => 0,
            ];

            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'output' => '',
                    'duration' => 0,
                    'completed_at' => now(),
                    'error_message' => $result['error'],
                ]);
            }

            return $result;
        }

        $startTime = microtime(true);

        try {
            $timeout = $command['timeout'] ?? config('nova-flex-runner.shell.timeout', 300);
            $workingDirectory = $command['working_directory'] ?? base_path();

            $process = Process::fromShellCommandline($shellCommand, $workingDirectory, null, null, $timeout);
            $process->run();

            $duration = microtime(true) - $startTime;
            $output = $process->getOutput();
            $errorOutput = $process->getErrorOutput();

            $result = [
                'success' => $process->isSuccessful(),
                'output' => $output . ($errorOutput ? "\nSTDERR:\n" . $errorOutput : ''),
                'exit_code' => $process->getExitCode(),
                'duration' => $duration,
            ];

            if (!$process->isSuccessful()) {
                $result['error'] = "Command failed with exit code {$process->getExitCode()}";
            }

            if ($log) {
                $log->update([
                    'status' => $process->isSuccessful() ? 'success' : 'failed',
                    'output' => $result['output'],
                    'duration' => $duration,
                    'completed_at' => now(),
                    'error_message' => $result['error'] ?? null,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $result = [
                'success' => false,
                'output' => '',
                'error' => $e->getMessage(),
                'duration' => $duration,
            ];

            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'output' => '',
                    'duration' => $duration,
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            return $result;
        }
    }

    protected function buildCommand(array $command, array $inputs): string
    {
        $baseCommand = $command['command'];
        $inputDefinitions = $command['inputs'] ?? [];

        foreach ($inputDefinitions as $input) {
            $name = $input['name'];
            $value = $inputs[$name] ?? null;

            if ($value !== null && $value !== '') {
                $placeholder = $input['placeholder'] ?? "{{$name}}";
                $escapedValue = escapeshellarg($this->sanitizeInput($value));
                $baseCommand = str_replace($placeholder, $escapedValue, $baseCommand);
            }
        }

        return $baseCommand;
    }

    protected function isCommandAllowed(string $command): bool
    {
        if (!empty($this->allowedCommands)) {
            foreach ($this->allowedCommands as $allowedPattern) {
                if (preg_match($allowedPattern, $command)) {
                    return true;
                }
            }
            return false;
        }

        foreach ($this->blockedCommands as $blockedCommand) {
            if (str_contains(strtolower($command), strtolower($blockedCommand))) {
                return false;
            }
        }

        return config('nova-flex-runner.shell.enabled', false);
    }

    public function validateCommand(array $command): bool
    {
        if (!isset($command['command'])) {
            return false;
        }

        if (!config('nova-flex-runner.shell.enabled', false)) {
            return false;
        }

        return $this->isCommandAllowed($command['command']);
    }
}