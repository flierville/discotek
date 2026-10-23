import { Controller } from '@hotwired/stimulus';

/**
 * WYSIWYG editor (TinyMCE, the editor of the WordPress classic editor), loaded from the CDN
 * only in the back office: no Node.js build needed.
 */
const TINYMCE_URL = 'https://cdn.jsdelivr.net/npm/tinymce@7.6.0/tinymce.min.js';

let tinymceLoading = null;
function loadTinymce() {
    tinymceLoading ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = TINYMCE_URL;
        script.referrerPolicy = 'origin';
        script.onload = () => resolve(window.tinymce);
        script.onerror = reject;
        document.head.appendChild(script);
    });

    return tinymceLoading;
}

export default class extends Controller {
    static values = { uploadUrl: String, uploadToken: String, contentCss: String };

    async connect() {
        const tinymce = await loadTinymce();
        const [editor] = await tinymce.init({
            target: this.element,
            license_key: 'gpl',
            language: 'fr_FR',
            language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@24.12.9/langs7/fr_FR.js',
            height: 620,
            menubar: 'edit insert format table',
            branding: false,
            promotion: false,
            plugins: 'advlist autolink link image lists charmap preview searchreplace visualblocks code fullscreen media table wordcount',
            toolbar: 'undo redo | blocks | bold italic underline forecolor backcolor | alignleft aligncenter alignright | bullist numlist | link image media table | removeformat code fullscreen',
            block_formats: 'Paragraphe=p; Titre 1=h1; Titre 2=h2; Titre 3=h3',
            // Same look as on the site: dark background and legacy styles.
            // Absolute URL: TinyMCE would resolve a path against its CDN.
            content_css: new URL(this.contentCssValue, window.location.href).href,
            body_class: 'rich-text',
            content_style: 'body { background:#181b19; color:#c1bcac; font-family:"Trebuchet MS",Tahoma,Arial,sans-serif; font-size:13px; line-height:1.4em; padding:12px; }',
            relative_urls: false,
            convert_urls: false,
            image_caption: false,
            images_upload_handler: (blobInfo, progress) => this.upload(blobInfo, progress),
            media_live_embeds: true,
        });
        this.editor = editor;
        // Keep the textarea in sync for the form submission.
        this.element.form?.addEventListener('submit', () => tinymce.triggerSave());
    }

    disconnect() {
        this.editor?.remove();
    }

    async upload(blobInfo) {
        const body = new FormData();
        body.append('file', blobInfo.blob(), blobInfo.filename());
        const response = await fetch(this.uploadUrlValue, {
            method: 'POST',
            body,
            headers: { 'X-CSRF-Token': this.uploadTokenValue },
        });
        const json = await response.json();
        if (!response.ok) {
            throw new Error(json.error?.message ?? "Erreur lors de l'envoi de l'image");
        }

        return json.location;
    }
}
