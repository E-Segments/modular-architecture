<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Validation;

use Composer\Semver\Semver;
use Esegments\ModularArchitecture\Module\Module;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Esegments\ModularArchitecture\Resolver\DependencyResolver;

class DependencyValidator
{
    public function __construct(
        protected readonly DependencyResolver $resolver,
    ) {}

    /**
     * Validate all dependencies for a module collection.
     */
    public function validate(ModuleCollection $modules): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Check for circular dependencies
        $cycles = $this->resolver->detectCycles($modules);
        foreach ($cycles as $cycle) {
            $errors[] = 'Circular dependency detected: ' . implode(' → ', $cycle);
        }

        // Check each module's dependencies
        foreach ($modules as $module) {
            $result = $this->validateModule($module, $modules);
            $errors = array_merge($errors, $result->errors);
            $warnings = array_merge($warnings, $result->warnings);
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Validate dependencies for a single module.
     */
    public function validateModule(Module $module, ModuleCollection $allModules): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Check required dependencies
        foreach ($module->getRequires() as $depName => $constraint) {
            $dependency = $allModules->findByName($depName);

            if (! $dependency) {
                $errors[] = "[{$module->getName()}] requires [{$depName}] which is not installed";

                continue;
            }

            if (! $dependency->isEnabled()) {
                $warnings[] = "[{$module->getName()}] requires [{$depName}] which is disabled";

                continue;
            }

            // Version constraint check
            if (! $this->satisfiesConstraint($dependency->getVersion(), $constraint)) {
                $errors[] = "[{$module->getName()}] requires [{$depName}] version [{$constraint}], but [{$dependency->getVersion()}] is installed";
            }
        }

        // Check conflicts
        foreach ($module->getConflicts() as $conflictName => $constraint) {
            $conflict = $allModules->findByName($conflictName);

            if ($conflict && $conflict->isEnabled()) {
                if ($this->satisfiesConstraint($conflict->getVersion(), $constraint)) {
                    $errors[] = "[{$module->getName()}] conflicts with [{$conflictName}] version [{$conflict->getVersion()}]";
                }
            }
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Check if a version satisfies a constraint.
     */
    public function satisfiesConstraint(string $version, string $constraint): bool
    {
        // Handle wildcard
        if ($constraint === '*') {
            return true;
        }

        try {
            return Semver::satisfies($version, $constraint);
        } catch (\Exception) {
            // If constraint parsing fails, be lenient
            return true;
        }
    }

    /**
     * Check if a module can be safely enabled.
     */
    public function canEnable(Module $module, ModuleCollection $allModules): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Check dependencies are enabled
        foreach (array_keys($module->getRequires()) as $depName) {
            $dependency = $allModules->findByName($depName);

            if (! $dependency) {
                $errors[] = "Required module [{$depName}] is not installed";
            } elseif (! $dependency->isEnabled()) {
                $errors[] = "Required module [{$depName}] is disabled";
            }
        }

        // Check for conflicts
        foreach ($module->getConflicts() as $conflictName => $constraint) {
            $conflict = $allModules->findByName($conflictName);

            if ($conflict && $conflict->isEnabled()) {
                if ($this->satisfiesConstraint($conflict->getVersion(), $constraint)) {
                    $errors[] = "Enabling would conflict with [{$conflictName}]";
                }
            }
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Check if a module can be safely disabled.
     */
    public function canDisable(Module $module, ModuleCollection $allModules): ValidationResult
    {
        $errors = [];
        $warnings = [];

        if ($module->isProtected()) {
            $errors[] = "Module [{$module->getName()}] is protected and cannot be disabled";

            return new ValidationResult(false, $errors, $warnings);
        }

        // Check if other enabled modules depend on this
        $dependents = $allModules->dependingOn($module->getName())->enabled();

        if ($dependents->isNotEmpty()) {
            $errors[] = 'The following modules depend on this module: ' . implode(', ', $dependents->names());
        }

        return new ValidationResult(
            valid: empty($errors),
            errors: $errors,
            warnings: $warnings,
        );
    }

    /**
     * Check if a module can be safely removed.
     */
    public function canRemove(Module $module, ModuleCollection $allModules): ValidationResult
    {
        $result = $this->canDisable($module, $allModules);

        if ($module->isProtected()) {
            return new ValidationResult(
                valid: false,
                errors: ["Module [{$module->getName()}] is protected and cannot be removed"],
                warnings: $result->warnings,
            );
        }

        return $result;
    }
}
