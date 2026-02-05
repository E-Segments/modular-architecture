<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Validation;

use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleManifest;

class ModuleValidator
{
    /**
     * Validate a module manifest.
     */
    public function validate(Module $module): ValidationResult
    {
        $errors = [];
        $warnings = [];

        $manifest = $module->getManifestObject();

        $this->validateName($manifest, $errors, $warnings);
        $this->validateVersion($manifest, $errors, $warnings);
        $this->validateProviders($module, $errors);
        $this->validatePaths($module, $errors);

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Validate module name.
     *
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateName(ModuleManifest $manifest, array &$errors, array &$warnings): void
    {
        if (empty($manifest->name)) {
            $errors[] = 'Module name is required';

            return;
        }

        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $manifest->name)) {
            $warnings[] = "Module name '{$manifest->name}' should be PascalCase";
        }
    }

    /**
     * Validate version format.
     *
     * @param  array<string>  $errors
     * @param  array<string>  $warnings
     */
    protected function validateVersion(ModuleManifest $manifest, array &$errors, array &$warnings): void
    {
        if (empty($manifest->version)) {
            $errors[] = 'Module version is required';

            return;
        }

        // Basic semver format check
        if (! preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?(\+[a-zA-Z0-9.]+)?$/', $manifest->version)) {
            $warnings[] = "Version '{$manifest->version}' doesn't follow semver format (x.y.z)";
        }
    }

    /**
     * Validate service providers exist.
     *
     * @param  array<string>  $errors
     */
    protected function validateProviders(Module $module, array &$errors): void
    {
        foreach ($module->getProviders() as $provider) {
            if (! class_exists($provider)) {
                $errors[] = "Service provider class not found: {$provider}";
            }
        }
    }

    /**
     * Validate expected paths exist.
     *
     * @param  array<string>  $errors
     */
    protected function validatePaths(Module $module, array &$errors): void
    {
        if (! is_dir($module->getPath())) {
            $errors[] = "Module path does not exist: {$module->getPath()}";
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
