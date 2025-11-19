<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Finder\Finder;

final class DumpCodebaseCommand extends Command
{
    protected $signature = 'code:dump
                            {--path= : Target file or folder to scan (default: base_path())}
                            {--clean : Remove comments and whitespace}
                            {--limit=110000 : Character limit per file}
                            {--recursive : Parse imports recursively}
                            {--scope=loose : Scope control: strict|loose|all}
                            {--timeout=60 : Max processing time in seconds}
                            {--max-depth=20 : Maximum recursion depth}
                            {--debug : Show detailed processing info}
                            {--exclude= : Additional paths to exclude (comma-separated)}
                            {--output=dump : Output directory name}
                            {--format=txt : Output format: txt|md|json}
                            {--progress : Show progress bar}';

    protected $description = 'Dump codebase PHP files (file or folder) with recursive import parsing';

    // State tracking
    private array $addedFiles = [];

    private array $fileContents = [];

    private array $pathCache = [];

    // Output management
    private int $currentChars = 0;

    private int $fileIndex = 1;

    private string $outputFile = '';

    // Configuration
    private string $outputDir = 'dump';

    private string $basePath = '';

    private string $targetPath = '';

    private bool $targetIsFile = false;

    private array $excludedFolders = [];

    private array $excludedFiles = [];

    private string $scope = 'loose';

    private string $format = 'txt';

    // Limits
    private int $startTime = 0;

    private int $timeoutSeconds = 60;

    private int $maxDepth = 20;

    // Statistics
    private int $filesProcessed = 0;

    private int $importsParsed = 0;

    private bool $debugMode = false;

    private bool $showProgress = false;

    private ?ProgressBar $progressBar = null;

    public function handle(): int
    {
        $this->initializeSettings();

        if (!$this->validateTarget()) {
            return self::FAILURE;
        }

        $this->setupExclusions();
        $this->displayHeader();
        $this->prepareOutputDirectory();

        try {
            $this->targetIsFile
                ? $this->processSingleFile()
                : $this->processDirectory();

            $this->finishProgress();
            $this->displayStatistics();

            return self::SUCCESS;
        } catch (\Throwable $throwable) {
            $this->finishProgress();
            $this->handleError($throwable);

            return self::FAILURE;
        }
    }

    private function initializeSettings(): void
    {
        $this->startTime = time();
        $this->timeoutSeconds = (int) ($this->option('timeout') ?? 60);
        $this->maxDepth = (int) ($this->option('max-depth') ?? 20);
        $this->debugMode = (bool) $this->option('debug');
        $this->showProgress = (bool) $this->option('progress');
        $this->outputDir = (string) ($this->option('output') ?? 'dump');
        $this->scope = (string) ($this->option('scope') ?? 'loose');
        $this->format = (string) ($this->option('format') ?? 'txt');
        $this->basePath = rtrim(base_path(), '/');
    }

    private function validateTarget(): bool
    {
        $pathOption = $this->option('path');

        if ($pathOption) {
            $absolutePath = $this->makeAbsolutePath($pathOption);

            if (File::isFile($absolutePath)) {
                if (pathinfo($absolutePath, PATHINFO_EXTENSION) !== 'php') {
                    $this->error('❌ Target file must be a PHP file (.php extension)');

                    return false;
                }

                $this->targetPath = $this->normalizePath($absolutePath) ?? $absolutePath;
                $this->targetIsFile = true;
            } elseif (File::isDirectory($absolutePath)) {
                $this->targetPath = $this->normalizePath($absolutePath) ?? $absolutePath;
                $this->targetIsFile = false;
            } else {
                $this->error('❌ Path does not exist: '.$pathOption);

                return false;
            }
        } else {
            $this->targetPath = $this->basePath;
            $this->targetIsFile = false;
        }

        if (!File::exists($this->targetPath)) {
            $this->error('❌ Cannot access target path: '.$this->targetPath);

            return false;
        }

        if (!in_array($this->format, ['txt', 'md', 'json'], true)) {
            $this->error(sprintf('❌ Invalid format: %s. Must be: txt, md, or json', $this->format));

            return false;
        }

        return true;
    }

