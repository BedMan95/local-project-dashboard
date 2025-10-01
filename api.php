<?php
function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                $sub = $dir . DIRECTORY_SEPARATOR . $object;
                if (is_dir($sub)) {
                    rrmdir($sub);
                } else {
                    @unlink($sub);
                }

            }
        }
        return @rmdir($dir);
    } elseif (is_file($dir)) {
        return @unlink($dir);
    }
    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create project
    if (isset($_POST['projectName'])) {
        header('Content-Type: application/json');
        $projectName = trim($_POST['projectName']);
        if (! preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $projectName)) {
            echo json_encode(['success' => false, 'error' => 'Invalid project name.']);
            exit;
        }
        $dir = __DIR__ . '/' . $projectName;
        if (file_exists($dir)) {
            echo json_encode(['success' => false, 'error' => 'Project already exists.']);
            exit;
        }
        if (! mkdir($dir, 0755, true)) {
            echo json_encode(['success' => false, 'error' => 'Failed to create project directory.']);
            exit;
        }
        $indexContent = "<?php\n// {$projectName} project\n?><!DOCTYPE html>\n<html lang=\"en\">\n<head>\n\t<meta charset=\"UTF-8\">\n\t<title>{$projectName}</title>\n</head>\n<body>\n\t<h1>Hello mate</h1>\n</body>\n</html>";
        if (file_put_contents("{$dir}/index.php", $indexContent) === false) {
            echo json_encode(['success' => false, 'error' => 'Failed to create index.php.']);
            exit;
        }
        // Add to .gitignore if not already present
        $gitignore      = __DIR__ . '/.gitignore';
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
        if (! $alreadyIgnored) {
            file_put_contents($gitignore, $projectName . "\n", FILE_APPEND | LOCK_EX);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    // Rename project
    if (isset($_POST['renameProject'])) {
        $old = trim($_POST['oldName']);
        $new = trim($_POST['newName']);
        if (! preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $new)) {
            echo json_encode(['success' => false, 'error' => 'Invalid new name.']);
            exit;
        }
        if (! is_dir($old)) {
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
            $lines     = [];
            if (file_exists($gitignore)) {
                $lines = file($gitignore, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                // Remove old name
                $lines = array_filter($lines, function ($line) use ($old) {
                    return trim($line) !== $old;
                });
            }
            // Add new name if not present
            if (! in_array($new, $lines)) {
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
        if (! is_dir($target)) {
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
        $url    = trim($_POST['githubUrl']);
        $folder = trim($_POST['cloneFolder']);
        if (! preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $folder)) {
            echo json_encode(['success' => false, 'error' => 'Invalid folder name.']);
            exit;
        }
        if (
            ! preg_match('#^(https://([^@]+@)?github\.com/[^/]+/[^/]+(\.git)?|git@github\.com:[^/]+/[^/]+(\.git)?)$#i', $url)
        ) {
            echo json_encode(['success' => false, 'error' => 'Invalid GitHub URL.']);
            exit;
        }
        if (is_dir($folder)) {
            echo json_encode(['success' => false, 'error' => 'Folder already exists.']);
            exit;
        }
        $jobId   = uniqid('clone_', true);
        $logFile = __DIR__ . "/.clone_log_{$jobId}.txt";
        $cmd     = sprintf(
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
        $jobId   = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $_POST['jobId']);
        $logFile = __DIR__ . "/.clone_log_{$jobId}.txt";
        $done    = false;
        $percent = 0;
        $log     = '';
        if (file_exists($logFile)) {
            $log = file_get_contents($logFile);
            if (preg_match('/Checking out files: +(\d+)%/', $log, $m)) {
                $percent = (int) $m[1];
            } elseif (preg_match('/Receiving objects: +(\d+)%/', $log, $m)) {
                $percent = (int) $m[1];
            }
            if (strpos($log, 'done.') !== false || strpos($log, 'Checking connectivity... done.') !== false) {
                $percent = 100;
                $done    = true;
                // Add folder to .gitignore if not already present
                $folder = isset($_POST['cloneFolder']) ? $_POST['cloneFolder'] : null;
                if (! $folder && preg_match("/Cloning into '([^']+)'/", $log, $m2)) {
                    $folder = $m2[1];
                }
                if ($folder) {
                    $gitignore      = __DIR__ . '/.gitignore';
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
                    if (! $alreadyIgnored) {
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
        $links   = isset($_POST['links']) ? $_POST['links'] : [];
        $file    = __DIR__ . "/link.txt";
        $content = implode("\n", array_map("trim", $links));
        if (file_put_contents($file, $content) !== false) {
            echo json_encode(["success" => true]);
        } else {
            echo json_encode(["success" => false, "error" => "Cannot write file"]);
        }
        exit;
    }

    if (isset($_POST['saveFile'])) {
        $folder  = isset($_POST['folder']) ? $_POST['folder'] : '.';
        $file    = isset($_POST['file']) ? $_POST['file'] : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';

        $baseDir = realpath(__DIR__);
        $target  = realpath($baseDir . '/' . $folder);

        if (! $target || strpos($target, $baseDir) !== 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid folder']);
            exit;
        }

        $path = $target . '/' . $file;

        // kalau folder ada tapi file belum ada, cek foldernya bisa ditulis
        if ((! file_exists($path) && ! is_writable($target)) ||
            (file_exists($path) && ! is_writable($path))) {
            echo json_encode(['success' => false, 'error' => 'File not writable']);
            exit;
        }

        file_put_contents($path, $content);
        echo json_encode(['success' => true]);
        exit;
    }

// create file
    if (isset($_POST['createFile'])) {
        $folder = $_POST['folder'];
        $name   = basename($_POST['name']);
        $path   = __DIR__ . '/' . $folder . '/' . $name;
        if (file_exists($path)) {
            echo json_encode(['success' => false, 'error' => 'File already exists']);
            exit;
        }
        file_put_contents($path, ""); // kosong
        echo json_encode(['success' => true]);
        exit;
    }

// create folder
    if (isset($_POST['createFolder'])) {
        $folder = $_POST['folder'];
        $name   = basename($_POST['name']);
        $path   = __DIR__ . '/' . $folder . '/' . $name;
        if (file_exists($path)) {
            echo json_encode(['success' => false, 'error' => 'Folder already exists']);
            exit;
        }
        mkdir($path, 0777, true);
        echo json_encode(['success' => true]);
        exit;
    }
}

// list directory
if (isset($_GET['listFiles'])) {
    $folder  = isset($_GET['folder']) ? $_GET['folder'] : '.'; // default ke current folder
    $baseDir = realpath(__DIR__);                              // root project dir

    // normalisasi path, hapus ./ di depan
    $folder = ltrim($folder, './');
    $folder = trim($folder, '/');

    $targetDir = realpath($baseDir . '/' . $folder);

    // cek validitas path
    if (! $targetDir || strpos($targetDir, $baseDir) !== 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid folder path']);
        exit;
    }

    if (! is_dir($targetDir)) {
        echo json_encode(['success' => false, 'error' => 'Not a directory: ' . $folder]);
        exit;
    }

    $entries = [];

    foreach (scandir($targetDir) as $f) {
        if ($f === '.') {
            continue;
        }

        if ($f === '..') {
            // hanya tambahkan kalau bukan root
            if ($folder !== '' && $folder !== '.' && $folder !== '/') {
                $parent = dirname($folder);

                // normalisasi supaya tidak keluar root
                if ($parent === '' || $parent === '.' || $parent === '/' || $parent === '\\') {
                    $parent = '.';
                }

                $entries[] = [
                    'name' => '..',
                    'type' => 'dir',
                    'path' => $parent,
                ];
            }
            continue;
        }

        $relPath = ($folder === '' || $folder === '.') ? $f : $folder . '/' . $f;

        $entries[] = [
            'name' => $f,
            'type' => is_dir($targetDir . '/' . $f) ? 'dir' : 'file',
            'path' => $relPath, // ✅ ini penting
        ];
    }

    echo json_encode([
        'success' => true,
        'entries' => $entries,
        'current' => $folder === '' ? '.' : $folder,
    ]);
    exit;
}

if (isset($_GET['getFile'])) {
    $folder = isset($_GET['folder']) ? $_GET['folder'] : '.';
    $file   = isset($_GET['file']) ? $_GET['file'] : '';

    $baseDir = realpath(__DIR__);
    $target  = realpath($baseDir . '/' . $folder . '/' . $file);

    // keamanan: pastikan file masih di dalam baseDir
    if (! $target || strpos($target, $baseDir) !== 0 || ! is_file($target)) {
        echo json_encode(['success' => false, 'error' => 'File not found']);
        exit;
    }
    echo json_encode([
        'success' => true,
        'content' => file_get_contents($target),
    ]);
    exit;
}

if (! empty($_POST['deleteEntry'])) {
    $path = $_POST['path'];
    if (! $path || $path === '.' || $path === '..') {
        echo json_encode(['success' => false, 'error' => 'Invalid path']);
        exit;
    }

    $result = rrmdir($path);
    echo json_encode(['success' => $result, 'error' => $result ? '' : 'Failed to delete']);
    exit;
}

if (! empty($_POST['renameEntry'])) {
    $path    = isset($_POST['path']) ? $_POST['path'] : '';
    $newName = isset($_POST['newName']) ? trim($_POST['newName']) : '';

    if (! $path || ! $newName) {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
        exit;
    }

    $dir     = dirname($path);
    $newPath = $dir . DIRECTORY_SEPARATOR . $newName;

    if (file_exists($newPath)) {
        echo json_encode(['success' => false, 'error' => 'File/Folder dengan nama tersebut sudah ada']);
        exit;
    }

    $result = @rename($path, $newPath);

    echo json_encode(['success' => $result, 'error' => $result ? '' : 'Failed to rename']);
    exit;
}
