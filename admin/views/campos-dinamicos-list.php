<?php
/** @var Campos_Dinamicos_List_Table $list_table */
?>
<div class="wrap">
    <h2><?php _e('Entradas con campos dinámicos', 'solicitar-producto'); ?></h2>
    <form method="get">
        <input type="hidden" name="page" value="campos-dinamicos-post" />
        <?php $list_table->search_box(__('Buscar por título', 'solicitar-producto'), 's'); ?>
        <?php $list_table->display(); ?>
    </form>
</div>
