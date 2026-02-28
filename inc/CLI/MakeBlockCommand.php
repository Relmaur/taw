<?php

declare(strict_types=1);

namespace TAW\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Scaffold a new TAW block.
 *
 * Symfony Console anatomy:
 *   - configure()  → Define the command's name, arguments, options, and help text.
 *   - execute()    → The actual logic that runs when the command is called.
 *   - SymfonyStyle → A helper that wraps Input/Output with pretty formatting
 *                     (success messages, tables, section headers, etc.)
 */
class MakeBlockCommand extends Command
{
    /**
     * The theme root directory.
     * We calculate this from the command file's location.
     */
    private string $themeDir;

    public function __construct()
    {
        // This MUST be called before any $this-> usage in the constructor.
        // Symfony's Command constructor sets up internal state.
        parent::__construct();

        // Walk from inc/CLI/ up to the theme root
        $this->themeDir = dirname(__DIR__, 2);
    }

    /**
     * Configure the command.
     *
     * This is where you define WHAT the command accepts. Symfony uses this
     * to generate help text, validate input, and parse argv.
     *
     * Three input types:
     *   - Arguments: positional, required or optional (e.g., `taw make:block Hero`)
     *   - Options with values: --type=meta (has a value)
     *   - Options as flags: --with-style (boolean, present or not)
     */
    protected function configure(): void
    {
        $this
            ->setName('make:block')
            ->setDescription('Scaffold a new TAW block')
            ->setHelp(<<<'HELP'
                Creates a new block with the proper folder structure, class file,
                and template. The block is immediately auto-discovered by BlockLoader.

                Examples:
                  <info>php bin/taw make:block Hero --type=meta --with-style</info>
                  <info>php bin/taw make:block Badge --type=ui</info>
                  <info>php bin/taw make:block PricingTable</info> (interactive mode)
                HELP)

            // Arguments are positional — the first word after the command name.
            // REQUIRED means the command fails if not provided.
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Block name in PascalCase (e.g., Hero, PricingTable)'
            )

            // Options use -- prefix. VALUE_REQUIRED means --type needs a value.
            ->addOption(
                'type',
                't',                       // shortcut: -t meta
                InputOption::VALUE_REQUIRED,
                'Block type: "meta" (MetaBlock) or "ui" (Block)',
                null                        // no default — we'll prompt interactively
            )

