<?php
// Constants
define('BASE_DIR', __DIR__);
define('GITIGNORE_FILE', BASE_DIR . '/.gitignore');
define('VALID_NAME_PATTERN', '/^[A-Za-z0-9_\-\. ]{1,32}$/'); // Allow dots (.)

// Utility function to recursively delete a directory or file
function rrmdir($path)
{
    if (is_dir($path)) {
        $objects = scandir($path);
        foreach ($objects as $object) {
            if ($object !== '.' && $object !== '..') {
                $subPath = $path . DIRECTORY_SEPARATOR . $object;
                if (is_dir($subPath)) {
                    rrmdir($subPath);
                } else {
                    @unlink($subPath);
                }
            }
        }
        return @rmdir($path);
    }
    return is_file($path) ? @unlink($path) : false;
}

// Utility function to update .gitignore
function updateGitignore($add = null, $remove = null)
{
    $lines = file_exists(GITIGNORE_FILE) ? file(GITIGNORE_FILE, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];

    if ($remove) {
        $filteredLines = [];
        foreach ($lines as $line) {
            if (trim($line) !== $remove) {
                $filteredLines[] = $line;
            }
        }
        $lines = $filteredLines;
    }

    if ($add && ! in_array($add, $lines)) {
        $lines[] = $add;
    }

    file_put_contents(GITIGNORE_FILE, implode("\n", $lines) . "\n", LOCK_EX);
}

