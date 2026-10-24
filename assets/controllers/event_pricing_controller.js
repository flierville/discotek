import { Controller } from '@hotwired/stimulus';

/**
 * Shows only the price field matching the pricing chosen for an event type (fixed price or bonus / malus).
 */
export default class extends Controller {
    static targets = ['mode', 'field'];

    connect() {
        this.toggle();
    }

    toggle() {
        this.fieldTargets.forEach((field) => {
            field.hidden = field.dataset.pricing !== this.modeTarget.value;
        });
    }
}
