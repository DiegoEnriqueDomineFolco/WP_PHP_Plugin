<?php

$columns = [
    'solicitud_id' => __('Solicitud', 'solicitar-producto'),
    'nombre' => __('Nombre', 'solicitar-producto'),
    'email' => __('Email', 'solicitar-producto'),
    'telefono' => __('Teléfono', 'solicitar-producto'),
    'fecha_inicio' => __('Fecha inicio', 'solicitar-producto'),
    'fecha_fin' => __('Fecha fin', 'solicitar-producto'),
];
?>
<h2><?php _e('Reservas asociadas', 'solicitar-producto'); ?></h2>
<table class="wp-list-table widefat fixed striped table-view-list posts">
    <thead>
        <tr>
            <?php foreach ($columns as $col_key => $col_label):
                $is_sorted = ($orderby === $col_key);
                $next_order = ($is_sorted && $order === 'asc') ? 'desc' : 'asc';
                $sort_class = 'manage-column sortable';
                if ($is_sorted) {
                    $sort_class .= ' sorted ' . $order;
                } else {
                    $sort_class .= ' desc';
                }
                $sort_url = add_query_arg(['orderby' => $col_key, 'order' => $next_order], $base_url);
            ?>
                <th scope="col" class="<?php echo esc_attr($sort_class); ?>" id="col-<?php echo esc_attr($col_key); ?>">
                    <a href="<?php echo esc_url($sort_url); ?>">
                        <span><?php echo esc_html($col_label); ?></span>
                        <span class="sorting-indicators">
                            <span class="sorting-indicator asc" aria-hidden="true"></span>
                            <span class="sorting-indicator desc" aria-hidden="true"></span>
                        </span>
                        <?php if ($is_sorted): ?>
                            <span class="screen-reader-text">
                                <?php echo $order === 'asc' ? __('Orden ascendente.', 'solicitar-producto') : __('Orden descendente.', 'solicitar-producto'); ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reservas)) : ?>
            <tr>
                <td colspan="6"><?php _e('No hay reservas registradas.', 'solicitar-producto'); ?></td>
            </tr>
            <?php else : foreach ($reservas as $reserva) : ?>
                <tr>
                    <td>
                        <?php
                        $edit_url = admin_url('post.php?post=' . intval($reserva['solicitud_id']) . '&action=edit');
                        echo '<a href="' . esc_url($edit_url) . '" target="_blank">' . esc_html($reserva['solicitud_id']) . '</a>';
                        ?>
                    </td>
                    <td>
                        <?php
                        $nombre_meta = get_post_meta($reserva['solicitud_id'], 'solicitud_nombre', true);
                        echo (esc_html($nombre_meta));
                        ?>
                    </td>
                    <td>
                        <?php
                        $email_meta = get_post_meta($reserva['solicitud_id'], 'solicitud_email', true);
                        echo esc_html($email_meta);
                        ?>
                    </td>
                    <td>
                        <?php
                        $tel_meta = get_post_meta($reserva['solicitud_id'], 'solicitud_telefono', true);
                        echo esc_html($tel_meta);
                        ?>
                    </td>
                    <td><?php echo esc_html($reserva['fecha_inicio']); ?></td>
                    <td><?php echo esc_html($reserva['fecha_fin']); ?></td>
                </tr>
        <?php endforeach;
        endif; ?>
    </tbody>
</table>