// Utility function to send JSON response
function sendJsonResponse($success, $data = [], $error = '')
{
    header('Content-Type: application/json');
    $response = ['success' => $success];
    if ($error) {
        $response['error'] = $error;
    }
    $response = array_merge($response, $data);
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create new project
    if (isset($_POST['projectName'])) {
        $projectName = trim($_POST['projectName']);
        if (! preg_match(VALID_NAME_PATTERN, $projectName)) {
            sendJsonResponse(false, [], 'Invalid project name.');
        }

        $dir = BASE_DIR . '/' . $projectName;
        if (file_exists($dir)) {
            sendJsonResponse(false, [], 'Project already exists.');
        }

        if (! mkdir($dir, 0755, true)) {
            sendJsonResponse(false, [], 'Failed to create project directory.');
        }

        $indexContent = "<?php\n// {$projectName} project\n?><!DOCTYPE html>\n<html lang=\"en\">\n<head>\n\t<meta charset=\"UTF-8\">\n\t<title>{$projectName}</title>\n</head>\n<body>\n\t<h1>Hello mate</h1>\n</body>\n</html>";
        if (file_put_contents("{$dir}/index.php", $indexContent) === false) {
            sendJsonResponse(false, [], 'Failed to create index.php.');
        }

        updateGitignore($projectName);
        sendJsonResponse(true);
    }

    // Rename project
    if (isset($_POST['renameProject'])) {
        $oldName = trim($_POST['oldName']);
        $newName = trim($_POST['newName']);

        if (! preg_match(VALID_NAME_PATTERN, $newName)) {
            sendJsonResponse(false, [], 'Invalid new name.');
        }

        if (! is_dir($oldName)) {
            sendJsonResponse(false, [], 'Original folder not found.');
        }

        if (is_dir($newName)) {
            sendJsonResponse(false, [], 'Target name already exists.');
        }

        if (rename($oldName, $newName)) {
            updateGitignore($newName, $oldName);
            sendJsonResponse(true);
        } else {
            sendJsonResponse(false, [], 'Rename failed.');
        }
    }

    // Delete project
    if (isset($_POST['deleteProject'])) {
        $target = trim($_POST['name']);
        if (! is_dir($target)) {
            sendJsonResponse(false, [], 'Folder not found.');
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $cmd       = $isWindows ? 'rmdir /s /q ' : 'rm -rf ';
        shell_exec($cmd . escapeshellarg($target));

        $maxWait = 10;
        while ($maxWait-- > 0 && file_exists($target)) {
            usleep(100000);
        }

        if (file_exists($target)) {
            sendJsonResponse(false, [], 'Some files could not be deleted due to permission issues.');
        }

        updateGitignore(null, $target);
        sendJsonResponse(true);
    }

    // Clone from GitHub
    if (isset($_POST['cloneGithub'])) {
        $url    = trim($_POST['githubUrl']);
        $folder = trim($_POST['cloneFolder']);

        if (! preg_match(VALID_NAME_PATTERN, $folder)) {
            sendJsonResponse(false, [], 'Invalid folder name.');
        }

        if (! preg_match('#^(https://([^@]+@)?github\.com/[^/]+/[^/]+(\.git)?|git@github\.com:[^/]+/[^/]+(\.git)?)$#i', $url)) {
            sendJsonResponse(false, [], 'Invalid GitHub URL.');
        }

        if (is_dir($folder)) {
            sendJsonResponse(false, [], 'Folder already exists.');
        }

        // Add clone folder to .gitignore before cloning
        updateGitignore($folder);

        $jobId   = uniqid('clone_', true);
        $logFile = BASE_DIR . "/.clone_log_{$jobId}.txt";
        $cmd     = sprintf('git clone --progress --depth=1 %s %s > %s 2>&1 & echo $!',
            escapeshellarg($url), escapeshellarg($folder), escapeshellarg($logFile));

        $pid = trim(shell_exec($cmd));
        sendJsonResponse(true, ['jobId' => $jobId]);
    }

    // Clone progress
    if (isset($_POST['cloneProgress'])) {
        $jobId   = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $_POST['jobId']);
        $logFile = BASE_DIR . "/.clone_log_{$jobId}.txt";
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
                @unlink($logFile);
            }
        }

        sendJsonResponse(true, ['log' => $log, 'percent' => $percent, 'done' => $done]);
    }

    // Pull from GitHub
    if (isset($_POST['pullGithub'])) {
        $folder = trim($_POST['folder']);

        if (! preg_match(VALID_NAME_PATTERN, $folder)) {
            sendJsonResponse(false, [], 'Invalid folder name.');
        }

        $gitDir = BASE_DIR . '/' . $folder . '/.git';
        if (! is_dir($gitDir)) {
            sendJsonResponse(false, [], 'Not a Git repository: .git folder not found.');
        }

        $jobId   = uniqid('pull_', true);
        $logFile = BASE_DIR . "/.pull_log_{$jobId}.txt";
        $cmd     = sprintf('cd %s && git pull --progress > %s 2>&1 & echo $!',
            escapeshellarg(BASE_DIR . '/' . $folder),
            escapeshellarg($logFile));

        $pid = trim(shell_exec($cmd));
        if (! $pid) {
            sendJsonResponse(false, [], 'Failed to start git pull process.');
        }

        sendJsonResponse(true, ['jobId' => $jobId]);
    }

    // Pull progress
    if (isset($_POST['pullProgress'])) {
        $jobId   = preg_replace('/[^a-zA-Z0-9_\.\-]/', '', $_POST['jobId']);
        $logFile = BASE_DIR . "/.pull_log_{$jobId}.txt";
        $done    = false;
        $percent = 0;
        $log     = '';

        if (file_exists($logFile)) {
            $log = file_get_contents($logFile);
            if (preg_match('/Receiving objects: +(\d+)%/', $log, $m)) {
                $percent = (int) $m[1];
            } elseif (preg_match('/Updating files: +(\d+)%/', $log, $m)) {
                $percent = (int) $m[1];
            }

            if (strpos($log, 'Already up to date.') !== false ||
                strpos($log, 'Fast-forward') !== false ||
                strpos($log, 'done.') !== false) {
                $percent = 100;
                $done    = true;
                @unlink($logFile);
            } elseif (strpos($log, 'error:') !== false ||
                strpos($log, 'fatal:') !== false) {
                $done  = true;
                $error = 'Git pull failed: ' . $log;
                @unlink($logFile);
                sendJsonResponse(false, ['log' => $log, 'percent' => $percent, 'done' => $done], $error);
            }
        }

        sendJsonResponse(true, ['log' => $log, 'percent' => $percent, 'done' => $done]);
    }

    // Save links
    if (isset($_POST['saveLinks'])) {
        $links   = isset($_POST['links']) ? $_POST['links'] : [];
        $file    = BASE_DIR . '/link.txt';
        $content = implode("\n", array_map('trim', $links));

        if (file_put_contents($file, $content) !== false) {
            sendJsonResponse(true);
        } else {
            sendJsonResponse(false, [], 'Cannot write file');
        }
    }

    // Save file
    if (isset($_POST['saveFile'])) {
        $folder  = isset($_POST['folder']) ? $_POST['folder'] : '.';
        $file    = isset($_POST['file']) ? $_POST['file'] : '';
        $content = isset($_POST['content']) ? $_POST['content'] : '';

        $baseDir = realpath(BASE_DIR);
        $target  = realpath($baseDir . '/' . $folder);

        if (! $target || strpos($target, $baseDir) !== 0) {
            sendJsonResponse(false, [], 'Invalid folder');
        }

        $path = $target . '/' . $file;
        if ((! file_exists($path) && ! is_writable($target)) || (file_exists($path) && ! is_writable($path))) {
            sendJsonResponse(false, [], 'File not writable');
        }

        file_put_contents($path, $content);
        sendJsonResponse(true);
    }

    // Create file
    if (isset($_POST['createFile'])) {
        $folder = $_POST['folder'];
        $name   = basename($_POST['name']);
        $path   = BASE_DIR . '/' . $folder . '/' . $name;

        if (file_exists($path)) {
            sendJsonResponse(false, [], 'File already exists');
        }

        file_put_contents($path, '');
        sendJsonResponse(true);
    }

    // Create folder
    if (isset($_POST['createFolder'])) {
        $folder = $_POST['folder'];
        $name   = basename($_POST['name']);
        $path   = BASE_DIR . '/' . $folder . '/' . $name;

        if (file_exists($path)) {
            sendJsonResponse(false, [], 'Folder already exists');
        }

        mkdir($path, 0777, true);
        sendJsonResponse(true);
    }

    // Delete file or folder
    if (isset($_POST['deleteEntry'])) {
        $path = $_POST['path'];
        if (! $path || $path === '.' || $path === '..') {
            sendJsonResponse(false, [], 'Invalid path');
        }

        $result = rrmdir($path);
        sendJsonResponse($result, [], $result ? '' : 'Failed to delete');
    }

    // Rename file or folder
    if (isset($_POST['renameEntry'])) {
        $path    = isset($_POST['path']) ? $_POST['path'] : '';
        $newName = isset($_POST['newName']) ? trim($_POST['newName']) : '';

        if (! $path || ! $newName) {
            sendJsonResponse(false, [], 'Invalid input');
        }

        $dir     = dirname($path);
        $newPath = $dir . DIRECTORY_SEPARATOR . $newName;

        if (file_exists($newPath)) {
            sendJsonResponse(false, [], 'File/Folder dengan nama tersebut sudah ada');
        }

        $result = @rename($path, $newPath);
        sendJsonResponse($result, [], $result ? '' : 'Failed to rename');
    }
}

