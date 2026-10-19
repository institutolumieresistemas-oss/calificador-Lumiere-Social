<?php
/**
 * ====================================================================
 * Herramienta de Activación y Diagnóstico de Webhook de Telegram
 * ====================================================================
 */

require_once __DIR__ . '/config.php';

$mensaje = null;
$tipoMensaje = 'info';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'set_webhook') {
        $webhookUrl = trim($_POST['webhook_url'] ?? '');
        if (empty($webhookUrl)) {
            $mensaje = "Debes ingresar una URL válida.";
            $tipoMensaje = 'danger';
        } elseif (strpos($webhookUrl, 'https://') !== 0) {
            $mensaje = "Telegram requiere obligatoriamente HTTPS con certificado SSL.";
            $tipoMensaje = 'warning';
        } else {
            $res = telegram_request('setWebhook', ['url' => $webhookUrl]);
            if ($res && !empty($res['ok'])) {
                $mensaje = "✅ ¡Webhook vinculado con éxito a: <code>{$webhookUrl}</code>!";
                $tipoMensaje = 'success';
            } else {
                $mensaje = "❌ Error al configurar webhook: " . json_encode($res, JSON_UNESCAPED_UNICODE);
                $tipoMensaje = 'danger';
            }
        }
    } elseif ($accion === 'delete_webhook') {
        $res = telegram_request('deleteWebhook', ['drop_pending_updates' => true]);
        if ($res && !empty($res['ok'])) {
            $mensaje = "🗑️ Webhook eliminado correctamente de Telegram.";
            $tipoMensaje = 'info';
        } else {
            $mensaje = "❌ Error al eliminar webhook: " . json_encode($res, JSON_UNESCAPED_UNICODE);
            $tipoMensaje = 'danger';
        }
    }
}

// Obtener info actual del bot y webhook
$botInfo = telegram_request('getMe');
$webhookInfo = telegram_request('getWebhookInfo');

