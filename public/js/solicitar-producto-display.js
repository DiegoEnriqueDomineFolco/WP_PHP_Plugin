// Archivo : wp-content/plugins/solicitar-producto/public/js/solicitar-producto-display.js
// JS para la gestión del formulario público de solicitar producto
// Descripción: Maneja la interacción y envío del formulario público de solicitar producto

jQuery(document).ready(function ($) {
    /**
     * Funciones y variables centralizadas
     */
    // Selectores centralizados
    var selectors = {
        form: '#public-form-insert_solicitar-producto',
        notice: '.public-form-insert-notice',
        error: '.public-form-insert-error',
        success: '.public-form-insert-success',
        loading: '.public-form-insert-loading',
        rangoFechasInvalido: '.public-form-insert-rango-fechas-invalida'
    };

    //return if not registro pago form
    if (!$(selectors.form).length) {
        return;
    }

    // Función para mostrar solo la notificación relevante
    function showOnlyNotice(type) {
        // // Oculta todas las notificaciones
        $(selectors.notice).hide();
        $(selectors.error).hide();
        $(selectors.success).hide();
        $(selectors.loading).hide();
        // Muestra solo la indicada
        var $notice = $(selectors[type]);
        if ($notice.length) {
            $notice.fadeIn();
        }

        // Ocultar la notificación después de 3.5 segundos
        setTimeout(function () {
            $notice.fadeOut();
        }, 3500);

    }///end showOnlyNotice

    /**
     * Manejo del formulario
     */

    /**
     * AJAX actions
     */
    // Evento submit del formulario
    $(selectors.form).on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        var formData = $form.serialize();
		
        var fechaInicio = $('#registro-inicio').val();
        var fechaFin = $('#registro-fin').val();
        
        if (rangoFechasInvalidas(fechaInicio, fechaFin)) {
            showOnlyNotice('rangoFechasInvalido');
            return;
        }

        var $submitBtn = $form.find('input[type="submit"]');

        // Deshabilitar el botón de submit
        $submitBtn.prop('disabled', true);

        showOnlyNotice('loading');

        const payload = {
            url: solicitar_producto_ajax.ajax_url,
            type: 'POST',
            action: "solicitar_producto_submit",
            nonce: solicitar_producto_ajax.solicitar_producto_nonce,
            dataType: 'json',
        };
        console.log(payload);

        const dataSend = formData + '&action=' + payload.action + '&solicitar_producto_nonce=' + payload.nonce;
        console.log(dataSend);

        $.ajax({
            url: payload.url,
            type: payload.type,
            data: dataSend,
            dataType: payload.dataType,

            success: function (response) {
                $submitBtn.prop('disabled', false);
                if (response.success) {
                    showOnlyNotice('success');
                    $form[0].reset();
                } else {
                    showOnlyNotice('error');
                }
            },
            error: function () {
                $submitBtn.prop('disabled', false);
                showOnlyNotice('error');
            }
        });
    });///end form submit

});///end document ready