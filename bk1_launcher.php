<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Book 1 - BK1.EXE Launcher</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        h1 {
            color: #333;
            margin-top: 0;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .info-panel {
            background: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .info-panel h3 {
            margin-top: 0;
            color: #17a2b8;
        }

        .status-box {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .status-running {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .status-not-running {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.2s;
            margin-right: 10px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .button-secondary {
            background: #6c757d;
        }

        .output-box {
            background: #2d2d2d;
            color: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            margin-top: 20px;
            max-height: 300px;
            overflow-y: auto;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }

        .checkbox-label input {
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Computer-Based Training - Book 1</h1>
        <h3>BK1.EXE Launcher</h3>

        <?php
        $exePath = 'E:\\Computer-Based Training\\ALC_BOOK_1\\BK1.EXE';
        $exeDir = 'E:\\Computer-Based Training\\ALC_BOOK_1\\';

        $fileExists = file_exists($exePath);

        function isBK1Running(): bool
        {
            $output = [];
            exec('tasklist /FI "IMAGENAME eq BK1.EXE" 2>&1', $output);
            foreach ($output as $line) {
                if (strpos($line, 'BK1.EXE') !== false) {
                    return true;
                }
            }

            return false;
        }

        $isRunning = isBK1Running();
        ?>

        <div class="status-box <?php echo $isRunning ? 'status-running' : 'status-not-running'; ?>">
            <strong>Process Status:</strong>
            <?php echo $isRunning ? 'BK1.EXE is currently running' : 'BK1.EXE is not running'; ?>
        </div>

        <form method="POST" action="">
            <h3>Launch Controls</h3>

            <div class="checkbox-label">
                <input type="checkbox" name="run_background" id="run_background">
                <label for="run_background">Run in background (non-blocking)</label>
            </div>

            <div class="checkbox-label">
                <input type="checkbox" name="wait_for_completion" id="wait_for_completion" checked>
                <label for="wait_for_completion">Wait for completion and show output</label>
            </div>

            <div>
                <button type="submit" name="action" value="launch" class="button" <?php echo !$fileExists ? 'disabled' : ''; ?>>
                    Launch BK1.EXE
                </button>

                <button type="submit" name="action" value="check" class="button button-secondary">
                    Refresh Status
                </button>
            </div>

            <?php if ($isRunning): ?>
                <p style="color: #856404; margin-top: 10px;">
                    Note: Application is already running. Launching again may open another instance.
                </p>
            <?php endif; ?>
        </form>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['action']) && $_POST['action'] === 'launch' && $fileExists) {
                echo '<div class="output-box">';
                echo '<strong>Execution Log:</strong><br><br>';

                $runBackground = isset($_POST['run_background']);

                try {
                    $cmd = '"' . $exePath . '"';
                    $currentDir = getcwd();

                    echo '> Changing to directory: ' . htmlspecialchars($exeDir) . '<br>';
                    chdir($exeDir);

                    if ($runBackground) {
                        echo '> Starting BK1.EXE in background...<br>';
                        pclose(popen('start /B ' . $cmd . ' > NUL 2>&1', 'r'));
                        echo "<span style='color: #28a745;'>BK1.EXE started successfully</span><br>";
                        echo '> PID: (running in background)<br>';
                    } else {
                        echo '> Executing: ' . htmlspecialchars($cmd) . '<br>';

                        $output = [];
                        $returnCode = 0;

                        $startTime = microtime(true);
                        exec($cmd . ' 2>&1', $output, $returnCode);
                        $execTime = microtime(true) - $startTime;

                        echo '> Execution time: ' . round($execTime, 2) . " seconds<br>";
                        echo '> Return code: ' . $returnCode . '<br>';

                        if ($returnCode === 0) {
                            echo "<span style='color: #28a745;'>BK1.EXE completed successfully</span><br>";
                        } else {
                            echo "<span style='color: #dc3545;'>BK1.EXE returned error code: " . $returnCode . '</span><br>';
                        }

                        echo '<br><strong>Program Output:</strong><br>';
                        echo "<pre style='color: #f0f0f0;'>" . htmlspecialchars(implode("\n", $output)) . '</pre>';
                    }

                    chdir((string) $currentDir);
                } catch (Throwable $e) {
                    echo "<span style='color: #dc3545;'>Error: " . htmlspecialchars($e->getMessage()) . '</span><br>';
                }

                echo '</div>';
            } elseif (isset($_POST['action']) && $_POST['action'] === 'check') {
                echo '<script>window.location.reload();</script>';
            }
        }
        ?>

        <div class="info-panel" style="margin-top: 20px;">
            <h3>Help and Information</h3>
            <ul>
                <li><strong>Run in background:</strong> Starts BK1.EXE without waiting</li>
                <li><strong>Wait for completion:</strong> Shows output after BK1.EXE closes</li>
                <?php if (!$fileExists): ?>
                    <li style="color: red;"><strong>BK1.EXE not found at the specified location.</strong></li>
                <?php endif; ?>
            </ul>

            <h4>PHP Configuration:</h4>
            <?php
            $execFunctions = ['exec', 'shell_exec', 'system', 'popen'];
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));

            foreach ($execFunctions as $func) {
                $available = !in_array($func, $disabled, true);
                echo "<div style='color: " . ($available ? 'green' : 'red') . ";'>";
                echo ($available ? 'OK' : 'DISABLED') . ' ' . $func . ' is ' . ($available ? 'available' : 'disabled');
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