// Auto-detectar URL propuesta en Hostinger
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$dir = dirname($_SERVER['REQUEST_URI'] ?? '');
$dir = ($dir === '/' || $dir === '\\') ? '' : $dir;
$urlPropuesta = "{$protocol}{$host}{$dir}/bot.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Webhook - Calificador Lumière</title>
    <style>
        :root {
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --bg: #0f172a;
            --card-bg: #1e293b;
            --border: #334155;
            --text: #f8fafc;
            --muted: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 24px;
            display: flex;
            justify-content: center;
        }
        .container {
            max-width: 680px;
            width: 100%;
        }
        .card {
            background: var(--card-bg);
            border-radius: 14px;
            padding: 28px;
            border: 1px solid var(--border);
            margin-bottom: 24px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }
        h1 { margin: 0 0 8px 0; font-size: 24px; color: #fff; }
        h2 { font-size: 18px; margin: 0 0 16px 0; border-bottom: 1px solid var(--border); padding-bottom: 10px; }
        p { color: var(--muted); margin: 0 0 16px 0; line-height: 1.5; font-size: 14px; }
        .alert {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.4;
        }
        .alert-success { background: rgba(16, 185, 129, 0.2); border: 1px solid var(--success); color: #6ee7b7; }
        .alert-danger { background: rgba(239, 68, 68, 0.2); border: 1px solid var(--danger); color: #fca5a5; }
        .alert-warning { background: rgba(245, 158, 11, 0.2); border: 1px solid var(--warning); color: #fde68a; }
        .alert-info { background: rgba(59, 130, 246, 0.2); border: 1px solid var(--primary); color: #93c5fd; }
        
        .field { margin-bottom: 16px; }
        label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 13px; color: #cbd5e1; }
        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            background: #0f172a;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: #fff;
            box-sizing: border-box;
            font-size: 14px;
            font-family: monospace;
        }
        input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
        }
        .btn-group { display: flex; gap: 10px; margin-top: 16px; }
        button {
            padding: 12px 20px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #475569; color: white; text-decoration: none; display: inline-block; padding: 12px 20px; border-radius: 8px; font-weight: 600; font-size: 14px; text-align: center; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }
        .info-box {
            background: #0f172a;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }
        .info-box .title { font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: bold; }
        .info-box .val { font-size: 14px; font-weight: 600; color: #fff; margin-top: 4px; word-break: break-all; }
        code { background: #0f172a; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
<div class="container">
    <div style="text-align: center; margin-bottom: 24px;">
        <h1>⚙️ Configuración del Bot Telegram</h1>
        <p>Panel para conectar tu servidor de Hostinger con la API de Telegram y Google Gemini</p>
    </div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipoMensaje; ?>">
            <?php echo $mensaje; ?>
        </div>
    <?php endif; ?>

    <!-- Estado de Gemini AI -->
    <?php if (GEMINI_API_KEY === 'TU_GEMINI_API_KEY_AQUI' || empty(GEMINI_API_KEY)): ?>
        <div class="alert alert-warning">
            ⚠️ <b>Google Gemini API Key pendiente:</b><br>
            Aún no has configurado tu clave gratuita de Gemini. Edita el archivo <code>config.php</code> y coloca tu API Key de <a href="https://aistudio.google.com/" target="_blank" style="color: #fbbf24; text-decoration: underline;">Google AI Studio</a>.
        </div>
    <?php else: ?>
        <div class="alert alert-success">
            ✨ <b>Google Gemini AI:</b> Configurado con éxito (Modelo: <code><?php echo htmlspecialchars(GEMINI_PRIMARY_MODEL); ?></code>).
        </div>
    <?php endif; ?>

    <!-- Estado del Bot en Telegram -->
    <div class="card">
        <h2>🤖 Estado del Bot en Telegram</h2>
        <?php if (!empty($botInfo['ok'])): ?>
            <div class="info-grid">
                <div class="info-box">
                    <div class="title">Nombre del Bot</div>
                    <div class="val"><?php echo htmlspecialchars($botInfo['result']['first_name']); ?></div>
                </div>
                <div class="info-box">
                    <div class="title">Usuario en Telegram</div>
                    <div class="val">@<?php echo htmlspecialchars($botInfo['result']['username']); ?></div>
                </div>
                <div class="info-box" style="grid-column: span 2;">
                    <div class="title">Webhook Registrado Actualmente</div>
                    <div class="val">
                        <?php 
                        $actualUrl = $webhookInfo['result']['url'] ?? '';
                        echo !empty($actualUrl) ? '<code>' . htmlspecialchars($actualUrl) . '</code>' : '<span style="color:#f59e0b;">(Ninguno vinculado actualmente)</span>'; 
                        ?>
                    </div>
                </div>
                <?php if (!empty($webhookInfo['result']['last_error_message'])): ?>
                    <div class="info-box" style="grid-column: span 2; border-color: var(--danger);">
                        <div class="title" style="color: #ef4444;">Último Error Reportado por Telegram</div>
                        <div class="val" style="color: #fca5a5;"><?php echo htmlspecialchars($webhookInfo['result']['last_error_message']); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                ❌ No se pudo conectar a Telegram. Verifica que el token en <code>config.php</code> sea válido.
            </div>
        <?php endif; ?>
    </div>

    <!-- Formulario para vincular Webhook -->
    <div class="card">
        <h2>🔗 Vincular URL de Webhook</h2>
        <p>Telegram enviará en tiempo real las fotos de los exámenes a la URL que registres aquí:</p>
        
        <form method="POST">
            <input type="hidden" name="accion" value="set_webhook">
            <div class="field">
                <label for="webhook_url">URL de tu archivo bot.php en Hostinger (Debe ser HTTPS):</label>
                <input type="text" id="webhook_url" name="webhook_url" value="<?php echo htmlspecialchars($urlPropuesta); ?>" required>
            </div>
            
            <div class="btn-group">
                <button type="submit" class="btn-primary">🚀 Activar Webhook</button>
            </div>
        </form>

        <form method="POST" style="margin-top: 16px;">
            <input type="hidden" name="accion" value="delete_webhook">
            <button type="submit" class="btn-danger" onclick="return confirm('¿Seguro que deseas desvincular el webhook?');">🗑️ Desvincular Webhook</button>
            <a href="panel_claves.php" class="btn-secondary" style="margin-left: 10px;">🔑 Ir al Panel de Claves</a>
        </form>
    </div>
</div>
</body>
</html>
