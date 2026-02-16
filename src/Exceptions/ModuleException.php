<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

use Esegments\Core\Exceptions\CoreException;

/**
 * Base exception for module-related errors.
 */
class ModuleException extends CoreException
{
    protected ?string $errorCode = 'MODULE_ERROR';

    /**
     * The module name, if applicable.
     */
    protected ?string $moduleName = null;

    /**
     * Get the module name.
     */
    public function getModuleName(): ?string
    {
        return $this->moduleName;
    }

    /**
     * Set the module name.
     */
    public function setModuleName(string $moduleName): static
    {
        $this->moduleName = $moduleName;
        $this->addContext(['module' => $moduleName]);

        return $this;
    }
}
