<?php

/**
 * Vista del metabox de campos dinámicos.
 * Recibe: $post, $campos, $post_id, $error_msg
 */
?>
<div id="campos-dinamicos-wrapper" class="campos-dinamicos-wrapper">
    <!--
        IDs y data-* añadidos para facilitar integración JS/AJAX.
        - #add-campo-dinamico: botón para agregar campo dinámico
        - .remove-campo: botón para quitar campo dinámico (con data-index)
        - #campos-dinamicos-rows: tbody de las filas
        - #form-campos-dinamicos: formulario principal
    -->
    <h2><?php echo esc_html(get_the_title($post)); ?> - <?php _e('Campos dinámicos', 'solicitar-producto'); ?></h2>
    <?php if (!empty($error_msg)) : ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_msg); ?></p>
        </div>
    <?php endif; ?>
    <div class="notice notice-info info-campos-dinamicos">
        <p><?php _e('Puedes agregar pares clave/valor personalizados. La clave debe ser única y descriptiva. Ejemplo: telefono, precio, url_video.', 'solicitar-producto'); ?></p>
    </div>
    <form method="post" id="form-campos-dinamicos" action="<?php echo admin_url('admin-post.php'); ?>" data-post-id="<?php echo esc_attr($post_id); ?>">
        <?php wp_nonce_field('campos_dinamicos_nonce', 'campos_dinamicos_nonce'); ?>
        <input type="hidden" name="action" value="guardar_campos_dinamicos" />
        <input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>" />
        <table class="wp-list-table widefat fixed striped table-view-list posts" id="campos-dinamicos-table">
            <thead>
                <tr>
                    <th scope="col" id="sort-clave" class="manage-column column-clave sortable" data-sort="clave" style="cursor:pointer;">
                        <a href="#" id="sort-clave-link">
                            <span><?php _e('Clave', 'solicitar-producto'); ?></span>
                            <span class="sorting-indicators">
                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                            </span>
                            <span class="screen-reader-text"><?php _e('Ordenar por clave', 'solicitar-producto'); ?></span>
                        </a>
                    </th>
                    <th><?php _e('Valor', 'solicitar-producto'); ?></th>
                    <th class="text-right"><button type="button" class="button button-primary" id="add-campo-dinamico" data-action="add">+ <?php _e('Agregar campo', 'solicitar-producto'); ?></button></th>
                </tr>
            </thead>
            <tbody id="campos-dinamicos-rows">
                <?php if (!empty($campos)) :
                    foreach ($campos as $i => $campo) : ?>
                        <tr data-index="<?php echo $i; ?>">
                            <td><input type="text" name="campos_dinamicos[<?php echo $i; ?>][key]" value="<?php echo esc_attr($campo['key'] ?? ''); ?>" data-index="<?php echo $i; ?>" required /></td>
                            <td><input type="text" name="campos_dinamicos[<?php echo $i; ?>][value]" value="<?php echo esc_attr($campo['value'] ?? ''); ?>" data-index="<?php echo $i; ?>" required /></td>
                            <td><button type="button" class="button remove-campo" id="remove-campo-<?php echo $i; ?>" data-index="<?php echo $i; ?>" title="Quitar">-</button><i> &lt;&lt; <?php _e('Remover', 'solicitar-producto'); ?></i></td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr>
                        <th colspan="3" id="no-campos-dinamicos-message" class="text-center" style="<?php echo empty($campos) ? '' : 'display:none;'; ?>"><?php _e('No hay campos dinámicos agregados.', 'solicitar-producto'); ?></th>
                    </tr>
                <?php endif; ?>
                <tr>
                    <th colspan="3">
                        <hr>
                    </th>
                </tr>
                <tr>
                    <th colspan="3" class="text-right">
                        <input type="submit" name="submit" id="submit-campos-dinamicos" class="button button-primary" value="<?php echo esc_attr__('Guardar campos', 'solicitar-producto'); ?>">
                    </th>
                </tr>
                <tr>
                    <th colspan="3">
                        <hr>
                    </th>
                </tr>
            </tbody>
        </table>
    </form>
</div>