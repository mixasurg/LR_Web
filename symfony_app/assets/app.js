import './stimulus_bootstrap.js';
import './styles/app.css';

const lightbox = document.getElementById('image-lightbox');
const lightboxImage = document.getElementById('image-lightbox-image');

let previousBodyOverflow = '';

const openLightbox = (source, altText) => {
    if (!lightbox || !lightboxImage || !source) {
        return;
    }

    lightboxImage.src = source;
    lightboxImage.alt = altText || 'Увеличенное изображение';
    lightbox.hidden = false;
    lightbox.setAttribute('aria-hidden', 'false');

    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
    if (!lightbox || !lightboxImage || lightbox.hidden) {
        return;
    }

    lightbox.hidden = true;
    lightbox.setAttribute('aria-hidden', 'true');
    lightboxImage.removeAttribute('src');
    lightboxImage.alt = '';
    document.body.style.overflow = previousBodyOverflow;
};

document.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!target) {
        return;
    }

    const enlargeableImage = target.closest('[data-enlargeable]');
    if (enlargeableImage instanceof HTMLImageElement) {
        event.preventDefault();
        const source = enlargeableImage.dataset.fullsrc || enlargeableImage.currentSrc || enlargeableImage.src;
        openLightbox(source, enlargeableImage.alt);
        return;
    }

    if (target.closest('[data-lightbox-close]')) {
        event.preventDefault();
        closeLightbox();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeLightbox();
    }
});
