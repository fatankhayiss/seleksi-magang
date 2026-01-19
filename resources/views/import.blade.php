<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk CSV Import</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">
    <div class="max-w-3xl mx-auto py-10 px-4">
        <h1 class="text-2xl font-semibold mb-6">Bulk CSV Import (Users)</h1>

        <div class="bg-white shadow rounded p-6">
            <form id="uploadForm" class="space-y-4" method="post" action="#" autocomplete="off">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                    <input id="fileInput" type="file" name="file" accept=".csv" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" required />
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50" id="uploadBtn">Upload</button>
                    <span id="statusText" class="text-sm text-gray-600"></span>
                </div>
            </form>
        </div>

        <div id="progressCard" class="bg-white shadow rounded p-6 mt-6 hidden">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-medium">Import Progress</h2>
                <span id="importId" class="text-sm text-gray-500"></span>
            </div>

            <div id="successBanner" class="hidden mb-4 rounded border border-green-200 bg-green-50 text-green-700 px-4 py-3 text-sm">
                ✅ Import selesai. Data berhasil diimpor.
            </div>

            <div class="w-full bg-gray-200 rounded h-3 overflow-hidden">
                <div id="progressBar" class="bg-green-500 h-3 transition-all" style="width: 0%"></div>
            </div>
            <div class="mt-3 text-sm text-gray-700">
                <span id="processed">0</span> / <span id="total">0</span> rows
                <span id="percent" class="ml-2 text-gray-500">(0%)</span>
            </div>

            <div id="logs" class="mt-4 text-sm text-gray-600"></div>
        </div>
    </div>

    <script>
        const form = document.getElementById('uploadForm');
        const fileInput = document.getElementById('fileInput');
        const uploadBtn = document.getElementById('uploadBtn');
        const statusText = document.getElementById('statusText');

        const card = document.getElementById('progressCard');
        const progressBar = document.getElementById('progressBar');
        const processedEl = document.getElementById('processed');
        const totalEl = document.getElementById('total');
        const percentEl = document.getElementById('percent');
        const importIdEl = document.getElementById('importId');
        const logs = document.getElementById('logs');
        const successBanner = document.getElementById('successBanner');

        let pollTimer = null;
        let isUploading = false;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const file = fileInput.files[0];
            if (!file) return;
            if (isUploading) return; // guard: prevent duplicate submissions
            isUploading = true;

            uploadBtn.disabled = true;
            statusText.textContent = 'Uploading…';
            logs.textContent = '';

            try {
                const formData = new FormData();
                formData.append('file', file);

                const res = await fetch('/api/import-users', {
                    method: 'POST',
                    body: formData
                });

                if (!res.ok) {
                    const text = await res.text();
                    throw new Error('Upload failed: ' + text);
                }

                const data = await res.json();
                if (!data.import_id) {
                    throw new Error('No import_id returned');
                }

                statusText.textContent = 'Processing…';
                card.classList.remove('hidden');
                importIdEl.textContent = `ID: ${data.import_id}`;
                progressBar.style.width = '0%';
                processedEl.textContent = '0';
                totalEl.textContent = '0';
                percentEl.textContent = '(0%)';
                successBanner.classList.add('hidden');

                startPolling(data.import_id);
            } catch (err) {
                console.error(err);
                alert(err.message || 'Upload error');
                statusText.textContent = '';
                isUploading = false;
                uploadBtn.disabled = false;
            } finally {
                // keep disabled; re-enable after processing completes or on error
            }
        });

        function startPolling(id) {
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(async () => {
                try {
                    const res = await fetch(`/api/import-users/${id}`);
                    if (!res.ok) return;
                    const data = await res.json();

                    const total = Number(data.total_rows || 0);
                    const processed = Number(data.processed_rows || 0);
                    const percent = total > 0 ? Math.min(100, Math.round((processed / Math.max(1, total)) * 100)) : 0;

                    processedEl.textContent = processed.toLocaleString();
                    totalEl.textContent = total.toLocaleString();
                    percentEl.textContent = `(${percent}%)`;
                    progressBar.style.width = `${percent}%`;

                    if (data.status === 'done') {
                        statusText.textContent = 'Completed';
                        clearInterval(pollTimer);
                        pollTimer = null;
                        isUploading = false;
                        uploadBtn.disabled = false;
                        successBanner.classList.remove('hidden');
                        // optional: reset form so user can upload another file
                        // form.reset();
                    }
                } catch (e) {
                    console.warn('Polling error', e);
                }
            }, 1000);
        }
    </script>
</body>
</html>
