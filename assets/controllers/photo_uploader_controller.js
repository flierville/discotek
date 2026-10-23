import { Controller } from '@hotwired/stimulus';

/**
 * Drag & drop upload of party photos and zip archives.
 * - files are queued and sent 2 at a time;
 * - each file is sliced in chunks (PHP upload_max_filesize is too small for big zip archives);
 * - the server resizes the pictures / extracts the archive and returns the new thumbnails.
 */
const CHUNK_SIZE = 4 * 1024 * 1024;
const CONCURRENCY = 2;
const ACCEPTED = /\.(jpe?g|png|gif|webp|zip)$/i;

export default class extends Controller {
    static targets = ['dropzone', 'input', 'queue', 'grid', 'count', 'empty', 'progressPanel', 'progressBar', 'progressLabel', 'progressPercent', 'summary'];
    static values = { url: String, token: String };

    connect() {
        this.pending = [];
        this.running = 0;
        this.totalBytes = 0;
        this.sentBytes = 0;
        this.added = 0;
        this.failed = 0;
        this.onBeforeUnload = (event) => {
            if (this.running > 0 || this.pending.length > 0) {
                event.preventDefault();
            }
        };
        window.addEventListener('beforeunload', this.onBeforeUnload);
    }

    disconnect() {
        window.removeEventListener('beforeunload', this.onBeforeUnload);
    }

    dragOver(event) {
        event.preventDefault();
        this.dropzoneTarget.classList.add('is-dragover');
    }

    dragLeave() {
        this.dropzoneTarget.classList.remove('is-dragover');
    }

    drop(event) {
        event.preventDefault();
        this.dragLeave();
        this.enqueue(event.dataTransfer.files);
    }

    select() {
        this.enqueue(this.inputTarget.files);
        this.inputTarget.value = '';
    }

    enqueue(fileList) {
        const files = Array.from(fileList);
        if (files.length === 0) {
            return;
        }
        this.progressPanelTarget.classList.remove('hidden');

        for (const file of files) {
            const row = this.createRow(file);
            if (!ACCEPTED.test(file.name)) {
                this.setRowState(row, 'error', 'format non pris en charge');
                this.failed++;
                continue;
            }
            this.totalBytes += file.size;
            this.pending.push({ file, row });
        }
        this.updateProgress();
        this.next();
    }

    next() {
        while (this.running < CONCURRENCY && this.pending.length > 0) {
            const job = this.pending.shift();
            this.running++;
            this.send(job)
                .catch((error) => {
                    this.failed++;
                    this.setRowState(job.row, 'error', error.message);
                })
                .finally(() => {
                    this.running--;
                    this.updateProgress();
                    this.next();
                });
        }
    }

    async send({ file, row }) {
        this.setRowState(row, 'progress', 'envoi...');
        const uploadId = `${Date.now().toString(36)}-${crypto.getRandomValues(new Uint32Array(2)).join('')}`;
        const count = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));
        let result = null;

        for (let index = 0; index < count; index++) {
            const chunk = file.slice(index * CHUNK_SIZE, (index + 1) * CHUNK_SIZE);
            const body = new FormData();
            body.append('chunk', chunk, file.name);
            body.append('name', file.name);
            body.append('uploadId', uploadId);
            body.append('index', String(index));
            body.append('count', String(count));

            if (index === count - 1) {
                this.setRowState(row, 'progress', file.name.toLowerCase().endsWith('.zip') ? 'décompression et redimensionnement...' : 'redimensionnement...');
            }

            const response = await fetch(this.urlValue, {
                method: 'POST',
                body,
                headers: { 'X-CSRF-Token': this.tokenValue, Accept: 'application/json' },
            });
            const json = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(json.error ?? `erreur ${response.status}`);
            }
            this.sentBytes += chunk.size;
            this.updateProgress();
            result = json;
        }

        result.photos.forEach((photo) => this.addThumbnail(photo));
        this.added += result.photos.length;
        const rejected = result.rejected.length > 0 ? ` (${result.rejected.length} fichier(s) ignoré(s))` : '';
        this.setRowState(row, 'done', `${result.photos.length} photo(s) ajoutée(s)${rejected}`);
    }

    async deletePhoto(event) {
        event.preventDefault();
        const form = event.currentTarget;
        const figure = form.closest('figure');
        figure.classList.add('opacity-40');
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json' },
        });
        if (response.ok) {
            figure.remove();
            this.countTarget.textContent = String(this.gridTarget.children.length);
        } else {
            figure.classList.remove('opacity-40');
        }
    }

    addThumbnail(photo) {
        const figure = document.createElement('figure');
        figure.className = 'group relative aspect-square overflow-hidden rounded-lg bg-slate-100 ring-1 ring-slate-200';
        figure.dataset.photoId = photo.id;

        const link = document.createElement('a');
        link.href = photo.large;
        link.target = '_blank';
        const image = document.createElement('img');
        image.src = photo.thumb;
        image.alt = photo.name;
        image.className = 'size-full object-cover';
        link.append(image);

        const form = document.createElement('form');
        form.method = 'post';
        form.action = photo.deleteUrl;
        form.className = 'absolute top-1 right-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100';
        form.dataset.action = 'photo-uploader#deletePhoto';
        const token = document.createElement('input');
        token.type = 'hidden';
        token.name = '_token';
        token.value = this.tokenValue;
        const button = document.createElement('button');
        button.type = 'submit';
        button.title = 'Supprimer cette photo';
        button.className = 'flex size-7 items-center justify-center rounded-full bg-white/90 text-red-600 shadow hover:bg-red-600 hover:text-white';
        button.textContent = '×';
        form.append(token, button);

        figure.append(link, form);
        this.gridTarget.prepend(figure);
        this.countTarget.textContent = String(this.gridTarget.children.length);
        if (this.hasEmptyTarget) {
            this.emptyTarget.remove();
        }
    }

    createRow(file) {
        const row = document.createElement('li');
        row.className = 'flex items-center justify-between gap-3 rounded-md bg-slate-50 px-3 py-1.5';
        const name = document.createElement('span');
        name.className = 'truncate font-medium text-slate-700';
        name.textContent = `${file.name} (${(file.size / 1024 / 1024).toFixed(1)} Mo)`;
        const state = document.createElement('span');
        state.className = 'shrink-0 text-slate-500';
        state.textContent = 'en attente';
        row.append(name, state);
        this.queueTarget.append(row);

        return row;
    }

    setRowState(row, state, text) {
        const label = row.lastElementChild;
        label.textContent = text;
        label.className = `shrink-0 ${{ error: 'text-red-600', done: 'text-emerald-600', progress: 'text-brand-600' }[state] ?? 'text-slate-500'}`;
        if (state === 'progress') {
            row.scrollIntoView({ block: 'nearest' });
        }
    }

    updateProgress() {
        const percent = this.totalBytes === 0 ? 100 : Math.round((this.sentBytes / this.totalBytes) * 100);
        this.progressBarTarget.style.width = `${percent}%`;
        this.progressPercentTarget.textContent = `${percent} %`;
        const working = this.running > 0 || this.pending.length > 0;
        this.progressLabelTarget.textContent = working
            ? `Envoi en cours... (${this.pending.length} en attente)`
            : `Terminé : ${this.added} photo(s) ajoutée(s)${this.failed > 0 ? `, ${this.failed} erreur(s)` : ''}`;
        this.summaryTarget.textContent = working ? 'Ne fermez pas la page pendant l\'envoi' : '';
    }
}
