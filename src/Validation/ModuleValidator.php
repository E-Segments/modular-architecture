<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Validation;

use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleManifest;

class ModuleValidator
{
    /**
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * @var array<string>
     */
    protected array $warnings = [];

    /**
     * Validate a module manifest.
     */
    public function validate(Module $module): ValidationResult
    {
        $this->errors = [];
        $this->warnings = [];

        $manifest = $module->getManifestObject();

        $this->validateName($manifest);
        $this->validateVersion($manifest);
        $this->validateProviders($module);
        $this->validatePaths($module);

        return new ValidationResult(
            valid: empty($this->errors),
            errors: $this->errors,
            warnings: $this->warnings,
        );
    }

    /**
     * Validate module name.
     */
    protected function validateName(ModuleManifest $manifest): void
    {
        if (empty($manifest->name)) {
            $this->errors[] = 'Module name is required';

            return;
        }

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $manifest->name)) {
            $this->warnings[] = "Module name '{$manifest->name}' should be PascalCase";
        }
    }

    /**
     * Validate version format.
     */
    protected function validateVersion(ModuleManifest $manifest): void
    {
        if (empty($manifest->version)) {
            $this->errors[] = 'Module version is required';

            return;
        }

        // Basic semver format check
        if (! preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?(\+[a-zA-Z0-9.]+)?$/', $manifest->version)) {
            $this->warnings[] = "Version '{$manifest->version}' doesn't follow semver format (x.y.z)";
        }
    }

    /**
     * Validate service providers exist.
     */
    protected function validateProviders(Module $module): void
    {
        foreach ($module->getProviders() as $provider) {
            if (! class_exists($provider)) {
                $this->errors[] = "Service provider class not found: {$provider}";
            }
        }
    }

    /**
     * Validate expected paths exist.
     */
    protected function validatePaths(Module $module): void
    {
        if (! is_dir($module->getPath())) {
            $this->errors[] = "Module path does not exist: {$module->getPath()}";
        }
    }

    /**
     * Quick validation check without detailed results.
     */
    public function isValid(Module $module): bool
    {
        return $this->validate($module)->isValid();
    }
}
