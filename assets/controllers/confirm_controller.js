import { Controller } from '@hotwired/stimulus';

/**
 * Asks for confirmation in the <dialog id="confirm-dialog"> of the admin layout before
 * submitting a destructive form (no blocking window.confirm()).
 */
export default class extends Controller {
    static values = { message: String };

    ask(event) {
        if (this.confirmed) {
            return;
        }
        event.preventDefault();

        const dialog = document.getElementById('confirm-dialog');
        dialog.querySelector('[data-confirm-message]').textContent = this.messageValue;
        dialog.addEventListener('close', () => {
            if (dialog.returnValue === 'confirm') {
                this.confirmed = true;
                this.element.requestSubmit();
            }
        }, { once: true });
        dialog.showModal();
        dialog.querySelector('[data-confirm-accept]').focus();
    }
}
