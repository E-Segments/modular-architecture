<?php

declare(strict_types=1);

namespace Esegments\ModularArchitecture\Commands;

use Esegments\ModularArchitecture\Modular;
use Esegments\ModularArchitecture\Module\ModuleCollection;
use Illuminate\Console\Command;

class GraphCommand extends Command
{
    protected $signature = 'modular:graph
                            {--format=text : Output format (text, dot, mermaid)}
                            {--enabled : Only show enabled modules}';

    protected $description = 'Visualize module dependency graph';

    public function handle(Modular $modular): int
    {
        $modules = $this->option('enabled')
            ? $modular->enabled()
            : $modular->all();

        if ($modules->isEmpty()) {
            $this->components->warn('No modules found.');

            return Command::SUCCESS;
        }

        $format = $this->option('format');

        return match ($format) {
            'dot' => $this->outputDot($modules),
            'mermaid' => $this->outputMermaid($modules),
            default => $this->outputText($modules),
        };
    }

    protected function outputText(ModuleCollection $modules): int
    {
        $this->components->info('Module Dependency Graph');
        $this->newLine();

        foreach ($modules as $module) {
            $status = $module->isEnabled() ? '<fg=green>●</>' : '<fg=yellow>○</>';
            $protected = $module->isProtected() ? ' <fg=blue>[protected]</>' : '';

            $this->line("{$status} {$module->getName()} v{$module->getVersion()}{$protected}");

            $requires = $module->getRequires();
            if (! empty($requires)) {
                foreach ($requires as $dep => $constraint) {
                    $depModule = $modules->findByName($dep);
                    $depStatus = $depModule
                        ? ($depModule->isEnabled() ? '<fg=green>→</>' : '<fg=yellow>→</>')
                        : '<fg=red>→</>';
                    $this->line("   {$depStatus} {$dep} {$constraint}");
                }
            }
        }

        return Command::SUCCESS;
    }

    protected function outputDot(ModuleCollection $modules): int
    {
        $output = "digraph modules {\n";
        $output .= "    rankdir=LR;\n";
        $output .= "    node [shape=box];\n\n";

        // Define nodes
        foreach ($modules as $module) {
            $color = $module->isEnabled() ? 'green' : 'gray';
            $style = $module->isProtected() ? 'bold' : 'solid';
            $output .= "    \"{$module->getName()}\" [color={$color}, style={$style}];\n";
        }

        $output .= "\n";

        // Define edges
        foreach ($modules as $module) {
            foreach (array_keys($module->getRequires()) as $dep) {
                $output .= "    \"{$module->getName()}\" -> \"{$dep}\";\n";
            }
        }

        $output .= "}\n";

        $this->line($output);

        return Command::SUCCESS;
    }

    protected function outputMermaid(ModuleCollection $modules): int
    {
        $output = "```mermaid\ngraph LR\n";

        // Define nodes with styles
        foreach ($modules as $module) {
            $name = $module->getName();
            if ($module->isProtected()) {
                $output .= "    {$name}[[\"{$name}\"]]\n";
            } else {
                $output .= "    {$name}[\"{$name}\"]\n";
            }
        }

        $output .= "\n";

        // Define edges
        foreach ($modules as $module) {
            $name = $module->getName();
            foreach (array_keys($module->getRequires()) as $dep) {
                $output .= "    {$name} --> {$dep}\n";
            }
        }

        $output .= "\n";

        // Styles
        $enabled = $modules->enabled()->names();
        $disabled = $modules->disabled()->names();

        if (! empty($enabled)) {
            $output .= '    style ' . implode(',', $enabled) . " fill:#90EE90\n";
        }
        if (! empty($disabled)) {
            $output .= '    style ' . implode(',', $disabled) . " fill:#D3D3D3\n";
        }

        $output .= "```\n";

        $this->line($output);

        return Command::SUCCESS;
    }
}