// List directory
if (isset($_GET['listFiles'])) {
    $folder    = isset($_GET['folder']) ? $_GET['folder'] : '.';
    $baseDir   = realpath(BASE_DIR);
    $folder    = trim(str_replace('./', '', $folder), '/');
    $targetDir = realpath($baseDir . '/' . $folder);

    if (! $targetDir || strpos($targetDir, $baseDir) !== 0) {
        sendJsonResponse(false, [], 'Invalid folder path');
    }

    if (! is_dir($targetDir)) {
        sendJsonResponse(false, [], 'Not a directory: ' . $folder);
    }

    $dirs  = [];
    $files = [];

    foreach (scandir($targetDir) as $f) {
        if ($f === '.') {
            continue;
        }

        if ($f === '..' && ! in_array($folder, ['', '.', '/'])) {
            $parent = dirname($folder);
            $parent = ($parent === '' || $parent === '.' || $parent === '/') ? '.' : $parent;
            $dirs[] = ['name' => '..', 'type' => 'dir', 'path' => $parent];
            continue;
        }

        $relPath = ($folder === '' || $folder === '.') ? $f : $folder . '/' . $f;
        $entry   = [
            'name' => $f,
            'type' => is_dir($targetDir . '/' . $f) ? 'dir' : 'file',
            'path' => $relPath,
        ];

        if ($entry['type'] === 'dir') {
            $dirs[] = $entry;
        } else {
            $files[] = $entry;
        }
    }

    sendJsonResponse(true, ['entries' => array_merge($dirs, $files), 'current' => $folder === '' ? '.' : $folder]);
}

// Get file content
if (isset($_GET['getFile'])) {
    $folder  = isset($_GET['folder']) ? $_GET['folder'] : '.';
    $file    = isset($_GET['file']) ? $_GET['file'] : '';
    $baseDir = realpath(BASE_DIR);
    $target  = realpath($baseDir . '/' . $folder . '/' . $file);

    if (! $target || strpos($target, $baseDir) !== 0 || ! is_file($target)) {
        sendJsonResponse(false, [], 'File not found');
    }

    sendJsonResponse(true, ['content' => file_get_contents($target)]);
}
