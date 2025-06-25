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
</head>

<body>
	<button id="darkmodeToggle" class="btn btn-outline-secondary btn-sm position-absolute top-0 end-0 m-3"
		title="Toggle dark mode">
		<span id="darkmodeIcon">&#9790;</span>
	</button>

	<div class="dashboard-card shadow">
		<div class="mb-4">
			<div class="dashboard-title text-center"><i class="fa-solid fa-gauge-high"></i>DK Project Dashboard</div>
			<div class="dashboard-desc text-center">Select a project to open</div>

			<div class="d-flex flex-column flex-md-row align-items-stretch justify-content-between gap-2 mt-4">
				<input type="text" id="searchInput" class="form-control" placeholder="Search projects...">
				<button id="createProjectBtn" class="btn-create-project">
					<i class="fa-solid fa-plus"></i>
					<span>Create Project</span>
				</button>
				<button id="cloneProjectBtn" class="btn-clone-project">
					<i class="fa-solid fa-plus"></i>
					<span>Clone github Project</span>
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

		<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-3 mt-3" id="projectGrid">
			<?php
			$projects = [];
			$files = scandir(__DIR__);
			foreach ($files as $file) {
				if (is_dir($file) && $file != '.' && $file != '..') {
					$filesInDir = scandir($file);
					$existIndex = in_array('index.php', $filesInDir) || in_array('index.html', $filesInDir);
					if ($existIndex) {
						$link = file_exists("$file/index.php") ? "$file/index.php" : "$file/index.html";
						$projects[] = ['name' => $file, 'link' => $link];
					} else {
						foreach ($filesInDir as $subDir) {
							$subDirPath = "{$file}/{$subDir}";
							if (is_dir($subDirPath) && $subDir != '.' && $subDir != '..') {
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
				$name = htmlspecialchars($proj['name']);
				$link = htmlspecialchars($proj['link']);
				echo "<div class='col'>
						<div class='d-flex align-items-stretch h-100 project-folder'>
							<a class='project-link flex-grow-1' href='$link' data-name='$name' target='_blank' rel='noopener'>
								<span class='folder-icon'><i class='fa-solid fa-folder'></i></span>
								<span>$name</span>
							</a>
							<div class='d-flex flex-column ms-2 justify-content-center action-buttons'>
								<button class='btn btn-sm btn-outline-secondary mb-1 edit-project-btn' data-folder='$name' title='Edit name'>
									<i class='fa fa-edit'></i>
								</button>
								<button class='btn btn-sm btn-outline-danger delete-project-btn' data-folder='$name' title='Delete project'>
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

	<script>
		<?php if (!empty($error)): ?>
			$(function () {
				$('#createProjectError').text(<?= json_encode($error) ?>);
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
			} else {
				Swal.update({
					background: '',
					color: '',
					customClass: {
						popup: ''
					}
				});
			}
		}
		$(function () {
			const modal = new bootstrap.Modal(document.getElementById('createProjectModal'));

			$('#createProjectBtn').on('click', function () {
				$('#projectName').val('');
				$('#createProjectError').text('');
				modal.show();
			});

			$('#createProjectForm').on('submit', function (e) {
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
						$.post('api.php', { projectName: name }, function (resp) {
							if (resp.success) {
								Swal.fire('Created!', '', 'success').then(() => location.reload());
							} else {
								Swal.fire('Error', resp.error || 'Failed to create project.', 'error');
							}
							applySwalDarkmode();
						}, 'json');
					}
				});
				applySwalDarkmode();
			});

			$('#searchInput').on('input', function () {
				const query = $(this).val().toLowerCase();
				$('#projectGrid a.project-link').each(function () {
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

			$('#darkmodeToggle').on('click', function () {
				setDarkmode(!$('body').hasClass('darkmode'));
			});

			if (localStorage.getItem('darkmode') === '1' ||
				(localStorage.getItem('darkmode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
				setDarkmode(true);
			} else {
				applySwalDarkmode();
			}

			$('.edit-project-btn').on('click', function () {
				const oldName = $(this).data('folder');
				Swal.fire({
					title: 'Edit Project Name',
					input: 'text',
					inputValue: oldName,
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
						if (newName === oldName) {
							Swal.showValidationMessage('Name is unchanged.');
							return false;
						}
						return newName;
					}
				}).then((result) => {
					if (result.isConfirmed) {
						$.post('api.php', { renameProject: 1, oldName: oldName, newName: result.value }, function (resp) {
							if (resp.success) {
								Swal.fire('Renamed!', '', 'success').then(() => location.reload());
								applySwalDarkmode();
							} else {
								Swal.fire('Error', resp.error || 'Failed to rename folder.', 'error');
								applySwalDarkmode();
							}
						}, 'json');
					}
				});
				applySwalDarkmode();
			});

			$('.delete-project-btn').on('click', function () {
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
						$.post('api.php', { deleteProject: 1, name: name }, function (resp) {
							if (resp.success) {
								Swal.fire('Deleted!', '', 'success').then(() => location.reload());
								applySwalDarkmode();
							} else {
								Swal.fire('Error', resp.error || 'Failed to delete folder.', 'error');
								applySwalDarkmode();
							}
						}, 'json');
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
				headers: { 'X-Api-Key': 'vqtdn5h8Bc9o2ujlg6GboQ==5mItNDgsfYQBz9lc' },
				success: function (resp) {
					if (resp && resp.datetime) {
						lastWorldTime = new Date(resp.datetime.replace(' ', 'T'));
						lastWorldTimeTs = Date.now();
						updateClockDisplay();
					} else {
						updateClockLocal();
					}
				},
				error: function () {
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

		$(function () {
			fetchWorldClock();
			setInterval(function () {
				if (lastWorldTime) {
					updateClockDisplay();
				} else {
					updateClockLocal();
				}
			}, 1000);
			setInterval(fetchWorldClock, 60000);
		});

		const cloneModal = new bootstrap.Modal(document.getElementById('cloneProjectModal'));

		$('#cloneProjectBtn').on('click', function () {
			$('#githubUrl').val('');
			$('#cloneFolder').val('');
			$('#cloneProjectError').text('');
			cloneModal.show();
		});

		let cloneJobId = null;
		let clonePollTimer = null;

		function pollCloneProgress() {
			if (!cloneJobId) return;
			$.post('api.php', { cloneProgress: 1, jobId: cloneJobId, cloneFolder: $('#cloneFolder').val().trim() }, function (resp) {
				$('#cloneProgressArea').show();
				$('#cloneLog').text(resp.log && resp.log.length > 0 ? resp.log : 'Starting clone...');
				$('#cloneProgressBar').css('width', resp.percent + '%').text(resp.percent + '%');
				if (resp.done) {
					$('#cloneProgressBar').removeClass('bg-info').addClass('bg-success');
					cloneJobId = null;
					clearTimeout(clonePollTimer);
					Swal.fire({
						position: 'top-end',
						icon: 'success',
						title: 'Clone Complete!',
						text: 'The GitHub project was cloned successfully.',
						confirmButtonText: 'OK'
					}).then(() => {
						location.reload();
					});
					applySwalDarkmode();
				} else {
					clonePollTimer = setTimeout(pollCloneProgress, 500); // Faster polling
				}
			}, 'json').fail(function () {
				$('#cloneLog').text('Waiting for clone process...');
				clonePollTimer = setTimeout(pollCloneProgress, 1000);
			});
		}

		$('#cloneProjectForm').on('submit', function (e) {
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
			$('#cloneProgressArea').show();
			$('#cloneProgressBar').removeClass('bg-success').addClass('bg-info').css('width', '0%').text('0%');
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
					$.post('api.php', { cloneGithub: 1, githubUrl: url, cloneFolder: folder }, function (resp) {
						if (resp.success && resp.jobId) {
							cloneJobId = resp.jobId;
							pollCloneProgress();
						} else {
							$('#cloneProjectError').text(resp.error || 'Clone failed.');
							Swal.fire('Error', resp.error || 'Clone failed.', 'error');
						}
						applySwalDarkmode();
					}, 'json');
				}
			});
			applySwalDarkmode();
		});
	</script>
</body>

</html>