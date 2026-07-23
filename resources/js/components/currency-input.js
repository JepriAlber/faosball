export default (initialRaw = '') => ({
    displayValue: '',

    init() {
        this.displayValue = this.toDisplay(initialRaw);
    },

    toDisplay(raw) {
        if (raw === '' || raw === null || Number.isNaN(Number(raw))) {
            return '';
        }

        return new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 }).format(Number(raw));
    },

    // Sejak user mulai mengetik, nominal diperlakukan sebagai Rupiah bulat
    // (tanpa sen) -- konsisten dengan kebiasaan input nominal di FAOSBall,
    // lihat docs/frontend-standard.md bagian "Input Nominal Rupiah".
    onInput(event) {
        const digits = event.target.value.replace(/\D/g, '').replace(/^0+(?=\d)/, '');

        this.$refs.rawInput.value = digits;
        this.displayValue = digits === '' ? '' : new Intl.NumberFormat('id-ID').format(Number(digits));
    },
});
