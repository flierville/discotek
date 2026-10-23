import { Controller } from '@hotwired/stimulus';

/**
 * Back office navigation drawer on small screens.
 */
export default class extends Controller {
    static targets = ['panel', 'overlay'];

    open() {
        this.panelTarget.classList.remove('-translate-x-full');
        this.overlayTarget.classList.remove('hidden');
    }

    close() {
        this.panelTarget.classList.add('-translate-x-full');
        this.overlayTarget.classList.add('hidden');
    }
}
