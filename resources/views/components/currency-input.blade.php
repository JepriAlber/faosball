{{--
    Input nominal Rupiah dengan pemisah ribuan otomatis ("1.000.000") saat diketik,
    lewat Alpine `currencyInput()` (resources/js/components/currency-input.js).
    Nilai yang benar-benar dikirim ke server tetap angka polos lewat hidden input
    ber-`name` yang sama seperti field aslinya -- Form Request/Service tidak perlu
    berubah sama sekali.

    Props:
    - name  : name attribute untuk value yang dikirim (dan key error validasi).
    - value : nilai awal (numeric|string|null), biasanya dari old($name, $model->$name).
    - id    : id attribute untuk visible input, default sama dengan $name.

    Atribut tambahan ($attributes) di-merge ke visible input (mis. `required`,
    `placeholder`, `class` untuk `form-danger` saat error) -- label & slot @error
    tetap ditulis manual di form pemanggil, sama seperti input polos lainnya.
--}}
<div class="form-input-group" x-data="currencyInput('{{ $rawValue }}')">
    <span class="form-input-prefix">Rp</span>

    <input type="text" inputmode="numeric" autocomplete="off" id="{{ $id }}"
        x-model="displayValue" @input="onInput($event)"
        {{ $attributes->merge(['class' => 'form-input pl-9']) }}>

    <input type="hidden" name="{{ $name }}" x-ref="rawInput" value="{{ $rawValue }}">
</div>
