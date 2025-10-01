<?php
define('BASE_DIR', __DIR__);
foreach (glob(__DIR__ . '/.clone_log_clone_*') as $logFile) {
    @unlink($logFile);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Project Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <button id="darkmodeToggle" class="btn btn-outline-secondary btn-sm position-absolute top-0 end-0 m-3" title="Toggle dark mode">
        <span id="darkmodeIcon">&#9790;</span>
    </button>

    <div class="dashboard-card shadow">
        <div class="mb-4">
            <div class="dashboard-title text-center"><i class="fa-solid fa-gauge-high"></i> DK Project Dashboard</div>
            <div class="dashboard-desc text-center">
                <marquee behavior="scroll" direction="left" id="link-marquee">Select a project to open</marquee>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-stretch justify-content-between gap-2 mt-4">
                <input type="text" id="searchInput" class="form-control" placeholder="Search projects...">
                <button id="createProjectBtn" class="btn-clone-project"><i class="fa-solid fa-plus"></i> Create Project</button>
                <button id="cloneProjectBtn" class="btn-clone-project"><i class="fa-solid fa-plus"></i> Github Project</button>
                <button id="manageLinksBtn" class="btn-clone-project"><i class="fa-solid fa-link"></i> Manage Links</button>
            </div>
        </div>

        <!-- Create Project Modal -->
        <div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="createProjectForm" method="post">
                        <div class="modal-header">
                            <h5 class="modal-title">Create New Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="projectName" class="form-label">Project Name</label>
                                <input type="text" class="form-control" id="projectName" name="projectName" required
                                    pattern="[A-Za-z0-9_\- ]{1,32}" maxlength="32" autocomplete="off" placeholder="Enter project name">
                                <div class="form-text">Allowed: letters, numbers, spaces, -, _ (max 32 chars)</div>
                            </div>
                            <div id="createProjectError" class="text-danger small"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Clone Project Modal -->
        <div class="modal fade" id="cloneProjectModal" tabindex="-1" aria-labelledby="cloneProjectModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form id="cloneProjectForm">
                        <div class="modal-header">
                            <h5 class="modal-title">Clone GitHub Project</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="githubUrl" class="form-label">GitHub Repository URL</label>
                                <input type="url" class="form-control" id="githubUrl" name="githubUrl" required
                                    placeholder="https://github.com/user/repo.git">
                            </div>
                            <div class="mb-3">
                                <label for="cloneFolder" class="form-label">Target Folder Name</label>
                                <input type="text" class="form-control" id="cloneFolder" name="cloneFolder" required
                                    pattern="[A-Za-z0-9_\- ]{1,32}" maxlength="32" placeholder="Folder name">
                                <div class="form-text">Allowed: letters, numbers, spaces, -, _ (max 32 chars)</div>
                            </div>
                            <div id="cloneProjectError" class="text-danger small"></div>
                            <div class="mb-3" id="cloneProgressArea" style="display:none;">
                                <label class="form-label">Clone Progress</label>
                                <div class="progress mb-2">
                                    <div id="cloneProgressBar" class="progress-bar" role="progressbar" style="width:0%">0%</div>
                                </div>
                                <pre id="cloneLog" style="height:120px;overflow:auto;background:#222;color:#eee;padding:8px;font-size:12px;"></pre>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Clone</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Manage Links Modal -->
        <div class="modal fade" id="manageLinksModal" tabindex="-1" aria-labelledby="manageLinksModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Manage Links</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th style="width:45%">URL</th>
                                    <th style="width:45%">Label</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="linksTableBody"></tbody>
                        </table>
                        <button class="btn btn-success btn-sm" id="addLinkBtn"><i class="fa fa-plus"></i> Add Link</button>
                        <div id="linkError" class="text-danger small mt-2"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="saveLinksBtn">Save Changes</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Project Grid -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 mt-3" id="projectGrid">
            <?php
            $projects = [];
            $files = scandir(__DIR__);
            foreach ($files as $file) {
                if (is_dir($file) && $file !== '.' && $file !== '..') {
                    $filesInDir = scandir($file);
                    $existIndex = in_array('index.php', $filesInDir) || in_array('index.html', $filesInDir);
                    if ($existIndex) {
                        $link = file_exists("$file/index.php") ? "$file/index.php" : "$file/index.html";
                        $projects[] = ['name' => $file, 'link' => $link];
                    } else {
                        foreach ($filesInDir as $subDir) {
                            $subDirPath = "$file/$subDir";
                            if (is_dir($subDirPath) && $subDir !== '.' && $subDir !== '..') {
                                $subFiles = scandir($subDirPath);
                                if (in_array('index.php', $subFiles) || in_array('index.html', $subFiles)) {
                                    $link = file_exists("$subDirPath/index.php") ? "$subDirPath/index.php" : "$subDirPath/index.html";
                                    $projects[] = ['name' => $subDirPath, 'link' => $link];
                                }
                            }
                        }
                    }
                }
            }
            foreach ($projects as $proj) {
				$name = htmlspecialchars($proj['name'], ENT_QUOTES, 'UTF-8');
				$link = htmlspecialchars($proj['link'], ENT_QUOTES, 'UTF-8');
				$gitDir = realpath(BASE_DIR . '/' . $proj['name'] . '/.git');
				$hasGit = is_dir($gitDir);
				$escapedName = htmlspecialchars(json_encode($proj['name']), ENT_QUOTES, 'UTF-8');
				echo "<div class='col'>
					<div class='d-flex align-items-stretch h-100 project-folder'>
						<a class='project-link flex-grow-1' href='$link' data-name='$name' target='_blank' rel='noopener'>
							<span class='folder-icon'><i class='fa-solid fa-folder'></i></span>
							<span>$name</span>
						</a>
						<div class='d-flex flex-column ms-2 justify-content-center action-buttons'>
							<button class='btn btn-sm btn-outline-secondary mb-1 edit-project-btn' data-folder='$name' title='Edit Project'>
								<i class='fa fa-edit'></i>
							</button>";
				if ($hasGit) {
					echo "<button class='btn btn-sm btn-outline-info mb-1' data-folder='$name' onclick='pullProject($escapedName)'>
						<i class='bi bi-arrow-down-circle'></i> Pull
					</button>";
				}
				echo "<button class='btn btn-sm btn-outline-danger mb-1 delete-project-btn' data-folder='$name' title='Delete Project'>
						<i class='fa fa-trash'></i>
					</button>
						</div>
					</div>
				</div>";
			}
            ?>
        </div>
    </div>

    <div id="realtimeClock"></div>

    <!-- Editor Modal -->
    <div id="editorModal" style="display:none; position:fixed; top:1%; left:2%; width:95%; height:93%; border-radius:10px; box-shadow:0 0 20px #000; z-index:1050;">
        <div style="height:100%; display:flex; flex-direction:column;">
            <div class="editor-header" style="padding:10px; display:flex; justify-content:space-between; align-items:center;">
                <span id="editorFilename"></span>
                <button onclick="closeEditor()" style="background:#e74c3c;color:#fff;border:none;padding:5px 10px;cursor:pointer;">✕</button>
            </div>
            <div style="flex:1; display:flex; overflow:hidden;">
                <div id="editorExplorer" class="editor-explorer" style="width:220px; padding:10px; overflow:auto;">
                    <ul id="explorerRoot" class="list-unstyled"></ul>
                </div>
                <div id="monacoEditor" style="flex:1;"></div>
            </div>
            <div class="editor-footer" style="padding:10px; text-align:right;">
                <button onclick="saveFile()" style="background:#2ecc71;color:#fff;border:none;padding:5px 10px;cursor:pointer;">💾 Save</button>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <ul id="explorerContextMenu" class="dropdown-menu" style="position:absolute; display:none; z-index:1051;">
        <li><a class="dropdown-item" href="#" id="ctx-new-file"><i class="fa fa-file me-2"></i>New File</a></li>
        <li><a class="dropdown-item" href="#" id="ctx-new-folder"><i class="fa fa-folder me-2"></i>New Folder</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="#" id="ctx-rename"><i class="fa fa-edit me-2"></i>Rename</a></li>
        <li><a class="dropdown-item text-danger" href="#" id="ctx-delete"><i class="fa fa-trash me-2"></i>Delete</a></li>
    </ul>

    <script src="dashboard.js"></script>
</body>
</html>