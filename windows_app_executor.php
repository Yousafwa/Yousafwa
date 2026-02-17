<?php
declare(strict_types=1);

/**
 * Windows Application Executor.
 *
 * This helper executes applications from a configured base directory
 * and blocks execution outside that directory.
 */
class WindowsAppExecutor
{
    private string $baseDirectory;

    /** @var string[] */
    private array $allowedExtensions = ['exe', 'bat', 'cmd', 'com', 'msi'];

    public function __construct(string $baseDirectory = 'E:\\Computer-Based Training\\ALC_BOOK_1\\')
    {
        $this->baseDirectory = $this->normalizeDirectory($baseDirectory);
    }

    /**
     * Execute an application inside the configured base directory.
     *
     * @param string   $appPath           Relative or absolute application path.
     * @param string[] $arguments         Command line arguments.
     * @param bool     $waitForCompletion Wait until execution completes.
     *
     * @return array<string, mixed>
     */
    public function execute(string $appPath, array $arguments = [], bool $waitForCompletion = true): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            throw new RuntimeException('WindowsAppExecutor can only run on Windows.');
        }

        $fullPath = $this->resolvePath($appPath);
        $this->assertPathIsAllowed($fullPath);
        $this->assertExecutableFile($fullPath);

        $command = $this->buildCommand($fullPath, $arguments);

        if (!$waitForCompletion) {
            // Run non-blocking using cmd/start on Windows.
            $backgroundCommand = 'cmd /c start "" /B ' . $command . ' > NUL 2>&1';
            pclose(popen($backgroundCommand, 'r'));

            return [
                'status' => 'started',
                'message' => 'Application started in background',
                'command' => $backgroundCommand,
            ];
        }

        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);

        return [
            'output' => implode(PHP_EOL, $output),
            'code' => $returnCode,
            'success' => $returnCode === 0,
            'command' => $command,
        ];
    }

    /**
     * List executable files in a subdirectory of the base directory.
     *
     * @param string $path Relative or absolute directory path.
     *
     * @return array<int, array<string, int|string>>
     */
    public function listExecutables(string $path = ''): array
    {
        $searchPath = $path === '' ? $this->baseDirectory : $this->resolvePath($path, false);
        $this->assertPathIsAllowed($searchPath);

        if (!is_dir($searchPath)) {
            return [];
        }

        $files = scandir($searchPath);
        if ($files === false) {
            return [];
        }

        $executables = [];
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = $this->joinPaths($searchPath, $file);
            if (!is_file($fullPath)) {
                continue;
            }

            $ext = strtolower((string) pathinfo($fullPath, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedExtensions, true)) {
                continue;
            }

            $executables[] = [
                'name' => $file,
                'path' => $fullPath,
                'size' => (int) filesize($fullPath),
                'modified' => date('Y-m-d H:i:s', (int) filemtime($fullPath)),
            ];
        }

        return $executables;
    }

    private function resolvePath(string $path, bool $mustExist = true): string
    {
        $candidate = preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)
            ? $path
            : $this->joinPaths($this->baseDirectory, $path);

        $normalized = $this->normalizePath($candidate);

        if ($mustExist && !file_exists($normalized)) {
            throw new RuntimeException('File not found: ' . $normalized);
        }

        return $normalized;
    }

    private function assertPathIsAllowed(string $path): void
    {
        $base = $this->normalizeDirectory($this->baseDirectory);
        $normalizedPath = $this->normalizePath($path);

        $baseReal = realpath($base);
        $pathReal = file_exists($normalizedPath) ? realpath($normalizedPath) : false;

        $baseToCheck = strtolower($this->normalizeDirectory($baseReal !== false ? $baseReal : $base));
        $pathToCheck = strtolower($this->normalizePath($pathReal !== false ? $pathReal : $normalizedPath));

        if (!str_starts_with($pathToCheck, $baseToCheck)) {
            throw new RuntimeException(
                'Security Error: only executables from ' . $this->baseDirectory . ' are allowed.'
            );
        }
    }

    private function assertExecutableFile(string $path): void
    {
        if (!is_file($path)) {
            throw new RuntimeException('Not a file: ' . $path);
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw new RuntimeException('Only executable files are allowed.');
        }
    }

    /**
     * @param string[] $arguments
     */
    private function buildCommand(string $fullPath, array $arguments): string
    {
        $command = '"' . str_replace('"', '\"', $fullPath) . '"';
        foreach ($arguments as $arg) {
            $command .= ' ' . escapeshellarg((string) $arg);
        }

        return $command;
    }

    private function joinPaths(string $left, string $right): string
    {
        $leftTrimmed = rtrim($left, "\\/ \t\n\r\0\x0B");
        $rightTrimmed = ltrim($right, "\\/ \t\n\r\0\x0B");

        return $leftTrimmed . '\\' . $rightTrimmed;
    }

    private function normalizeDirectory(string $path): string
    {
        $normalized = $this->normalizePath($path);
        return rtrim($normalized, '\\') . '\\';
    }

    private function normalizePath(string $path): string
    {
        $path = str_replace('/', '\\', $path);
        return preg_replace('/\\\\+/', '\\\\', $path) ?? $path;
    }
}

// Optional CLI usage example:
// php windows_app_executor.php "bk1.exe" "arg1" "arg2"
if (PHP_SAPI === 'cli' && isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    try {
        // Change this directory if your executables are on a different path.
        $executor = new WindowsAppExecutor('E:\\Computer-Based Training\\ALC_BOOK_1\\');

        global $argv;
        $target = $argv[1] ?? 'bk1.exe';
        $args = array_slice($argv, 2);

        $result = $executor->execute($target, $args, true);
        print_r($result);
    } catch (Throwable $e) {
        fwrite(STDERR, 'Error: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
