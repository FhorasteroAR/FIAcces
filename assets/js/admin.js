(function ($) {
    'use strict';
    $(function () {
        // Color picker
        $('.fiacces-color-picker').wpColorPicker();

        // Exportar configuración
        $('#fiacces-export-btn').on('click', function () {
            $.ajax({
                url: (window.wpApiSettings && wpApiSettings.root ? wpApiSettings.root : '/wp-json/') + 'fiacces/v1/settings',
                method: 'GET',
                beforeSend: function (xhr) {
                    if (window.wpApiSettings && wpApiSettings.nonce) {
                        xhr.setRequestHeader('X-WP-Nonce', wpApiSettings.nonce);
                    }
                }
            }).done(function (data) {
                var blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
                var url  = URL.createObjectURL(blob);
                var a    = document.createElement('a');
                a.href     = url;
                a.download = 'fiacces-settings-' + new Date().toISOString().slice(0, 10) + '.json';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            }).fail(function () {
                alert('No se pudo exportar la configuración.');
            });
        });

        // Atajo: solo permite letras A-Z
        $('#fiacces_shortcut_key').on('input', function () {
            this.value = this.value.replace(/[^A-Za-z]/g, '').toUpperCase().slice(0, 1);
        });
    });
})(jQuery);
