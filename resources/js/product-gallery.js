document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
    const mainImage = gallery.querySelector('[data-product-gallery-main]');
    const thumbnails = [...gallery.querySelectorAll('[data-product-gallery-thumb]')];

    if (!mainImage || thumbnails.length === 0) {
        return;
    }

    thumbnails.forEach((thumbnail) => {
        thumbnail.addEventListener('click', () => {
            const source = thumbnail.dataset.src;

            if (!source || mainImage.getAttribute('src') === source) {
                return;
            }

            mainImage.setAttribute('src', source);
            thumbnails.forEach((item) => item.classList.toggle('is-active', item === thumbnail));
        });
    });
});
