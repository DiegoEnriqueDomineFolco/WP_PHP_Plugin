<?php

/**
 * Vista de agenda/reservas para el admin.
 * Recibe: $reservas (array), $post_id
 * Permite mostrar como calendario (mes/año) o como lista.
 */
$modo = isset($_GET['agenda_modo']) ? $_GET['agenda_modo'] : 'calendario';
if (!is_array($reservas)) {
    $reservas = [];
}

$page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
?>
<div class="agenda-admin-wrapper" id="agenda-admin-wrapper" style="max-width:900px;">
    <!--
        IDs y data-* añadidos para facilitar integración JS/AJAX.
        - #btn-alta-reserva: botón para mostrar el formulario de alta
        - #form-alta-reserva: formulario de alta manual de reserva
    -->
    
    <!-- TITULO -->
    <h2><?php _e('Agenda de reservas', 'solicitar-producto'); ?></h2>

    <!-- BOTONERA -->
    <div style="margin-bottom:16px;">
        <a href="?page=<?php echo esc_attr($page); ?>&post_id=<?php echo esc_attr($post_id); ?>&agenda_modo=calendario" class="button<?php echo $modo == 'calendario' ? ' button-primary' : ''; ?>" id="btn-ver-calendario">Ver calendario</a>
        <a href="?page=<?php echo esc_attr($page); ?>&post_id=<?php echo esc_attr($post_id); ?>&agenda_modo=lista" class="button<?php echo $modo == 'lista' ? ' button-primary' : ''; ?>" id="btn-ver-lista">Ver lista</a>
        <button type="button" class="button button-success" id="btn-alta-reserva" data-action="alta" style="margin-left:8px;">Dar de alta reserva manual</button>
    </div>

    <!-- NOTIF -->
    <div id="agenda-admin-msg" style="margin-bottom:12px;"></div>

    <!-- FORMULARIO ALTA RESERVA MANUAL -->
    <div class="agenda-alta-reserva" id="agenda-alta-reserva" style="margin-bottom:24px; background:#f6f6f6; border:1px solid #e0e0e0; padding:16px; display:none;">
        <h3><?php _e('Alta manual de reserva', 'solicitar-producto'); ?></h3>
        <form method="post" id="form-alta-reserva" action="<?php echo admin_url('admin-post.php'); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
            <?php wp_nonce_field('alta_reserva_manual', 'alta_reserva_nonce'); ?>
            <input type="hidden" name="action" value="guardar_reserva_manual" />
            <input type="hidden" name="post_id" id="input-post-id" value="<?php echo esc_attr($post_id); ?>" />
            
            <div style="display:flex; gap:16px; flex-wrap:wrap;" class="form-table">
                <div>
                    <label><b><?php _e('Nombre y Apellido', 'solicitar-producto'); ?>:</b></label><br>
                    <input type="text" name="solicitud[nombre]" required class="regular-text" id="input-solicitud-nombre" />
                </div>
                <div>
                    <label><b><?php _e('Documento', 'solicitar-producto'); ?>:</b></label><br>
                    <input type="text" name="solicitud[dni]" required class="regular-text" id="input-solicitud-dni" />
                </div>
                <div>
                    <label><b><?php _e('Email', 'solicitar-producto'); ?>:</b></label><br>
                    <input type="email" name="solicitud[email]" required class="regular-text" id="input-solicitud-email" />
                </div>
                <div>
                    <label><b><?php _e('Teléfono', 'solicitar-producto'); ?>:</b></label><br>
                    <input type="tel" name="solicitud[telefono]" required class="regular-text" id="input-solicitud-telefono" />
                </div>
                <div>
                    <label><b><?php _e('Fecha inicio', 'solicitar-producto'); ?>:</b></label><br>
                    <input type="date" min="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" name="solicitud[inicio]" required id="input-solicitud-inicio" />
                </div>
                <div>
                    <label><b><?php _e('Fecha fin', 'solicitar-producto'); ?>:</b></label><br>
                    <input type="date" min="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" name="solicitud[fin]" required id="input-solicitud-fin" />
                </div>
                <div style="flex:1 1 100%;">
                    <label><b><?php _e('Detalles adicionales', 'solicitar-producto'); ?>:</b></label><br>
                    <textarea name="solicitud[detalles]" class="regular-text" id="input-solicitud-detalles" style="width:100%; min-height:40px;"></textarea>
                </div>
            </div>
            <button type="submit" class="button button-primary" id="btn-guardar-reserva" style="margin-top:12px;"><?php _e('Guardar reserva', 'solicitar-producto'); ?></button>
            <button type="button" class="button" id="btn-cancelar-reserva" style="margin-top:12px; margin-left:8px;"><?php _e('Cancelar', 'solicitar-producto'); ?></button>
        </form>
    </div>
    <!-- FIN FORMULARIO ALTA RESERVA MANUAL -->

    <!-- DESDE AQUÍ VA EL CALENDARIO O LISTA DE RESERVAS -->
    <?php self::render_reservas_helper($modo, $reservas); ?>
    <!-- HASTA AQUÍ VA EL CALENDARIO O LISTA DE RESERVAS -->

</div>