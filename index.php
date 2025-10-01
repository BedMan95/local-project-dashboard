<?php
    foreach (glob(__DIR__ . '/.clone_log_clone_*') as $logFile) {
        @unlink($logFile);
    }
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<title>Project Dashboard</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
	<link href="style.css" rel="stylesheet">
	<!-- Monaco Editor CDN -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs/loader.min.js"></script>
</head>

<body>
	<button id="darkmodeToggle" class="btn btn-outline-secondary btn-sm position-absolute top-0 end-0 m-3"
		title="Toggle dark mode">
		<span id="darkmodeIcon">&#9790;</span>
	</button>

	<div class="dashboard-card shadow">
		<div class="mb-4">
			<div class="dashboard-title text-center"><i class="fa-solid fa-gauge-high"></i>DK Project Dashboard</div>
			<div class="dashboard-desc text-center">
				<marquee behavior="scroll" direction="left" id="link-marquee">
					Select a project to open
				</marquee>
			</div>

			<div class="d-flex flex-column flex-md-row align-items-stretch justify-content-between gap-2 mt-4">
				<input type="text" id="searchInput" class="form-control" placeholder="Search projects...">
				<button id="createProjectBtn" class="btn-clone-project">
					<i class="fa-solid fa-plus"></i>
					<span>Create Project</span>
				</button>
				<button id="cloneProjectBtn" class="btn-clone-project">
					<i class="fa-solid fa-plus"></i>
					<span>Github Project</span>
				</button>
				<button id="manageLinksBtn" class="btn-clone-project">
					<i class="fa-solid fa-link"></i>
					<span>Manage Links</span>
				</button>
			</div>
		</div>

		<div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel"
			aria-hidden="true">
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
									pattern="[A-Za-z0-9_\- ]{1,32}" maxlength="32" autocomplete="off"
									placeholder="Enter project name">
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

		<div class="modal fade" id="cloneProjectModal" tabindex="-1" aria-labelledby="cloneProjectModalLabel"
			aria-hidden="true">
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
									<div id="cloneProgressBar" class="progress-bar" role="progressbar" style="width:0%">
										0%</div>
								</div>
								<pre id="cloneLog"
									style="height:120px;overflow:auto;background:#222;color:#eee;padding:8px;font-size:12px;"></pre>
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
									<th >Actions</th>
								</tr>
							</thead>
							<tbody id="linksTableBody"></tbody>
						</table>
						<button class="btn btn-success btn-sm" id="addLinkBtn">
							<i class="fa fa-plus"></i> Add Link
						</button>
						<div id="linkError" class="text-danger small mt-2"></div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
						<button type="button" class="btn btn-primary" id="saveLinksBtn">Save Changes</button>
					</div>
				</div>
			</div>
		</div>

		<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 mt-3" id="projectGrid">
			<?php
                $projects = [];
                $files    = scandir(__DIR__);
                foreach ($files as $file) {
                    if (is_dir($file) && $file != '.' && $file != '..') {
                        $filesInDir = scandir($file);
                        $existIndex = in_array('index.php', $filesInDir) || in_array('index.html', $filesInDir);
                        if ($existIndex) {
                            $link       = file_exists("$file/index.php") ? "$file/index.php" : "$file/index.html";
                            $projects[] = ['name' => $file, 'link' => $link];
                        } else {
                            foreach ($filesInDir as $subDir) {
                                $subDirPath = "{$file}/{$subDir}";
                                if (is_dir($subDirPath) && $subDir != '.' && $subDir != '..') {
                                    $subFiles = scandir($subDirPath);
                                    if (in_array('index.php', $subFiles) || in_array('index.html', $subFiles)) {
                                        $link       = file_exists("$subDirPath/index.php") ? "$subDirPath/index.php" : "$subDirPath/index.html";
                                        $projects[] = ['name' => $subDirPath, 'link' => $link];
                                    }
                                }
                            }
                        }
                    }
                }
                foreach ($projects as $proj) {
                    $name = htmlspecialchars($proj['name']);
                    $link = htmlspecialchars($proj['link']);
                    echo "<div class='col'>
						<div class='d-flex align-items-stretch h-100 project-folder'>
							<a class='project-link flex-grow-1' href='$link' data-name='$name' target='_blank' rel='noopener'>
								<span class='folder-icon'><i class='fa-solid fa-folder'></i></span>
								<span>$name</span>
							</a>
							<div class='d-flex flex-column ms-2 justify-content-center action-buttons'>
								<button class='btn btn-sm btn-outline-secondary mb-1 edit-project-btn' data-folder='$name' title='Edit Project'>
									<i class='fa fa-edit'></i>
								</button>
								<button class='btn btn-sm btn-outline-danger delete-project-btn' data-folder='$name' title='Delete Project'>
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

	<div id="editorModal" style="display:none; position:fixed; top:5%; left:5%; 
		width:90%; height:90%; border-radius:10px; box-shadow:0 0 20px #000; z-index:1050;">
			
		<div style="height:100%; display:flex; flex-direction:column;">
			<!-- Header -->
			<div class="editor-header" style="padding:10px; display:flex; justify-content:space-between; align-items:center;">
				<span id="editorFilename"></span>
				<button onclick="closeEditor()" 
						style="background:#e74c3c;color:#fff;border:none;padding:5px 10px;cursor:pointer;">
					✕
				</button>
			</div>

			<!-- Body: Explorer + Editor -->
			<div style="flex:1; display:flex; overflow:hidden;">
				<!-- Sidebar explorer -->
				<div id="editorExplorer" class="editor-explorer" style="width:220px; padding:10px; overflow:auto;">
					<ul id="explorerRoot" class="list-unstyled"></ul>
				</div>

				<!-- Monaco editor -->
				<div id="monacoEditor" style="flex:1;"></div>
			</div>

			<!-- Footer -->
			<div class="editor-footer" style="padding:10px; text-align:right;">
				<button onclick="saveFile()" 
						style="background:#2ecc71;color:#fff;border:none;padding:5px 10px;cursor:pointer;">
					💾 Save
				</button>
			</div>
		</div>
	</div>

	<!-- Context menu untuk panel kiri -->
	<ul id="explorerContextMenu" class="dropdown-menu" style="position:absolute; display:none; z-index:1051;">
		<li><a class="dropdown-item" href="#" id="ctx-new-file"><i class="fa fa-file me-2"></i>New File</a></li>
		<li><a class="dropdown-item" href="#" id="ctx-new-folder"><i class="fa fa-folder me-2"></i>New Folder</a></li>
		<li><hr class="dropdown-divider"></li>
		<li><a class="dropdown-item" href="#" id="ctx-rename"><i class="fa fa-edit me-2"></i>Rename</a></li>
		<li><a class="dropdown-item text-danger" href="#" id="ctx-delete"><i class="fa fa-trash me-2"></i>Delete</a></li>
	</ul>

	<script>
		<?php if (! empty($error)): ?>
			$(function() {
				$('#createProjectError').text(<?php echo json_encode($error) ?>);
				new bootstrap.Modal(document.getElementById('createProjectModal')).show();
			});
		<?php endif; ?>

		function applySwalDarkmode() {
			if ($('body').hasClass('darkmode')) {
				Swal.update({
					background: '#23262b',
					color: '#e0e0e0',
					customClass: {
						popup: 'swal2-darkmode'
					}
				});
				$('table').addClass('table-dark');
				$('.swal2-select').css({
					'background-color': '#2c2f36',
					'color': '#e0e0e0',
					'border': '1px solid #444'
				});
				$('#explorerContextMenu').css({
					'background-color': '#2c2f36',
					'color': '#e0e0e0',
					'border': '1px solid #444'
				});
				$('.dropdown-item').css({
					'color': '#e0e0e0',
				});
			} else {
				Swal.update({
					background: '',
					color: '',
					customClass: {
						popup: ''
					}
				});
				$('table').removeClass('table-dark');
				$('.swal2-select').css({
					'background-color': '',
					'color': '',
					'border': ''
				});
				$('#explorerContextMenu').css({
					'background-color': '',
					'color': '',
					'border': ''
				});
				$('.dropdown-item').css({
					'background-color': '',
					'color': '',
					'border': ''
				});
			}

			if (typeof monaco !== "undefined" && typeof editorInstance !== "undefined") {
				if ($('body').hasClass('darkmode')) {
					monaco.editor.setTheme("vs-dark");
				} else {
					monaco.editor.setTheme("vs");
				}
			}
		}

		function showLoading(message = 'Loading...') {
			Swal.fire({
				title: message,
				allowOutsideClick: false,
				didOpen: () => {
					Swal.showLoading();
				}
			});
			applySwalDarkmode();
		}

		function hideLoading() {
			Swal.close();
		}

		$(function() {
			const modal = new bootstrap.Modal(document.getElementById('createProjectModal'));

			$('#createProjectBtn').on('click', function() {
				$('#projectName').val('');
				$('#createProjectError').text('');
				modal.show();
			});

			$('#createProjectForm').on('submit', function(e) {
				e.preventDefault();
				const name = $('#projectName').val().trim();
				if (!/^[A-Za-z0-9_\- ]{1,32}$/.test(name)) {
					Swal.fire({
						icon: 'error',
						title: 'Invalid project name',
						text: 'Allowed: letters, numbers, spaces, -, _ (max 32 chars)'
					});
					applySwalDarkmode();
					return false;
				}
				Swal.fire({
					title: 'Create project?',
					text: 'Create project "' + name + '"?',
					icon: 'question',
					showCancelButton: true,
					confirmButtonText: 'Create',
					cancelButtonText: 'Cancel'
				}).then((result) => {
					if (result.isConfirmed) {
						showLoading('Creating project...');
						$.post('api.php', {
							projectName: name
						}, function(resp) {
							hideLoading();
							if (resp.success) {
								Swal.fire('Created!', '', 'success').then(() => location.reload());
								applySwalDarkmode();
							} else {
								Swal.fire('Error', resp.error || 'Failed to create project.', 'error');
								applySwalDarkmode();
							}
							applySwalDarkmode();
						}, 'json').fail(function() {
							hideLoading();
							Swal.fire('Error', 'Failed to create project.', 'error');
							applySwalDarkmode();
						});
					}
				});
				applySwalDarkmode();
			});

			$('#searchInput').on('input', function() {
				const query = $(this).val().toLowerCase();
				$('#projectGrid a.project-link').each(function() {
					const name = $(this).data('name').toLowerCase();
					$(this).closest('.col').toggle(name.includes(query));
				});
			});

			function setDarkmode(on) {
				if (on) {
					$('body').addClass('darkmode');
					$('#darkmodeIcon').html('&#9728;');
					localStorage.setItem('darkmode', '1');
				} else {
					$('body').removeClass('darkmode');
					$('#darkmodeIcon').html('&#9790;');
					localStorage.setItem('darkmode', '0');
				}
				applySwalDarkmode();
			}

			$('#darkmodeToggle').on('click', function() {
				setDarkmode(!$('body').hasClass('darkmode'));
			});

			if (localStorage.getItem('darkmode') === '1' ||
				(localStorage.getItem('darkmode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
				setDarkmode(true);
			} else {
				applySwalDarkmode();
			}

			$('.edit-project-btn').on('click', function () {
				const folder = $(this).data('folder');

				Swal.fire({
					title: 'Select Edit Mode',
					input: 'select',
					inputOptions: {
						name: 'Edit Project Name',
						file: 'Edit Project Files'
					},
					inputPlaceholder: 'Choose mode',
					showCancelButton: true
				}).then((choice) => {
					if (!choice.isConfirmed) return;

					if (choice.value === 'name') {
						// ==== RENAME PROJECT ====
						Swal.fire({
							title: 'Edit Project Name',
							input: 'text',
							inputValue: folder,
							inputAttributes: {
								maxlength: 32,
								autocapitalize: 'off',
								autocorrect: 'off'
							},
							showCancelButton: true,
							confirmButtonText: 'Rename',
							cancelButtonText: 'Cancel',
							preConfirm: (newName) => {
								if (!/^[A-Za-z0-9_\- ]{1,32}$/.test(newName)) {
									Swal.showValidationMessage('Allowed: letters, numbers, spaces, -, _ (max 32 chars)');
									return false;
								}
								if (newName === folder) {
									Swal.showValidationMessage('Name is unchanged.');
									return false;
								}
								return newName;
							}
						}).then((result) => {
							if (result.isConfirmed) {
								showLoading('Renaming project...');
								$.post('api.php', {
									renameProject: 1,
									oldName: folder,
									newName: result.value
								}, function (resp) {
									hideLoading();
									if (resp.success) {
										Swal.fire('Renamed!', '', 'success').then(() => location.reload());
										applySwalDarkmode();
									} else {
										Swal.fire('Error', resp.error || 'Failed to rename folder.', 'error');
										applySwalDarkmode();
									}
								}, 'json').fail(function () {
									hideLoading();
									Swal.fire('Error', 'Failed to rename folder.', 'error');
									applySwalDarkmode();
								});
							}
						});
						applySwalDarkmode();

					} else if (choice.value === 'file') {
						editorInstance.setValue("");
						// ==== OPEN PROJECT IN EDITOR ====
						showLoading('Loading project...');
						$.getJSON('api.php', { listFiles: 1, folder: folder }, function (resp) {
							hideLoading();
							if (!resp.success) {
								alert(resp.error || 'Failed to load files.');
								return;
							}

							// render daftar file ke sidebar kiri editor
							let explorerHtml = `
								<div class="mb-2 d-flex justify-content-end gap-2">
									<button class="btn btn-sm btn-primary create-file-btn" data-folder="${folder}">
										<i class="fa fa-file"></i> New File
									</button>
									<button class="btn btn-sm btn-success create-folder-btn" data-folder="${folder}">
										<i class="fa fa-folder"></i> New Folder
									</button>
								</div>
								<div class="file-grid"
									style="display:flex;flex-direction:column;gap:5px;">`;

							$.each(resp.entries, function (i, entry) {
								if (entry.name === '.' || entry.name === '..') return;
								if (entry.type === 'dir') {
									explorerHtml += `
										<button class="file-item btn btn-sm btn-secondary text-start"
												data-path="${entry.path}" data-type="dir">
											<i class="fa fa-folder me-1"></i> ${entry.name}
										</button>`;
								} else {
									explorerHtml += `
										<button class="file-item btn btn-sm btn-outline-primary text-start"
												data-path="${entry.path}" data-type="file">
											<i class="fa fa-file-code me-1"></i> ${entry.name}
										</button>`;
								}
							});
							explorerHtml += '</div>';

							// masukkan explorer ke panel kiri dalam modal editor
							$('#editorExplorer').html(explorerHtml);

							// tampilkan editor modal (1 modal untuk semua)
							$('#editorModal').show();
						});
					}
				});
				applySwalDarkmode();
			});

			function openFolder(folder) {
				$.getJSON('api.php', { listFiles: 1, folder: folder }, function (resp) {
					hideLoading();

					if (!resp.success) {
						// ganti Swal -> alert / modal error
						alert(resp.error || 'Failed to load files.');
						return;
					}

					let fileListHtml = `
						<div class="mb-2 d-flex justify-content-end gap-2">
							<button class="btn btn-sm btn-primary create-file-btn" data-folder="${folder}">
								<i class="fa fa-file"></i> New File
							</button>
							<button class="btn btn-sm btn-success create-folder-btn" data-folder="${folder}">
								<i class="fa fa-folder"></i> New Folder
							</button>
						</div>
						<div class="file-grid"
							style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px;">`;

					$.each(resp.entries, function (i, entry) {
						if (entry.name === '.' || entry.name === '..') return;
						if (entry.type === 'dir') {
							fileListHtml += `
								<button class="file-item btn btn-sm btn-secondary"
										data-path="${entry.path}" data-type="dir">
									<i class="fa fa-folder me-1"></i> ${entry.name}
								</button>`;
						} else {
							fileListHtml += `
								<button class="file-item btn btn-sm btn-outline-primary"
										data-path="${entry.path}" data-type="file">
									<i class="fa fa-file-code me-1"></i> ${entry.name}
								</button>`;
						}
					});
					fileListHtml += '</div>';

					// === isi konten modal pakai jQuery ===
					$('#fileModal .modal-title').text('Browsing: ' + folder);
					$('#fileModal .modal-body').html(fileListHtml);

					// === tampilkan modal pakai jQuery + Bootstrap 5 ===
					var modalEl = document.getElementById('fileModal');
					var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
					modal.show();
				});
			}

			$(document).on('click', '.file-item', function () {
				let path = $(this).data('path');
				let type = $(this).data('type');
				if (type === 'dir') {
					openFolder(path);
				} else if (type === "file") {
						$.getJSON("api.php", { getFile: 1, folder: path.split("/").slice(0, -1).join("/"), file: path.split("/").pop() }, function(resp) {
							if (resp.success) {
								// deteksi bahasa berdasarkan ekstensi
								let ext = path.split(".").pop();
								let lang = "plaintext";
								if (ext === "php") lang = "php";
								if (ext === "js") lang = "javascript";
								if (ext === "css") lang = "css";
								if (ext === "html") lang = "html";
								if (ext === "py") lang = "python";

								openFileInEditor(path, resp.content, lang);
							} else {
								alert("Error: " + resp.error);
							}
						});
					}
			});

			// === tombol new file ===
			$(document).on('click', '.create-file-btn', function () {
				const folder = $(this).data('folder');
				Swal.fire({
					title: 'New File',
					input: 'text',
					inputPlaceholder: 'example.txt',
					showCancelButton: true,
					confirmButtonText: 'Create',
					preConfirm: (val) => val.trim()
				}).then((res) => {
					if (!res.isConfirmed) return;
					$.post('api.php', { createFile: 1, folder: folder, name: res.value }, function (resp) {
						if (resp.success) {
							Swal.fire("Berhasil!", "File berhasil dibuat", "success");
							applySwalDarkmode();
                    		refreshExplorer();
						} else {
							Swal.fire('Error', resp.error || 'Failed to create file.', 'error');
							applySwalDarkmode();
						}
						applySwalDarkmode();
					}, 'json');
				});
				applySwalDarkmode();
			});

			// === tombol new folder ===
			$(document).on('click', '.create-folder-btn', function () {
				const folder = $(this).data('folder');
				Swal.fire({
					title: 'New Folder',
					input: 'text',
					inputPlaceholder: 'new-folder',
					showCancelButton: true,
					confirmButtonText: 'Create',
					preConfirm: (val) => val.trim(),
					allowOutsideClick: true,
					allowEscapeKey: true,
					didOpen: (el) => {
						$(el).find('input').trigger('focus'); // paksa fokus ke input
					}
				}).then((res) => {
					if (!res.isConfirmed) return;
					$.post('api.php', { createFolder: 1, folder: folder, name: res.value }, function (resp) {
						if (resp.success) {
							Swal.fire("Berhasil!", "File berhasil dibuat", "success");
							applySwalDarkmode();
                    		refreshExplorer();
						} else {
							Swal.fire('Error', resp.error || 'Failed to create folder.', 'error');
							applySwalDarkmode();
						}
						applySwalDarkmode();
					}, 'json');
				});
				applySwalDarkmode();
			});

			$('.delete-project-btn').on('click', function() {
				const name = $(this).data('folder');
				Swal.fire({
					title: 'Delete Project?',
					text: 'Are you sure you want to delete project "' + name + '"? This cannot be undone.',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: 'Delete',
					cancelButtonText: 'Cancel',
					confirmButtonColor: '#d33'
				}).then((result) => {
					if (result.isConfirmed) {
						showLoading('Deleting project...');
						$.post('api.php', {
							deleteProject: 1,
							name: name
						}, function(resp) {
							hideLoading();
							if (resp.success) {
								Swal.fire('Deleted!', '', 'success').then(() => location.reload());
								applySwalDarkmode();
							} else {
								Swal.fire('Error', resp.error || 'Failed to delete folder.', 'error');
								applySwalDarkmode();
							}
						}, 'json').fail(function() {
							hideLoading();
							Swal.fire('Error', 'Failed to delete folder.', 'error');
							applySwalDarkmode();
						});
					}
				});
				applySwalDarkmode();
			});
		});
		let lastWorldTime = null;
		let lastWorldTimeTs = null;

		function fetchWorldClock() {
			$.ajax({
				url: 'https://api.api-ninjas.com/v1/worldtime?timezone=asia/jakarta',
				method: 'GET',
				dataType: 'json',
				timeout: 3000,
				headers: {
					'X-Api-Key': 'vqtdn5h8Bc9o2ujlg6GboQ==5mItNDgsfYQBz9lc'
				},
				success: function(resp) {
					if (resp && resp.datetime) {
						lastWorldTime = new Date(resp.datetime.replace(' ', 'T'));
						lastWorldTimeTs = Date.now();
						updateClockDisplay();
					} else {
						updateClockLocal();
					}
				},
				error: function() {
					updateClockLocal();
				}
			});
		}

		function updateClockDisplay() {
			if (lastWorldTime && lastWorldTimeTs) {
				const now = new Date(lastWorldTime.getTime() + (Date.now() - lastWorldTimeTs));
				const pad = n => n.toString().padStart(2, '0');
				const time = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
				const date = `${pad(now.getDate())} ${pad(now.getMonth() + 1)} ${now.getFullYear()}`;
				$('#realtimeClock').text(`${date} ${time} (Jakarta)`);
			} else {
				updateClockLocal();
			}
		}

		function updateClockLocal() {
			const now = new Date();
			const pad = n => n.toString().padStart(2, '0');
			const time = `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;
			const date = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
			$('#realtimeClock').text(`${date} ${time} (local)`);
		}

		$(function() {
			fetchWorldClock();
			setInterval(function() {
				if (lastWorldTime) {
					updateClockDisplay();
				} else {
					updateClockLocal();
				}
			}, 1000);
			setInterval(fetchWorldClock, 60000);
		});

		const cloneModal = new bootstrap.Modal(document.getElementById('cloneProjectModal'));

		$('#cloneProjectBtn').on('click', function() {
			$('#githubUrl').val('');
			$('#cloneFolder').val('');
			$('#cloneProjectError').text('');
			cloneModal.show();
		});

		$('#cloneProjectForm').on('submit', function(e) {
			e.preventDefault();
			const url = $('#githubUrl').val().trim();
			const folder = $('#cloneFolder').val().trim();
			if (!/^(https:\/\/([^@]+@)?github\.com\/[^/]+\/[^/]+(\.git)?|git@github\.com:[^/]+\/[^/]+(\.git)?)$/i.test(url)) {
				$('#cloneProjectError').text('Invalid GitHub URL.');
				return false;
			}
			if (!/^[A-Za-z0-9_\- ]{1,32}$/.test(folder)) {
				$('#cloneProjectError').text('Invalid folder name.');
				return false;
			}
			$('#cloneProjectError').text('');
			$('#cloneLog').text('');
			Swal.fire({
				title: 'Clone project?',
				text: `Clone "${url}" into folder "${folder}"?`,
				icon: 'question',
				showCancelButton: true,
				confirmButtonText: 'Clone',
				cancelButtonText: 'Cancel'
			}).then((result) => {
				if (result.isConfirmed) {
					showLoading('Cloning project...');
					$.post('api.php', {
						cloneGithub: 1,
						githubUrl: url,
						cloneFolder: folder
					}, function(resp) {
						if (resp.success && resp.jobId) {
							hideLoading();
							Swal.fire({
								icon: 'success',
								title: 'Clone Complete!',
								text: 'The GitHub project was cloned successfully.',
								confirmButtonText: 'OK'
							}).then(() => {
								location.reload();
							});
							applySwalDarkmode();
						} else {
							$('#cloneProjectError').text(resp.error || 'Clone failed.');
							Swal.fire('Error', resp.error || 'Clone failed.', 'error');
							applySwalDarkmode();
						}
						applySwalDarkmode();
					}, 'json');
				}
			});
			applySwalDarkmode();
		});

		$(function() {
			$.get("link.txt", function(data) {
				let lines = data.trim().split("\n").filter(line => line.trim() !== "");

				if (lines.length === 0) {
					$("#link-marquee").text("Select a project to open");
				} else {
					let html = "";
					$.each(lines, function(i, line) {
						let parts = line.split("|");
						let url = parts[0].trim();
						let name = parts[1] ? parts[1].trim() : url;
						html += `<a href="${url}" target="_blank">${name}</a>`;
					});
					$("#link-marquee").html(html);
				}
			}).fail(function() {
				$("#link-marquee").text("Select a project to open");
			});
		});

		const linksModal = new bootstrap.Modal(document.getElementById('manageLinksModal'));

		$('#manageLinksBtn').on('click', function() {
			loadLinks();
			$('#linkError').text('');
			linksModal.show();
		});

		function loadLinks() {
			$.get("link.txt", function(data) {
				let lines = data.trim().split("\n").filter(line => line.trim() !== "");
				let tbody = $("#linksTableBody");
				tbody.empty();

				if (lines.length === 0) {
					tbody.append('<tr><td colspan="3" class="text-center text-muted">No links available</td></tr>');
				} else {
					$.each(lines, function(i, line) {
						let parts = line.split("|");
						let url = parts[0].trim();
						let label = parts[1] ? parts[1].trim() : url;
						tbody.append(`
							<tr>
								<td><input type="text" class="form-control form-control-sm link-url" value="${url}"></td>
								<td><input type="text" class="form-control form-control-sm link-label" value="${label}"></td>
								<td>
								<button class="btn btn-danger btn-sm remove-link"><i class="fa fa-trash"></i></button>
								</td>
							</tr>
							`);
					});
				}
			});
		}

		$('#addLinkBtn').on('click', function() {
			$("#linksTableBody").append(`
				<tr>
				<td><input type="text" class="form-control form-control-sm link-url" placeholder="https://example.com"></td>
				<td><input type="text" class="form-control form-control-sm link-label" placeholder="My Project"></td>
				<td>
					<button class="btn btn-danger btn-sm remove-link"><i class="fa fa-trash"></i></button>
				</td>
				</tr>
			`);
		});

		$(document).on('click', '.remove-link', function() {
			$(this).closest('tr').remove();
		});

		$('#saveLinksBtn').on('click', function() {
			let rows = [];
			let error = false;
			$("#linksTableBody tr").each(function() {
				let url = $(this).find(".link-url").val().trim();
				let label = $(this).find(".link-label").val().trim();
				if (url !== "") {
					if (!/^https?:\/\/.+/i.test(url)) {
						$('#linkError').text("Invalid URL: " + url);
						error = true;
						return false;
					}
					rows.push(url + "|" + (label || url));
				}
			});
			if (error) return;

			$.post("api.php", {
				saveLinks: 1,
				links: rows
			}, function(resp) {
				if (resp.success) {
					Swal.fire("Saved!", "Links updated successfully", "success").then(() => {
						linksModal.hide();
						location.reload();
					});
					applySwalDarkmode();
				} else {
					$('#linkError').text(resp.error || "Failed to save links.");
				}
			}, "json").fail(function() {
				$('#linkError').text("Failed to save links.");
			});
		});

		let editorInstance;
		let currentFilePath = "";

		// load monaco
		require.config({ paths: { 'vs': 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' }});
		require(["vs/editor/editor.main"], function () {
			editorInstance = monaco.editor.create(document.getElementById('monacoEditor'), {
				value: "// Pilih file untuk mulai mengedit...",
				language: "php",
				theme: "vs-dark",
				automaticLayout: true
			});
		});

		// buka file ke editor
		function openFileInEditor(path, content, language="php") {
			currentFilePath = path;
			document.getElementById("editorFilename").innerText = path;
			editorInstance.setValue(content);
			monaco.editor.setModelLanguage(editorInstance.getModel(), language);

			// tampilkan modal (custom)
			document.getElementById("editorModal").style.display = "block";
		}

		// tutup editor
		function closeEditor() {
			document.getElementById("editorModal").style.display = "none";
		}

		// simpan file
		function saveFile() {
			const content = editorInstance.getValue();
			$.post("api.php", {
				saveFile: 1,
				folder: currentFilePath.split("/").slice(0, -1).join("/"),
				file: currentFilePath.split("/").pop(),
				content: content
			}, function(resp) {
				if (resp.success) {
					Swal.fire({
						toast: true,
						position: 'top-end',
						icon: 'success',
						title: 'File saved!',
						showConfirmButton: false,
						timer: 2000,
					});
					applySwalDarkmode();
				} else {
					Swal.fire({
						toast: true,
						position: 'top-end',
						icon: 'error',
						title: resp.error || "Save failed",
						showConfirmButton: false,
						timer: 3000,
					});
					applySwalDarkmode();
				}
				applySwalDarkmode();
			}, "json");
		}

		function renderExplorer(folder, container) {
			$.get('api.php', { listFiles: 1, folder: folder }, function(resp) {
				if (!resp.success) {
					alert(resp.error);
					return;
				}

				container.empty();
				resp.entries.forEach(item => {
					if (item.type === 'dir') {
						const li = $(`
							<li>
								<div class="folder cursor-pointer">
									<i class="fa fa-folder me-1"></i> ${item.name}
								</div>
								<ul class="children list-unstyled ms-3 d-none"></ul>
							</li>
						`);

						// klik folder
						li.find('.folder').on('click', function() {
							const children = li.find('.children');
							if (children.hasClass('d-none')) {
								children.removeClass('d-none');
								li.find('i').removeClass('fa-folder').addClass('fa-folder-open');

								if (children.is(':empty')) {
									renderExplorer(item.path, children);
								}
							} else {
								children.addClass('d-none');
								li.find('i').removeClass('fa-folder-open').addClass('fa-folder');
							}
						});

						container.append(li);
					} else {
						const file = $(`
							<li class="file cursor-pointer ms-4">
								<i class="fa fa-file-code me-1"></i> ${item.name}
							</li>
						`);

						file.on('click', function() {
							openFile(item.path, item.name);
						});

						container.append(file);
					}
				});
			}, 'json');
		}

		// pertama kali load root
		$(document).ready(function() {
			renderExplorer('.', $('#explorerRoot'));
		});

		function openFile(path, name) {
			$.get('api.php', { getFile: 1, path: path }, function(resp) {
				if (resp.success) {
					openFileInEditor(path, resp.content, guessLanguage(name));
				} else {
					Swal.fire('Error', 'Tidak bisa buka file', 'error');
					applySwalDarkmode();
				}
				applySwalDarkmode();
			}, 'json');
		}

		// deteksi bahasa berdasarkan ekstensi
		function guessLanguage(filename) {
			const ext = filename.split('.').pop().toLowerCase();
			switch(ext) {
				case 'js': return 'javascript';
				case 'php': return 'php';
				case 'html': return 'html';
				case 'css': return 'css';
				case 'json': return 'json';
				default: return 'plaintext';
			}
		}

		$(document).on("click", "#editorExplorer .file-item[data-type='dir']", function (e) {
			e.stopPropagation();
			const $btn = $(this);
			const path = $btn.data("path");

			// cek apakah sudah pernah di-expand
			if ($btn.hasClass("expanded")) {
				$btn.next("ul").toggle(); // collapse/expand
				return;
			}

			// ambil isi folder via AJAX
			$.getJSON("api.php", { listFiles: 1, folder: path }, function (resp) {
				if (!resp.success) {
					alert(resp.error || "Failed to load folder.");
					return;
				}

				let subHtml = "<ul class='list-unstyled ms-3'>";
				$.each(resp.entries, function (i, entry) {
					if (entry.name === '.' || entry.name === '..') return;

					if (entry.type === "dir") {
						subHtml += `
							<li>
								<button class="file-item btn btn-sm btn-secondary text-start" 
									data-path="${entry.path}" data-type="dir">
									<i class="fa fa-folder me-1"></i> ${entry.name}
								</button>
							</li>`;
					} else {
						subHtml += `
							<li>
								<button class="file-item btn btn-sm btn-outline-primary text-start" 
									data-path="${entry.path}" data-type="file">
									<i class="fa fa-file-code me-1"></i> ${entry.name}
								</button>
							</li>`;
					}
				});
				subHtml += "</ul>";

				$btn.after(subHtml);
				$btn.addClass("expanded");
			});
		});

		function refreshExplorer() {
			const folder = $('.create-file-btn').data('folder') || $('.create-folder-btn').data('folder');
			if (!folder) return;

			$.getJSON('api.php', { listFiles: 1, folder: folder }, function (resp) {
				if (!resp.success) {
					Swal.fire('Error', resp.error || 'Failed to refresh explorer.', 'error');
					applySwalDarkmode();
					return;
				}

				let explorerHtml = `
					<div class="mb-2 d-flex justify-content-end gap-2">
						<button class="btn btn-sm btn-primary create-file-btn" data-folder="${folder}">
							<i class="fa fa-file"></i> New File
						</button>
						<button class="btn btn-sm btn-success create-folder-btn" data-folder="${folder}">
							<i class="fa fa-folder"></i> New Folder
						</button>
					</div>
					<div class="file-grid" style="display:flex;flex-direction:column;gap:5px;">`;

				$.each(resp.entries, function (i, entry) {
					if (entry.name === '.' || entry.name === '..') return;
					if (entry.type === 'dir') {
						explorerHtml += `
							<button class="file-item btn btn-sm btn-secondary text-start"
									data-path="${entry.path}" data-type="dir">
								<i class="fa fa-folder me-1"></i> ${entry.name}
							</button>`;
					} else {
						explorerHtml += `
							<button class="file-item btn btn-sm btn-outline-primary text-start"
									data-path="${entry.path}" data-type="file">
								<i class="fa fa-file-code me-1"></i> ${entry.name}
							</button>`;
					}
				});
				explorerHtml += '</div>';

				$('#editorExplorer').html(explorerHtml);
			});
		}

		let contextTargetPath = null;
		let contextTargetType = null;

		// === tampilkan context menu hanya di file/folder ===
		$(document).on("contextmenu", ".file-item, .folder-item", function(e) {
			e.preventDefault();
			e.stopPropagation();

			contextTargetPath = $(this).data("path");
			contextTargetType = $(this).data("type");

			console.log(contextTargetPath, contextTargetType);

			$("#explorerContextMenu")
				.css({ top: e.pageY, left: e.pageX })
				.show();
		});

		// sembunyikan menu kalau klik di luar
		$(document).on("click contextmenu", function(e) {
			if (!$(e.target).closest("#explorerContextMenu").length) {
				$("#explorerContextMenu").hide();
			}
		});

		// New File
		$("#ctx-new-file").on("click", function (e) {
			e.preventDefault();
			const folder = contextTargetPath;
			$("#explorerContextMenu").hide();

			Swal.fire({
				title: "Nama file baru",
				input: "text",
				inputPlaceholder: "example.txt",
				showCancelButton: true,
				confirmButtonText: "Create",
				preConfirm: (val) => val.trim()
			}).then(result => {
				if (!result.isConfirmed) return;
				$.post("api.php", { createFile: 1, folder: folder, name: result.value }, function (resp) {
					if (resp.success) {
						Swal.fire("Berhasil!", "File berhasil dibuat", "success");
						applySwalDarkmode();
						refreshExplorer();
					} else {
						Swal.fire("Error", resp.error || "Gagal membuat file", "error");
						applySwalDarkmode();
					}
				}, "json");
			});
			applySwalDarkmode();
		});

		// New Folder
		$("#ctx-new-folder").on("click", function (e) {
			e.preventDefault();
			const folder = contextTargetPath;
			$("#explorerContextMenu").hide();

			Swal.fire({
				title: "Nama folder baru",
				input: "text",
				inputPlaceholder: "new-folder",
				showCancelButton: true,
				confirmButtonText: "Create",
				preConfirm: (val) => val.trim()
			}).then(result => {
				if (!result.isConfirmed) return;
				$.post("api.php", { createFolder: 1, folder: folder, name: result.value }, function (resp) {
					if (resp.success) {
						Swal.fire("Berhasil!", "Folder berhasil dibuat", "success");
						applySwalDarkmode();
						refreshExplorer();
					} else {
						Swal.fire("Error", resp.error || "Gagal membuat folder", "error");
						applySwalDarkmode();
					}
				}, "json");
			});
			applySwalDarkmode();
		});

		$("#ctx-delete").on("click", function(e) {
			e.preventDefault();
			$("#explorerContextMenu").hide();

			if (!contextTargetPath) return;

			Swal.fire({
				title: "Delete?",
				text: "Yakin ingin menghapus " + contextTargetType + " ini?\n" + contextTargetPath,
				icon: "warning",
				showCancelButton: true,
				confirmButtonText: "Delete",
				cancelButtonText: "Cancel",
				confirmButtonColor: "#d33"
			}).then((res) => {
				if (!res.isConfirmed) return;

				showLoading("Deleting...");

				$.post("api.php", { 
					deleteEntry: 1, 
					path: contextTargetPath 
				}, function(resp) {
					hideLoading();
					if (resp.success) {
						Swal.fire("Deleted!", "", "success");
						applySwalDarkmode();
						refreshExplorer();
					} else {
						Swal.fire("Error", resp.error || "Failed to delete.", "error");
						applySwalDarkmode();
					}
					applySwalDarkmode();
				}, "json").fail(function() {
					hideLoading();
					Swal.fire("Error", "Failed to delete.", "error");
					applySwalDarkmode();
				});
			});
			applySwalDarkmode();
		});

		// === aksi rename ===
		$("#ctx-rename").on("click", function(e) {
			e.preventDefault();
			$("#explorerContextMenu").hide();

			if (!contextTargetPath) return;

			const currentName = contextTargetPath.split(/[\\/]/).pop(); // ambil nama file/folder terakhir

			Swal.fire({
				title: "Rename " + contextTargetType,
				input: "text",
				inputValue: currentName,
				showCancelButton: true,
				confirmButtonText: "Rename",
				cancelButtonText: "Cancel",
				inputValidator: (value) => {
					if (!value) return "Nama tidak boleh kosong!";
				}
			}).then((res) => {
				if (!res.isConfirmed) return;

				showLoading("Renaming...");

				$.post("api.php", { 
					renameEntry: 1, 
					path: contextTargetPath,
					newName: res.value
				}, function(resp) {
					hideLoading();
					if (resp.success) {
						Swal.fire("Renamed!", "", "success");
						applySwalDarkmode();
						refreshExplorer();
					} else {
						Swal.fire("Error", resp.error || "Failed to rename.", "error");
						applySwalDarkmode();
					}
					applySwalDarkmode();
				}, "json").fail(function() {
					hideLoading();
					Swal.fire("Error", "Failed to rename.", "error");
					applySwalDarkmode();
				});
			});
			applySwalDarkmode();
		});

	</script>
</body>

</html>