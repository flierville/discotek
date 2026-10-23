import { Controller } from '@hotwired/stimulus';

/**
 * Fills the slug (URL shortcut) from the title until the slug is edited by hand.
 */
export default class extends Controller {
    static targets = ['source', 'slug'];
    static values = { locked: Boolean };

    sync() {
        if (!this.lockedValue) {
            this.slugTarget.value = this.constructor.slugify(this.sourceTarget.value);
        }
    }

    lock() {
        this.lockedValue = this.slugTarget.value !== '';
    }

    static slugify(text) {
        return text
            .normalize('NFD')
            .replace(/[̀-ͯ]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
}
