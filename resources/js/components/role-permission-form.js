export default function () {
    return {
        selectAll() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(el => {
                el.checked = true;
                el.dispatchEvent(new Event('change'));
            });
        },

        unselectAll() {
            document.querySelectorAll('input[name="permissions[]"]').forEach(el => {
                el.checked = false;
                el.dispatchEvent(new Event('change'));
            });
        }
    };
}