    private function makeAbsolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[a-zA-Z]:[\\\\\\/]/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    private function setupExclusions(): void
    {
        $defaultExcluded = [
            '/docker', '/app/Console',
            '/'.$this->outputDir, '/public', '/build',
            '/resources', '/storage', '/tests', '/vendor',
            '/bootstrap/cache', '/node_modules', '/.git',
        ];

        if ($customExclusions = $this->option('exclude')) {
            $customPaths = array_map(trim(...), explode(',', $customExclusions));
            foreach ($customPaths as $customPath) {
                if ($customPath) {
                    $defaultExcluded[] = '/'.ltrim($customPath, '/');
                }
            }
        }

        $this->excludedFolders = array_map(
            fn (string $path): string => $this->basePath.$path,
            $defaultExcluded
        );

        $this->excludedFiles = [
            $this->basePath.'/_ide_helper_models.php',
            $this->basePath.'/_ide_helper.php',
            $this->basePath.'/.phpstorm.meta.php',
            $this->basePath.'/rector.php',
            $this->basePath.'/artisan',
        ];
    }

    private function prepareOutputDirectory(): void
    {
        if (File::exists($this->outputDir)) {
            File::deleteDirectory($this->outputDir);
        }

        File::makeDirectory($this->outputDir, 0755, true);
    }

    private function processSingleFile(): void
    {
        $this->info('Processing single file...');
        $cleanMode = (bool) $this->option('clean');
        $charLimit = (int) $this->option('limit');
        $recursive = (bool) $this->option('recursive');

        if ($recursive) {
            $this->processFileRecursive($this->targetPath, $cleanMode, $charLimit, 0);
        } else {
            $this->processFile($this->targetPath, $cleanMode, $charLimit);
        }
    }

    private function processDirectory(): void
    {
        $finder = new Finder();
        $finder->files()
            ->in($this->targetPath)
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true);

        // Apply exclusions to Finder
        foreach ($this->excludedFolders as $excludedFolder) {
            // Finder expects relative paths or names for exclusions usually,
            // but efficient path checking is done in the loop for absolute safety.
            if (str_starts_with((string) $excludedFolder, $this->targetPath)) {
                $finder->notPath(basename((string) $excludedFolder));
            }
        }

        $cleanMode = (bool) $this->option('clean');
        $charLimit = (int) $this->option('limit');
        $recursive = (bool) $this->option('recursive');

        // Count for progress bar (can be slow on huge dirs, maybe optional?)
        $count = $this->showProgress ? iterator_count($finder) : 0;
        // Reset iterator
        $finder = $finder->getIterator();

        $this->info(sprintf('Found approximately %d PHP files', $count));

        if ($this->showProgress && !$this->debugMode) {
            $this->progressBar = $this->output->createProgressBar($count);
            $this->progressBar->start();
        }