            // VALUE_NONE means it's a boolean flag — present or not.
            ->addOption(
                'with-style',
                null,
                InputOption::VALUE_NONE,
                'Include a style.scss file'
            )
            ->addOption(
                'with-script',
                null,
                InputOption::VALUE_NONE,
                'Include a script.js file'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Overwrite if block already exists'
            );
    }

    /**
     * Execute the command.
     *
     * Symfony calls this with two objects:
     *   - InputInterface:  read arguments and options
     *   - OutputInterface: write to the terminal
     *
     * SymfonyStyle wraps both into a friendlier API with colored output,
     * tables, questions, and pre-styled messages (success, error, warning).
     *
     * Return codes:
     *   Command::SUCCESS (0) = everything worked
     *   Command::FAILURE (1) = something went wrong
     *   Command::INVALID (2) = bad input
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // SymfonyStyle is your best friend — it gives you:
        //   $io->success('Done!')        → green box
        //   $io->error('Failed!')        → red box
        //   $io->warning('Careful...')    → yellow box
        //   $io->title('Section')        → underlined header
        //   $io->table([headers], [rows]) → formatted table
        //   $io->ask('Question?')        → interactive prompt
        //   $io->confirm('Sure?')        → yes/no prompt
        $io = new SymfonyStyle($input, $output);

        $io->title('TAW Block Scaffolder');

        // --- Read and validate the block name ---
        $name = $input->getArgument('name');

        if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $name)) {
            $io->error([
                "Invalid block name: '{$name}'",
                'Block names must be PascalCase (e.g., Hero, PricingTable, BlogGrid).',
            ]);
            return Command::INVALID;
        }

        // --- Determine block type (option or interactive prompt) ---
        $type = $input->getOption('type');

        if ($type === null) {
            // No --type flag provided — ask interactively!
            // This is a killer feature of Symfony Console.
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion(
                'What type of block? (default: meta)',
                ['meta' => 'MetaBlock — owns data via metaboxes', 'ui' => 'Block — presentational, receives props'],
                'meta'
            );
            $type = $helper->ask($input, $output, $question);
        }

        if (!in_array($type, ['meta', 'ui'])) {
            $io->error("Invalid type '{$type}'. Must be 'meta' or 'ui'.");
            return Command::INVALID;
        }

        // --- Check if block already exists ---
        $blockDir = $this->themeDir . '/inc/Blocks/' . $name;
        $force    = $input->getOption('force');

        if (is_dir($blockDir) && !$force) {
            $io->error([
                "Block '{$name}' already exists at:",
                $blockDir,
                'Use --force to overwrite.',
            ]);
            return Command::FAILURE;
        }

        // --- Create the block ---
        $withStyle  = $input->getOption('with-style');
        $withScript = $input->getOption('with-script');

        // Convert PascalCase to snake_case for the $id property
        // "PricingTable" → "pricing_table"
        $id = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

        // Create directory
        if (!is_dir($blockDir)) {
            mkdir($blockDir, 0755, true);
        }

        // Track created files for the summary table
        $createdFiles = [];

        // 1. Class file
        $classContent = $type === 'meta'
            ? $this->generateMetaBlockClass($name, $id)
            : $this->generateUiBlockClass($name, $id);
        file_put_contents($blockDir . '/' . $name . '.php', $classContent);
        $createdFiles[] = ['Class', "inc/Blocks/{$name}/{$name}.php"];

        // 2. Template
        $templateContent = $type === 'meta'
            ? $this->generateMetaTemplate($name, $id)
            : $this->generateUiTemplate($name, $id);
        file_put_contents($blockDir . '/index.php', $templateContent);
        $createdFiles[] = ['Template', "inc/Blocks/{$name}/index.php"];

        // 3. Optional style
        if ($withStyle) {
            $scss = <<<SCSS
            /**
             * {$name} Block Styles
             *
             * This file is auto-enqueued only on pages that render this block.
             * Use Tailwind utilities in the template for most styling —
             * reserve this file for complex or stateful CSS.
             */
            
            .{$id} {
                
            }
            SCSS;
            file_put_contents($blockDir . '/style.scss', $scss);
            $createdFiles[] = ['Stylesheet', "inc/Blocks/{$name}/style.scss"];
        }

        // 4. Optional script
        if ($withScript) {
            $js = <<<JS
            /**
             * {$name} Block Script
             *
             * Auto-enqueued only on pages that render this block.
             * Loaded as type="module" — you can use import/export syntax.
             */
            
            console.log('{$name} block initialized.');
            JS;
            file_put_contents($blockDir . '/script.js', $js);
            $createdFiles[] = ['Script', "inc/Blocks/{$name}/script.js"];
        }

        // --- Success output ---
        $io->success("Block '{$name}' created!");

        // Show a nice table of what was created
        $io->table(['Asset', 'Path'], $createdFiles);

        // Helpful next steps
        $io->section('Next Steps');
        $io->listing([
            'Run <info>composer dump-autoload</info> to register the new class',
            $type === 'meta'
                ? "Add <info>BlockRegistry::queue('{$id}')</info> to your template"
                : "Use <info>(new {$name}())->render([...])</info> in any template",
            'Edit the class to add your fields and data logic',
        ]);

        return Command::SUCCESS;
    }

    /* ---------------------------------------------------------------
     * Template Generators
     *
     * These return the file contents as strings. Heredoc syntax
     * keeps them readable. Notice every generated file includes:
     *   - declare(strict_types=1)
     *   - Correct namespace
     *   - i18n-ready strings (using __())
     * --------------------------------------------------------------- */

    private function generateMetaBlockClass(string $name, string $id): string
    {
        // Heredoc with variable interpolation.
        // We escape the $ in PHP variables inside the template using {$var}
        // for our generator variables, and \$ for the generated PHP variables.
        return <<<PHP
        <?php
        
        declare(strict_types=1);
        
        namespace TAW\Blocks\\{$name};
        
        use TAW\Core\MetaBlock;
        use TAW\Core\Metabox\Metabox;
        
        class {$name} extends MetaBlock
        {
            protected string \$id = '{$id}';
        
            protected function registerMetaboxes(): void
            {
                new Metabox([
                    'id'     => 'taw_{$id}',
                    'title'  => __( '{$name} Section', 'taw-theme' ),
                    'screen' => 'page',
                    'fields' => [
                        [
                            'id'    => '{$id}_heading',
                            'label' => __( 'Heading', 'taw-theme' ),
                            'type'  => 'text',
                        ],
                    ],
                ]);
            }
        
            protected function getData(int \$postId): array
            {
                return [
                    'heading' => \$this->getMeta(\$postId, '{$id}_heading'),
                ];
            }
        }
        
        PHP;
    }

    private function generateUiBlockClass(string $name, string $id): string
    {
        return <<<PHP
        <?php
        
        declare(strict_types=1);
        
        namespace TAW\Blocks\\{$name};
        
        use TAW\Core\Block;
        
        class {$name} extends Block
        {
            protected string \$id = '{$id}';
        
            protected function defaults(): array
            {
                return [
                    'text' => '',
                ];
            }
        }
        
        PHP;
    }

    private function generateMetaTemplate(string $name, string $id): string
    {
        return <<<PHP
        <?php
        /**
         * {$name} Block Template
         *
         * Available variables (from getData):
         * @var string \$heading
         */
        
        if (empty(\$heading)) return;
        ?>
        
        <section class="{$id}">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold">
                    <?php echo esc_html(\$heading); ?>
                </h2>
            </div>
        </section>
        
        PHP;
    }

    private function generateUiTemplate(string $name, string $id): string
    {
        return <<<PHP
        <?php
        /**
         * {$name} Block Template
         *
         * Available props (from defaults):
         * @var string \$text
         */
        ?>
        
        <div class="{$id}">
            <?php echo esc_html(\$text); ?>
        </div>
        
        PHP;
    }
}
