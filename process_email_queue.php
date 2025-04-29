<?php
// process_email_queue.php
require_once 'Config/Config.php';
require_once 'Helpers/Helpers.php';
require_once 'Libraries/Core/Autoload.php';
require_once 'Services/Autoload.php';

// Procesar N correos en cada ejecución
$limit = 20;

// Iniciar el procesador de cola
$queueService = new Services\Email\EmailQueueService();
$stats = $queueService->processQueue($limit);

// Mostrar estadísticas
echo "Procesamiento completado:\n";
echo "- Procesados: {$stats['processed']}\n";
echo "- Enviados: {$stats['sent']}\n";
echo "- Fallidos: {$stats['failed']}\n";
echo "- Reprogramados: {$stats['requeued']}\n";

// Registrar en log
error_log("Email Queue Processing: " . json_encode($stats));