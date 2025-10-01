$(function () {
    const API_NINJAS_KEY = 'vqtdn5h8Bc9o2ujlg6GboQ==5mItNDgsfYQBz9lc';
    let editorInstance, currentFilePath = '';
    let lastWorldTime = null, lastWorldTimeTs = null;
    let contextTargetPath = null, contextTargetType = null;

    const createProjectModal = new bootstrap.Modal(document.getElementById('createProjectModal'));
    const cloneModal = new bootstrap.Modal(document.getElementById('cloneProjectModal'));
    const linksModal = new bootstrap.Modal(document.getElementById('manageLinksModal'));

    // Dark Mode Handling
    function applySwalDarkmode() {
        const isDarkMode = $('body').hasClass('darkmode');
        Swal.update({
            background: isDarkMode ? '#23262b' : '',
            color: isDarkMode ? '#e0e0e0' : '',
            customClass: { popup: isDarkMode ? 'swal2-darkmode' : '' }
        });
        $('table').toggleClass('table-dark', isDarkMode);
        $('.swal2-select').css({
            'background-color': isDarkMode ? '#2c2f36' : '',
            'color': isDarkMode ? '#e0e0e0' : '',
            'border': isDarkMode ? '1px solid #444' : ''
        });
        $('#explorerContextMenu').css({
            'background-color': isDarkMode ? '#2c2f36' : '',
            'color': isDarkMode ? '#e0e0e0' : '',
            'border': isDarkMode ? '1px solid #444' : ''
        });
        $('.dropdown-item').css({ 'color': isDarkMode ? '#e0e0e0' : '' });

        if (typeof monaco !== 'undefined' && editorInstance) {
            monaco.editor.setTheme(isDarkMode ? 'vs-dark' : 'vs');
        }
    }

    function setDarkMode(on) {
        $('body').toggleClass('darkmode', on);
        $('#darkmodeIcon').html(on ? '&#9728;' : '&#9790;');
        localStorage.setItem('darkmode', on ? '1' : '0');
        applySwalDarkmode();
    }

    // Loading Indicator
    function showLoading(message = 'Loading...') {
        Swal.fire({
            title: message,
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });
        applySwalDarkmode();
    }

    function hideLoading() {
        Swal.close();
    }

    // Project Creation
    $('#createProjectBtn').on('click', () => {
        $('#projectName').val('');
        $('#createProjectError').text('');
        createProjectModal.show();
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
            return;
        }
        Swal.fire({
            title: 'Create project?',
            text: `Create project "${name}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Create'
        }).then(result => {
            if (result.isConfirmed) {
                showLoading('Creating project...');
                $.post('api.php', { projectName: name }, (resp) => {
                    hideLoading();
                    if (resp.success) {
                        Swal.fire('Created!', '', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', resp.error || 'Failed to create project.', 'error');
                    }
                    applySwalDarkmode();
                }, 'json').fail(() => {
                    hideLoading();
                    Swal.fire('Error', 'Failed to create project.', 'error');
                    applySwalDarkmode();
                });
            }
        });
        applySwalDarkmode();
    });

    // Project Search
    $('#searchInput').on('input', function () {
        const query = $(this).val().toLowerCase();
        $('#projectGrid a.project-link').each(function () {
            $(this).closest('.col').toggle($(this).data('name').toLowerCase().includes(query));
        });
    });

    // Dark Mode Toggle
    $('#darkmodeToggle').on('click', () => setDarkMode(!$('body').hasClass('darkmode')));
    if (localStorage.getItem('darkmode') === '1' || 
        (localStorage.getItem('darkmode') === null && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        setDarkMode(true);
    } else {
        applySwalDarkmode();
    }

    // Project Editing
    $('.edit-project-btn').on('click', function () {
        const folder = $(this).data('folder');
        Swal.fire({
            title: 'Select Edit Mode',
            input: 'select',
            inputOptions: { name: 'Edit Project Name', file: 'Edit Project Files' },
            inputPlaceholder: 'Choose mode',
            showCancelButton: true
        }).then(choice => {
            if (!choice.isConfirmed) return;
            if (choice.value === 'name') {
                Swal.fire({
                    title: 'Edit Project Name',
                    input: 'text',
                    inputValue: folder,
                    inputAttributes: { maxlength: 32, autocapitalize: 'off', autocorrect: 'off' },
                    showCancelButton: true,
                    confirmButtonText: 'Rename',
                    preConfirm: newName => {
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
                }).then(result => {
                    if (result.isConfirmed) {
                        showLoading('Renaming project...');
                        $.post('api.php', { renameProject: 1, oldName: folder, newName: result.value }, resp => {
                            hideLoading();
                            if (resp.success) {
                                Swal.fire('Renamed!', '', 'success').then(() => location.reload());
                            } else {
                                Swal.fire('Error', resp.error || 'Failed to rename folder.', 'error');
                            }
                            applySwalDarkmode();
                        }, 'json').fail(() => {
                            hideLoading();
                            Swal.fire('Error', 'Failed to rename folder.', 'error');
                            applySwalDarkmode();
                        });
                    }
                });
                applySwalDarkmode();
            } else if (choice.value === 'file') {
                editorInstance.setValue('// pilih file untuk mulai mengedit ...');
                showLoading('Loading project...');
                $.getJSON('api.php', { listFiles: 1, folder: folder }, resp => {
                    hideLoading();
                    if (!resp.success) {
                        Swal.fire('Error', resp.error || 'Failed to load files.', 'error');
                        return;
                    }
                    renderExplorer(folder, $('#editorExplorer'));
                    $('#editorModal').show();
                });
            }
        });
        applySwalDarkmode();
    });

    // Project Deletion
    $('.delete-project-btn').on('click', function () {
        const name = $(this).data('folder');
        Swal.fire({
            title: 'Delete Project?',
            text: `Are you sure you want to delete project "${name}"? This cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33'
        }).then(result => {
            if (result.isConfirmed) {
                showLoading('Deleting project...');
                $.post('api.php', { deleteProject: 1, name: name }, resp => {
                    hideLoading();
                    if (resp.success) {
                        Swal.fire('Deleted!', '', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', resp.error || 'Failed to delete folder.', 'error');
                    }
                    applySwalDarkmode();
                }, 'json').fail(() => {
                    hideLoading();
                    Swal.fire('Error', 'Failed to delete folder.', 'error');
                    applySwalDarkmode();
                });
            }
        });
        applySwalDarkmode();
    });

    // Clock Functions
    function fetchWorldClock() {
        $.ajax({
            url: 'https://api.api-ninjas.com/v1/worldtime?timezone=asia/jakarta',
            method: 'GET',
            dataType: 'json',
            timeout: 3000,
            headers: { 'X-Api-Key': API_NINJAS_KEY },
            success: resp => {
                if (resp?.datetime) {
                    lastWorldTime = new Date(resp.datetime.replace(' ', 'T'));
                    lastWorldTimeTs = Date.now();
                    updateClockDisplay();
                } else {
                    updateClockLocal();
                }
            },
            error: () => updateClockLocal()
        });
    }

    function updateClockDisplay() {
        if (lastWorldTime && lastWorldTimeTs) {
            const now = new Date(lastWorldTime.getTime() + (Date.now() - lastWorldTimeTs));
            const pad = n => n.toString().padStart(2, '0');
            $('#realtimeClock').text(`${pad(now.getDate())} ${pad(now.getMonth() + 1)} ${now.getFullYear()} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} (Jakarta)`);
        } else {
            updateClockLocal();
        }
    }

    function updateClockLocal() {
        const now = new Date();
        const pad = n => n.toString().padStart(2, '0');
        $('#realtimeClock').text(`${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())} (local)`);
    }

    fetchWorldClock();
    setInterval(() => lastWorldTime ? updateClockDisplay() : updateClockLocal(), 1000);
    setInterval(fetchWorldClock, 60000);

    // GitHub Cloning
    $('#cloneProjectBtn').on('click', () => {
        $('#githubUrl').val('');
        $('#cloneFolder').val('');
        $('#cloneProjectError').text('');
        cloneModal.show();
    });

    $('#cloneProjectForm').on('submit', function (e) {
        e.preventDefault();
        const url = $('#githubUrl').val().trim();
        const folder = $('#cloneFolder').val().trim();
        if (!/^(https:\/\/([^@]+@)?github\.com\/[^/]+\/[^/]+(\.git)?|git@github\.com:[^/]+\/[^/]+(\.git)?)$/i.test(url)) {
            $('#cloneProjectError').text('Invalid GitHub URL.');
            return;
        }
        if (!/^[A-Za-z0-9_\- ]{1,32}$/.test(folder)) {
            $('#cloneProjectError').text('Invalid folder name.');
            return;
        }
        $('#cloneProjectError').text('');
        $('#cloneLog').text('');
        Swal.fire({
            title: 'Clone project?',
            text: `Clone "${url}" into folder "${folder}"?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Clone'
        }).then(result => {
            if (result.isConfirmed) {
                showLoading('Cloning project...');
                $.post('api.php', { cloneGithub: 1, githubUrl: url, cloneFolder: folder }, resp => {
                    if (resp.success && resp.jobId) {
                        hideLoading();
                        Swal.fire({
                            icon: 'success',
                            title: 'Clone Complete!',
                            text: 'The GitHub project was cloned successfully.',
                            confirmButtonText: 'OK'
                        }).then(() => location.reload());
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

    function pullProject(folder) {
        $.post('api.php', { pullGithub: true, folder: folder }, function(data) {
            if (data.success) {
                Swal.fire('Git Pull Started', 'Pulling changes for ' + folder, 'info');
                checkPullProgress(data.jobId, folder);
            } else {
                Swal.fire('Error', data.error, 'error');
            }
        });
    }

    function checkPullProgress(jobId, folder) {
        $.post('api.php', { pullProgress: true, jobId: jobId }, function(data) {
            if (data.done) {
                if (data.success) {
                    Swal.fire('Git Pull Complete', 'Repository updated successfully.', 'success');
                } else {
                    Swal.fire('Git Pull Failed', data.error, 'error');
                }
            } else {
                // Update progress (e.g., show percentage in UI)
                setTimeout(function() { checkPullProgress(jobId, folder); }, 1000);
            }
        });
    }
    // Link Management
    $('#manageLinksBtn').on('click', () => {
        loadLinks();
        $('#linkError').text('');
        linksModal.show();
    });

    function loadLinks() {
        $.get('link.txt', data => {
            const lines = data.trim().split('\n').filter(line => line.trim() !== '');
            const tbody = $('#linksTableBody').empty();
            if (lines.length === 0) {
                tbody.append('<tr><td colspan="3" class="text-center text-muted">No links available</td></tr>');
                $('#link-marquee').text('Select a project to open');
            } else {
                const html = lines.map(line => {
                    const [url, label = url] = line.split('|').map(s => s.trim());
                    return `<a href="${url}" target="_blank">${label}</a>`;
                }).join('');
                $('#link-marquee').html(html);
                lines.forEach(line => {
                    const [url, label = url] = line.split('|').map(s => s.trim());
                    tbody.append(`
                        <tr>
                            <td><input type="text" class="form-control form-control-sm link-url" value="${url}"></td>
                            <td><input type="text" class="form-control form-control-sm link-label" value="${label}"></td>
                            <td><button class="btn btn-danger btn-sm remove-link"><i class="fa fa-trash"></i></button></td>
                        </tr>
                    `);
                });
            }
        }).fail(() => $('#link-marquee').text('Select a project to open'));
    }

    $('#addLinkBtn').on('click', () => {
        $('#linksTableBody').append(`
            <tr>
                <td><input type="text" class="form-control form-control-sm link-url" placeholder="https://example.com"></td>
                <td><input type="text" class="form-control form-control-sm link-label" placeholder="My Project"></td>
                <td><button class="btn btn-danger btn-sm remove-link"><i class="fa fa-trash"></i></button></td>
            </tr>
        `);
    });

    $(document).on('click', '.remove-link', function () {
        $(this).closest('tr').remove();
    });

    $('#saveLinksBtn').on('click', () => {
        const rows = [];
        let error = false;
        $('#linksTableBody tr').each(function () {
            const url = $(this).find('.link-url').val().trim();
            const label = $(this).find('.link-label').val().trim();
            if (url) {
                if (!/^https?:\/\/.+/i.test(url)) {
                    $('#linkError').text(`Invalid URL: ${url}`);
                    error = true;
                    return false;
                }
                rows.push(`${url}|${label || url}`);
            }
        });
        if (error) return;
        $.post('api.php', { saveLinks: 1, links: rows }, resp => {
            if (resp.success) {
                Swal.fire('Saved!', 'Links updated successfully', 'success').then(() => {
                    linksModal.hide();
                    location.reload();
                });
            } else {
                $('#linkError').text(resp.error || 'Failed to save links.');
            }
            applySwalDarkmode();
        }, 'json').fail(() => $('#linkError').text('Failed to save links.'));
    });

    // Editor Functions
    require.config({ paths: { vs: 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.45.0/min/vs' } });
    require(['vs/editor/editor.main'], () => {
        editorInstance = monaco.editor.create(document.getElementById('monacoEditor'), {
            value: '// Pilih file untuk mulai mengedit...',
            language: 'php',
            theme: $('body').hasClass('darkmode') ? 'vs-dark' : 'vs',
            automaticLayout: true
        });
    });

    window.openFileInEditor = (path, content, language = 'php') => {
        currentFilePath = path;
        $('#editorFilename').text(path);
        editorInstance.setValue(content);
        monaco.editor.setModelLanguage(editorInstance.getModel(), language);
        $('#editorModal').show();
    };

    window.closeEditor = () => $('#editorModal').hide();

    window.saveFile = () => {
        const content = editorInstance.getValue();
        $.post('api.php', {
            saveFile: 1,
            folder: currentFilePath.split('/').slice(0, -1).join('/'),
            file: currentFilePath.split('/').pop(),
            content
        }, resp => {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: resp.success ? 'success' : 'error',
                title: resp.success ? 'File saved!' : (resp.error || 'Save failed'),
                showConfirmButton: false,
                timer: resp.success ? 2000 : 3000
            });
            applySwalDarkmode();
        }, 'json');
    };

    function renderExplorer(folder, container) {
        $.getJSON('api.php', { listFiles: 1, folder }, resp => {
            if (!resp.success) {
                Swal.fire('Error', resp.error || 'Failed to load files.', 'error');
                applySwalDarkmode();
                return;
            }
            container.html(`
                <div class="mb-2 d-flex justify-content-center gap-1">
                    <button class="btn btn-sm btn-primary create-file-btn" data-folder="${folder}" style="display:flex; align-items:center; gap:4px; font-size:0.85rem;">
                        <i class="fa fa-file"></i> File
                    </button>
                    <button class="btn btn-sm btn-success create-folder-btn" data-folder="${folder}" style="display:flex; align-items:center; gap:4px; font-size:0.85rem;">
                        <i class="fa fa-folder"></i> Folder
                    </button>
                </div>
                <div class="file-grid" style="display:flex; flex-direction:column; gap:4px; padding:2px;">
                    ${resp.entries.filter(e => !['.', '..'].includes(e.name)).map(entry => `
                        <button class="file-item btn btn-sm ${entry.type === 'dir' ? 'btn-secondary' : 'btn-outline-primary'} text-start" data-path="${entry.path}" data-type="${entry.type}">
                            <i class="fa fa-${entry.type === 'dir' ? 'folder' : 'file-code'} me-1"></i> ${entry.name}
                        </button>
                    `).join('')}
                </div>
            `);
        });
    }

    function guessLanguage(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        return { js: 'javascript', php: 'php', html: 'html', css: 'css', json: 'json' }[ext] || 'plaintext';
    }

    $(document).on('click', '.file-item[data-type="file"]', function () {
        const path = $(this).data('path');
        $.getJSON('api.php', { getFile: 1, folder: path.split('/').slice(0, -1).join('/'), file: path.split('/').pop() }, resp => {
            if (resp.success) {
                openFileInEditor(path, resp.content, guessLanguage(path));
            } else {
                Swal.fire('Error', resp.error || 'Tidak bisa buka file', 'error');
                applySwalDarkmode();
            }
        });
    });

    $(document).on('click', '.file-item[data-type="dir"]', function (e) {
        e.stopPropagation();
        const $btn = $(this);
        const path = $btn.data('path');
        if ($btn.hasClass('expanded')) {
            $btn.next('ul').toggle();
            return;
        }
        $.getJSON('api.php', { listFiles: 1, folder: path }, resp => {
            if (!resp.success) {
                Swal.fire('Error', resp.error || 'Failed to load folder.', 'error');
                applySwalDarkmode();
                return;
            }
            const subHtml = `<ul class="list-unstyled ms-3">${resp.entries.filter(e => !['.', '..'].includes(e.name)).map(entry => `
                <li>
                    <button class="file-item btn btn-sm ${entry.type === 'dir' ? 'btn-secondary' : 'btn-outline-primary'} text-start" data-path="${entry.path}" data-type="${entry.type}">
                        <i class="fa fa-${entry.type === 'dir' ? 'folder' : 'file-code'} me-1"></i> ${entry.name}
                    </button>
                </li>`).join('')}</ul>`;
            $btn.after(subHtml);
            $btn.addClass('expanded');
        });
    });

    function refreshExplorer() {
        const folder = $('.create-file-btn').data('folder') || $('.create-folder-btn').data('folder');
        if (folder) renderExplorer(folder, $('#editorExplorer'));
    }

    $(document).on('click', '.create-file-btn', function () {
        const folder = $(this).data('folder');
        Swal.fire({
            title: 'New File',
            input: 'text',
            inputPlaceholder: 'example.txt',
            showCancelButton: true,
            confirmButtonText: 'Create',
            preConfirm: val => val.trim()
        }).then(res => {
            if (!res.isConfirmed) return;
            $.post('api.php', { createFile: 1, folder, name: res.value }, resp => {
                Swal.fire({
                    icon: resp.success ? 'success' : 'error',
                    title: resp.success ? 'Berhasil!' : 'Error',
                    text: resp.success ? 'File berhasil dibuat' : (resp.error || 'Failed to create file.')
                });
                if (resp.success) refreshExplorer();
                applySwalDarkmode();
            }, 'json');
        });
        applySwalDarkmode();
    });

    $(document).on('click', '.create-folder-btn', function () {
        const folder = $(this).data('folder');
        Swal.fire({
            title: 'New Folder',
            input: 'text',
            inputPlaceholder: 'new-folder',
            showCancelButton: true,
            confirmButtonText: 'Create',
            preConfirm: val => val.trim(),
            didOpen: el => $(el).find('input').trigger('focus')
        }).then(res => {
            if (!res.isConfirmed) return;
            $.post('api.php', { createFolder: 1, folder, name: res.value }, resp => {
                Swal.fire({
                    icon: resp.success ? 'success' : 'error',
                    title: resp.success ? 'Berhasil!' : 'Error',
                    text: resp.success ? 'Folder berhasil dibuat' : (resp.error || 'Failed to create folder.')
                });
                if (resp.success) refreshExplorer();
                applySwalDarkmode();
            }, 'json');
        });
        applySwalDarkmode();
    });

    // Context Menu
    $(document).on('contextmenu', '.file-item, .folder-item', function (e) {
        e.preventDefault();
        e.stopPropagation();
        contextTargetPath = $(this).data('path');
        contextTargetType = $(this).data('type');
        $('#explorerContextMenu').css({ top: e.pageY, left: e.pageX }).show();
    });

    $(document).on('click contextmenu', e => {
        if (!$(e.target).closest('#explorerContextMenu').length) {
            $('#explorerContextMenu').hide();
        }
    });

    $('#ctx-new-file').on('click', function (e) {
        e.preventDefault();
        $('#explorerContextMenu').hide();
        Swal.fire({
            title: 'Nama file baru',
            input: 'text',
            inputPlaceholder: 'example.txt',
            showCancelButton: true,
            confirmButtonText: 'Create',
            preConfirm: val => val.trim()
        }).then(result => {
            if (!result.isConfirmed) return;
            $.post('api.php', { createFile: 1, folder: contextTargetPath, name: result.value }, resp => {
                Swal.fire({
                    icon: resp.success ? 'success' : 'error',
                    title: resp.success ? 'Berhasil!' : 'Error',
                    text: resp.success ? 'File berhasil dibuat' : (resp.error || 'Gagal membuat file')
                });
                if (resp.success) refreshExplorer();
                applySwalDarkmode();
            }, 'json');
        });
        applySwalDarkmode();
    });

    $('#ctx-new-folder').on('click', function (e) {
        e.preventDefault();
        $('#explorerContextMenu').hide();
        Swal.fire({
            title: 'Nama folder baru',
            input: 'text',
            inputPlaceholder: 'new-folder',
            showCancelButton: true,
            confirmButtonText: 'Create',
            preConfirm: val => val.trim()
        }).then(result => {
            if (!result.isConfirmed) return;
            $.post('api.php', { createFolder: 1, folder: contextTargetPath, name: result.value }, resp => {
                Swal.fire({
                    icon: resp.success ? 'success' : 'error',
                    title: resp.success ? 'Berhasil!' : 'Error',
                    text: resp.success ? 'Folder berhasil dibuat' : (resp.error || 'Gagal membuat folder')
                });
                if (resp.success) refreshExplorer();
                applySwalDarkmode();
            }, 'json');
        });
        applySwalDarkmode();
    });

    $('#ctx-delete').on('click', function (e) {
        e.preventDefault();
        $('#explorerContextMenu').hide();
        if (!contextTargetPath) return;
        Swal.fire({
            title: 'Delete?',
            text: `Yakin ingin menghapus ${contextTargetType} ini?\n${contextTargetPath}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Delete',
            confirmButtonColor: '#d33'
        }).then(res => {
            if (!res.isConfirmed) return;
            showLoading('Deleting...');
            $.post('api.php', { deleteEntry: 1, path: contextTargetPath }, resp => {
                hideLoading();
                Swal.fire({
                    icon: resp.success ? 'success' : 'error',
                    title: resp.success ? 'Deleted!' : 'Error',
                    text: resp.success ? '' : (resp.error || 'Failed to delete.')
                });
                if (resp.success) refreshExplorer();
                applySwalDarkmode();
            }, 'json').fail(() => {
                hideLoading();
                Swal.fire('Error', 'Failed to delete.', 'error');
                applySwalDarkmode();
            });
        });
        applySwalDarkmode();
    });

    $('#ctx-rename').on('click', function (e) {
        e.preventDefault();
        $('#explorerContextMenu').hide();
        if (!contextTargetPath) return;
        const currentName = contextTargetPath.split(/[\\/]/).pop();
        Swal.fire({
            title: `Rename ${contextTargetType}`,
            input: 'text',
            inputValue: currentName,
            showCancelButton: true,
            confirmButtonText: 'Rename',
            inputValidator: value => !value ? 'Nama tidak boleh kosong!' : null
        }).then(res => {
            if (!res.isConfirmed) return;
            showLoading('Renaming...');
            $.post('api.php', { renameEntry: 1, path: contextTargetPath, newName: res.value }, resp => {
                hideLoading();
                Swal.fire({
                    icon: resp.success ? 'success' : 'error',
                    title: resp.success ? 'Renamed!' : 'Error',
                    text: resp.success ? '' : (resp.error || 'Failed to rename.')
                });
                if (resp.success) refreshExplorer();
                applySwalDarkmode();
            }, 'json').fail(() => {
                hideLoading();
                Swal.fire('Error', 'Failed to rename.', 'error');
                applySwalDarkmode();
            });
        });
        applySwalDarkmode();
    });

    // Initialize
    renderExplorer('.', $('#explorerRoot'));

    window.pullProject = pullProject;
});