// Archivo: wp-content/plugins/solicitar-producto/admin/js/agenda-admin.js
// JS para la gestión de la agenda en el admin
// Descripción: Maneja la lógica del formulario de alta manual de reservas en el admin de WordPress

jQuery(document).ready(function ($) {
	// Elementos principales del formulario de alta manual de reserva
	var $formDiv = $('#agenda-alta-reserva');
	var $btnAlta = $('#btn-alta-reserva');
	var $btnCancelar = $('#btn-cancelar-reserva');

	// Mostrar el formulario al hacer clic en "Dar de alta reserva manual"
	$btnAlta.on('click', function (e) {
		e.preventDefault();
		$formDiv.slideDown(180);
		$btnAlta.prop('disabled', true);
	});

	// Ocultar el formulario al hacer clic en "Cancelar"
	$btnCancelar.on('click', function (e) {
		e.preventDefault();
		$formDiv.slideUp(180);
		$btnAlta.prop('disabled', false);
	});

	// Validación antes de enviar el formulario manual
	$('#form-alta-reserva').on('submit', function (e) {
		e.preventDefault();
		mostrarMensaje('Procesando reserva...', 'info');

		// Obtener valores del formulario
		var nombre = $('#input-solicitud-nombre').val().trim();
		var dni = $('#input-solicitud-dni').val().trim();
		var email = $('#input-solicitud-email').val().trim();
		var telefono = $('#input-solicitud-telefono').val().trim();
		var fechaInicio = $('#input-solicitud-inicio').val();
		var fechaFin = $('#input-solicitud-fin').val();

		// Validación de campos requeridos
		if (!nombre || !dni || !email || !telefono || !fechaInicio || !fechaFin) {
			mostrarMensaje('Todos los campos son obligatorios.', 'error');
			return false;
		}

		// Validación de formato de email
		if (!/^\S+@\S+\.\S+$/.test(email)) {
			mostrarMensaje('El email no es válido.', 'error');
			return false;
		}

		// Validación de rango de fechas
		if (fechaInicio > fechaFin) {
			mostrarMensaje('La fecha de inicio debe ser menor o igual a la fecha de fin.', 'error');
			return false;
		}

		// Validar disponibilidad de la agenda mediante llamada AJAX
		var postId = $('#input-post-id').val();
		var ajaxData = {
			action: 'agenda_check_disponibilidad',
			post_id: postId,
			fecha_inicio: fechaInicio,
			fecha_fin: fechaFin,
			nonce: AgendaAdminVars.nonce
		};

		$.ajax({
			url: AgendaAdminVars.ajax_url,
			type: 'POST',
			data: ajaxData,
			success: function (response) {
				if (response.success) {
					mostrarMensaje('La reserva está disponible.', 'success');
					// Enviar el formulario manualmente
					setTimeout(function() {
						$('#form-alta-reserva')[0].submit();
					}, 300);
				} else {
					mostrarMensaje('La reserva no está disponible: ' + response.data.message, 'error');
				}
			},
			error: function () {
				mostrarMensaje('Error al verificar la disponibilidad.', 'error');
			}
		});
		// No submit por defecto
		return false;
	});

	// Manejar la respuesta del servidor después de enviar el formulario
	$('#form-alta-reserva').on('ajaxComplete', function (e, xhr, settings) {
		var response = {};
		try {
			response = JSON.parse(xhr.responseText);
		} catch (err) {
			mostrarMensaje('Respuesta inválida del servidor.', 'error');
			return;
		}

		if (response.success) {
			mostrarMensaje('Reserva creada correctamente.', 'success');
			// Opcional: recargar la página para ver la nueva reserva
			setTimeout(function () {
				location.reload();
			}, 1000);
		} else {
			mostrarMensaje('Error al crear la reserva: ' + response.data.message, 'error');
		}
	});

	// Función para mostrar mensajes en pantalla
	function mostrarMensaje(msg, tipo) {
		var $msgDiv = $('#agenda-admin-msg');
		$msgDiv.html('<div class="notice notice-' + tipo + '"><p>' + msg + '</p></div>');
		$msgDiv.stop(true, true).show();
		setTimeout(function () {
			$msgDiv.fadeOut(400);
		}, tipo === 'success' ? 2500 : 4000);
	}
	// ...otros scripts de agenda admin...
});