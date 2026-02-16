<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Exceptions;

/**
 * Exception thrown when a circular dependency is detected.
 */
class CircularDependencyException extends ModuleException
{
    protected int $statusCode = 422;

    protected ?string $errorCode = 'CIRCULAR_DEPENDENCY';

    /**
     * @param  array<string>  $cycle
     */
    public function __construct(
        public readonly array $cycle,
        ?string $message = null,
    ) {
        $cyclePath = implode(' → ', $cycle);
        parent::__construct(
            message: $message ?? "Circular dependency detected: {$cyclePath}",
            context: ['cycle' => $cycle, 'cycle_path' => $cyclePath],
        );
    }

    /**
     * @param  array<string>  $cycle
     */
    public static function forCycle(array $cycle): self
    {
        return new self($cycle);
    }

    /**
     * @param  array<array<string>>  $cycles
     */
    public static function forMultipleCycles(array $cycles): self
    {
        $cycleStrings = array_map(
            fn (array $cycle) => implode(' → ', $cycle),
            $cycles
        );

        return new self(
            cycle: $cycles[0] ?? [],
            message: 'Multiple circular dependencies detected: '.implode('; ', $cycleStrings)
        );
    }

    public function getCyclePath(): string
    {
        return implode(' → ', $this->cycle);
    }
}
