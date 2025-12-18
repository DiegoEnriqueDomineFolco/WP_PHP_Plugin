<?php
// Incluir la clase principal si no está cargada
// if (!class_exists('Campos_Dinamicos_Post')) {
//     require_once plugin_dir_path(__FILE__) . '../campos-dinamicos-post.php';
// }
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

// Usar constantes de la clase principal para escalabilidad
class Campos_Dinamicos_List_Table extends WP_List_Table {
    public function __construct() {
        parent::__construct([
            'singular' => 'entrada',
            'plural'   => 'entradas',
            'ajax'     => false
        ]);
    }

    public function get_columns() {
        return [
            'title'   => __('Título', Campos_Dinamicos_Post::TEXT_DOMAIN),
            'actions' => __('Acciones', Campos_Dinamicos_Post::TEXT_DOMAIN)
        ];
    }

    protected function get_sortable_columns() {
        return [
            'title' => ['title', true]
        ];
    }

    public function prepare_items() {
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        $orderby = isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'title';
        $order = isset($_REQUEST['order']) && strtolower($_REQUEST['order']) === 'desc' ? 'DESC' : 'ASC';
        $paged = isset($_REQUEST['paged']) ? max(1, intval($_REQUEST['paged'])) : 1;
        $per_page = 20;

        $args = [
            'post_type' => 'post',
            'posts_per_page' => $per_page,
            'paged' => $paged,
            'meta_query' => [
                [
                    'key' => Campos_Dinamicos_Post::META_KEY,
                    'compare' => 'EXISTS'
                ]
            ],
            'orderby' => $orderby,
            'order' => $order
        ];
        if ($search) {
            $args['s'] = $search;
        }
        $query = new WP_Query($args);
        $items = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $items[] = [
                    'ID' => get_the_ID(),
                    'title' => get_the_title(),
                ];
            }
            wp_reset_postdata();
        }
        $this->items = $items;
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->set_pagination_args([
            'total_items' => $query->found_posts,
            'per_page'    => $per_page,
            'total_pages' => $query->max_num_pages
        ]);
    }

    public function column_title($item) {
        $edit_url = admin_url('admin.php?page=' . Campos_Dinamicos_Post::PAGE_SLUG . '&post_id=' . $item['ID']);
        return '<a href="' . esc_url($edit_url) . '">' . esc_html($item['title']) . '</a>';
    }

    public function column_actions($item) {
        $edit_url = admin_url('admin.php?page=' . Campos_Dinamicos_Post::PAGE_SLUG . '&post_id=' . $item['ID']);
        return '<a class="button" href="' . esc_url($edit_url) . '">' . __('Editar campos dinámicos', Campos_Dinamicos_Post::TEXT_DOMAIN) . '</a>';
    }
}
