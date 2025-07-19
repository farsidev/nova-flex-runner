<?php

namespace Farsi\NovaFlexRunner\Services;

use Farsi\NovaFlexRunner\Models\CommandLog;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ArtisanExecutorService extends BaseExecutorService
{
    public function execute(array $command, array $inputs = [], ?CommandLog $log = null): array
    {
        $commandName = $command['command'];
        $arguments = $this->prepareArguments($inputs, $command['inputs'] ?? []);

        $output = new BufferedOutput();
        $startTime = microtime(true);

        try {
            $exitCode = Artisan::call($commandName, $arguments, $output);
            $duration = microtime(true) - $startTime;
            $outputContent = $output->fetch();

            $result = [
                'success' => $exitCode === 0,
                'output' => $outputContent,
                'exit_code' => $exitCode,
                'duration' => $duration,
            ];

            if ($exitCode !== 0) {
                $result['error'] = "Command failed with exit code {$exitCode}";
            }

            if ($log) {
                $log->update([
                    'status' => $exitCode === 0 ? 'success' : 'failed',
                    'output' => $outputContent,
                    'duration' => $duration,
                    'completed_at' => now(),
                    'error_message' => $exitCode !== 0 ? $result['error'] : null,
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            $result = [
                'success' => false,
                'output' => $output->fetch(),
                'error' => $e->getMessage(),
                'duration' => $duration,
            ];

            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'output' => $output->fetch(),
                    'duration' => $duration,
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            return $result;
        }
    }

    protected function prepareArguments(array $inputs, array $inputDefinitions): array
    {
        $arguments = [];

        foreach ($inputDefinitions as $input) {
            $key = $input['name'];
            $value = $inputs[$key] ?? null;

            if ($value === null) {
                continue;
            }

            switch ($input['type']) {
                case 'boolean':
                case 'checkbox':
                    if ($value) {
                        $arguments["--{$key}"] = true;
                    }
                    break;

                case 'multiselect':
                case 'tags':
                    if (is_array($value) && !empty($value)) {
                        $arguments["--{$key}"] = $value;
                    }
                    break;

                default:
                    if ($value !== '') {
                        if ($input['is_option'] ?? false) {
                            $arguments["--{$key}"] = $value;
                        } else {
                            $arguments[$key] = $value;
                        }
                    }
                    break;
            }
        }

        return $arguments;
    }

    public function validateCommand(array $command): bool
    {
        if (!isset($command['command'])) {
            return false;
        }

        $artisanCommands = collect(Artisan::all())->keys();
        
        return $artisanCommands->contains($command['command']);
    }
}