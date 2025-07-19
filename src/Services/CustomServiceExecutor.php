<?php

namespace Farsi\NovaFlexRunner\Services;

use Farsi\NovaFlexRunner\Models\CommandLog;

class CustomServiceExecutor extends BaseExecutorService
{
    public function execute(array $command, array $inputs = [], ?CommandLog $log = null): array
    {
        $serviceClass = $command['service_class'];
        $method = $command['method'] ?? 'handle';
        
        $startTime = microtime(true);

        try {
            if (!class_exists($serviceClass)) {
                throw new \Exception("Service class {$serviceClass} does not exist.");
            }

            $service = app($serviceClass);

            if (!method_exists($service, $method)) {
                throw new \Exception("Method {$method} does not exist in {$serviceClass}.");
            }

            ob_start();
            
            $result = $service->{$method}($inputs, $command);
            
            $output = ob_get_clean();
            $duration = microtime(true) - $startTime;

            if (is_array($result) && isset($result['success'])) {
                $executionResult = array_merge($result, [
                    'duration' => $duration,
                    'output' => $result['output'] ?? $output,
                ]);
            } else {
                $executionResult = [
                    'success' => true,
                    'output' => $output ?: ($result ? json_encode($result) : 'Service executed successfully'),
                    'duration' => $duration,
                    'result' => $result,
                ];
            }

            if ($log) {
                $log->update([
                    'status' => $executionResult['success'] ? 'success' : 'failed',
                    'output' => $executionResult['output'],
                    'duration' => $duration,
                    'completed_at' => now(),
                    'error_message' => $executionResult['error'] ?? null,
                ]);
            }

            return $executionResult;
        } catch (\Exception $e) {
            $duration = microtime(true) - $startTime;
            $output = ob_get_clean();
            
            $result = [
                'success' => false,
                'output' => $output,
                'error' => $e->getMessage(),
                'duration' => $duration,
            ];

            if ($log) {
                $log->update([
                    'status' => 'failed',
                    'output' => $output,
                    'duration' => $duration,
                    'completed_at' => now(),
                    'error_message' => $e->getMessage(),
                ]);
            }

            return $result;
        }
    }

    public function validateCommand(array $command): bool
    {
        if (!isset($command['service_class'])) {
            return false;
        }

        if (!class_exists($command['service_class'])) {
            return false;
        }

        $method = $command['method'] ?? 'handle';
        
        return method_exists($command['service_class'], $method);
    }

    public function getServiceMethods(string $serviceClass): array
    {
        if (!class_exists($serviceClass)) {
            return [];
        }

        $reflection = new \ReflectionClass($serviceClass);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        
        return collect($methods)
            ->filter(function ($method) {
                return !$method->isConstructor() && 
                       !$method->isDestructor() && 
                       !$method->isStatic() &&
                       !str_starts_with($method->getName(), '__');
            })
            ->map(function ($method) {
                return [
                    'name' => $method->getName(),
                    'parameters' => collect($method->getParameters())->map(function ($param) {
                        return [
                            'name' => $param->getName(),
                            'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                            'required' => !$param->isOptional(),
                            'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                        ];
                    })->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }
}