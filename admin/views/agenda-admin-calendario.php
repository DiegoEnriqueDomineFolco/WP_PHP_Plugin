<div style="background:#f9f9f9; border:1px solid #ddd; padding:16px;">
    <strong><?php _e('Calendario (mes/año)', 'solicitar-producto'); ?></strong>
    <div id="agenda-fullcalendar" style="max-width: 800px; margin: 24px auto;"></div>
</div>
<?php
// Preparar eventos para el calendario desde $reservas
$eventos = array();
if (!empty($reservas) && is_array($reservas)) {
    foreach ($reservas as $reserva) {
        $nombre = get_post_meta($reserva['solicitud_id'], 'solicitud_nombre', true);
        $eventos[] = array(
            'title' => $nombre ? $nombre : __('Reserva', 'solicitar-producto'),
            'start' => $reserva['fecha_inicio'],
            'end' => date('Y-m-d', strtotime($reserva['fecha_fin'] . ' +1 day')),
            'url' => admin_url('post.php?post=' . intval($reserva['solicitud_id']) . '&action=edit'),
        );
    }
}
?>
<script>
// Pasar eventos PHP a JS
var agendaEventos = <?php echo json_encode($eventos); ?>;
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.initFullCalendar === 'function') {
        window.initFullCalendar('#agenda-fullcalendar', {
            initialView: 'dayGridMonth',
            height: 500,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            events: agendaEventos,
            eventClick: function(info) {
                if (info.event.url) {
                    window.open(info.event.url, '_blank');
                    info.jsEvent.preventDefault();
                }
            }
        });
    } else {
        console.error('initFullCalendar no está disponible');
    }
});
</script>