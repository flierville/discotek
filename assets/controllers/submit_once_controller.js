import { Controller } from '@hotwired/stimulus';

/**
 * Prevents double submissions (and thus duplicated quotes / emails).
 */
export default class extends Controller {
    static targets = ['button'];

    connect() {
        this.onSubmit = () => {
            this.buttonTargets.forEach((button) => {
                button.disabled = true;
                button.dataset.label = button.textContent;
                button.textContent = 'Envoi en cours...';
            });
        };
        this.element.addEventListener('submit', this.onSubmit);
    }

    disconnect() {
        this.element.removeEventListener('submit', this.onSubmit);
    }
}
