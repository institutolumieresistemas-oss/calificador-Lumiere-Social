<?php
/**
 * ====================================================================
 * Webhook Principal de Telegram - Bot Calificador Instituto Lumière
 * (Sistema Simplificado de Hoja Base Única)
 * ====================================================================
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/calificador.php';

// Si se accede directamente por el navegador (GET), mostrar estado amigable
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Bot Calificador Lumière - Estado</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; }
            .card { background: #1e293b; border-radius: 16px; padding: 36px; max-width: 500px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.5); text-align: center; border: 1px solid #334155; }
            .badge { display: inline-block; background: #10b981; color: white; padding: 6px 14px; border-radius: 20px; font-weight: bold; font-size: 13px; margin-bottom: 20px; }
            h1 { margin: 0 0 10px 0; font-size: 24px; color: #fff; }
            p { color: #94a3b8; font-size: 15px; line-height: 1.5; margin: 10px 0 25px 0; }
            .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; margin: 6px; transition: 0.2s; }
            .btn:hover { background: #2563eb; }
            .btn-secondary { background: #475569; }
            .btn-secondary:hover { background: #334155; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="badge">● BOT ACTIVO Y FUNCIONANDO</div>
            <h1>🤖 Calificador Lumière</h1>
            <p>El webhook de Telegram está activo y esperando hojas de examen desde <b>@<?php echo htmlspecialchars(TELEGRAM_BOT_USERNAME); ?></b>.</p>
            <div>
                <a href="set_webhook.php" class="btn">⚙️ Configurar Webhook</a>
                <a href="panel_claves.php" class="btn btn-secondary">🔑 Hoja Base Web</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Obtener payload POST enviado por Telegram
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    http_response_code(200);
    echo "OK";
    exit;
}

$update = json_decode($rawInput, true);
if (!$update) {
    bot_log("Error: No se pudo decodificar JSON de Telegram: " . substr($rawInput, 0, 200));
    http_response_code(200);
    exit;
}

// Procesar mensaje
$message = $update['message'] ?? $update['edited_message'] ?? null;
if (!$message) {
    http_response_code(200);
    exit;
}

$chatId = $message['chat']['id'] ?? null;
$messageId = $message['message_id'] ?? null;
$text = trim($message['text'] ?? '');
$caption = trim($message['caption'] ?? '');
$userId = $message['from']['id'] ?? 0;
$userName = $message['from']['first_name'] ?? 'Usuario';

if (!$chatId) {
    http_response_code(200);
    exit;
}

// ====================================================================
// 1. MANEJO DE COMANDOS DE TEXTO
// ====================================================================
if (!empty($text)) {
    $cmd = strtolower(explode(' ', $text)[0]);

    // 1.1 Menú de bienvenida / ayuda
    if ($cmd === '/start' || $cmd === '/ayuda' || $cmd === '/help') {
        $bienvenida = "🎓 <b>¡Hola, {$userName}!</b>\n";
        $bienvenida .= "Bienvenido al <b>Bot Calificador de Exámenes del Instituto Lumière</b>.\n\n";
        $bienvenida .= "📸 <b>¿Cómo calificar tu hoja de respuestas?</b>\n";
        $bienvenida .= "1️⃣ Toma una fotografía clara de tu hoja de respuestas.\n";
        $bienvenida .= "2️⃣ Envíamela directamente aquí como <b>Foto</b> o <b>Archivo</b>.\n\n";
        $bienvenida .= "🤖 <b>¿Qué hace la IA?</b>\n";
        $bienvenida .= "• Detecta automáticamente tus burbujas rellenadas por sección.\n";
        $bienvenida .= "• Compara contra la <b>Hoja Base oficial</b>.\n";
        $bienvenida .= "• Calcula tu <b>Puntaje Estimado College Board PAA (400 a 1,600 pts)</b>.\n\n";
        $bienvenida .= "👨‍🏫 <b>Profesores:</b>\n";
        $bienvenida .= "Para registrar o actualizar la Hoja Base con respuestas correctas, escribe: <code>A-EXAMEN</code>\n\n";
        $bienvenida .= "📋 <b>Comandos:</b>\n";
        $bienvenida .= "• /base - Ver estado de la Hoja Base actual\n";
        $bienvenida .= "• /ayuda - Consejos para tomar la foto";

        telegram_send_message($chatId, $bienvenida, $messageId);
        http_response_code(200);
        exit;
    }

    // 1.2 Ver estado de la Hoja Base actual
    if ($cmd === '/base' || $cmd === '/examenes') {
        $base = cargar_hoja_base();
        $lecCount = count($base['lectura'] ?? []);
        $redCount = count($base['redaccion'] ?? []);
        $matCount = count($base['matematicas'] ?? []);
        $ingCount = count($base['ingles'] ?? []);
        $total = $lecCount + $redCount + $matCount + $ingCount;

        if ($total === 0) {
            $msg = "⚠️ <b>Aún no hay una Hoja Base registrada.</b>\n\nPara registrarla, escribe: <code>A-EXAMEN</code> y envía la foto de la hoja contestada con las respuestas correctas.";
        } else {
            $msg = "📋 <b>HOJA BASE OFICIAL (INSTITUTO LUMIÈRE):</b>\n";
            $msg .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
            $msg .= "📅 <b>Última actualización:</b> " . ($base['fecha'] ?? 'Reciente') . "\n";
            $msg .= "📊 <b>Total de reactivos clave:</b> {$total}\n";
            $msg .= "• 📖 <b>Lectura:</b> {$lecCount} reactivos\n";
            $msg .= "• ✍️ <b>Redacción:</b> {$redCount} reactivos\n";
            $msg .= "• 📐 <b>Matemáticas:</b> {$matCount} reactivos\n";
            if ($ingCount > 0) {
                $msg .= "• 🇬🇧 <b>Inglés:</b> {$ingCount} reactivos\n";
            }
            $msg .= "\n💡 <i>Para actualizar la hoja base, escribe <code>A-EXAMEN</code></i>";
        }

        telegram_send_message($chatId, $msg, $messageId);
        http_response_code(200);
        exit;
    }

    // 1.3 Cancelar modo
    if (strtoupper($text) === 'CANCELAR' || strtolower($text) === '/cancelar') {
        clear_chat_state($chatId);
        telegram_send_message($chatId, "❌ Operación cancelada. El bot continuará calificando hojas normales.", $messageId);
        http_response_code(200);
        exit;
    }

    // 1.4 Comando A-EXAMEN (en mayúsculas): Registro de la Hoja Base
    if (strtoupper($text) === 'A-EXAMEN' || strtolower($text) === '/a_examen') {
        set_chat_state($chatId, 'esperando_hoja_base');

        $msgBase = "📥 <b>MODO REGISTRO DE HOJA BASE ACTIVADO</b>\n";
        $msgBase .= "━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $msgBase .= "📸 <b>Por favor, envía ahora la foto de la Hoja de Contestación Base</b> (con las respuestas correctas rellenadas).\n\n";
        $msgBase .= "🤖 La Inteligencia Artificial escaneará automáticamente todas las burbujas correctas y guardará la clave oficial para comparar los exámenes de todos los alumnos.\n\n";
        $msgBase .= "💡 <i>(Para cancelar en cualquier momento, escribe <b>CANCELAR</b>)</i>";

        telegram_send_message($chatId, $msgBase, $messageId);
        http_response_code(200);
        exit;
    }

    // Si envió cualquier otro texto que no sea comando y no está en espera de hoja base
    $chatState = get_chat_state($chatId);
    if (empty($message['photo']) && empty($message['document']) && (!$chatState || $chatState['estado'] !== 'esperando_hoja_base')) {
        $msg = "📸 <b>Por favor, envíame una foto de tu hoja de respuestas</b> para calificarla automáticamente con Inteligencia Artificial.\n\n";
        $msg .= "💡 <i>Profesores:</i> Escriban <code>A-EXAMEN</code> para registrar o actualizar la hoja de respuestas base.\n\nEscribe /ayuda para más información.";
        telegram_send_message($chatId, $msg, $messageId);
        http_response_code(200);
        exit;
    }
}

// ====================================================================
// 2. MANEJO DE FOTOGRAFÍAS Y DOCUMENTOS DE IMAGEN
// ====================================================================
$fileId = null;
$mimeType = 'image/jpeg';

if (!empty($message['photo'])) {
    // Tomar la foto de mayor resolución (último elemento del arreglo)
    $fotoMayor = end($message['photo']);
    $fileId = $fotoMayor['file_id'] ?? null;
} elseif (!empty($message['document'])) {
    $doc = $message['document'];
    $docMime = $doc['mime_type'] ?? '';
    if (strpos($docMime, 'image/') === 0) {
        $fileId = $doc['file_id'] ?? null;
        $mimeType = $docMime;
    }
}

if (!$fileId) {
    http_response_code(200);
    exit;
}

// Verificar si estamos en modo Hoja Base (por estado de chat previo o por caption de la foto)
$chatState = get_chat_state($chatId);
$esModoBase = ($chatState && ($chatState['estado'] ?? '') === 'esperando_hoja_base') || (strtoupper($caption) === 'A-EXAMEN');

if ($esModoBase) {
    clear_chat_state($chatId);
}

// Avisar al usuario que la foto fue recibida y se está procesando
telegram_send_action($chatId, 'typing');
if ($esModoBase) {
    telegram_send_message($chatId, "⏳ <b>Hoja Base recibida.</b> Escaneando y guardando respuestas correctas con Inteligencia Artificial...", $messageId);
} else {
    telegram_send_message($chatId, "⏳ <b>Foto recibida.</b> Analizando hoja de respuestas con Inteligencia Artificial...", $messageId);
}

// Obtener ruta de descarga del archivo desde Telegram API
$fileInfo = telegram_request('getFile', ['file_id' => $fileId]);
$filePath = $fileInfo['result']['file_path'] ?? null;

if (!$filePath) {
    telegram_send_message($chatId, "❌ Error al descargar la foto desde Telegram. Por favor intenta reenviarla.", $messageId);
    http_response_code(200);
    exit;
}

// Descargar imagen a memoria con cURL (rápido y con timeout seguro para hostings)
$downloadUrl = TELEGRAM_FILE_URL . $filePath;
$chDl = curl_init();
curl_setopt($chDl, CURLOPT_URL, $downloadUrl);
curl_setopt($chDl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chDl, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($chDl, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($chDl, CURLOPT_TIMEOUT, 15);
$imageBytes = curl_exec($chDl);
$httpDl = curl_getinfo($chDl, CURLINFO_HTTP_CODE);
curl_close($chDl);

if (!$imageBytes || $httpDl !== 200) {
    $imageBytes = @file_get_contents($downloadUrl);
}

if (!$imageBytes) {
    telegram_send_message($chatId, "❌ No se pudo descargar la imagen desde Telegram. Verifica tu conexión e intenta de nuevo.", $messageId);
    http_response_code(200);
    exit;
}

// Enviar directamente a Google Gemini Vision
telegram_send_action($chatId, 'upload_document');
$datosDetectados = gemini_analyze_sheet($imageBytes, $mimeType);

if (isset($datosDetectados['error'])) {
    $errorMsg = "❌ <b>No se pudo procesar la hoja:</b>\n" . htmlspecialchars($datosDetectados['error']);
    $errorMsg .= "\n\n💡 <i>Consejos: Asegúrate de que la hoja tenga buena iluminación, esté sobre una superficie plana y las burbujas estén bien marcadas.</i>";
    telegram_send_message($chatId, $errorMsg, $messageId);
    http_response_code(200);
    exit;
}

// ====================================================================
// CASO A: ACTUALIZAR HOJA BASE (CLAVE OFICIAL)
// ====================================================================
if ($esModoBase) {
    $hojaBase = [
        'nombre' => 'Hoja Base Lumière PAA',
        'fecha' => date('d/m/Y H:i'),
        'lectura' => $datosDetectados['lectura'] ?? [],
        'redaccion' => $datosDetectados['redaccion'] ?? [],
        'matematicas' => $datosDetectados['matematicas'] ?? [],
        'ingles' => $datosDetectados['ingles'] ?? []
    ];
    guardar_hoja_base($hojaBase);

    $countLec = count($datosDetectados['lectura'] ?? []);
    $countRed = count($datosDetectados['redaccion'] ?? []);
    $countMat = count($datosDetectados['matematicas'] ?? []);
    $countIng = count($datosDetectados['ingles'] ?? []);
    $total = $countLec + $countRed + $countMat + $countIng;

    $msgConfirm = "✅ <b>¡HOJA BASE REGISTRADA EXITOSAMENTE!</b>\n";
    $msgConfirm .= "━━━━━━━━━━━━━━━━━━━━━━━\n";
    $msgConfirm .= "📅 <b>Fecha de actualización:</b> " . date('d/m/Y H:i') . "\n\n";
    $msgConfirm .= "📊 <b>Respuestas correctas guardadas ({$total} reactivos):</b>\n";
    $msgConfirm .= "• 📖 <b>Lectura:</b> {$countLec} reactivos\n";
    $msgConfirm .= "• ✍️ <b>Redacción:</b> {$countRed} reactivos\n";
    $msgConfirm .= "• 📐 <b>Matemáticas:</b> {$countMat} reactivos\n";
    if ($countIng > 0) {
        $msgConfirm .= "• 🇬🇧 <b>Inglés:</b> {$countIng} reactivos\n";
    }
    $msgConfirm .= "\n🎯 <b>¡El sistema está listo!</b> A partir de este momento, todos los exámenes que envíen los alumnos se compararán y calificarán contra esta hoja base.\n\n";
    $msgConfirm .= "💡 <i>Puedes consultar la hoja base en cualquier momento escribiendo /base</i>";

    telegram_send_message($chatId, $msgConfirm, $messageId);
    http_response_code(200);
    echo "OK";
    exit;
}

// ====================================================================
// CASO B: CALIFICAR HOJA DE ALUMNO REGULAR CONTRA LA HOJA BASE
// ====================================================================
$resultadoCalificacion = calificar_examen($datosDetectados);

// Formatear mensajes
$mensajesReporte = formatear_reporte_telegram($resultadoCalificacion);

// Enviar los mensajes a Telegram
foreach ($mensajesReporte as $idx => $repMsg) {
    telegram_send_message($chatId, $repMsg, $idx === 0 ? $messageId : null);
}

// Enviar también la foto con los círculos analizados
$hojaBase = cargar_hoja_base();
$dataUri = generar_hoja_con_circulos_anotados($imageBytes, $datosDetectados, $hojaBase);
if ($dataUri) {
    $imgBin = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1));
    telegram_send_photo($chatId, $imgBin, "🎨 <b>Hoja Analizada con Círculos Detectados</b>");
}

http_response_code(200);
echo "OK";
