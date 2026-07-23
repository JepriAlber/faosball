/**
 * Cascading dropdown academy-scoped: dipakai di form create Team/Staff/Staff
 * Position, hanya efektif untuk Super Admin (yang punya select id_academy).
 * User academy biasa: dropdown anak sudah benar sejak render server, x-data
 * ini idle (idAcademy selalu kosong, init() tidak pernah trigger fetch).
 *
 * endpoint -- URL endpoint JSON module ybs (mis. route('teams.cascade-options')).
 * Kontrak response: { [nama_select_anak]: [{value, label}, ...], ... }
 * (issue19.md Bagian 2b). Jangan dipakai untuk form EDIT (Aturan Emas).
 */
export default (endpoint) => ({
    idAcademy: '',
    loading: false,

    // oldAcademyId/oldSelected dipanggil dari x-init -- restore state kalau
    // form ini re-render gara-gara validasi gagal (Super Admin sudah pilih
    // academy tapi ada error field lain).
    init(oldAcademyId = '', oldSelected = {}) {
        if (oldAcademyId) {
            this.idAcademy = oldAcademyId;
            this.loadOptions(oldSelected);
        }
    },

    async loadOptions(restoreSelected = {}) {
        const targets = Object.keys(restoreSelected);

        if (!this.idAcademy) {
            targets.forEach((name) => this.resetSelect(name));
            return;
        }

        this.loading = true;

        try {
            const response = await fetch(`${endpoint}?id_academy=${this.idAcademy}`, {
                headers: { Accept: 'application/json' },
            });

            const options = await response.json();

            Object.keys(options).forEach((name) => {
                this.fillSelect(name, options[name], restoreSelected[name] ?? '');
            });
        } finally {
            this.loading = false;
        }
    },

    resetSelect(name) {
        const select = this.$el.querySelector(`[name="${name}"]`);
        if (!select) return;

        select.querySelectorAll('option:not(:first-child)').forEach((opt) => opt.remove());
        select.value = '';
    },

    fillSelect(name, options, selectedValue) {
        const select = this.$el.querySelector(`[name="${name}"]`);
        if (!select) return;

        select.querySelectorAll('option:not(:first-child)').forEach((opt) => opt.remove());

        options.forEach((opt) => {
            const el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.label;
            if (String(opt.value) === String(selectedValue)) el.selected = true;
            select.appendChild(el);
        });
    },
});
