<?php
/**
 * ====================================================================
 * Web App Móvil de Calificación de Exámenes - Instituto Lumière
 * ====================================================================
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/calificador.php';

$base = cargar_hoja_base();
$totalClaves = count($base['lectura'] ?? []) + count($base['redaccion'] ?? []) + count($base['matematicas'] ?? []) + count($base['ingles'] ?? []);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Calificador Móvil - Instituto Lumière</title>
    <style>
        :root {
            --primary: #c01b2f;
            --primary-hover: #9e1324;
            --primary-light: #fef2f2;
            --secondary: #3b82f6;
            --bg: #0b1120;
            --card-bg: #1e293b;
            --card-sub: #0f172a;
            --border: #334155;
            --text: #f8fafc;
            --muted: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            margin: 0;
            padding: 16px 12px 60px 12px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .container {
            max-width: 580px;
            width: 100%;
        }

        /* Encabezado */
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(192, 27, 47, 0.15);
            border: 1px solid rgba(192, 27, 47, 0.3);
            color: #f87171;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 8px;
        }
        h1 {
            margin: 4px 0 6px 0;
            font-size: 24px;
            font-weight: 800;
            color: #fff;
            letter-spacing: -0.5px;
        }
        .subtitle {
            color: var(--muted);
            font-size: 14px;
            margin: 0;
        }

        /* Pestañas */
        .nav-tabs {
            display: flex;
            background: #1e293b;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }
        .tab-btn {
            flex: 1;
            padding: 10px;
            text-align: center;
            border: none;
            background: transparent;
            color: var(--muted);
            font-weight: 600;
            font-size: 13px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.2s;
        }
        .tab-btn.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 12px rgba(192, 27, 47, 0.35);
        }

        /* Tarjetas */
        .card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 22px;
            border: 1px solid var(--border);
            margin-bottom: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        .field { margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #cbd5e1; margin-bottom: 6px; }
        input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            background: var(--card-sub);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: #fff;
            font-size: 15px;
            outline: none;
        }
        input[type="text"]:focus { border-color: var(--primary); }

        /* Área de Cámara */
        .camera-box {
            border: 2px dashed #475569;
            border-radius: 14px;
            padding: 30px 16px;
            text-align: center;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.6);
            transition: 0.2s;
            margin-bottom: 16px;
        }
        .camera-box:hover, .camera-box:active {
            border-color: var(--primary);
            background: rgba(192, 27, 47, 0.05);
        }
        .camera-icon {
            font-size: 44px;
            margin-bottom: 8px;
            display: inline-block;
        }
        .camera-text {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 4px;
        }
        .camera-sub {
            font-size: 12px;
            color: var(--muted);
        }

        /* Previsualización */
        .preview-container {
            display: none;
            position: relative;
            margin-bottom: 16px;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            max-height: 280px;
            background: #000;
        }
        .preview-container img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
        }
        .btn-change-photo {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(4px);
            color: #fff;
            border: 1px solid #475569;
            padding: 8px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Botón de Acción */
        .btn-submit {
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #c01b2f, #991b1b);
            color: white;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(192, 27, 47, 0.4);
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-submit:disabled {
            background: #475569;
            box-shadow: none;
            cursor: not-allowed;
            opacity: 0.6;
        }

        /* Opciones de Cámara / Cargar Archivo */
        .upload-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .upload-card {
            border: 2px dashed #475569;
            border-radius: 14px;
            padding: 20px 12px;
            text-align: center;
            cursor: pointer;
            background: rgba(15, 23, 42, 0.6);
            transition: all 0.2s ease;
        }
        .upload-card:hover, .upload-card:active {
            border-color: var(--primary);
            background: rgba(192, 27, 47, 0.08);
            transform: translateY(-2px);
        }
        .upload-icon {
            font-size: 32px;
            margin-bottom: 6px;
        }
        .upload-title {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 2px;
        }
        .upload-sub {
            font-size: 11px;
            color: var(--muted);
        }

        /* Pestañas del Visor de Círculos */
        .visor-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
            background: #0f172a;
            padding: 4px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        .visor-tab-btn {
            flex: 1;
            padding: 8px 10px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }
        .visor-tab-btn.active {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.35);
        }

        /* Spinner y Pantalla de Carga */
        .loading-overlay {
            display: none;
            text-align: center;
            padding: 40px 20px;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(192, 27, 47, 0.2);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px auto;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Reporte de Calificación */
        .report-card {
            display: none;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .score-hero {
            background: linear-gradient(135deg, rgba(192, 27, 47, 0.2), rgba(30, 41, 59, 0.9));
            border: 1px solid rgba(192, 27, 47, 0.4);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin-bottom: 16px;
        }
        .score-hero-title {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
            color: #fca5a5;
        }
        .score-hero-val {
            font-size: 52px;
            font-weight: 900;
            color: #fff;
            line-height: 1.1;
            margin: 6px 0;
        }
        .score-hero-val span { font-size: 22px; color: var(--muted); font-weight: 500; }
        
        .paa-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #0f172a;
            border: 1px solid #334155;
            padding: 8px 18px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 15px;
            color: #38bdf8;
            margin-top: 6px;
        }

        /* Grid de Componentes */
        .components-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }
        .comp-card {
            background: var(--card-sub);
            padding: 14px;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-align: center;
        }
        .comp-title { font-size: 11px; text-transform: uppercase; color: var(--muted); font-weight: 700; }
        .comp-pts { font-size: 22px; font-weight: 800; color: #fff; margin: 4px 0; }
        .comp-sub { font-size: 12px; color: #94a3b8; }

        /* Lista de Secciones */
        .sec-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            font-size: 14px;
        }
        .sec-row:last-child { border-bottom: none; }
        .sec-name { display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .sec-stats { font-weight: 700; text-align: right; }
        .sec-pct { font-size: 12px; color: var(--muted); font-weight: normal; }

        /* Chips de Reactivos Interactivos */
        .chips-container {
            display: none;
            margin-top: 16px;
            max-height: 340px;
            overflow-y: auto;
            padding: 12px;
            background: var(--card-sub);
            border-radius: 12px;
            border: 1px solid var(--border);
        }
        .chips-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(78px, 1fr));
            gap: 6px;
        }
        .chip {
            padding: 7px 4px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            font-family: monospace;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
            position: relative;
        }
        .chip:hover {
            transform: translateY(-2px);
            filter: brightness(1.2);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .chip:active {
            transform: scale(0.95);
        }
        .chip-ok { background: rgba(16, 185, 129, 0.2); color: #6ee7b7; border: 1px solid var(--success); }
        .chip-err { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid var(--danger); font-size: 11px; }
        .chip-omit { background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid #475569; }
        .chip-editado::after {
            content: '✏️';
            position: absolute;
            top: -5px;
            right: -3px;
            font-size: 10px;
        }

        /* Modal Flotante de Corrección */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(11, 17, 32, 0.85);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        .modal-content {
            background: #1e293b;
            border: 1px solid #475569;
            border-radius: 16px;
            max-width: 380px;
            width: 100%;
            padding: 20px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            animation: modalFadeIn 0.2s ease-out;
            text-align: center;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.92); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-info-box {
            display: flex;
            justify-content: space-around;
            background: #0f172a;
            padding: 10px;
            border-radius: 10px;
            margin: 12px 0 16px 0;
            border: 1px solid #334155;
            font-size: 13px;
        }
        .badge-clave {
            display: inline-block;
            background: #047857;
            color: #ecfdf5;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
        }
        .badge-marcada {
            display: inline-block;
            background: #334155;
            color: #e2e8f0;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 13px;
        }
        .modal-options-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 14px 0 8px 0;
        }
        .btn-opt {
            padding: 14px 0;
            background: #0f172a;
            border: 2px solid #334155;
            color: #f8fafc;
            font-size: 20px;
            font-weight: 800;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-opt:hover {
            border-color: #3b82f6;
            background: #1e3a8a;
            color: #fff;
        }
        .btn-opt.current {
            border-color: #10b981;
            background: rgba(16, 185, 129, 0.25);
            color: #6ee7b7;
        }
        .btn-opt-omit {
            grid-column: span 4;
            padding: 12px 0;
            font-size: 13px;
            background: #334155;
            border: 1px solid #475569;
            border-radius: 10px;
            color: #cbd5e1;
            cursor: pointer;
            font-weight: 600;
            margin-top: 4px;
            transition: all 0.15s;
        }
        .btn-opt-omit:hover {
            background: #475569;
            color: #fff;
        }

        /* Notificación Toast */
        .toast-notif {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #064e3b;
            color: #a7f3d0;
            border: 1px solid #059669;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 700;
            font-size: 13px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            z-index: 10000;
            transition: transform 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28);
            pointer-events: none;
            max-width: 90%;
            text-align: center;
        }
        .toast-notif.show {
            transform: translateX(-50%) translateY(0);
        }

        /* Botones del Reporte */
        .btn-group {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 16px;
        }
        .btn-action {
            padding: 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #334155;
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .btn-action:hover { background: #475569; }
        .btn-action.active { background: #2563eb; border-color: #3b82f6; }

        /* Impresión Limpia */
        @media print {
            body { background: #fff; color: #000; padding: 0; }
            .nav-tabs, .camera-box, .btn-submit, .btn-group, .header p, .preview-container, .btn-toggle-chips { display: none !important; }
            .card { background: #fff; color: #000; border: 1px solid #ccc; box-shadow: none; padding: 10px; }
            .score-hero { background: #f8f9fa; color: #000; border: 1px solid #000; }
            .score-hero-val { color: #c01b2f; }
            .chips-container { display: block !important; max-height: none; }
        }
    </style>
</head>
<body>
<div class="container">

    <!-- Encabezado Lumière -->
    <div class="header">
        <div class="brand">π INSTITUTO LUMIÈRE</div>
        <h1>Calificador Inteligente PAA</h1>
        <p class="subtitle">Evaluación instantánea con visión artificial y tabulador oficial</p>
    </div>

    <!-- Pestañas de Navegación -->
    <div class="nav-tabs">
        <button class="tab-btn active" id="tabAlumnos" onclick="switchTab('alumno')">🎓 Calificar Alumno</button>
        <button class="tab-btn" id="tabProfesor" onclick="switchTab('profesor')">👨‍🏫 Registrar Hoja Base</button>
    </div>

    <!-- TAB 1: CALIFICAR ALUMNO -->
    <div id="sectionAlumno">
        <div class="card" id="formCard">
            <div class="field">
                <label for="nombreAlumno">Nombre del Alumno (Opcional):</label>
                <input type="text" id="nombreAlumno" placeholder="Ej: Juan Pérez">
            </div>

            <!-- Inputs para cámara o archivo -->
            <input type="file" id="fotoInputCamara" accept="image/*" capture="environment" style="display:none" onchange="handleFileSelected(this)">
            <input type="file" id="fotoInputGaleria" accept="image/*" style="display:none" onchange="handleFileSelected(this)">

            <!-- Opciones: Tomar foto o Cargar imagen -->
            <div class="upload-grid" id="uploadOptionsAlumno">
                <div class="upload-card" onclick="document.getElementById('fotoInputCamara').click()">
                    <div class="upload-icon">📸</div>
                    <div class="upload-title">Tomar Foto</div>
                    <div class="upload-sub">Abrir cámara del celular</div>
                </div>
                <div class="upload-card" onclick="document.getElementById('fotoInputGaleria').click()">
                    <div class="upload-icon">📁</div>
                    <div class="upload-title">Cargar Imagen</div>
                    <div class="upload-sub">Galería o archivos</div>
                </div>
            </div>

            <!-- Previsualización -->
            <div class="preview-container" id="previewContainer">
                <img id="previewImg" src="" alt="Previsualización">
                <button type="button" class="btn-change-photo" onclick="cambiarFotoAlumno()">🔄 Cambiar Foto</button>
            </div>

            <button type="button" class="btn-submit" id="btnCalificar" onclick="enviarCalificacion('calificar')" disabled>
                ⚡ Calificar Examen con IA
            </button>
        </div>
    </div>

    <!-- TAB 2: REGISTRAR HOJA BASE -->
    <div id="sectionProfesor" style="display:none;">
        <div class="card">
            <h2 style="font-size:16px; margin:0 0 8px 0; color:#fff;">🔑 Registrar Nueva Hoja Base (Maestra)</h2>
            <p style="color:var(--muted); font-size:13px; line-height:1.5; margin:0 0 16px 0;">
                Toma la foto de la hoja de respuestas donde marcaste las <b>respuestas correctas oficiales</b>. La IA extraerá los círculos y actualizará la clave maestra del sistema.
            </p>

            <input type="file" id="fotoBaseCamara" accept="image/*" capture="environment" style="display:none" onchange="handleBaseFileSelected(this)">
            <input type="file" id="fotoBaseGaleria" accept="image/*" style="display:none" onchange="handleBaseFileSelected(this)">

            <div class="upload-grid" id="uploadOptionsBase">
                <div class="upload-card" onclick="document.getElementById('fotoBaseCamara').click()">
                    <div class="upload-icon">📸</div>
                    <div class="upload-title">Tomar Foto</div>
                    <div class="upload-sub">Abrir cámara del celular</div>
                </div>
                <div class="upload-card" onclick="document.getElementById('fotoBaseGaleria').click()">
                    <div class="upload-icon">📁</div>
                    <div class="upload-title">Cargar Imagen</div>
                    <div class="upload-sub">Galería o archivos</div>
                </div>
            </div>

            <div class="preview-container" id="previewBaseContainer">
                <img id="previewBaseImg" src="" alt="Previsualización Base">
                <button type="button" class="btn-change-photo" onclick="cambiarFotoBase()">🔄 Cambiar Foto</button>
            </div>

            <button type="button" class="btn-submit" id="btnGuardarBase" onclick="enviarCalificacion('guardar_base')" disabled style="background:#059669;">
                💾 Guardar como Hoja Base Oficial
            </button>
        </div>
    </div>

    <!-- PANTALLA DE CARGA -->
    <div class="card loading-overlay" id="loadingCard">
        <div class="spinner"></div>
        <div id="loadingText" style="font-weight:700; font-size:16px; color:#fff; margin-bottom:6px;">Subiendo fotografía en alta resolución...</div>
        <div style="font-size:13px; color:var(--muted);">Analizando burbujas con Inteligencia Artificial...</div>
    </div>

    <!-- REPORTE DE RESULTADOS (OCULTO INICIALMENTE) -->
    <div class="card report-card" id="reportCard">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div>
                <span id="repAlumnoNombre" style="font-size:16px; font-weight:800; color:#fff;">Juan Pérez</span>
                <div id="repFecha" style="font-size:12px; color:var(--muted);">14/10/2026 15:30</div>
            </div>
            <span style="background:rgba(16,185,129,0.2); color:#6ee7b7; border:1px solid #10b981; padding:4px 10px; border-radius:12px; font-size:11px; font-weight:700;">
                EVALUADO CON ÉXITO
            </span>
        </div>

        <!-- Puntaje 0 a 100 -->
        <div class="score-hero">
            <div class="score-hero-title">Puntaje de Examen (Escala 0 a 100)</div>
            <div class="score-hero-val" id="repPuntaje100">85.42 <span>/ 100</span></div>
            <div class="paa-badge">
                🏆 College Board PAA: <b id="repPaaTotal">1,425 / 1,600 pts</b>
            </div>
        </div>

        <!-- Escalas Verbal y Matemáticas -->
        <div class="components-grid">
            <div class="comp-card">
                <div class="comp-title">📖 Componente Verbal</div>
                <div class="comp-pts" id="repPaaVerbal">715 pts</div>
                <div class="comp-sub" id="repAciertosVerbal">61 aciertos</div>
            </div>
            <div class="comp-card">
                <div class="comp-title">📐 Matemático</div>
                <div class="comp-pts" id="repPaaMate">710 pts</div>
                <div class="comp-sub" id="repAciertosMate">45 aciertos</div>
            </div>
        </div>

        <!-- Desglose por Secciones -->
        <h3 style="font-size:14px; margin:16px 0 8px 0; color:#cbd5e1; border-bottom:1px solid var(--border); padding-bottom:6px;">
            📊 Desglose por Secciones
        </h3>
        <div id="seccionesContainer"></div>

        <!-- Detalle de reactivos desplegable -->
        <button type="button" class="btn-action btn-toggle-chips" onclick="toggleChips()" style="width:100%; margin-top:14px;">
            🔍 <span id="lblToggleChips">Ver Detalle de Reactivos</span>
        </button>

        <div class="chips-container" id="chipsContainer">
            <div style="font-size:12px; color:var(--muted); margin-bottom:10px; display:flex; align-items:center; gap:6px;">
                💡 <span>Toca cualquier reactivo para <b>corregir la respuesta</b> y recalcular el puntaje de inmediato.</span>
            </div>
            <div id="chipsContent"></div>
        </div>

        <!-- Botones de Acción -->
        <div class="btn-group" style="grid-template-columns: 1fr; margin-top: 14px;">
            <button type="button" class="btn-action" id="btnVerHojaAnotada" onclick="abrirVisorHoja()" style="background: #2563eb; font-size: 15px; padding: 14px; font-weight: 700;">
                🎨 Ver Hoja con Círculos Analizados
            </button>
        </div>
        <div class="btn-group" style="margin-top: 8px;">
            <button type="button" class="btn-action" onclick="window.print()">🖨️ Imprimir / PDF</button>
            <button type="button" class="btn-action" onclick="resetForm()" style="background:var(--primary);">🔄 Calificar Otro</button>
        </div>
    </div>

    <!-- Modal Visualizador de Hoja con Círculos Analizados -->
    <div class="modal-overlay" id="modalVisorHoja" onclick="cerrarVisorHoja(event)">
        <div class="modal-content" style="max-width: 900px; max-height: 92vh; display: flex; flex-direction: column; padding: 16px;" onclick="event.stopPropagation()">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; border-bottom:1px solid #334155; padding-bottom:8px;">
                <h3 style="margin:0; font-size:16px; color:#fff;">🎨 Círculos Detectados en la Hoja</h3>
                <button type="button" onclick="cerrarVisorHoja()" style="background:none; border:none; color:var(--muted); font-size:26px; cursor:pointer; line-height:1; padding:0 6px;">&times;</button>
            </div>

            <!-- Selector de Modo de Visualización -->
            <div style="display:flex; gap:8px; margin-bottom:8px;">
                <button type="button" id="btnVisorEscaneo" class="btn-action active" onclick="cambiarVistaHoja('escaneo')" style="flex:1; padding:8px 10px; font-size:12px; font-weight:600;">
                    🔴 Círculos de Escaneo (Rojo=Contestada, Verde=Vacía)
                </button>
                <button type="button" id="btnVisorEval" class="btn-action" onclick="cambiarVistaHoja('evaluacion')" style="flex:1; padding:8px 10px; font-size:12px; font-weight:600;">
                    🎯 Círculos de Evaluación (Aciertos/Errores)
                </button>
            </div>

            <!-- Leyendas según el modo -->
            <div id="legendEscaneo" style="font-size:11px; color:#94a3b8; margin-bottom:8px; display:flex; gap:12px; flex-wrap:wrap; background:#1e293b; padding:6px 10px; border-radius:6px;">
                <span style="color:#fca5a5;">🔴 <b>Rojo:</b> Burbuja contestada detectada</span>
                <span style="color:#6ee7b7;">🟢 <b>Verde:</b> Burbuja vacía (no contestada)</span>
            </div>
            <div id="legendEval" style="font-size:11px; color:#94a3b8; margin-bottom:8px; display:none; gap:12px; flex-wrap:wrap; background:#1e293b; padding:6px 10px; border-radius:6px;">
                <span style="color:#6ee7b7;">🟢 <b>Verde:</b> Acierto</span>
                <span style="color:#fca5a5;">🔴 <b>Rojo:</b> Error detectado</span>
                <span style="color:#fde047;">🟡 <b>Amarillo:</b> Omitida</span>
            </div>
            
            <div style="flex:1; overflow:auto; text-align:center; background:#0b1120; border-radius:10px; padding:6px; border:1px solid #334155; max-height: calc(92vh - 120px);">
                <img id="imgHojaAnotada" src="" alt="Hoja Analizada" style="max-width: 100%; height: auto; border-radius: 6px; box-shadow: 0 4px 15px rgba(0,0,0,0.5);">
            </div>

            <div style="margin-top:10px; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:12px; color:var(--muted);">💡 Puedes hacer zoom o deslizar con los dedos en el celular.</span>
                <button type="button" class="btn-action" onclick="cerrarVisorHoja()" style="padding:8px 16px; font-size:13px;">Cerrar</button>
            </div>
        </div>
    </div>

    <!-- Modal Flotante de Corrección Manual -->
    <div class="modal-overlay" id="modalCorreccion" onclick="cerrarModalCorreccion(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                <h3 id="modalTitulo" style="margin:0; font-size:17px; color:#fff;">✏️ Corregir Reactivo</h3>
                <button type="button" onclick="cerrarModalCorreccion()" style="background:none; border:none; color:var(--muted); font-size:24px; cursor:pointer; line-height:1; padding:0 6px;">&times;</button>
            </div>
            
            <div class="modal-info-box">
                <div><b>Clave Oficial:</b> <span id="modalClave" class="badge-clave">A</span></div>
                <div><b>Detectada:</b> <span id="modalMarcada" class="badge-marcada">B</span></div>
            </div>

            <p style="font-size:13px; color:var(--muted); margin:10px 0 8px 0;">Selecciona la respuesta que marcó el alumno:</p>
            <div class="modal-options-grid">
                <button type="button" class="btn-opt" id="btnOptA" onclick="aplicarCorreccion('A')">A</button>
                <button type="button" class="btn-opt" id="btnOptB" onclick="aplicarCorreccion('B')">B</button>
                <button type="button" class="btn-opt" id="btnOptC" onclick="aplicarCorreccion('C')">C</button>
                <button type="button" class="btn-opt" id="btnOptD" onclick="aplicarCorreccion('D')">D</button>
                <button type="button" class="btn-opt-omit" onclick="aplicarCorreccion('')">⚪ Dejar Omitida / En Blanco</button>
            </div>
        </div>
    </div>

    <!-- Notificación Toast -->
    <div id="toastNotif" class="toast-notif"></div>

</div>

<script>
let archivoSeleccionado = null;
let archivoBaseSeleccionado = null;
let currentData = null;
let respuestasAlumnoActuales = {};
let respuestasEditadas = {};
let reactivoEditando = null;

function switchTab(tab) {
    document.getElementById('tabAlumnos').classList.toggle('active', tab === 'alumno');
    document.getElementById('tabProfesor').classList.toggle('active', tab === 'profesor');
    document.getElementById('sectionAlumno').style.display = tab === 'alumno' ? 'block' : 'none';
    document.getElementById('sectionProfesor').style.display = tab === 'profesor' ? 'block' : 'none';
    document.getElementById('reportCard').style.display = 'none';
}

function handleFileSelected(input) {
    if (input.files && input.files[0]) {
        archivoSeleccionado = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImg').src = e.target.result;
            document.getElementById('uploadOptionsAlumno').style.display = 'none';
            document.getElementById('previewContainer').style.display = 'block';
            document.getElementById('btnCalificar').disabled = false;
        };
        reader.readAsDataURL(archivoSeleccionado);
    }
}

function cambiarFotoAlumno() {
    document.getElementById('uploadOptionsAlumno').style.display = 'grid';
    document.getElementById('previewContainer').style.display = 'none';
    document.getElementById('btnCalificar').disabled = true;
    document.getElementById('fotoInputCamara').value = '';
    document.getElementById('fotoInputGaleria').value = '';
    archivoSeleccionado = null;
}

function handleBaseFileSelected(input) {
    if (input.files && input.files[0]) {
        archivoBaseSeleccionado = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewBaseImg').src = e.target.result;
            document.getElementById('uploadOptionsBase').style.display = 'none';
            document.getElementById('previewBaseContainer').style.display = 'block';
            document.getElementById('btnGuardarBase').disabled = false;
        };
        reader.readAsDataURL(archivoBaseSeleccionado);
    }
}

function cambiarFotoBase() {
    document.getElementById('uploadOptionsBase').style.display = 'grid';
    document.getElementById('previewBaseContainer').style.display = 'none';
    document.getElementById('btnGuardarBase').disabled = true;
    document.getElementById('fotoBaseCamara').value = '';
    document.getElementById('fotoBaseGaleria').value = '';
    archivoBaseSeleccionado = null;
}

async function enviarCalificacion(accion) {
    const file = (accion === 'guardar_base') ? archivoBaseSeleccionado : archivoSeleccionado;
    if (!file) return;

    // Mostrar pantalla de carga
    document.getElementById('formCard').style.display = 'none';
    document.getElementById('loadingCard').style.display = 'block';
    document.getElementById('reportCard').style.display = 'none';

    const formData = new FormData();
    formData.append('foto', file);
    formData.append('accion', accion);
    formData.append('nombre_alumno', document.getElementById('nombreAlumno').value);

    try {
        const response = await fetch('api_calificar.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        document.getElementById('loadingCard').style.display = 'none';

        if (!data.exito) {
            alert('⚠️ ' + (data.error || 'Ocurrió un error al procesar la imagen.'));
            document.getElementById('formCard').style.display = 'block';
            return;
        }

        if (accion === 'guardar_base') {
            alert('✅ ' + data.mensaje + '\n\nLectura: ' + data.conteo.lectura + ' | Redacción: ' + data.conteo.redaccion + ' | Matemáticas: ' + data.conteo.matematicas);
            location.reload();
            return;
        }

        // Renderizar Reporte
        respuestasEditadas = {};
        mostrarReporte(data);

    } catch (err) {
        document.getElementById('loadingCard').style.display = 'none';
        document.getElementById('formCard').style.display = 'block';
        alert('❌ Error de conexión al servidor: ' + err.message);
    }
}

let imagenAnotadaActual = null;
let imagenAnotadaEscaneo = null;
let imagenAnotadaEval = null;

function mostrarReporte(d) {
    currentData = d;
    if (d.respuestas_detectadas) {
        respuestasAlumnoActuales = JSON.parse(JSON.stringify(d.respuestas_detectadas));
    }

    imagenAnotadaEscaneo = d.imagen_anotada_escaneo || d.imagen_anotada;
    imagenAnotadaEval = d.imagen_anotada_evaluacion || d.imagen_anotada;
    imagenAnotadaActual = imagenAnotadaEscaneo;

    // Manejar imagen analizada con círculos pintados
    if (imagenAnotadaActual) {
        document.getElementById('imgHojaAnotada').src = imagenAnotadaActual;
        document.getElementById('btnVerHojaAnotada').style.display = 'flex';
        cambiarVistaHoja('escaneo');
    } else {
        document.getElementById('btnVerHojaAnotada').style.display = 'none';
    }

    // Badge si fue alineada con OpenCV
    const badgeAlign = document.getElementById('repAlineacionBadge');
    if (badgeAlign) {
        badgeAlign.style.display = d.fue_alineada ? 'inline-flex' : 'none';
    }

    document.getElementById('repAlumnoNombre').textContent = d.alumno_nombre;
    document.getElementById('repFecha').textContent = d.fecha + (d.duracion ? ' (' + d.duracion + 's)' : '');
    document.getElementById('repPuntaje100').innerHTML = d.puntaje_escala_100 + ' <span>/ 100</span>';
    document.getElementById('repPaaTotal').textContent = Number(d.paa_total_escala).toLocaleString() + ' / 1,600 pts';
    document.getElementById('repPaaVerbal').textContent = d.paa_verbal_escala + ' pts';
    document.getElementById('repPaaMate').textContent = d.paa_mate_escala + ' pts';
    document.getElementById('repAciertosVerbal').textContent = d.aciertos_verbal + ' aciertos';
    document.getElementById('repAciertosMate').textContent = d.aciertos_mate + ' aciertos';

    // Secciones
    let secHtml = '';
    let chipsHtml = '';
    const iconos = { lectura: '📖', redaccion: '✍️', matematicas: '📐', ingles: '🇬🇧' };

    for (const [k, s] of Object.entries(d.secciones)) {
        const icon = iconos[k] || '📝';
        secHtml += `
            <div class="sec-row">
                <div class="sec-name"><span>${icon}</span> ${s.nombre}</div>
                <div class="sec-stats">
                    ${s.aciertos} / ${s.total_clave} aciertos
                    <div class="sec-pct">${s.porcentaje}% (Errores: ${s.errores}, Omitidas: ${s.omitidas})</div>
                </div>
            </div>
        `;

        // Chips interactivos con onclick
        chipsHtml += `<div style="font-weight:700; font-size:13px; margin:8px 0 6px 0; color:#cbd5e1;">${icon} ${s.nombre}</div><div class="chips-grid">`;
        for (const item of s.detalles) {
            let cls = 'chip-ok';
            let txt = `${item.num}:${item.marcada}`;
            if (item.estado === 'error') {
                cls = 'chip-err';
                txt = `${item.num}:${item.marcada} (${item.correcta})`;
            } else if (item.estado === 'omitida') {
                cls = 'chip-omit';
                txt = `${item.num}:— (${item.correcta})`;
            }
            const editKey = `${k}_${item.num}`;
            if (respuestasEditadas[editKey]) {
                cls += ' chip-editado';
            }
            chipsHtml += `<div class="chip ${cls}" onclick="abrirModalCorreccion('${k}', '${s.nombre}', '${item.num}', '${item.marcada}', '${item.correcta}')" title="Toca para corregir">${txt}</div>`;
        }
        chipsHtml += `</div>`;
    }

    document.getElementById('seccionesContainer').innerHTML = secHtml;
    document.getElementById('chipsContent').innerHTML = chipsHtml;
    document.getElementById('reportCard').style.display = 'block';
}

function cambiarVistaHoja(modo) {
    const btnEsc = document.getElementById('btnVisorEscaneo');
    const btnEval = document.getElementById('btnVisorEval');
    const legEsc = document.getElementById('legendEscaneo');
    const legEval = document.getElementById('legendEval');
    const imgHoja = document.getElementById('imgHojaAnotada');

    if (modo === 'escaneo') {
        if (btnEsc) btnEsc.classList.add('active');
        if (btnEval) btnEval.classList.remove('active');
        if (legEsc) legEsc.style.display = 'flex';
        if (legEval) legEval.style.display = 'none';
        if (imgHoja && imagenAnotadaEscaneo) {
            imgHoja.src = imagenAnotadaEscaneo;
        }
    } else {
        if (btnEsc) btnEsc.classList.remove('active');
        if (btnEval) btnEval.classList.add('active');
        if (legEsc) legEsc.style.display = 'none';
        if (legEval) legEval.style.display = 'flex';
        if (imgHoja && imagenAnotadaEval) {
            imgHoja.src = imagenAnotadaEval;
        }
    }
}

function abrirVisorHoja() {
    if (!imagenAnotadaActual) {
        alert('La imagen con los círculos analizados no está disponible.');
        return;
    }
    document.getElementById('modalVisorHoja').style.display = 'flex';
}

function cerrarVisorHoja(e) {
    document.getElementById('modalVisorHoja').style.display = 'none';
}

function abrirModalCorreccion(seccionClave, seccionNombre, num, marcada, correcta) {
    reactivoEditando = { seccionClave, seccionNombre, num, marcada, correcta };
    document.getElementById('modalTitulo').textContent = `✏️ Reactivo #${num} (${seccionNombre})`;
    document.getElementById('modalClave').textContent = correcta || '?';
    document.getElementById('modalMarcada').textContent = (marcada && marcada !== '—') ? marcada : 'Omitida';

    ['A', 'B', 'C', 'D'].forEach(opt => {
        const btn = document.getElementById('btnOpt' + opt);
        if (btn) btn.classList.toggle('current', opt === marcada);
    });

    document.getElementById('modalCorreccion').style.display = 'flex';
}

function cerrarModalCorreccion(e) {
    document.getElementById('modalCorreccion').style.display = 'none';
    reactivoEditando = null;
}

async function aplicarCorreccion(nuevaOpcion) {
    if (!reactivoEditando) return;
    const { seccionClave, num } = reactivoEditando;

    if (!respuestasAlumnoActuales[seccionClave]) {
        respuestasAlumnoActuales[seccionClave] = {};
    }

    if (nuevaOpcion) {
        respuestasAlumnoActuales[seccionClave][num] = nuevaOpcion;
    } else {
        delete respuestasAlumnoActuales[seccionClave][num];
    }

    respuestasEditadas[`${seccionClave}_${num}`] = true;
    cerrarModalCorreccion();

    try {
        const formData = new FormData();
        formData.append('accion', 'recalcular');
        formData.append('respuestas', JSON.stringify(respuestasAlumnoActuales));
        formData.append('nombre_alumno', document.getElementById('nombreAlumno').value);

        const resp = await fetch('api_calificar.php', {
            method: 'POST',
            body: formData
        });
        const nuevoResultado = await resp.json();
        if (nuevoResultado.exito) {
            mostrarReporte(nuevoResultado);
            mostrarToast(`✏️ Reactivo #${num} corregido a '${nuevaOpcion || 'Omitida'}'. Puntaje: ${nuevoResultado.puntaje_escala_100} / 100`);
        } else {
            alert('Error: ' + (nuevoResultado.error || 'No se pudo recalcular'));
        }
    } catch (err) {
        alert('Error al recalcular: ' + err.message);
    }
}

function mostrarToast(msg) {
    const t = document.getElementById('toastNotif');
    t.textContent = msg;
    t.classList.add('show');
    setTimeout(() => {
        t.classList.remove('show');
    }, 3500);
}

function toggleChips() {
    const el = document.getElementById('chipsContainer');
    const isVisible = el.style.display === 'block';
    el.style.display = isVisible ? 'none' : 'block';
    document.getElementById('lblToggleChips').textContent = isVisible ? 'Ver Detalle de Reactivos' : 'Ocultar Detalle de Reactivos';
}

function resetForm() {
    archivoSeleccionado = null;
    respuestasAlumnoActuales = {};
    respuestasEditadas = {};
    document.getElementById('previewImg').src = '';
    const inCam = document.getElementById('fotoInputCamara');
    if (inCam) inCam.value = '';
    const inGal = document.getElementById('fotoInputGaleria');
    if (inGal) inGal.value = '';
    const optBox = document.getElementById('uploadOptionsAlumno');
    if (optBox) optBox.style.display = 'grid';
    document.getElementById('previewContainer').style.display = 'none';
    document.getElementById('btnCalificar').disabled = true;
    document.getElementById('formCard').style.display = 'block';
    document.getElementById('reportCard').style.display = 'none';
}
</script>
</body>
</html>

