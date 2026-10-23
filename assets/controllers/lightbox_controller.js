import { Controller } from '@hotwired/stimulus';

/**
 * Photo lightbox: zoom from the thumbnail, previous / next (buttons, keyboard arrows, swipe),
 * counter, download, closing with Escape or a click on the backdrop.
 */
export default class extends Controller {
    static targets = ['item', 'overlay', 'image', 'caption', 'counter', 'download'];
    static values = { title: String };

    connect() {
        this.index = -1;
        this.onKeydown = this.keydown.bind(this);
        this.overlayTarget.addEventListener('touchstart', (event) => {
            this.touchStartX = event.changedTouches[0].clientX;
        }, { passive: true });
        this.overlayTarget.addEventListener('touchend', (event) => this.swipe(event), { passive: true });
    }

    disconnect() {
        document.removeEventListener('keydown', this.onKeydown);
    }

    open(event) {
        event.preventDefault();
        const item = event.currentTarget;
        this.lastFocus = item;
        this.overlayTarget.hidden = false;
        document.body.style.overflow = 'hidden';
        document.addEventListener('keydown', this.onKeydown);
        this.show(this.itemTargets.indexOf(item), item.querySelector('img'));
        requestAnimationFrame(() => this.overlayTarget.classList.add('is-open'));
        this.overlayTarget.querySelector('.lightbox__button--close').focus();
    }

    close() {
        this.overlayTarget.classList.remove('is-open');
        document.removeEventListener('keydown', this.onKeydown);
        document.body.style.overflow = '';
        setTimeout(() => {
            this.overlayTarget.hidden = true;
            this.imageTarget.removeAttribute('src');
        }, 250);
        this.lastFocus?.focus();
    }

    closeOnBackdrop(event) {
        if (event.target === this.overlayTarget || event.target.classList.contains('lightbox__figure')) {
            this.close();
        }
    }

    previous() {
        this.show((this.index - 1 + this.itemTargets.length) % this.itemTargets.length);
    }

    next() {
        this.show((this.index + 1) % this.itemTargets.length);
    }

    show(index, zoomFrom = null) {
        this.index = index;
        const item = this.itemTargets[index];
        const image = this.imageTarget;

        image.classList.add('is-loading');
        image.onload = () => {
            image.classList.remove('is-loading');
            if (zoomFrom) {
                this.zoomFrom(zoomFrom);
            }
        };
        image.src = item.href;
        image.alt = `${this.titleValue} - photo ${index + 1}`;
        this.captionTarget.textContent = this.titleValue;
        this.counterTarget.textContent = `${index + 1} / ${this.itemTargets.length}`;
        this.downloadTarget.href = item.dataset.download;
        this.preload(index + 1);
        this.preload(index - 1);
    }

    /** "Zoom" effect: the big picture starts at the position and size of the thumbnail. */
    zoomFrom(thumbnail) {
        const from = thumbnail.getBoundingClientRect();
        const to = this.imageTarget.getBoundingClientRect();
        if (to.width === 0 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        const scale = from.width / to.width;
        const dx = from.left + from.width / 2 - (to.left + to.width / 2);
        const dy = from.top + from.height / 2 - (to.top + to.height / 2);

        this.imageTarget.classList.add('is-zooming');
        this.imageTarget.style.transform = `translate(${dx}px, ${dy}px) scale(${scale})`;
        requestAnimationFrame(() => requestAnimationFrame(() => {
            this.imageTarget.classList.remove('is-zooming');
            this.imageTarget.style.transform = '';
        }));
    }

    preload(index) {
        const item = this.itemTargets[(index + this.itemTargets.length) % this.itemTargets.length];
        if (item) {
            new Image().src = item.href;
        }
    }

    keydown(event) {
        if (event.key === 'ArrowLeft') this.previous();
        else if (event.key === 'ArrowRight') this.next();
        else if (event.key === 'Escape') this.close();
    }

    swipe(event) {
        const delta = event.changedTouches[0].clientX - this.touchStartX;
        if (Math.abs(delta) > 50) {
            delta > 0 ? this.previous() : this.next();
        }
    }
}
