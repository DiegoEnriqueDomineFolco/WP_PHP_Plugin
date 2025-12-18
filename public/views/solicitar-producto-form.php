<?php
ob_start();
//post information
$post = get_post();
// error_log('Mostrando formulario de solicitud de producto para el post ID: ' . $post->ID);
$btn_close = '<button type="button" onclick="this.parentElement.style.display=\'none\'" class="public-form-insert-close">×</button>';
?>
<div class="public-form-insert-container">
    <div id="public-form-insert-mensaje"></div>
    <div class="public-form-insert-wrapper">
        
        <form id="public-form-insert_<?php echo self::PLUGIN_SLUG; ?>" method="post">
            <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
            <h2>Solicitar Producto - <i><?php echo esc_html(get_the_title($post->ID)); ?></i></h2>

            <!-- NOTIFICACIONES -->
            <div class="public-form-insert-error public-form-insert public-form-insert-error" style="display: none;">
                Ha ocurrido un error al enviar la información. Por favor, inténtalo de nuevo.
                <?php echo $btn_close; ?>
            </div>
            <div class="public-form-insert-success public-form-insert public-form-insert-success" style="display: none;">
                Información enviada correctamente. ¡Gracias!
                <?php echo $btn_close; ?>
            </div>
            <div class="public-form-insert-loading public-form-insert public-form-insert-loading" style="display: none;">
                Enviando información...
                <?php echo $btn_close; ?>
            </div>
            <div class="public-form-insert-rango-fechas-invalida public-form-insert public-form-insert-error" style="display: none;">
                Fechas inválidas.
                <?php echo $btn_close; ?>
            </div>

            <!-- CAMPOS DEL FORMULARIO -->
            <div class="public-form-insert-group">
                <label for="registro-nombre">Tu Nombre y Apellido:</label>
                <input type="text" id="registro-nombre" name="registro-nombre" required>
            </div>

            <div class="public-form-insert-group">
                <label for="registro-dni">Documento:</label>
                <input type="text" id="registro-dni" name="registro-dni" required>
            </div>

            <div class="public-form-insert-group">
                <label for="registro-email">Tu Email:</label>
                <input type="email" id="registro-email" name="registro-email" required>
            </div>

            <div class="public-form-insert-group">
                <label for="registro-telefono">Tu Teléfono:</label>
                <input type="tel" pattern="[0-9]+" id="registro-telefono" name="registro-telefono" required>
            </div>

            <div class="public-form-insert-group">
                <label for="registro-inicio">Inicio de reserva:</label>
                <input type="date" min="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" id="registro-inicio" name="registro-inicio" required>
            </div>

            <div class="public-form-insert-group">
                <label for="registro-fin">Fin de reserva: <i style="font-weight: 400;">(Opcional)</i></label>
                <input type="date" min="<?php echo date('Y-m-d', strtotime('-1 day')); ?>" id="registro-fin" name="registro-fin" required>
            </div>

            <div class="public-form-insert-group">
                <label for="registro-detalles">Detalles Adicionales:</label>
                <textarea id="registro-detalles" name="registro-detalles"></textarea>
            </div>

            <!-- BTNS -->
            <center><input type="submit" name="<?php echo self::POST_TYPE; ?>_submit" value="Solicitar Producto"></center>
        </form>
    </div>
</div>

<?php
$html = ob_get_clean();
?>