import { Controller } from '@hotwired/stimulus';

/**
 * City autocomplete (postal code or beginning of the name), accessible combobox.
 * Choosing a city fills the hidden postal code and coordinates used to compute the travel fee.
 */
export default class extends Controller {
    static targets = ['input', 'list', 'postalCode', 'latitude', 'longitude'];
    static values = { url: String, minLength: { type: Number, default: 2 } };

    connect() {
        this.cache = new Map();
        this.results = [];
        this.activeIndex = -1;
        this.onOutsideClick = (event) => {
            if (!this.element.contains(event.target)) {
                this.close();
            }
        };
        document.addEventListener('click', this.onOutsideClick);
    }

    disconnect() {
        document.removeEventListener('click', this.onOutsideClick);
        clearTimeout(this.timer);
    }

    search() {
        this.clearLocation();
        clearTimeout(this.timer);
        const term = this.inputTarget.value.trim();
        if (term.length < this.minLengthValue) {
            this.close();
            return;
        }
        this.timer = setTimeout(() => this.fetch(term), 150);
    }

    async fetch(term) {
        if (!this.cache.has(term)) {
            try {
                const url = new URL(this.urlValue, window.location.origin);
                url.searchParams.set('q', term);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                this.cache.set(term, response.ok ? await response.json() : []);
            } catch {
                this.cache.set(term, []);
            }
        }
        // Ignore late answers for an outdated term.
        if (term === this.inputTarget.value.trim()) {
            this.render(this.cache.get(term));
        }
    }

    render(results) {
        this.results = results;
        this.activeIndex = -1;
        this.listTarget.replaceChildren(...results.map((city, index) => {
            const option = document.createElement('div');
            option.className = 'autocomplete__option';
            option.id = `${this.listTarget.id}_${index}`;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.textContent = city.label;
            option.addEventListener('mousedown', (event) => {
                event.preventDefault();
                this.choose(index);
            });
            return option;
        }));
        results.length > 0 ? this.openList() : this.close();
    }

    navigate(event) {
        if (this.listTarget.hidden || this.results.length === 0) {
            return;
        }
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                this.highlight(Math.min(this.activeIndex + 1, this.results.length - 1));
                break;
            case 'ArrowUp':
                event.preventDefault();
                this.highlight(Math.max(this.activeIndex - 1, 0));
                break;
            case 'Enter':
                if (this.activeIndex >= 0) {
                    event.preventDefault();
                    this.choose(this.activeIndex);
                }
                break;
            case 'Escape':
                this.close();
                break;
        }
    }

    highlight(index) {
        this.activeIndex = index;
        [...this.listTarget.children].forEach((option, position) => {
            option.setAttribute('aria-selected', String(position === index));
            if (position === index) {
                option.scrollIntoView({ block: 'nearest' });
                this.inputTarget.setAttribute('aria-activedescendant', option.id);
            }
        });
    }

    choose(index) {
        const city = this.results[index];
        this.inputTarget.value = city.name;
        this.postalCodeTarget.value = city.postalCode;
        this.latitudeTarget.value = city.latitude;
        this.longitudeTarget.value = city.longitude;
        this.inputTarget.classList.remove('is-invalid');
        this.close();
    }

    clearLocation() {
        this.postalCodeTarget.value = '';
        this.latitudeTarget.value = '';
        this.longitudeTarget.value = '';
    }

    openList() {
        this.listTarget.hidden = false;
        this.inputTarget.setAttribute('aria-expanded', 'true');
    }

    close() {
        this.listTarget.hidden = true;
        this.inputTarget.setAttribute('aria-expanded', 'false');
        this.inputTarget.removeAttribute('aria-activedescendant');
    }
}
