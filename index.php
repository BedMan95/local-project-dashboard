<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['projectName'])) {
	$projectName = trim($_POST['projectName']);
	if (preg_match('/^[A-Za-z0-9_\- ]{1,32}$/', $projectName)) {
		$dir = __DIR__ . '/' . $projectName;
		if (!file_exists($dir)) {
			mkdir($dir, 0755, true);
			file_put_contents(
				"{$dir}/index.php",
				"<?php\n// {$projectName} project\n?><!DOCTYPE html>\n<html><head><title>{$projectName}</title></head><body><h1>{$projectName}</h1></body></html>"
			);
			header("Location: " . $_SERVER['PHP_SELF']);
			exit;
		} else {
			$error = 'Project already exists.';
		}
	} else {
		$error = 'Invalid project name.';
	}
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['renameProject'])) {
	header('Content-Type: application/json');
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
		echo json_encode(['success' => true]);
	} else {
		echo json_encode(['success' => false, 'error' => 'Rename failed.']);
	}
	exit;
}
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
					unlink($path);
			}
		}
		rmdir($dir);
	}
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteProject'])) {
	header('Content-Type: application/json');
	$target = trim($_POST['name']);
	if (!is_dir($target)) {
		echo json_encode(['success' => false, 'error' => 'Folder not found.']);
		exit;
	}
	rrmdir($target);
	echo json_encode(['success' => true]);
	exit;
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
				const name = $('#projectName').val().trim();
				if (!/^[A-Za-z0-9_\- ]{1,32}$/.test(name)) {
					e.preventDefault();
					Swal.fire({
						icon: 'error',
						title: 'Invalid project name',
						text: 'Allowed: letters, numbers, spaces, -, _ (max 32 chars)'
					});
					return false;
				}
				e.preventDefault();
				Swal.fire({
					title: 'Create project?',
					text: 'Create project "' + name + '"?',
					icon: 'question',
					showCancelButton: true,
					confirmButtonText: 'Create',
					cancelButtonText: 'Cancel'
				}).then((result) => {
					if (result.isConfirmed) {
						$('#createProjectForm')[0].submit();
					}
				});
				applySwalDarkmode();
			});

			$('#searchInput').on('input', function () {
				const query = $(this).val().toLowerCase();
				$('#projectGrid a.project-link').each(function () {
					const name = $(this).data('name').toLowerCase();
					$(this).parent().toggle(name.includes(query));
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
						$.post('', { renameProject: 1, oldName: oldName, newName: result.value }, function (resp) {
							if (resp.success) {
								Swal.fire('Renamed!', '', 'success').then(() => location.reload());
							} else {
								Swal.fire('Error', resp.error || 'Failed to rename folder.', 'error');
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
						$.post('', { deleteProject: 1, name: name }, function (resp) {
							if (resp.success) {
								Swal.fire('Deleted!', '', 'success').then(() => location.reload());
							} else {
								Swal.fire('Error', resp.error || 'Failed to delete folder.', 'error');
							}
						}, 'json');
					}
				});
				applySwalDarkmode();
			});
		});
	</script>
</body>

</html>