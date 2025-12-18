<?php

$nombre = $this->get_meta($post->ID, self::META_NOMBRE);
$dni = $this->get_meta($post->ID, self::META_DNI);
$email = $this->get_meta($post->ID, self::META_EMAIL);
$telefono = $this->get_meta($post->ID, self::META_TELEFONO);
$inicio = $this->get_meta($post->ID, self::META_INICIO);
$fin = $this->get_meta($post->ID, self::META_FIN);
$estado = $this->get_meta($post->ID, self::META_ESTADO);
$observaciones = $this->get_meta($post->ID, self::META_OBSERVACIONES);
$mensaje = $post->post_content;

// Marcar como leida si el admin abre el metabox y aún no fue leida
$leida = get_post_meta($post->ID, self::META_LEIDA, true);
if ($leida != 1 && current_user_can('edit_post', $post->ID)) {
    update_post_meta($post->ID, self::META_LEIDA, 1);
}

// Obtener el icono correspondiente al estado actual
$icon_html = '';
$estado_actual = $estado;
if (isset(self::ESTADOS[$estado_actual])) {
    $icon_html = '<span class="registro-estado-icon-metabox" style="font-size:1.2em;">' . self::ESTADOS[$estado_actual]['icon'] . '</span>';
}
?>
<form method="post">
    <?php wp_nonce_field('save_solicitud_producto', 'solicitud_produto_nonce'); ?>
    <table class="form-table">
        <tr>
            <th><?php echo esc_html(__('Nombre:', self::PLUGIN_SLUG)); ?></th>
            <td><?php echo esc_html($nombre); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Documento:', self::PLUGIN_SLUG)); ?></th>
            <td><?php echo esc_html($dni); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Email:', self::PLUGIN_SLUG)); ?></th>
            <td><a href="mailto:<?php echo esc_attr($email); ?>" target="_blank"><?php echo esc_html($email); ?></a></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Teléfono:', self::PLUGIN_SLUG)); ?></th>
            <td><?php echo esc_html($telefono); ?></td>
        </tr>

        <!-- Mostrar fechas con día de la semana -->
        <tr>
            <th><?php echo esc_html(__('Inicio de Reserva:', self::PLUGIN_SLUG)); ?></th>
            <td><?php echo esc_html($this->format_fecha($inicio, true)); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Fin de Reserva:', self::PLUGIN_SLUG)); ?></th>
            <td><?php echo esc_html($this->format_fecha($fin, true)); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Mensaje:', self::PLUGIN_SLUG)); ?></th>
            <td><?php echo esc_html($mensaje); ?></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Estado Actual:', self::PLUGIN_SLUG)); ?></th>
            <td>
                <div style="border-radius: 8px; max-width: 150px; padding: 4px; background-color: <?php echo isset(self::ESTADOS[$estado]) ? self::ESTADOS[$estado]['color'] : 'transparent'; ?>"><?php echo $icon_html . ' ' . esc_html($estado); ?></div>
            </td>
        </tr>
        <tr>
            <th colspan="2">
                <h3>Administrar:</h3>
            </th>
        </tr>
        <tr>
            <th>Observaciones:</th>
            <td><textarea name="<?php echo self::META_OBSERVACIONES;?>" rows="4" cols="50"><?php echo esc_textarea($observaciones); ?></textarea></td>
        </tr>
        <tr>
            <th><?php echo esc_html(__('Cambiar Estado:', self::PLUGIN_SLUG)); ?></th>
            <td>

                <!-- Agregar el select -->
                <div style="display:inline-flex;align-items:center;gap:8px;">
                    <?php echo $this->render_estado_select($estado); ?>
                </div>
            </td>
        </tr>
    </table>
    <div style="margin-top:15px;">
        <center>
            <a href="<?php echo admin_url('edit.php?post_type=solicitud_producto'); ?>" class="button" style="margin-right:15px;"><?php echo esc_html(__('Volver', 'solicitar-producto')); ?></a>
            <button type="submit" class="button button-primary"><?php echo esc_html(__('Aplicar cambios', 'solicitar-producto')); ?></button>
        </center>
    </div>
</form>