<?php

namespace Convertain\Onboarding\Steps;

use Convertain\Onboarding\Contracts\StepInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class BaseStep implements StepInterface
{
    protected string $id;
    protected string $name;
    protected string $view;
    protected ?string $position = null;
    protected bool $required = true;
    protected bool $skippable = false;
    protected ?string $faqGroup = null;
    protected array $config = [];
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->configure();
    }
    
    protected function configure(): void
    {
        // Override in child classes to set properties
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getView(): string
    {
        return $this->view;
    }
    
    public function getPosition(): ?string
    {
        return $this->position;
    }
    
    public function isRequired(): bool
    {
        return $this->required && !$this->evaluateConfig('allow_skip', false);
    }
    
    public function isSkippable(): bool
    {
        return $this->skippable || $this->evaluateConfig('allow_skip', false);
    }
    
    public function shouldShow($user): bool
    {
        // Check if step should be shown based on conditions
        if ($condition = $this->config['condition'] ?? null) {
            if (is_callable($condition)) {
                return $condition($user);
            }
            
            if (is_string($condition)) {
                return $this->evaluateStringCondition($condition, $user);
            }
        }
        
        return true;
    }
    
    public function validate(Request $request): array
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules(),
            $this->getValidationMessages()
        );
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }
        
        return [
            'valid' => true,
            'data' => $validator->validated(),
        ];
    }
    
    public function process(Request $request, $user): bool
    {
        $validation = $this->validate($request);
        
        if (!$validation['valid']) {
            return false;
        }
        
        return $this->saveData($validation['data'], $user);
    }
    
    abstract public function getValidationRules(): array;
    
    public function getValidationMessages(): array
    {
        return config('onboarding.messages', []);
    }
    
    abstract public function getData($user): array;
    
    abstract protected function saveData(array $data, $user): bool;
    
    public function getFAQGroup(): ?string
    {
        return $this->faqGroup ?? $this->config['faq_group'] ?? null;
    }
    
    protected function evaluateConfig(string $key, $default = null)
    {
        $value = $this->config[$key] ?? config("onboarding.steps.{$this->id}.{$key}", $default);
        
        if (is_string($value) && str_starts_with($value, 'env(')) {
            // Parse env() calls in config
            preg_match('/env\([\'"](.+?)[\'"](,\s*(.+))?\)/', $value, $matches);
            if (!empty($matches[1])) {
                $envKey = $matches[1];
                $envDefault = $matches[3] ?? $default;
                return env($envKey, $envDefault);
            }
        }
        
        return $value;
    }
    
    protected function evaluateStringCondition(string $condition, $user): bool
    {
        // Simple condition evaluation for common patterns
        // Examples: "hasRole('admin')", "hasPackage('laravel-xero')"
        
        if (str_contains($condition, 'hasRole')) {
            preg_match('/hasRole\([\'"](.+?)[\'"]\)/', $condition, $matches);
            if (!empty($matches[1]) && method_exists($user, 'hasRole')) {
                return $user->hasRole($matches[1]);
            }
        }
        
        if (str_contains($condition, 'hasPackage')) {
            preg_match('/hasPackage\([\'"](.+?)[\'"]\)/', $condition, $matches);
            if (!empty($matches[1])) {
                return app('onboarding.detector')->hasPackage($matches[1]);
            }
        }
        
        // Allow for custom condition evaluation via events
        $result = app('events')->dispatch('onboarding.evaluate_condition', [
            $condition,
            $user,
            $this
        ]);
        
        if (!empty($result)) {
            return $result[0] === true;
        }
        
        return false;
    }
}