        foreach ($finder as $file) {
            $this->checkTimeout();
            $filepath = $file->getRealPath();

            if ($recursive) {
                $this->processFileRecursive($filepath, $cleanMode, $charLimit, 0);
            } else {
                $this->processFile($filepath, $cleanMode, $charLimit);
            }

            $this->advanceProgress();
        }
    }

    private function processFileRecursive(string $filepath, bool $cleanMode, int $charLimit, int $depth = 0): void
    {
        if ($depth > $this->maxDepth) {
            $this->logSkip(sprintf('max depth (%d)', $this->maxDepth));

            return;
        }

        $filepath = $this->normalizePath($filepath);
        if (!$filepath || isset($this->addedFiles[$filepath])) {
            return;
        }

        if (!$this->isFileAccessible($filepath) || $this->shouldExcludeFile($filepath)) {
            return;
        }

        if ($depth > 0 && !$this->isFileInScope($filepath)) {
            return;
        }

        $fileContent = $this->getFileContent($filepath);
        $imports = $this->parseImports($fileContent);
        $imports = $this->filterImportsByScope($imports);

        // Depth-first traversal
        foreach ($imports as $import) {
            $this->checkTimeout();
            $this->processFileRecursive($import, $cleanMode, $charLimit, $depth + 1);
        }

        $this->addFileToOutput($filepath, $fileContent, $cleanMode, $charLimit, $depth);
    }

    private function processFile(string $filepath, bool $cleanMode, int $charLimit): void
    {
        $filepath = $this->normalizePath($filepath);
        if (!$filepath || isset($this->addedFiles[$filepath])) {
            return;
        }

        if ($this->isFileAccessible($filepath) && !$this->shouldExcludeFile($filepath)) {
            $fileContent = $this->getFileContent($filepath);
            $this->addFileToOutput($filepath, $fileContent, $cleanMode, $charLimit, 0);
        }
    }

    private function addFileToOutput(string $filepath, string $fileContent, bool $cleanMode, int $charLimit, int $depth): void
    {
        if (isset($this->addedFiles[$filepath])) {
            return;
        }

        if ($cleanMode) {
            $fileContent = $this->cleanContentTokenBased($fileContent);
        }

        $relativePath = $this->getRelativePath($filepath);
        $formattedContent = match ($this->format) {
            'md' => $this->formatAsMarkdown($relativePath, $fileContent),
            'json' => '',
            default => $this->formatAsText($relativePath, $fileContent),
        };

        $charsToAdd = strlen($formattedContent);

        // Chunking logic
        if ($this->currentChars > 0 && ($this->currentChars + $charsToAdd) > $charLimit) {
            $this->fileIndex++;
            $this->currentChars = 0;
        }

        if ($this->currentChars === 0 && $this->format !== 'json') {
            $ext = $this->format === 'md' ? 'md' : 'txt';
            $this->outputFile = sprintf('%s/php_dump_part_%04d.%s', $this->outputDir, $this->fileIndex, $ext);
        }

        if ($this->format !== 'json') {
            File::append($this->outputFile, $formattedContent);
        }

        $this->currentChars += $charsToAdd;
        $this->addedFiles[$filepath] = [
            'path' => $relativePath,
            'size' => strlen($fileContent),
            'depth' => $depth,
            'content' => $cleanMode ? $fileContent : null,
        ];
        $this->filesProcessed++;

        if (!$this->showProgress || $this->debugMode) {
            $this->displayFileAdded($relativePath, $depth);
        }
    }

    /**
     * Safer comment stripping using PHP's tokenizer
     */
    private function cleanContentTokenBased(string $content): string
    {
        try {
            $tokens = token_get_all($content);
            $newContent = '';
            foreach ($tokens as $token) {
                if (is_string($token)) {
                    $newContent .= $token;
                } else {
                    // token is array: [id, text, line]
                    if ($token[0] === T_COMMENT) {
                        continue;
                    }

                    if ($token[0] === T_DOC_COMMENT) {
                        continue;
                    }

                    $newContent .= $token[1];
                }
            }

            // Collapse multiple newlines
            return preg_replace("/\n{3,}/", "\n\n", trim($newContent));
        } catch (\Throwable) {
            // Fallback to original regex if tokenization fails
            return $this->cleanContentRegex($content);
        }
    }

    private function cleanContentRegex(string $content): string
    {
        // Original regex approach (fallback)
        $content = preg_replace('#/\*\*.*?\*/#s', '', $content) ?? $content;
        $content = preg_replace('#/\*.*?\*/#s', '', $content) ?? $content;
        $content = preg_replace('#^\s*//.*$#m', '', $content) ?? $content;
        $content = preg_replace('#(?<!:)//(?!/)[^\n]*#', '', $content) ?? $content;

        return preg_replace("/\n{3,}/", "\n\n", trim($content));
    }

    // ... (Existing helper methods: getFileContent, formatAsText, formatAsMarkdown, parseImports, etc.) ...
    // Assumed to be present from your original code, kept mostly the same below for brevity,
    // but ensure `getPhpFiles` is removed as we replaced it with Finder in `processDirectory`.

    private function getFileContent(string $filepath): string
    {
        // Optimization: Don't cache content in RAM if we aren't recursively parsing
        // AND we aren't doing JSON export. This saves huge memory on large dumps.
        if (!$this->option('recursive') && $this->format !== 'json') {
            return File::get($filepath);
        }

        if (!isset($this->fileContents[$filepath])) {
            $this->fileContents[$filepath] = File::get($filepath);
        }

        return $this->fileContents[$filepath];
    }

    // ... [Parsing Logic Methods: parseImports, extractUseStatement, etc. from original code] ...
    // NOTE: For brevity, assuming the parsing logic from your original snippet is pasted here.
    // It was robust enough for a regex/token hybrid approach.

    private function parseImports(string $content): array
    {
        $imports = [];
        $tokens = @token_get_all($content);
        if (!is_array($tokens)) {
            return [];
        }

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if (!is_array($token)) {
                continue;
            }

            if ($token[0] !== T_USE) {
                continue;
            }

            if (!$this->isClassUseStatement($tokens, $i)) {
                continue;
            }

            if ($this->isGroupedUseStatement($tokens, $i)) {
                foreach ($this->extractGroupedUseStatement($tokens, $i) as $gImport) {
                    if ($path = $this->resolveClassToFile($gImport)) {
                        $imports[] = $path;
                        $this->importsParsed++;
                    }
                }
            } elseif ($className = $this->extractUseStatement($tokens, $i)) {
                if ($path = $this->resolveClassToFile($className)) {
                    $imports[] = $path;
                    $this->importsParsed++;
                }
            }
        }

        return array_values(array_unique(array_filter($imports)));
    }

    // [Include isClassUseStatement, isGroupedUseStatement, extractGroupedUseStatement, extractUseStatement from original]

    private function isClassUseStatement(array $tokens, int $index): bool
    {
        // Simple lookbehind for closures
        for ($i = $index - 1; $i >= max(0, $index - 10); $i--) {
            $token = $tokens[$i];
            if (is_array($token) && $token[0] === T_FUNCTION) {
                return false;
            }

            if (!is_array($token)) {
                continue;
            }

            if (!in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                break;
            }
        }

        return true;
    }

    private function isGroupedUseStatement(array $tokens, int $index): bool
    {
        $lookAhead = 30; // Increased safety margin
        for ($i = $index; $i < min(count($tokens), $index + $lookAhead); $i++) {
            if (!is_array($tokens[$i]) && $tokens[$i] === '{') {
                return true;
            }

            if (!is_array($tokens[$i]) && $tokens[$i] === ';') {
                return false;
            }
        }

        return false;
    }

    private function extractGroupedUseStatement(array $tokens, int &$index): array
    {
        $base = '';
        $classes = [];
        $index++;

        while (isset($tokens[$index])) {
            $t = $tokens[$index];
            if (!is_array($t) && $t === '{') {
                break;
            }

            if (is_array($t) && in_array($t[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                $base .= $t[1];
            }

            $index++;
        }

        $base = trim($base, " \t\n\r\0\x0B\\");
        $index++;

        $curr = '';
        while (isset($tokens[$index])) {
            $t = $tokens[$index];
            if (!is_array($t) && $t === '}') {
                if ($curr !== '' && $curr !== '0') {
                    $classes[] = $base.'\\'.trim($curr);
                }

                break;
            }

            if (!is_array($t) && $t === ',') {
                if ($curr !== '' && $curr !== '0') {
                    $classes[] = $base.'\\'.trim($curr);
                    $curr = '';
                }

                $index++;

                continue;
            }

            if (is_array($t)) {
                if ($t[0] === T_AS) {
                    // Skip alias
                    $index++;
                    while (isset($tokens[$index]) && is_array($tokens[$index]) && $tokens[$index][0] !== T_STRING) {
                        $index++;
                    }

                    // skip whitespace
                    if (isset($tokens[$index]) && is_array($tokens[$index]) && $tokens[$index][0] === T_STRING) {
                        $index++;
                    }

                    // skip alias name
                    continue;
                }

                if (in_array($t[0], [T_STRING, T_NS_SEPARATOR])) {
                    $curr .= $t[1];
                }
            }

            $index++;
        }

        return $classes;
    }

    private function extractUseStatement(array $tokens, int &$index): ?string
    {
        $class = '';
        $index++;
        while (isset($tokens[$index])) {
            $t = $tokens[$index];
            if (is_array($t)) {
                if (in_array($t[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                    $class .= $t[1];
                } elseif ($t[0] === T_AS) {
                    // Skip alias
                    $index++;
                    while (isset($tokens[$index]) && (!is_array($tokens[$index]) || $tokens[$index][0] !== T_STRING)) {
                        $index++;
                    }

                    break;
                } elseif ($t[0] !== T_WHITESPACE) {
                    break;
                }
            } elseif ($t === ';' || $t === ',') {
                break;
            }

            $index++;
        }

        $class = trim($class, " \t\n\r\0\x0B\\");

        return $class ?: null;
    }

    private function resolveClassToFile(string $className): ?string
    {
        $className = ltrim($className, '\\');
        static $resolveCache = [];
        if (isset($resolveCache[$className])) {
            return $resolveCache[$className];
        }

        $mappings = $this->getPsr4Mappings();
        $path = $className;

        foreach ($mappings as $ns => $dir) {
            if (str_starts_with($className, (string) $ns)) {
                $path = $dir.substr($className, strlen((string) $ns));

                break;
            }
        }

        $fullPath = $this->basePath.'/'.str_replace('\\', '/', $path).'.php';
        $normalized = $this->normalizePath($fullPath);

        $result = ($normalized && File::exists($normalized) && !$this->shouldExcludeFile($normalized)) ? $normalized : null;

        return $resolveCache[$className] = $result;
    }

    private function getPsr4Mappings(): array
    {
        static $mappings = null;
        if ($mappings === null) {
            // Defaults
            $mappings = ['App\\' => 'app/'];

            // Try composer.json
            $composer = $this->basePath.'/composer.json';
            if (File::exists($composer)) {
                $json = json_decode(File::get($composer), true);
                if (isset($json['autoload']['psr-4'])) {
                    foreach ($json['autoload']['psr-4'] as $ns => $dir) {
                        // Normalize to string in case of array format
                        $dir = is_array($dir) ? $dir[0] : $dir;
                        $mappings[$ns] = rtrim((string) $dir, '/').'/';
                    }
                }
            }
        }

        return $mappings;
    }

    private function isFileAccessible(string $path): bool
    {
        return File::exists($path) && File::isFile($path);
    }

    private function shouldExcludeFile(string $path): bool
    {
        if (in_array($path, $this->excludedFiles, true)) {
            return true;
        }

        foreach ($this->excludedFolders as $excludedFolder) {
            if (str_starts_with($path, $excludedFolder.'/')) {
                return true;
            }
        }

        return false;
    }

    private function normalizePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (isset($this->pathCache[$path])) {
            return $this->pathCache[$path];
        }

        $real = @realpath($path);

        return $this->pathCache[$path] = $real ?: null;
    }

    // Scope checking
    private function isFileInScope(string $path): bool
    {
        $path = $this->normalizePath($path);

        return match ($this->scope) {
            'strict' => $this->isInStrictScope($path),
            'loose' => str_starts_with((string) $path, $this->basePath.'/app/'),
            'all' => true,
            default => true
        };
    }

    private function isInStrictScope(string $path): bool
    {
        if ($this->targetIsFile) {
            return true;
        }

        $target = $this->normalizePath($this->targetPath);

        return str_starts_with($path, $target.'/') || $path === $target;
    }

    private function filterImportsByScope(array $imports): array
    {
        return array_values(array_filter($imports, $this->isFileInScope(...)));
    }

    // Output Formatters
    private function formatAsText(string $rel, string $content): string
    {
        return sprintf("// ========== Start: %s ==========\n\n%s\n\n// ========== End: %s ==========\n\n", $rel, $content, $rel);
    }

    private function formatAsMarkdown(string $rel, string $content): string
    {
        return sprintf("## 📄 %s\n\n```php\n%s\n```\n\n---\n\n", $rel, $content);
    }

    // Utilities
    private function checkTimeout(): void
    {
        if ((time() - $this->startTime) > $this->timeoutSeconds) {
            throw new \RuntimeException(sprintf('Processing timeout (%ds). Processed %d files.', $this->timeoutSeconds, $this->filesProcessed));
        }
    }

    private function getRelativePath(string $path): string
    {
        return ltrim(str_replace($this->basePath.'/', '', $path), '/');
    }

    private function displayHeader(): void
    {
        $this->info('📦 PHP Codebase Dumper');
        $this->line('📂 Base: '.$this->basePath);
        $this->line('🎯 Target: '.$this->getRelativePath($this->targetPath));
        $this->newLine();
    }

    private function displayStatistics(): void
    {
        $this->info('✅ Dump Complete!');
        $this->line(sprintf('📦 Processed: %d files', $this->filesProcessed));
        $this->line('📑 Parts: '.$this->fileIndex);
        if ($this->format === 'json') {
            $this->exportAsJson();
        }

        $this->info(sprintf('💾 Output: %s/', $this->outputDir));
    }

    private function exportAsJson(): void
    {
        $data = [
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'base_path' => $this->basePath,
                'files_processed' => count($this->addedFiles),
            ],
            'files' => array_values(array_map(fn (array $f): array => [
                'path' => $f['path'],
                'content' => $f['content'] ?? $this->getFileContent($this->basePath.'/'.$f['path']),
            ], $this->addedFiles)),
        ];

        File::put($this->outputDir.'/dump.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->line('📄 JSON export: dump.json');
    }

    private function advanceProgress(): void
    {
        if ($this->progressBar instanceof \Symfony\Component\Console\Helper\ProgressBar) {
            $this->progressBar->advance();
        }
    }

    private function finishProgress(): void
    {
        if ($this->progressBar instanceof \Symfony\Component\Console\Helper\ProgressBar) {
            $this->progressBar->finish();
        } $this->newLine(2);
    }

    private function handleError(\Throwable $throwable): void
    {
        $this->error('⚠️ '.$throwable->getMessage());
    }

    private function logSkip(string $r): void
    {
        if ($this->debugMode) {
            $this->line('  Skipped: '.$r);
        }
    }

    private function displayFileAdded(string $p, int $d): void
    {
        $this->line(str_repeat('  ', min($d, 3)).('📄 '.$p));
    }
}
