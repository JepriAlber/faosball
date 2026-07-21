import Cropper from 'cropperjs';

export default (initialPreview = '', aspectRatio = 1, outputWidth = 1024, outputHeight = 1024) => ({
    imagePreview: initialPreview,
    showCropModal: false,
    cropper: null,
    pendingSourceUrl: null,

    onFileSelected(event) {
        const file = event.target.files[0];

        if (!file) {
            return;
        }

        const reader = new FileReader();

        reader.onload = (e) => {
            this.pendingSourceUrl = e.target.result;
            this.showCropModal = true;

            this.$nextTick(() => this.initCropper());
        };

        reader.readAsDataURL(file);
    },

    initCropper() {
        this.cropper = new Cropper(this.$refs.cropperImage, {
            aspectRatio: aspectRatio,
            viewMode: 1,
            autoCropArea: 1,
            background: false,
        });
    },

    confirmCrop() {
        this.cropper.getCroppedCanvas({ width: outputWidth, height: outputHeight }).toBlob((blob) => {

            const croppedFile = new File([blob], 'logo.png', { type: 'image/png' });

            // WAJIB DataTransfer -- <input type="file"> tidak bisa diisi
            // langsung dengan Blob/File biasa, cuma lewat FileList asli.
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(croppedFile);
            this.$refs.fileInput.files = dataTransfer.files;

            this.imagePreview = URL.createObjectURL(blob);

            this.closeCropModal();

        }, 'image/png');
    },

    cancelCrop() {
        // Crop WAJIB, bukan opsional -- batal berarti tidak ada file yang
        // valid untuk di-submit, input harus dikosongkan lagi. Lihat
        // issue4.md Aturan Emas.
        this.$refs.fileInput.value = '';
        this.closeCropModal();
    },

    closeCropModal() {
        this.cropper?.destroy();
        this.cropper = null;
        this.pendingSourceUrl = null;
        this.showCropModal = false;
    },
});
