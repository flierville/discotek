import { Controller } from '@hotwired/stimulus';

/**
 * Flash message closed by its button, or automatically after a few seconds when it is a success.
 */
export default class extends Controller {
    connect() {
        if (this.element.getAttribute('role') === 'status') {
            this.timer = setTimeout(() => this.dismiss(), 6000);
        }
    }

    disconnect() {
        clearTimeout(this.timer);
    }

    dismiss() {
        this.element.remove();
    }
}
