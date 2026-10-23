import { Controller } from '@hotwired/stimulus';

/**
 * Folds the main menu behind a button on small screens.
 */
export default class extends Controller {
    static targets = ['button'];
    static classes = ['open'];

    toggle() {
        const open = this.element.classList.toggle(this.openClass);
        this.buttonTarget.setAttribute('aria-expanded', String(open));
    }
}
