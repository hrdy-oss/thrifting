const MAX_GALLERY_PHOTOS = 6;
const MAX_IMAGE_EDGE = 1600;
const JPEG_QUALITY = 0.85;
const COMPRESS_THRESHOLD = 600 * 1024;

const alreadyCompressed = new WeakSet();
const inputFiles = new WeakMap();

const loadImage = (file) => new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(image);
    };

    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        reject(new Error('Gambar tidak bisa dibaca.'));
    };

    image.src = objectUrl;
});

const canvasToBlob = (canvas, type, quality) => new Promise((resolve) => {
    canvas.toBlob((blob) => resolve(blob), type, quality);
});

const replaceExtension = (name) => name.replace(/\.[^.]+$/, '') + '.jpg';

const compressImage = async (file) => {
    if (!file.type.startsWith('image/') || alreadyCompressed.has(file)) {
        return file;
    }

    const image = await loadImage(file);
    const scale = Math.min(1, MAX_IMAGE_EDGE / Math.max(image.naturalWidth, image.naturalHeight));

    if (scale === 1 && file.size <= COMPRESS_THRESHOLD) {
        return file;
    }

    const canvas = document.createElement('canvas');
    canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
    canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));

    const context = canvas.getContext('2d');
    context.fillStyle = '#ffffff';
    context.fillRect(0, 0, canvas.width, canvas.height);
    context.drawImage(image, 0, 0, canvas.width, canvas.height);

    const blob = await canvasToBlob(canvas, 'image/jpeg', JPEG_QUALITY);

    if (!blob) {
        return file;
    }

    const compressed = new File([blob], replaceExtension(file.name || 'foto.jpg'), {
        type: 'image/jpeg',
        lastModified: Date.now(),
    });

    alreadyCompressed.add(compressed);

    return compressed.size < file.size ? compressed : file;
};

const setInputFiles = (input, files) => {
    const transfer = new DataTransfer();
    files.forEach((file) => transfer.items.add(file));

    input.files = transfer.files;
    inputFiles.set(input, files);
};

const getInputFiles = (input) => {
    if (!inputFiles.has(input)) {
        inputFiles.set(input, [...input.files]);
    }

    return inputFiles.get(input);
};

const galleryManager = document.querySelector('[data-gallery-manager]');
const galleryLimit = Number(galleryManager?.dataset.maxGallery) || MAX_GALLERY_PHOTOS;
const galleryInput = galleryManager?.querySelector('[data-photo-input="gallery"]');
const galleryItems = galleryManager?.querySelector('[data-gallery-items]');
const galleryCount = galleryManager?.querySelector('[data-gallery-count]');
const mainInput = document.querySelector('[data-photo-input="main"]');
const mainPreview = document.querySelector('[data-photo-preview]');
const mainPreviewImage = mainPreview?.querySelector('[data-photo-preview-image]');
const mainPreviewEmpty = mainPreview?.querySelector('[data-photo-preview-empty]');

const keptGalleryCount = () => galleryItems
    ? galleryItems.querySelectorAll('[data-gallery-item]').length
    : 0;

const updateGalleryCount = () => {
    if (!galleryCount) {
        return;
    }

    const pending = galleryInput ? getInputFiles(galleryInput).length : 0;
    galleryCount.textContent = `${keptGalleryCount() + pending} / ${galleryLimit} foto`;
};

const renderGalleryPreviews = () => {
    if (!galleryItems || !galleryInput) {
        return;
    }

    galleryItems.querySelectorAll('[data-gallery-pending]').forEach((item) => {
        const image = item.querySelector('img');
        if (image?.src.startsWith('blob:')) {
            URL.revokeObjectURL(image.src);
        }

        item.remove();
    });

    getInputFiles(galleryInput).forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'gallery-item is-pending';
        item.dataset.galleryPending = '';
        item.innerHTML = `<img alt="Foto galeri baru"><button class="gallery-item-remove" type="button" aria-label="Batalkan foto" data-gallery-pending-remove>×</button>`;
        item.querySelector('img').src = URL.createObjectURL(file);

        item.querySelector('[data-gallery-pending-remove]').addEventListener('click', () => {
            const remaining = getInputFiles(galleryInput).filter((_, fileIndex) => fileIndex !== index);
            setInputFiles(galleryInput, remaining);
            renderGalleryPreviews();
            updateGalleryCount();
        });

        galleryItems.appendChild(item);
    });

    updateGalleryCount();
};

const showMainPreview = (file) => {
    if (!mainPreviewImage || !mainPreviewEmpty) {
        return;
    }

    if (mainPreviewImage.src.startsWith('blob:')) {
        URL.revokeObjectURL(mainPreviewImage.src);
    }

    mainPreviewImage.src = URL.createObjectURL(file);
    mainPreviewImage.hidden = false;
    mainPreviewEmpty.hidden = true;
};

const processMainInput = async () => {
    if (!mainInput) {
        return;
    }

    const [file] = [...mainInput.files];

    if (!file) {
        return;
    }

    const compressed = await compressImage(file);

    if (compressed !== file) {
        setInputFiles(mainInput, [compressed]);
    }

    showMainPreview(compressed);
};

