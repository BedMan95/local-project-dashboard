<?php
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $path = $dir . "/" . $object;
                if (is_dir($path))
                    rrmdir($path);
                else
                    @unlink($path);
            }
        }
        @rmdir($dir);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create project
    if (isset($_POST['projectName'])) {
        header('Content-Type: application/json');
        $projectName = trim($_POST['projectName']);
        if (!preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $projectName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid project name.']);
            exit;
        }
        $dir = __DIR__ . '/' . $projectName;
        if (file_exists($dir)) {
            echo json_encode(['success' => false, 'error' => 'Project already exists.']);
            exit;
        }
        if (!mkdir($dir, 0755, true)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create project directory.']);
            exit;
        }
        $indexContent = "<?php\n// {$projectName} project\n?><!DOCTYPE html>\n<html lang=\"en\">\n<head>\n\t<meta charset=\"UTF-8\">\n\t<title>{$projectName}</title>\n</head>\n<body>\n\t<h1>Hello mate</h1>\n</body>\n</html>";
        if (file_put_contents("{$dir}/index.php", $indexContent) === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to create index.php.']);
            exit;
        }
        // Add to .gitignore if not already present
        $gitignore = __DIR__ . '/.gitignore';
        $alreadyIgnored = false;
        if (file_exists($gitignore)) {
            $lines = file($gitignore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (trim($line) === $projectName) {
                    $alreadyIgnored = true;
                    break;
                }
            }
        }
        if (!$alreadyIgnored) {
            file_put_contents($gitignore, $projectName . "\n", FILE_APPEND | LOCK_EX);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Rename project
    if (isset($_POST['renameProject'])) {
        $old = trim($_POST['oldName']);
        $new = trim($_POST['newName']);
        if (!preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $new)) {
            echo json_encode(['success' => false, 'error' => 'Invalid new name.']);
            exit;
        }
        if (!is_dir($old)) {
            echo json_encode(['success' => false, 'error' => 'Original folder not found.']);
            exit;
        }
        if (is_dir($new)) {
            echo json_encode(['success' => false, 'error' => 'Target name already exists.']);
            exit;
        }
        if (rename($old, $new)) {
            // Update .gitignore: remove old, add new
            $gitignore = __DIR__ . '/.gitignore';
            $lines = [];
            if (file_exists($gitignore)) {
                $lines = file($gitignore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                // Remove old name
                $lines = array_filter($lines, function ($line) use ($old) {
                    return trim($line) !== $old;
                });
            }
            // Add new name if not present
            if (!in_array($new, $lines)) {
                $lines[] = $new;
            }
            file_put_contents($gitignore, implode("\n", $lines) . "\n", LOCK_EX);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Rename failed.']);
        }
        exit;
    }

    // Delete project
    if (isset($_POST['deleteProject'])) {
        $target = trim($_POST['name']);
        if (!is_dir($target)) {
            echo json_encode(['success' => false, 'error' => 'Folder not found.']);
            exit;
        }

        // Use shell command for recursive delete
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        if ($isWindows) {
            // Windows: rmdir /s /q
            shell_exec('rmdir /s /q ' . escapeshellarg($target));
        } else {
            // Linux/macOS: rm -rf
            shell_exec('rm -rf ' . escapeshellarg($target));
        }

        // Remove from .gitignore
        $gitignore = __DIR__ . '/.gitignore';
        if (file_exists($gitignore)) {
            $lines = file($gitignore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lines = array_filter($lines, function ($line) use ($target) {
                return trim($line) !== $target;
            });
            file_put_contents($gitignore, implode("\n", $lines) . "\n", LOCK_EX);
        }

        $maxWait = 10; // 10 x 100ms = 1s
        while ($maxWait-- > 0 && file_exists($target)) {
            usleep(100000); // 100ms
        }

        if (file_exists($target)) {
            echo json_encode(['success' => false, 'error' => 'Some files could not be deleted due to permission issues.']);
        } else {
            echo json_encode(['success' => true]);
        }
        exit;
    }

    // Clone from GitHub
    if (isset($_POST['cloneGithub'])) {
        $url = trim($_POST['githubUrl']);
        $folder = trim($_POST['cloneFolder']);
        if (!preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $folder)) {
            echo json_encode(['success' => false, 'error' => 'Invalid folder name.']);
            exit;
        }
        if (
            !preg_match('#^(https://([^@]+@)?github\.com/[^/]+/[^/]+(\.git)?|git@github\.com:[^/]+/[^/]+(\.git)?)$#i', $url)
        ) {
            echo json_encode(['success' => false, 'error' => 'Invalid GitHub URL.']);
            exit;
        }
        if (is_dir($folder)) {
            echo json_encode(['success' => false, 'error' => 'Folder already exists.']);
            exit;
        }
        $jobId = uniqid('clone_', true);
        $logFile = __DIR__ . "/.clone_log_{$jobId}.txt";
        $cmd = sprintf(
            'git clone --progress --depth=1 %s %s > %s 2>&1 & echo $!',
            escapeshellarg($url),
            escapeshellarg($folder),
            escapeshellarg($logFile)
        );
        $pid = trim(shell_exec($cmd));
        echo json_encode(['success' => true, 'jobId' => $jobId]);
        exit;
    }

    // Clone progress
    if (isset($_POST['cloneProgress'])) {
        $jobId = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $_POST['jobId']);
        $logFile = __DIR__ . "/.clone_log_{$jobId}.txt";
        $done = false;
        $percent = 0;
        $log = '';
        if (file_exists($logFile)) {
            $log = file_get_contents($logFile);
            if (preg_match('/Checking out files: +(\d+)%/', $log, $m)) {
                $percent = (int) $m[1];
            } elseif (preg_match('/Receiving objects: +(\d+)%/', $log, $m)) {
                $percent = (int) $m[1];
            }
            if (strpos($log, 'done.') !== false || strpos($log, 'Checking connectivity... done.') !== false) {
                $percent = 100;
                $done = true;
                // Add folder to .gitignore if not already present
                $folder = isset($_POST['cloneFolder']) ? $_POST['cloneFolder'] : null;
                if (!$folder && preg_match("/Cloning into '([^']+)'/", $log, $m2)) {
                    $folder = $m2[1];
                }
                if ($folder) {
                    $gitignore = __DIR__ . '/.gitignore';
                    $alreadyIgnored = false;
                    if (file_exists($gitignore)) {
                        $lines = file($gitignore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                        foreach ($lines as $line) {
                            if (trim($line) === $folder) {
                                $alreadyIgnored = true;
                                break;
                            }
                        }
                    }
                    if (!$alreadyIgnored) {
                        file_put_contents($gitignore, $folder . "\n", FILE_APPEND | LOCK_EX);
                    }
                }
                @unlink($logFile);
            }
        }
        echo json_encode(['log' => $log, 'percent' => $percent, 'done' => $done]);
        exit;
    }

    if (isset($_POST['saveLinks'])) {
        $links = isset($_POST['links']) ? $_POST['links'] : array();
        $file = __DIR__ . "/link.txt";
        $content = implode("\n", array_map("trim", $links));
        if (file_put_contents($file, $content) !== false) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Cannot write file"]);
        }
        exit;
    }
}
