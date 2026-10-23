import { Controller } from '@hotwired/stimulus';

/**
 * Generates a party code easy to dictate (same alphabet as App\Party\AccessCodeGenerator).
 */
export default class extends Controller {
    static values = { alphabet: String, length: { type: Number, default: 6 } };

    generate() {
        const input = this.element.querySelector('[data-access-code-target="input"]');
        const random = crypto.getRandomValues(new Uint32Array(this.lengthValue));
        input.value = Array.from(random, (value) => this.alphabetValue[value % this.alphabetValue.length]).join('');
        input.focus();
    }
}