const processGalleryInput = async () => {
    if (!galleryInput) {
        return;
    }

    const files = [...galleryInput.files];
    const compressed = [];

    for (const file of files) {
        compressed.push(await compressImage(file));
    }

    const limited = compressed.slice(0, galleryLimit);
    setInputFiles(galleryInput, limited);
    renderGalleryPreviews();
};

const runProcessing = async (input) => {
    const form = input.closest('form');
    const submitButtons = form ? [...form.querySelectorAll('button[type="submit"]')] : [];

    submitButtons.forEach((button) => { button.disabled = true; });

    try {
        if (input === mainInput) {
            await processMainInput();
        } else if (input === galleryInput) {
            await processGalleryInput();
        }
    } catch (error) {
        console.warn(error);
    } finally {
        submitButtons.forEach((button) => { button.disabled = false; });
    }
};

mainInput?.addEventListener('change', () => runProcessing(mainInput));
galleryInput?.addEventListener('change', () => runProcessing(galleryInput));

if (galleryManager) {
    const urlList = galleryManager.querySelector('[data-gallery-url-list]');
    const urlTemplate = galleryManager.querySelector('[data-gallery-url-template]');
    const urlAdd = galleryManager.querySelector('[data-gallery-url-add]');

    urlAdd?.addEventListener('click', () => {
        if (!urlList || !urlTemplate) {
            return;
        }

        const row = urlTemplate.content.firstElementChild.cloneNode(true);
        urlList.appendChild(row);
        row.querySelector('input')?.focus();
    });

    galleryManager.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-gallery-remove]');
        if (removeButton) {
            removeButton.closest('[data-gallery-item]')?.remove();
            updateGalleryCount();
            return;
        }

        const urlRemoveButton = event.target.closest('[data-gallery-url-remove]');
        if (urlRemoveButton) {
            urlRemoveButton.closest('[data-gallery-url-row]')?.remove();
        }
    });
}

const cameraModal = document.querySelector('[data-camera-modal]');

if (cameraModal) {
    const video = cameraModal.querySelector('[data-camera-video]');
    const status = cameraModal.querySelector('[data-camera-status]');
    const captureButton = cameraModal.querySelector('[data-camera-capture]');
    const closeButtons = cameraModal.querySelectorAll('[data-camera-close]');
    const openButtons = document.querySelectorAll('[data-camera-open]');
    let cameraStream = null;
    let activeTarget = null;
    let lastFocusedElement = null;

    const setStatus = (message) => {
        if (status) {
            status.textContent = message;
        }
    };

    const stopCamera = () => {
        cameraStream?.getTracks().forEach((track) => track.stop());
        cameraStream = null;
        video.srcObject = null;
    };

    const closeCamera = () => {
        stopCamera();
        cameraModal.hidden = true;
        document.body.classList.remove('has-camera-open');

        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus({ preventScroll: true });
        }
    };

    const openCamera = async (target, trigger) => {
        activeTarget = target;
        lastFocusedElement = trigger;

        cameraModal.hidden = false;
        document.body.classList.add('has-camera-open');
        setStatus('Menyalakan kamera…');

        if (!navigator.mediaDevices?.getUserMedia) {
            setStatus('Browser ini tidak mendukung akses kamera. Gunakan unggah file sebagai gantinya.');
            return;
        }

        try {
            cameraStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1080 }, height: { ideal: 1080 } },
                audio: false,
            });

            video.srcObject = cameraStream;
            await video.play().catch(() => {});
            captureButton.disabled = false;
            setStatus('Arahkan kamera ke produk, lalu tekan ambil foto.');
        } catch (error) {
            setStatus(error?.name === 'NotAllowedError'
                ? 'Akses kamera ditolak. Izinkan kamera di pengaturan browser, atau unggah file.'
                : 'Kamera tidak tersedia. Gunakan unggah file sebagai gantinya.');
        }
    };

    const capturePhoto = async () => {
        if (!cameraStream || !video.videoWidth) {
            return;
        }

        const scale = Math.min(1, MAX_IMAGE_EDGE / Math.max(video.videoWidth, video.videoHeight));
        const canvas = document.createElement('canvas');
        canvas.width = Math.round(video.videoWidth * scale);
        canvas.height = Math.round(video.videoHeight * scale);

        const context = canvas.getContext('2d');
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const blob = await canvasToBlob(canvas, 'image/jpeg', JPEG_QUALITY);
        if (!blob) {
            setStatus('Gagal mengambil foto. Coba lagi.');
            return;
        }

        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const file = new File([blob], `kamera-${timestamp}.jpg`, { type: 'image/jpeg' });
        alreadyCompressed.add(file);

        if (activeTarget === 'gallery' && galleryInput) {
            const remaining = galleryLimit - keptGalleryCount();
            const files = [...getInputFiles(galleryInput), file].slice(0, Math.max(0, remaining));
            setInputFiles(galleryInput, files);
            renderGalleryPreviews();
        } else if (mainInput) {
            setInputFiles(mainInput, [file]);
            showMainPreview(file);
        }

        closeCamera();
    };

    openButtons.forEach((button) => button.addEventListener('click', () => {
        openCamera(button.dataset.cameraTarget || 'main', button);
    }));

    closeButtons.forEach((button) => button.addEventListener('click', closeCamera));
    captureButton?.addEventListener('click', capturePhoto);

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !cameraModal.hidden) {
            closeCamera();
        }
    });

    window.addEventListener('pageshow', closeCamera);
}
