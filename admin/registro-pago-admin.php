<?php

/**
 * Gestion de visualización de solicitudes en dashboard admin.
 */

class Registro_Pago_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_title    The ID of this plugin.
     */
    private $plugin_title;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    private $utils;

    /**CONSTANTES INTERNAS */
    const PLUGIN_SLUG = 'registro-pago';
    const POST_TYPE = 'registro_pago';

    // Meta keys principales
    const META_TIPO_PAGO = 'registro_pago_tipo_pago'; // 'parcial', 'total', etc.
    const META_FECHA_PAGO = 'registro_pago_fecha_pago'; // Fecha del pago (cargada por el usuario)
    const META_SOLICITUD_ID = 'registro_pago_solicitud_id'; // ID del post tipo 'solicitud_producto' asociado
    const META_TOTAL = 'registro_pago_total'; // Monto total pagado
    const META_DNI = 'registro_pago_dni'; // DNI del solicitante
    const META_EMAIL = 'registro_pago_email'; // Email del solicitante

    // Meta keys admin
    const META_ESTADO = 'registro_pago_estado'; // 'inicial', 'acreditado', 'pendiente', 'rechazado', 'anulado', etc.
    const META_OBSERVACIONES = 'registro_pago_observaciones'; // observaciones de administracion
    const META_LEIDA = 'registro_pago_leida';
    // const META_SOLICITUD_POST_ID = META_SOLICITUD_ID; // ID del post de lo que fue solicitado (value <> postmeta.key=solicitud_post_id WHERE post_id = META_SOLICITUD_ID)

    /**
     * Clase CSS para filas no leidas
     */
    const ROW_NO_LEIDA_CLASS = 'no-leida-row';

    /**
     * Estados posibles de la solicitud, centralizados con label, color y icono.
     */
    const ESTADOS = [
        'Inicial'   => ['label' => 'Inicial',   'color' => '#f7b731', 'icon' => "\u{1F7E1}"],
        'Pendiente'     => ['label' => 'Pendiente',     'color' => '#3498db', 'icon' => "\u{1F441}"],
        'Acreditado'    => ['label' => 'Acreditado',    'color' => '#27ae60', 'icon' => "\u{2705}"],
        'Rechazado' => ['label' => 'Rechazado', 'color' => '#e67e22', 'icon' => "\u{26D4}"],
        'Anulado' => ['label' => 'Anulado', 'color' => '#e74c3c', 'icon' => "\u{274C}"],
    ];

    /**
     * Iconos leida/no leida
     */
    const ICONO_LEIDA = "\u{1F4D6}";       // Libro abierto
    const ICONO_NO_LEIDA = "\u{1F4D5}";    // Libro cerrado

    /***************************** CLASS ****************************/
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_title       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_title, $version)
    {
        $this->plugin_title = $plugin_title;
        $this->version = $version;

        //utilidades comunes
        // require_once plugin_dir_path((plugin_dir_path(__DIR__))) . 'utilities/Utility-Methods.php';
        // $this->utils = new Solicitar_Utility_Methods($plugin_title, $version);
    }

    /**
     * Configura el menú principal y submenús del plugin
     */
    public function setup_menu()
    {
        $this->add_main_menu();
        $this->add_submenus();
    }

    /**
     * Añade clase CSS a la fila si la solicitud no fue leida
     */
    public function add_row_class_no_leida($classes, $class, $post_id)
    {
        $post = get_post($post_id);
        if ($post && $post->post_type === self::POST_TYPE) {
            $leida = get_post_meta($post_id, self::META_LEIDA, true);
            if ($leida != 1) {
                $classes[] = self::ROW_NO_LEIDA_CLASS;
            }
        }
        return $classes;
    }

    /**
     * Encola los assets (JS y CSS) necesarios para el admin del CPT.
     */

    public function enqueue_assets()
    {
        // // Encolar solo en pantallas del CPT 'solicitud_producto'
        // if ($this->is_solicitud_producto_screen()) {
        //     wp_enqueue_script(
        //         'solicitar-producto-admin-menu-js',
        //         plugins_url('admin/js/solicitar-producto-admin.js', dirname(__FILE__)),
        //         array('jquery'),
        //         $this->version,
        //         true
        //     );

        //     wp_enqueue_style(
        //         'solicitar-producto-admin-css',
        //         plugins_url('admin/css/solicitar-producto-admin.css', dirname(__FILE__)),
        //         array(),
        //         $this->version
        //     );
        // }

        // Encolar CSS global admin
    }

    /**
     * Obtiene la cantidad de solicitudes no leidas por estado.
     * @param string|null $estado Si se pasa, filtra por estado; si no, cuenta todas.
     * @return int
     */
    public static function get_nuevas_por_estado($estado = null)
    {
        global $wpdb;
        $where_estado = '';
        if ($estado) {
            $where_estado = $wpdb->prepare("AND pm_estado.meta_value = %s", $estado);
        }
        $sql = "SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm_estado ON p.ID = pm_estado.post_id AND pm_estado.meta_key = '" . self::META_ESTADO . "'
                INNER JOIN {$wpdb->postmeta} pm_vista ON p.ID = pm_vista.post_id AND pm_vista.meta_key = '" . self::META_LEIDA . "'
                WHERE p.post_type = '" . self::POST_TYPE . "'
                  AND p.post_status = 'private'
                  AND pm_vista.meta_value = '0'
                  $where_estado";
        return (int) $wpdb->get_var($sql);
    }

    /**
     * Filtra el título para mostrar solo el post_title en el listado del CPT.
     */
    public function hide_title_post_state()
    {
        return false;
    }

    /**
     * Elimina los filtros de estado por defecto y las acciones en bloque
     * Unificado para DRY.
     */
    public function remove_filters_and_bulk_actions($input)
    {
        if ($this->is_registro_pago_screen()) {
            return array();
        }
        return $input;
    }

    /**
     * Agrega filtro select por estado personalizado
     */
    public function add_custom_filters()
    {
        //lo
        global $typenow;
        if ($typenow === self::POST_TYPE) {

            $selected_estado = isset($_GET[self::META_ESTADO]) ? sanitize_text_field($_GET[self::META_ESTADO]) : '';
            echo $this->render_estado_select($selected_estado);

            $selected_leida = isset($_GET[self::META_LEIDA]) ? sanitize_text_field($_GET[self::META_LEIDA]) : '';
            echo $this->render_leida_select($selected_leida);

            // Botón VER TODO
            $url = admin_url('edit.php?post_type=' . self::POST_TYPE);
            echo '<a href="' . esc_url($url) . '" class="button">' . esc_html(__('Ver todo', self::PLUGIN_SLUG)) . '</a>';

            echo "&nbsp;_&nbsp;";

            // echo '<input type="submit" name="filter_action" class="button" value="Filtrar">';
        }
    }

    /**
     * Filtra la query por estado seleccionado usando solo meta_query (no meta_key/meta_value).
     */
    public function filter_by_estado($query)
    {
        global $pagenow;
        if ($pagenow == 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] == self::POST_TYPE) {
            $meta_query = $query->get('meta_query') ?: array();

            // Filtrar por estado si está seleccionado
            if (isset($_GET[self::META_ESTADO]) && $_GET[self::META_ESTADO] != '') {
                $estado = sanitize_text_field($_GET[self::META_ESTADO]);
                $meta_query[] = array(
                    'key' => self::META_ESTADO,
                    'value' => $estado,
                    'compare' => '=',
                );
            }

            // Filtrar por leidas/no leidas si está seleccionado
            if (isset($_GET[self::META_LEIDA]) && $_GET[self::META_LEIDA] != '') {
                $leida = sanitize_text_field($_GET[self::META_LEIDA]);
                if ($leida === 'abierta') {
                    $meta_query[] = array(
                        'key' => self::META_LEIDA,
                        'value' => '1',
                        'compare' => '=',
                    );
                } elseif ($leida === 'no_abierta') {
                    $meta_query[] = array(
                        'key' => self::META_LEIDA,
                        'value' => '1',
                        'compare' => '!=',
                    );
                }
            }

            if (!empty($meta_query)) {
                $query->set('meta_query', $meta_query);
            }
        }
    }

    /**
     * Renderiza el contenido de las columnas personalizadas
     */
    public function render_custom_columns($column, $post_id)
    {
        // Contenido para la columna personalizada 'solicitud_inicio'
        if ($column === self::META_DNI) {
            $dni = $this->get_meta($post_id, self::META_DNI);
            // error_log('SolicitarProducto: render_custom_columns - post_id=' . $post_id . ' solicitud_dni=' . print_r($dni, true));
            echo esc_html($dni);
        }

        // Contenido para la columna personalizada 'solicitud_estado'
        if ($column === self::META_ESTADO) {
            $estado = $this->get_meta($post_id, self::META_ESTADO);
            // error_log('SolicitarProducto: render_custom_columns - post_id=' . $post_id . ' solicitud_estado=' . print_r($estado, true));
            echo $this->render_estado_label($estado);
        }

        // Contenido para la columna personalizada 'solicitud_leida'
        if ($column === self::META_LEIDA) {
            $leida = $this->get_meta($post_id, self::META_LEIDA);
            if ($leida == 1) {
                echo '<span title="' . esc_attr(__('Leida', self::PLUGIN_SLUG)) . '">' . self::ICONO_LEIDA . '</span>';
            } else {
                echo '<span title="' . esc_attr(__('No leida', self::PLUGIN_SLUG)) . '">' . self::ICONO_NO_LEIDA . '</span>';
            }
        }
    }

    /**
     * Personaliza las acciones rápidas en el listado del CPT.
     * Actualmente solo muestra "Visualizar" pero se puede extender fácilmente.
     */
    public function custom_row_actions($actions, $post)
    {
        if ($post->post_type === self::POST_TYPE) {
            $view_link = get_edit_post_link($post->ID, '');
            $new_actions = array(
                'view' => '<a href="' . esc_url($view_link) . '">' . esc_html(__('Visualizar', self::PLUGIN_SLUG)) . '</a>'
                // Ejemplo para agregar más acciones:
                // 'duplicar' => '<a href="#">' . esc_html(__('Duplicar', self::PLUGIN_SLUG)) . '</a>'
            );
            return $new_actions;
        }
        return $actions;
    }

    /**
     * Configura las columnas personalizadas del listado del CPT y elimina el checkbox inicial.
     */
    public function setup_custom_columns($columns)
    {
        if (!$this->is_registro_pago_screen()) {
            return $columns;
        }

        if (isset($columns['cb'])) {
            unset($columns['cb']);
        }

        $columns[self::META_DNI] = esc_html(__('DNI', self::PLUGIN_SLUG));
        $columns[self::META_ESTADO] = esc_html(__('Estado', self::PLUGIN_SLUG));
        $columns[self::META_LEIDA] = esc_html(__('Leida', self::PLUGIN_SLUG));
        return $columns;
    }

    /**
     * Renderiza el metabox de detalles
     */
    public function render_detalles_metabox($post)
    {
        require_once plugin_dir_path(__FILE__) . '../admin/views/registro-pago-admin-edit.php';
    }

    /**
     * Configura los metaboxes: agrega el metabox de detalles y quita el de publicar.
     */
    public function setup_metaboxes()
    {
        add_meta_box(
            'solicitud_producto_detalles',
            esc_html(__('Detalles de la solicitud', self::PLUGIN_SLUG)),
            array($this, 'render_detalles_metabox'),
            self::POST_TYPE,
            'normal',
            'high'
        );
        remove_meta_box('submitdiv', self::POST_TYPE, 'side');
    }

    /**
     * Guarda el estado de la solicitud al actualizar el post
     */
    public function save_detalles_metabox($post_id)
    {
        // Validar nonce
        if (!isset($_POST['save_registro_pago']) || !wp_verify_nonce($_POST['save_registro_pago'], 'registro_pago_nonce')) {
            return;
        }
        // Validar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar el estado si se envió
        if (isset($_POST[self::META_ESTADO])) {
            $estado = sanitize_text_field($_POST[self::META_ESTADO]);
            // Validar que el estado sea uno permitido
            if (array_key_exists($estado, self::ESTADOS)) {
                update_post_meta($post_id, self::META_ESTADO, $estado);
            }
        }

        // Guardar las observaciones si se enviaron
        if (isset($_POST[self::META_OBSERVACIONES])) {
            $observaciones = sanitize_textarea_field($_POST[self::META_OBSERVACIONES]);
            update_post_meta($post_id, self::META_OBSERVACIONES, $observaciones);
        }
    }

    // // /**
    // //  * Quita el metabox de publicar
    // //  */
    // // public function remove_publish_metabox()
    // // {
    // //     remove_meta_box('submitdiv', self::POST_TYPE, 'side');
    // // }

    /**
     * Hace las columnas personalizadas ordenables en el listado del CPT
     */
    public function register_custom_columns_sortable($columns)
    {
        $columns[self::META_DNI] = self::META_DNI;
        $columns[self::META_ESTADO] = self::META_ESTADO;
        $columns[self::META_LEIDA] = self::META_LEIDA;
        return $columns;
    }

    /**
     * Unifica y gestiona el ordenamiento por columnas personalizadas (meta) en el listado del CPT.
     * Aplica tanto en "Todas las solicitudes" como en los submenús filtrados por estado.
     */
    public function handle_custom_orderby($query)
    {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        if ($query->get('post_type') !== self::POST_TYPE) {
            return;
        }

        $orderby = $query->get('orderby');
        $estado = isset($_GET[self::META_ESTADO]) ? sanitize_text_field($_GET[self::META_ESTADO]) : '';

        // Si se ordena por una columna meta, setear meta_key y orderby
        if (in_array($orderby, [self::META_ESTADO, self::META_DNI, self::META_LEIDA])) {
            $query->set('meta_key', $orderby);
            $query->set('orderby', 'meta_value');
        }

        // Si se está filtrando por estado, agregar meta_query para que orderby funcione junto al filtro
        if ($estado !== '') {
            $meta_query = $query->get('meta_query');
            if (!is_array($meta_query)) {
                $meta_query = array();
            }
            $meta_query[] = array(
                'key' => self::META_ESTADO,
                'value' => $estado,
                'compare' => '='
            );
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Callback para forzar el orderby por meta_value en listados filtrados y generales.
     * Aplica CAST según el tipo de columna.
     */
    public static function handle_posts_orderby($orderby_sql, $query_obj)
    {
        if (!is_admin() || !$query_obj->is_main_query()) {
            return $orderby_sql;
        }
        if ($query_obj->get('post_type') !== self::POST_TYPE) {
            return $orderby_sql;
        }

        $orderby = $query_obj->get('orderby');
        $meta_key = $query_obj->get('meta_key');

        global $wpdb;
        switch ($orderby) {
            case self::META_ESTADO:
            case self::META_DNI:
                if (!$meta_key) {
                    $query_obj->set('meta_key', $orderby);
                }
                $orderby_sql = "CAST($wpdb->postmeta.meta_value AS CHAR) " . ($query_obj->get('order') === 'desc' ? 'DESC' : 'ASC');
                break;
            case self::META_LEIDA:
                if (!$meta_key) {
                    $query_obj->set('meta_key', $orderby);
                }
                $orderby_sql = "CAST($wpdb->postmeta.meta_value AS UNSIGNED) " . ($query_obj->get('order') === 'desc' ? 'DESC' : 'ASC');
                break;
        }
        return $orderby_sql;
    }

    /**
     * Desactiva el reemplazo de emojis por imágenes en el admin para el CPT 'solicitud_producto'.
     * Debe ejecutarse en admin_init para que sea efectivo antes de que se carguen los scripts.
     */
    public function disable_emoji_admin_init()
    {
        global $pagenow;
        if (
            $pagenow === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === self::POST_TYPE ||
            $pagenow === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === self::POST_TYPE
        ) {
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
        }
    }

    /*************************** UTILS *************************/
    /**
     * Verifica si el screen actual corresponde al CPT 'solicitud_producto'.
     * Útil para condicionar scripts y estilos solo en el contexto adecuado.
     */
    private function is_registro_pago_screen()
    {
        $screen = get_current_screen();
        return isset($screen->post_type) && $screen->post_type === self::POST_TYPE;
    }

    // /**
    //  * Formatea una fecha a 'd/m/Y H:i' y opcionalmente muestra el día de la semana.
    //  * @param string $fecha
    //  * @param bool $mostrar_dia_semana
    //  * @return string
    //  */
    // private function format_fecha($fecha, $mostrar_dia_semana = false)
    // {
    //     if (empty($fecha)) return '';
    //     $timestamp = strtotime($fecha);
    //     if (!$timestamp) return '';
    //     $formato = $mostrar_dia_semana ? 'l d/m/Y H:i' : 'd/m/Y H:i';
    //     // 'l' = día de la semana en inglés, para español se puede traducir
    //     $fecha_str = date($formato, $timestamp);
    //     if ($mostrar_dia_semana) {
    //         // Traducir día de la semana al español
    //         $dias = array(
    //             'Monday' => 'Lunes',
    //             'Tuesday' => 'Martes',
    //             'Wednesday' => 'Miércoles',
    //             'Thursday' => 'Jueves',
    //             'Friday' => 'Viernes',
    //             'Saturday' => 'Sábado',
    //             'Sunday' => 'Domingo',
    //         );
    //         $fecha_str = str_replace(array_keys($dias), array_values($dias), $fecha_str);
    //     }
    //     return $fecha_str;
    // }

    /**
     * Renderiza el estado con icono y etiqueta para mostrar en el admin.
     * Ejemplo: Pagada → icono verde + texto.
     */
    private function render_estado_label($estado)
    {
        $css_class = 'registro-estado-label registro-estado-' . strtolower($estado);
        $data = isset(self::ESTADOS[$estado]) ? self::ESTADOS[$estado] : null;
        $icon = $data ? '<span class="registro-estado-icon">' . $data['icon'] . '</span>' : '';
        $label = $data ? esc_html(__($data['label'], self::PLUGIN_SLUG)) : esc_html(__($estado, self::PLUGIN_SLUG));
        return '<span class="' . esc_attr($css_class) . '">' . $icon . ' ' . $label . '</span>';
    }

    /**
     * Obtiene el valor de un meta key de un post, o string vacío si no existe.
     * Evita warnings y facilita el acceso seguro a metadatos.
     */
    private function get_meta($post_id, $key)
    {
        $value = get_post_meta($post_id, $key, true);
        return $value ? $value : '';
    }

    /**
     * Renderiza el select de estado reutilizable.
     * @param string $selected Estado seleccionado
     * @param array|null $allowed_keys Si se pasa, solo muestra esos estados
     * @param string $name Nombre del select (por defecto 'solicitud_estado')
     * @param string $id ID del select (por defecto 'solicitud_estado')
     * @param string $extra_attrs Atributos extra para el select
     * @return string HTML del select
     */
    private function render_estado_select($selected = '', $allowed_keys = null, $name = 'solicitud_estado', $id = 'solicitud_estado', $extra_attrs = '')
    {
        $estados = self::ESTADOS;

        $html = '<select name="' . esc_attr($name) . '" id="' . esc_attr($id) . '" ' . $extra_attrs . '>';
        $html .= '<option value="">' . esc_html(__('Todos los estados', self::PLUGIN_SLUG)) . '</option>';

        foreach ($estados as $key => $data) {
            if ($allowed_keys && !in_array($key, $allowed_keys)) continue;
            $html .= '<option value="' . esc_attr($key) . '"' . selected($selected, $key, false) . '>' . esc_html(__($data['label'], self::PLUGIN_SLUG)) . '</option>';
        }

        $html .= '</select>';

        return $html;
    }

    /**
     * Renderiza el select de leídas/no leídas.
     * @param string $selected Valor seleccionado
     * @return string HTML del select
     */
    private function render_leida_select($selected = '')
    {
        $options = array(
            '' => esc_html(__('Todas', self::PLUGIN_SLUG)),
            'abierta' => esc_html(__('Leídas', self::PLUGIN_SLUG)),
            'no_abierta' => esc_html(__('No leídas', self::PLUGIN_SLUG)),
        );

        $html = '<select name="' . self::META_LEIDA . '" id="' . self::META_LEIDA . '">';

        foreach ($options as $value => $label) {
            $html .= '<option value="' . esc_attr($value) . '"' . selected($selected, $value, false) . '>' . esc_html($label) . '</option>';
        }

        $html .= '</select>';
        return $html;
    }

    /**
     * Agrega el menú principal de Registro de Pagos con globito rojo y href correcto
     */
    private function add_main_menu()
    {
        $menu_slug = 'edit.php?post_type=' . self::POST_TYPE;
        $count_nuevas = self::get_nuevas_por_estado();
        $badge_html = $count_nuevas > 0 ? ' <span class="count-badge">' . $count_nuevas . '</span>' : '';

        add_menu_page(
            esc_html__('Pagos', self::PLUGIN_SLUG),
            esc_html__('Pagos', self::PLUGIN_SLUG) . $badge_html,
            'manage_options',
            $menu_slug,
            '',
            'dashicons-money-alt',
            6
        );
    }

    /**
     * Agrega submenús por estado al menú de Solicitudes.
     */
    private function add_submenus()
    {
        $estados = self::ESTADOS;
        $menu_slug = 'edit.php?post_type=' . self::POST_TYPE;

        ///ESTADO EXAMPLE        'Inicial'   => ['label' => 'Inicial',   'color' => '#f7b731', 'icon' => "\u{1F7E1}"],  
        foreach ($estados as $key => $data) {
            $count_nuevas = self::get_nuevas_por_estado($key);
            $badge_html = $count_nuevas > 0 ? ' <span class="count-badge">' . $count_nuevas . '</span>' : '';

            $icon_html = isset(self::ESTADOS[$key]) ? '<span class="registro-estado-icon"> ' . self::ESTADOS[$key]['icon'] . '</span>' : '';
            $sbmnu_color = isset(self::ESTADOS[$key]) ? self::ESTADOS[$key]['color'] : 'transparent';
            $sbmnu_stle = ' style="color: ' . esc_attr($sbmnu_color) . ';" ';

            add_submenu_page(
                $menu_slug,
                $icon_html . $data['label'] . $badge_html,
                $icon_html . "<span " . $sbmnu_stle . "> " . $data['label'] . $badge_html . "</span>",
                'edit_posts',
                $menu_slug . '&' . self::META_ESTADO . '=' . urlencode($key),
                '',
                null
            );
        } ///end foreach

        //log results
    } ///end add_estado_submenus

}///end class
