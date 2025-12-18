// JS para la gestión de campos dinámicos en el metabox del admin
// Autor: [Tu nombre o equipo]
// Descripción: Permite agregar, quitar y ordenar filas de campos dinámicos en el metabox de WordPress

jQuery(document).ready(function ($) {
    // Agregar nueva fila de campo dinámico
    $('#add-campo-dinamico').on('click', function () {
        var rowCount = $('#campos-dinamicos-rows tr').length;
        var row = '<tr data-index="' + rowCount + '">' +
            '<td><label for="campo-key-' + rowCount + '"><b>Clave:</b></label> <input type="text" id="campo-key-' + rowCount + '" name="campos_dinamicos[' + rowCount + '][key]" data-index="' + rowCount + '" required /></td>' +
            '<td><label for="campo-value-' + rowCount + '"><b>Valor:</b></label> <input type="text" id="campo-value-' + rowCount + '" name="campos_dinamicos[' + rowCount + '][value]" data-index="' + rowCount + '" required /></td>' +
            '<td><button type="button" class="button remove-campo" id="remove-campo-' + rowCount + '" data-index="' + rowCount + '" title="Quitar">-</button><i> &lt;&lt; Cancelar</i></td>' +
            '</tr>';
        $('#campos-dinamicos-rows').append(row);
    });

    // Bloquear submit si hay errores de clave duplicada
    $('#submit-campos-dinamicos').on('click', function (e) {
        var error = false;
        $('input[name*="[key]"]').each(function () {
            if ($(this).hasClass('campo-key-error')) {
                error = true;
            }
        });

        if (error) {
            alert('No se puede guardar porque hay claves duplicadas. Corrige los errores antes de continuar.');
            e.preventDefault();
            return false;
        }
        // Permitir submit si no hay errores
        return true;
    });

    // Quitar fila de campo dinámico
    $(document).on('click', '.remove-campo', function () {
        $(this).closest('tr').remove();
    });

    // Ordenar alfabéticamente por nombre de clave al hacer click en el enlace de la cabecera
    $('#sort-clave-link').on('click', function (e) {
        e.preventDefault();
        var th = $(this).closest('th');
        var rows = $('#campos-dinamicos-rows tr').get();
        var asc = th.hasClass('asc');
        rows.sort(function (a, b) {
            var aVal = $(a).find('input[name*="[key]"]').val().toLowerCase();
            var bVal = $(b).find('input[name*="[key]"]').val().toLowerCase();
            if (aVal < bVal) return asc ? 1 : -1;
            if (aVal > bVal) return asc ? -1 : 1;
            return 0;
        });
        $.each(rows, function (idx, row) {
            $('#campos-dinamicos-rows').append(row);
        });
        // Actualizar clases de orden y los indicadores visuales
        $('#campos-dinamicos-table th').removeClass('asc desc');
        th.toggleClass('asc', !asc).toggleClass('desc', asc);
        th.find('.sorting-indicator.asc').toggleClass('active', !asc);
        th.find('.sorting-indicator.desc').toggleClass('active', asc);
    });


    // Validación AJAX de clave duplicada al editar/agregar
    $(document).on('keyup', 'input[name*="[key]"]', function () {
        var $input = $(this);
        var clave = $input.val();
        var $row = $input.closest('tr');
        var postId = $('input[name="post_id"]').val();
        if (!clave || !postId) return;

        $.ajax({
            url: CamposDinamicosVars.ajax_url,
            type: 'POST',
            data: {
                action: 'campos_dinamicos_check_key',
                key: clave,
                post_id: postId,
                nonce: CamposDinamicosVars.nonce
            },
            success: function (resp) {
                $row.find('.campo-key-msg').remove();
                if (resp.success && resp.data.exists) {
                    $input.addClass('campo-key-error');
                    $row.find('td:first').append('<span class="campo-key-msg" style="color:red; margin-left:8px;">' + resp.data.message + '</span>');
                } else {
                    $input.removeClass('campo-key-error');
                }
            }
        });
    });

});///end of file