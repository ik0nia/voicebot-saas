<div class="p-6">
    <h3 class="text-lg font-semibold text-slate-900 mb-1">Upload Fisiere</h3>
    <p class="text-sm text-slate-500 mb-6">Incarca fisiere PDF, DOCX, TXT sau CSV. Continutul va fi extras, fragmentat si vectorizat automat.</p>

    {{-- Upload tabs --}}
    <div class="flex border-b border-slate-200 mb-6">
        <button onclick="switchUploadTab('text')" id="utab-text" class="upload-tab-btn px-4 py-2.5 text-sm font-medium border-b-2 transition-colors border-red-800 text-red-800">Text</button>
        <button onclick="switchUploadTab('url')" id="utab-url" class="upload-tab-btn px-4 py-2.5 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">URL</button>
        <button onclick="switchUploadTab('file')" id="utab-file" class="upload-tab-btn px-4 py-2.5 text-sm font-medium border-b-2 transition-colors border-transparent text-slate-500 hover:text-slate-700">Fisier</button>
    </div>

    {{-- Validation errors banner --}}
    @if($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-red-600 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-700">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Text form --}}
    <form id="uform-text" action="/dashboard/boti/{{ $bot->id }}/knowledge" method="POST" class="space-y-4">
        @csrf
        <input type="hidden" name="type" value="text">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Titlu</label>
            <input type="text" name="title" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" placeholder="ex: Informatii despre companie">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Continut</label>
            <textarea name="content" rows="8" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition resize-y" placeholder="Introdu textul..."></textarea>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adauga
            </button>
        </div>
    </form>

    {{-- URL form --}}
    <form id="uform-url" action="/dashboard/boti/{{ $bot->id }}/knowledge" method="POST" class="space-y-4 hidden">
        @csrf
        <input type="hidden" name="type" value="url">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Titlu</label>
            <input type="text" name="title" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" placeholder="ex: Pagina de preturi">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">URL</label>
            <input type="url" name="url" required class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-red-700 focus:ring-2 focus:ring-red-700/20 outline-none transition" placeholder="https://exemplu.ro/pagina">
        </div>
        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adauga
            </button>
        </div>
    </form>

    {{-- File form -- BATCH UPLOAD cu progress per fisier --}}
    <div id="uform-file" class="space-y-4 hidden">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Fisiere</label>
            <div id="upload-drop-zone" class="relative border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-red-400 hover:bg-red-50/50 transition-all duration-200 cursor-pointer"
                 onclick="document.getElementById('upload-file-input').click()">
                <input type="file" id="upload-file-input" multiple accept=".pdf,.docx,.txt,.csv" class="hidden" onchange="handleBatchFileSelect(this)">
                <svg class="w-10 h-10 text-slate-400 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <p id="upload-drop-text" class="text-sm text-slate-600">
                    <span class="font-semibold text-red-800">Click pentru a alege</span> sau trage fisierele aici
                </p>
                <p id="upload-drop-hint" class="text-xs text-slate-400 mt-1">PDF, DOCX, TXT, CSV &mdash; max. 10 MB per fisier &mdash; poti selecta mai multe fisiere odata</p>
            </div>
        </div>

        {{-- File queue list (appears after selection) --}}
        <div id="upload-file-queue" class="hidden space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-sm font-medium text-slate-700">Fisiere selectate</label>
                <button type="button" onclick="clearFileQueue()" class="text-xs text-slate-500 hover:text-red-600 transition-colors">Sterge toate</button>
            </div>
            <div id="upload-file-list" class="space-y-2 max-h-80 overflow-y-auto"></div>
            <div id="upload-batch-summary" class="flex items-center justify-between pt-2 border-t border-slate-200">
                <span class="text-xs text-slate-500"><span id="batch-file-count">0</span> fisiere &mdash; <span id="batch-total-size">0 MB</span></span>
                <button type="button" onclick="startBatchUpload()" id="btn-batch-upload" class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-800 text-white text-sm font-semibold rounded-lg hover:bg-red-900 transition-colors shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    Incarca toate
                </button>
            </div>
        </div>

        {{-- Batch upload global progress --}}
        <div id="upload-batch-progress" class="hidden">
            <div class="bg-slate-50 rounded-lg border border-slate-200 p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-slate-700">Progres upload</span>
                    <span id="batch-progress-label" class="text-xs text-slate-500">0 / 0</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2">
                    <div id="batch-progress-bar" class="bg-red-700 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function switchUploadTab(tab) {
        document.querySelectorAll('[id^="uform-"]').forEach(function(f) { f.classList.add('hidden'); });
        document.querySelectorAll('.upload-tab-btn').forEach(function(t) {
            t.classList.remove('border-red-800', 'text-red-800');
            t.classList.add('border-transparent', 'text-slate-500');
        });
        document.getElementById('uform-' + tab).classList.remove('hidden');
        var btn = document.getElementById('utab-' + tab);
        btn.classList.remove('border-transparent', 'text-slate-500');
        btn.classList.add('border-red-800', 'text-red-800');
    }

    // ─── Batch upload state ───
    var batchFiles = [];
    var ALLOWED_EXTENSIONS = ['pdf', 'docx', 'txt', 'csv'];
    var MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

    function getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function generateTitleFromFilename(filename) {
        // Remove extension, replace separators with spaces, capitalize first letter
        var name = filename.replace(/\.[^/.]+$/, '');
        name = name.replace(/[-_]+/g, ' ').replace(/\s+/g, ' ').trim();
        return name.charAt(0).toUpperCase() + name.slice(1);
    }

    function handleBatchFileSelect(input) {
        if (!input.files || input.files.length === 0) return;
        addFilesToQueue(input.files);
    }

    function addFilesToQueue(fileList) {
        var errors = [];
        for (var i = 0; i < fileList.length; i++) {
            var file = fileList[i];
            var ext = getFileExtension(file.name);

            if (ALLOWED_EXTENSIONS.indexOf(ext) === -1) {
                errors.push(file.name + ': format nesuportat (doar PDF, DOCX, TXT, CSV)');
                continue;
            }
            if (file.size > MAX_FILE_SIZE) {
                errors.push(file.name + ': depaseste limita de 10 MB (' + formatFileSize(file.size) + ')');
                continue;
            }
            // Check for duplicates
            var isDuplicate = batchFiles.some(function(bf) { return bf.file.name === file.name && bf.file.size === file.size; });
            if (isDuplicate) {
                errors.push(file.name + ': deja adaugat');
                continue;
            }

            batchFiles.push({
                file: file,
                title: generateTitleFromFilename(file.name),
                status: 'pending', // pending | uploading | done | error
                progress: 0,
                errorMsg: ''
            });
        }

        if (errors.length > 0) {
            showUploadToast(errors.join('\n'), 'warning');
        }

        renderFileQueue();
    }

    function removeFileFromQueue(index) {
        batchFiles.splice(index, 1);
        renderFileQueue();
    }

    function clearFileQueue() {
        batchFiles = [];
        renderFileQueue();
        document.getElementById('upload-file-input').value = '';
    }

    function updateFileTitle(index, value) {
        if (batchFiles[index]) {
            batchFiles[index].title = value;
        }
    }

    function renderFileQueue() {
        var listEl = document.getElementById('upload-file-list');
        var queueEl = document.getElementById('upload-file-queue');
        var dropText = document.getElementById('upload-drop-text');
        var dropHint = document.getElementById('upload-drop-hint');

        if (batchFiles.length === 0) {
            queueEl.classList.add('hidden');
            dropText.innerHTML = '<span class="font-semibold text-red-800">Click pentru a alege</span> sau trage fisierele aici';
            dropHint.classList.remove('hidden');
            listEl.innerHTML = '';
            return;
        }

        queueEl.classList.remove('hidden');
        dropText.innerHTML = '<span class="font-semibold text-red-800">+ Adauga mai multe fisiere</span>';
        dropHint.classList.add('hidden');

        var totalSize = 0;
        var html = '';

        batchFiles.forEach(function(item, idx) {
            totalSize += item.file.size;
            var ext = getFileExtension(item.file.name);
            var extColor = ext === 'pdf' ? 'bg-red-100 text-red-700' :
                           ext === 'docx' ? 'bg-blue-100 text-blue-700' :
                           ext === 'csv' ? 'bg-green-100 text-green-700' :
                           'bg-slate-100 text-slate-600';

            var statusIcon = '';
            var rowOpacity = '';
            if (item.status === 'uploading') {
                statusIcon = '<svg class="w-4 h-4 animate-spin text-red-700 shrink-0" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>';
            } else if (item.status === 'done') {
                statusIcon = '<svg class="w-4 h-4 text-green-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>';
                rowOpacity = 'opacity-60';
            } else if (item.status === 'error') {
                statusIcon = '<svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>';
            }

            html += '<div class="flex items-center gap-3 bg-white border border-slate-200 rounded-lg px-3 py-2.5 ' + rowOpacity + '">';
            html += '  <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ' + extColor + '">' + ext + '</span>';
            html += '  <div class="flex-1 min-w-0">';
            if (item.status === 'pending') {
                html += '    <input type="text" value="' + item.title.replace(/"/g, '&quot;') + '" onchange="updateFileTitle(' + idx + ', this.value)" class="w-full text-sm text-slate-900 border-0 border-b border-transparent focus:border-slate-300 outline-none px-0 py-0.5 bg-transparent placeholder-slate-400" placeholder="Titlu document">';
            } else {
                html += '    <span class="text-sm text-slate-900 truncate block">' + item.title + '</span>';
            }
            html += '    <span class="text-[11px] text-slate-400">' + item.file.name + ' &mdash; ' + formatFileSize(item.file.size) + '</span>';
            if (item.status === 'error') {
                html += '    <span class="text-[11px] text-red-500 block">' + item.errorMsg + '</span>';
            }
            if (item.status === 'uploading') {
                html += '    <div class="w-full bg-slate-200 rounded-full h-1 mt-1"><div class="bg-red-700 h-1 rounded-full transition-all duration-200" style="width:' + item.progress + '%"></div></div>';
            }
            html += '  </div>';
            html += '  <div class="flex items-center gap-1.5">';
            html += statusIcon;
            if (item.status === 'pending') {
                html += '    <button type="button" onclick="removeFileFromQueue(' + idx + ')" class="p-1 text-slate-400 hover:text-red-500 transition-colors"><svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>';
            }
            html += '  </div>';
            html += '</div>';
        });

        listEl.innerHTML = html;
        document.getElementById('batch-file-count').textContent = batchFiles.length;
        document.getElementById('batch-total-size').textContent = formatFileSize(totalSize);
    }

    // ─── Batch sequential upload via XHR ───

    async function startBatchUpload() {
        if (batchFiles.length === 0) return;

        var pendingFiles = batchFiles.filter(function(f) { return f.status === 'pending'; });
        if (pendingFiles.length === 0) {
            showUploadToast('Toate fisierele au fost deja incarcate.', 'info');
            return;
        }

        // Validate titles
        for (var i = 0; i < batchFiles.length; i++) {
            if (batchFiles[i].status === 'pending' && !batchFiles[i].title.trim()) {
                showUploadToast('Fisierul "' + batchFiles[i].file.name + '" nu are titlu.', 'warning');
                return;
            }
        }

        var btn = document.getElementById('btn-batch-upload');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg> Se incarca...';

        document.getElementById('upload-batch-progress').classList.remove('hidden');

        var completed = 0;
        var total = pendingFiles.length;

        for (var i = 0; i < batchFiles.length; i++) {
            if (batchFiles[i].status !== 'pending') continue;

            batchFiles[i].status = 'uploading';
            renderFileQueue();

            try {
                await uploadSingleFile(i);
                batchFiles[i].status = 'done';
                batchFiles[i].progress = 100;
                completed++;
            } catch (err) {
                batchFiles[i].status = 'error';
                batchFiles[i].errorMsg = err.message || 'Eroare la upload';
            }

            renderFileQueue();
            updateBatchProgress(completed, total);
        }

        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg> Incarca toate';

        var errored = batchFiles.filter(function(f) { return f.status === 'error'; }).length;
        if (errored === 0) {
            showUploadToast(completed + ' fisiere incarcate cu succes!', 'success');
            setTimeout(function() { location.reload(); }, 2000);
        } else {
            showUploadToast(completed + ' incarcate, ' + errored + ' esuate. Poti reincerca fisierele esuate.', 'warning');
            // Reset errored files to pending for retry
            batchFiles.forEach(function(f) {
                if (f.status === 'error') f.status = 'pending';
            });
            renderFileQueue();
        }
    }

    function uploadSingleFile(index) {
        return new Promise(function(resolve, reject) {
            var item = batchFiles[index];
            var formData = new FormData();
            formData.append('_token', '{{ csrf_token() }}');
            formData.append('type', getFileExtension(item.file.name));
            formData.append('title', item.title.trim());
            formData.append('file', item.file);

            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/dashboard/boti/{{ $bot->id }}/knowledge', true);
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    batchFiles[index].progress = Math.round((e.loaded / e.total) * 100);
                    renderFileQueue();
                }
            };

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve();
                } else {
                    var msg = 'Eroare server (' + xhr.status + ')';
                    try {
                        var resp = JSON.parse(xhr.responseText);
                        if (resp.message) msg = resp.message;
                        if (resp.errors) {
                            var firstErr = Object.values(resp.errors)[0];
                            if (Array.isArray(firstErr)) msg = firstErr[0];
                        }
                    } catch(e) {}
                    reject(new Error(msg));
                }
            };

            xhr.onerror = function() {
                reject(new Error('Eroare de conexiune'));
            };

            xhr.send(formData);
        });
    }

    function updateBatchProgress(completed, total) {
        var pct = total > 0 ? Math.round((completed / total) * 100) : 0;
        document.getElementById('batch-progress-bar').style.width = pct + '%';
        document.getElementById('batch-progress-label').textContent = completed + ' / ' + total + ' fisiere';
    }

    // ─── Toast notifications ───

    function showUploadToast(message, type) {
        var colors = {
            success: 'bg-green-50 border-green-200 text-green-800',
            warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
            error: 'bg-red-50 border-red-200 text-red-800',
            info: 'bg-blue-50 border-blue-200 text-blue-800'
        };
        var toastEl = document.createElement('div');
        toastEl.className = 'fixed top-4 right-4 z-50 max-w-sm p-3 rounded-lg border text-sm shadow-lg transition-all duration-300 ' + (colors[type] || colors.info);
        toastEl.style.whiteSpace = 'pre-line';
        toastEl.textContent = message;
        document.body.appendChild(toastEl);
        setTimeout(function() {
            toastEl.style.opacity = '0';
            setTimeout(function() { toastEl.remove(); }, 300);
        }, 5000);
    }

    // ─── Drag and drop (supports multiple files) ───
    (function() {
        var dz = document.getElementById('upload-drop-zone');
        if (!dz) return;

        ['dragenter', 'dragover'].forEach(function(e) {
            dz.addEventListener(e, function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                dz.classList.add('border-red-400', 'bg-red-50', 'scale-[1.01]');
            });
        });
        ['dragleave', 'drop'].forEach(function(e) {
            dz.addEventListener(e, function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                dz.classList.remove('border-red-400', 'bg-red-50', 'scale-[1.01]');
            });
        });
        dz.addEventListener('drop', function(e) {
            if (e.dataTransfer.files.length > 0) {
                addFilesToQueue(e.dataTransfer.files);
            }
        });
    })();
</script>
@endpush
