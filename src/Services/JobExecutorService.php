<?php

namespace Farsi\NovaFlexRunner\Services;

use Farsi\NovaFlexRunner\Models\CommandLog;
use Illuminate\Support\Facades\Queue;

class JobExecutorService extends BaseExecutorService
{
    public function execute(array $command, array $inputs = [], ?CommandLog $log = null): array
    {
        $jobClass = $command['job_class'];
        $jobData = $this->prepareJobData($inputs, $command['inputs'] ?? []);
        
        $startTime = microtime(true);

        try {
            if (!class_exists($jobClass)) {
                throw new \Exception("Job class {$jobClass} does not exist.");
            }

            $job = new $jobClass($jobData);
            
            $queue = $command['queue'] ?? config('queue.default');
            $connection = $command['connection'] ?? null;
            
            if ($connection) {
                $jobId = Queue::connection($connection)->push($job, '', $queue);
            } else {
                $jobId = Queue::push($job, '', $queue);
            }

            $duration = microtime(true) - $startTime;
            
            $result = [
                'success' => true,
                'output' => "Job {$jobClass} queued successfully with ID: {$jobId}",
                'job_id' => $jobId,
                'duration' => $duration,
            ];

            if ($log) {
                $log->update([
                    'status' => 'success',
                    'output' => $result['output'],
                    'duration' => $duration,
                    'completed_at' => now(),
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

    protected function prepareJobData(array $inputs, array $inputDefinitions): array
    {
        $data = [];

        foreach ($inputDefinitions as $input) {
            $key = $input['name'];
            $value = $inputs[$key] ?? null;

            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    public function validateCommand(array $command): bool
    {
        if (!isset($command['job_class'])) {
            return false;
        }

        return class_exists($command['job_class']);
    }

    public function dispatchSync(array $command, array $inputs = []): array
    {
        $jobClass = $command['job_class'];
        $jobData = $this->prepareJobData($inputs, $command['inputs'] ?? []);
        
        $startTime = microtime(true);

        try {
            if (!class_exists($jobClass)) {
                throw new \Exception("Job class {$jobClass} does not exist.");
            }

            $job = new $jobClass($jobData);
            
            ob_start();
            $job->handle();
            $output = ob_get_clean();

            $duration = microtime(true) - $startTime;
            
            return [
                'success' => true,
                'output' => $output ?: "Job {$jobClass} executed successfully (sync)",
                'duration' => $duration,
            ];
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            
            return [
                'success' => false,
                'output' => ob_get_clean() ?: '',
                'error' => $e->getMessage(),
                'duration' => $duration,
            ];
        }
    }
}