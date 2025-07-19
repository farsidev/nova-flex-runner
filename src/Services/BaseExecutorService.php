<?php

namespace Farsi\NovaFlexRunner\Services;

use Farsi\NovaFlexRunner\Models\CommandLog;

abstract class BaseExecutorService
{
    abstract public function execute(array $command, array $inputs = [], ?CommandLog $log = null): array;
    
    abstract public function validateCommand(array $command): bool;

    protected function sanitizeInput($value): string
    {
        if (is_array($value)) {
            return implode(',', array_map([$this, 'sanitizeInput'], $value));
        }

        return (string) $value;
    }

    protected function validateInputs(array $inputs, array $inputDefinitions): array
    {
        $errors = [];
        
        foreach ($inputDefinitions as $input) {
            $name = $input['name'];
            $value = $inputs[$name] ?? null;
            
            if ($input['required'] ?? false) {
                if ($value === null || $value === '') {
                    $errors[$name] = "The {$name} field is required.";
                    continue;
                }
            }
            
            if ($value !== null && $value !== '') {
                $errors = array_merge($errors, $this->validateInputType($name, $value, $input));
            }
        }
        
        return $errors;
    }

    protected function validateInputType(string $name, $value, array $input): array
    {
        $errors = [];
        $type = $input['type'] ?? 'text';
        
        switch ($type) {
            case 'select':
                $options = $input['options'] ?? [];
                if (!in_array($value, array_column($options, 'value'))) {
                    $errors[$name] = "Invalid option selected for {$name}.";
                }
                break;
                
            case 'multiselect':
                if (!is_array($value)) {
                    $errors[$name] = "The {$name} field must be an array.";
                } else {
                    $options = array_column($input['options'] ?? [], 'value');
                    foreach ($value as $item) {
                        if (!in_array($item, $options)) {
                            $errors[$name] = "Invalid option selected for {$name}.";
                            break;
                        }
                    }
                }
                break;
                
            case 'number':
                if (!is_numeric($value)) {
                    $errors[$name] = "The {$name} field must be a number.";
                } else {
                    $min = $input['min'] ?? null;
                    $max = $input['max'] ?? null;
                    
                    if ($min !== null && $value < $min) {
                        $errors[$name] = "The {$name} field must be at least {$min}.";
                    }
                    
                    if ($max !== null && $value > $max) {
                        $errors[$name] = "The {$name} field must not be greater than {$max}.";
                    }
                }
                break;
                
            case 'email':
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name] = "The {$name} field must be a valid email address.";
                }
                break;
                
            case 'url':
                if (!filter_var($value, FILTER_VALIDATE_URL)) {
                    $errors[$name] = "The {$name} field must be a valid URL.";
                }
                break;
        }
        
        return $errors;
    }
}