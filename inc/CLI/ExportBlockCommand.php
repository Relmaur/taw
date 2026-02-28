<?php

declare(strict_types=1);

namespace TAW\CLI;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Export a block as a portable ZIP archive.
 *
 * The ZIP includes all block files plus a block.json manifest
 * that describes the block for import on another project.
 */
class ExportBlockCommand extends Command
{
    private string $themeDir;

    public function __construct()
    {
        parent::__construct();
        $this->themeDir = dirname(__DIR__, 2);
    }

    protected function configure(): void
    {
        $this
            ->setName('export:block')
            ->setDescription('Export a block as a portable ZIP')
            ->setHelp(<<<'HELP'
                Packages a block's entire directory (class, template, styles, scripts)
                into a ZIP file with a manifest. The ZIP can be imported into any
                TAW theme using <info>import:block</info>.

                Example:
                  <info>php bin/taw export:block Hero</info>
                  <info>php bin/taw export:block Hero --output=~/Desktop</info>
                HELP)
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Block name to export (e.g., Hero)'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_REQUIRED,
                'Output directory for the ZIP',
                '.'  // default: current working directory
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');

        $blockDir = $this->themeDir . '/Blocks/' . $name;

        if (!is_dir($blockDir)) {
            $io->error("Block '{$name}' not found at {$blockDir}");
            return Command::FAILURE;
        }

        // Resolve output path
        $outputDir = rtrim($input->getOption('output'), '/');
        $zipName   = "taw-block-{$name}.zip";
        $zipPath   = $outputDir . '/' . $zipName;

        // Check that ZipArchive is available
        // It's a PHP extension — usually enabled, but worth checking.
        if (!class_exists(\ZipArchive::class)) {
            $io->error([
                'The PHP zip extension is not installed.',
                'Install it: sudo apt install php-zip (Linux) or brew install php (macOS).',
            ]);
            return Command::FAILURE;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            $io->error("Could not create ZIP at {$zipPath}");
            return Command::FAILURE;
        }

        // Collect all files recursively
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($blockDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        $fileList = [];
        foreach ($files as $file) {
            // Store files inside a {Name}/ folder in the ZIP
            // so extraction creates the correct directory structure.
            $relativePath = $name . '/' . substr($file->getRealPath(), strlen($blockDir) + 1);
            $zip->addFile($file->getRealPath(), $relativePath);
            $fileList[] = substr($file->getRealPath(), strlen($blockDir) + 1);
        }

        // Add a manifest — metadata about the block for the importer.
        // This is like package.json for npm packages.
        $manifest = [
            'name'        => $name,
            'exported_at' => date('c'),
            'taw_version' => '1.0.0',
            'php_version'  => PHP_VERSION,
            'files'       => $fileList,
        ];

        $zip->addFromString(
            $name . '/block.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
        $zip->close();

        // Format the file size nicely
        $sizeBytes = filesize($zipPath);
        $sizeFormatted = $sizeBytes > 1024
            ? round($sizeBytes / 1024, 1) . ' KB'
            : $sizeBytes . ' bytes';

        $io->success("Exported '{$name}' → {$zipPath} ({$sizeFormatted})");

        $io->table(
            ['File', 'Included'],
            array_map(fn($f) => [$f, '✓'], $fileList)
        );

        return Command::SUCCESS;
    }
}
