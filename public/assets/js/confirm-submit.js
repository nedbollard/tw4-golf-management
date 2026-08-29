// Confirmation prompts for destructive forms. Declared with data-confirm so no
// inline handler is needed under the Content-Security-Policy.
document.addEventListener('DOMContentLoaded', function () {
    var forms = document.querySelectorAll('form[data-confirm]');

    Array.prototype.forEach.call(forms, function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });
});
