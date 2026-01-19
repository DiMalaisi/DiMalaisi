<?php
// ==============================================
// CONFIGURACIÓN MEJORADA - MANTENIENDO COMPATIBILIDAD
// ==============================================

// Solo mostrar errores en modo desarrollo (mejorado)
if (isset($_GET['debug']) && $_GET['debug'] === 'true') {
error_reporting(E_ALL);
ini_set('display_errors', 1);
} else {
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', 0);
}

// Registrar errores en archivo log (mejorado)
ini_set('log_errors', 1);
ini_set('error_log', **DIR** . '/php_errors.log');

// ==============================================
// CONSTANTES DE CONFIGURACIÓN (optimizadas)
// ==============================================

define('TO_EMAIL', 'no-reply@limondigital.com.ar');
define('SITE_NAME', 'Limón Digital');
define('SITE_URL', '[https://limondigital.com.ar](https://limondigital.com.ar/)');

// Configuración de seguridad (ajustada)
define('MAX_REQUESTS_PER_HOUR', 30); // Aumentado para formulario real
define('MIN_TIME_BETWEEN_REQUESTS', 10); // Reducido para mejor UX
define('HONEYPOT_FIELD', 'user_verification');

// ==============================================
// FUNCIONES AUXILIARES MEJORADAS (más robustas)
// ==============================================

/**

- Sanitiza texto - VERSIÓN MEJORADA
*/
function sanitizar_texto($texto, $max_length = 100) {
if (empty($texto)) return '';
    
    // Recortar espacios
    $texto = trim($texto);
    
    // Limitar longitud (mejor manejo de multibyte)
    if (mb_strlen($texto, 'UTF-8') > $max_length) {
    $texto = mb_substr($texto, 0, $max_length, 'UTF-8');
    }
    
    // Eliminar caracteres peligrosos pero mantener tildes y eñes
    $texto = preg_replace('/[<>"\']/', '', $texto);
    
    // Convertir caracteres especiales (más seguro)
    $texto = htmlspecialchars($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Remover múltiples espacios
    $texto = preg_replace('/\s+/', ' ', $texto);
    
    return $texto;
    }
    

/**

- Valida y sanitiza email - VERSIÓN MEJORADA
*/
function validar_email($email) {
if (empty($email)) return false;
    
    // Sanitizar primero (más completo)
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    
    // Validar formato
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return false;
    }
    
    // Validar que no sea email temporal (lista reducida para Argentina)
    $dominios_temporales = [
    '[tempmail.com](http://tempmail.com/)', '[10minutemail.com](http://10minutemail.com/)', '[mailinator.com](http://mailinator.com/)',
    '[yopmail.com](http://yopmail.com/)', '[trashmail.com](http://trashmail.com/)', '[guerrillamail.com](http://guerrillamail.com/)'
    ];
    
    $partes = explode('@', $email);
    if (count($partes) !== 2) return false;
    
    $dominio = strtolower($partes[1]);
    
    foreach ($dominios_temporales as $temp) {
    if (strpos($dominio, $temp) !== false) {
    return false;
    }
    }
    
    return $email;
    }
    

/**

- Valida número de WhatsApp argentino - VERSIÓN MEJORADA
*/
function validar_whatsapp($numero) {
if (empty($numero) || $numero === 'No provisto') {
return ''; // Retorna vacío en lugar de false para compatibilidad
}
    
    // Eliminar todo excepto números y signo +
    $numero = preg_replace('/[^0-9+]/', '', $numero);
    
    // Si está vacío después de limpiar
    if (empty($numero)) return '';
    
    // Patrones válidos para Argentina (más flexibles):
    // +5491122334455, 5491122334455, 91122334455, 1122334455
    if (preg_match('/^(\+?54)?9?\d{8,11}$/', $numero)) {
    // Limpiar prefijo
    $numero = preg_replace('/^\+?54?/', '', $numero);
    
    ```
     // Asegurar que empiece con 9 (móvil Argentina)
     if (!preg_match('/^9/', $numero)) {
         $numero = '9' . $numero;
     }
    
     // Asegurar 13 dígitos totales (+549 + 10 números)
     if (strlen($numero) > 10) {
         $numero = substr($numero, 0, 10);
     }
    
     return '+54' . $numero;
    
    ```
    
    }
    
    return ''; // Retorna vacío en lugar de false
    }
    

/**

- Sistema de rate limiting simple - MEJORADO
*/
function controlar_rate_limit() {
// Solo aplicar rate limiting si no hay sesión (evita bloquear tests legítimos)
if (session_status() === PHP_SESSION_NONE) {
session_start();
}
    
    // Si hay sesión activa, no aplicar rate limiting estricto
    if (isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true) {
    return;
    }
    
    $ip = $*SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $cache_file = **DIR** . '/rate*' . md5($ip) . '.tmp';
    
    // Leer datos existentes
    if (file_exists($cache_file)) {
    $data = json_decode(file_get_contents($cache_file), true);
    
    ```
     if ($data && isset($data['hora']) && $data['hora'] === date('Y-m-d-H')) {
         if ($data['contador'] >= MAX_REQUESTS_PER_HOUR) {
             // Responder con error pero manteniendo formato esperado
             echo "ERROR_SERVIDOR - Límite de envíos alcanzado. Intente más tarde.";
             exit;
         }
         $data['contador']++;
     } else {
         // Nueva hora, resetear contador
         $data = [
             'hora' => date('Y-m-d-H'),
             'contador' => 1,
             'ultimo_envio' => time()
         ];
     }
    
     // Verificar tiempo mínimo entre envíos (solo si no es primer envío)
     if (isset($data['ultimo_envio'])) {
         $tiempo_espera = time() - $data['ultimo_envio'];
         if ($tiempo_espera < MIN_TIME_BETWEEN_REQUESTS) {
             echo "ERROR_SERVIDOR - Espere unos segundos antes de otro envío.";
             exit;
         }
     }
    
     $data['ultimo_envio'] = time();
    
    ```
    
    } else {
    // Primera solicitud desde esta IP
    $data = [
    'hora' => date('Y-m-d-H'),
    'contador' => 1,
    'ultimo_envio' => time()
    ];
    }
    
    // Guardar datos (sin crear directorio)
    file_put_contents($cache_file, json_encode($data));
    
    // Limpiar archivos antiguos (más de 24 horas)
    if (rand(1, 10) === 1) { // Solo 10% de las veces para no sobrecargar
    $archivos = glob(**DIR** . '/rate_*.tmp');
    foreach ($archivos as $archivo) {
    if (filemtime($archivo) < time() - 86400) {
    @unlink($archivo);
    }
    }
    }
    }
    

/**

- Detectar bots con honeypot - COMPATIBLE CON TU HTML
*/
function detectar_bot() {
// Verificar campo honeypot (compatible con tu HTML actual)
if (isset($_POST['user_verification']) && !empty($_POST['user_verification'])) {
// Bot detectado - responder como si fuera éxito para no revelar
echo "OK_ENVIADO";
exit;
}
    
    // Verificar si es un envío muy rápido (posible bot)
    // Solo si tenemos el campo tiempo_inicio (lo agregarás después)
    if (isset($_POST['tiempo_inicio'])) {
    $tiempo_envio = time() - intval($_POST['tiempo_inicio']);
    if ($tiempo_envio < 2) { // Menos de 2 segundos
    echo "ERROR_SERVIDOR - Complete el formulario más lentamente.";
    exit;
    }
    }
    }
    

/**

- Crear cuerpo del email - MANTENIENDO FORMATO SIMILAR
*/
function crear_cuerpo_email($datos) {
$mensaje = "NUEVA POSTULACIÓN PARA: " . $datos['area'] . "\n";
$mensaje .= "==================================\n\n";
    
    $mensaje .= "📅 FECHA: " . date('d/m/Y H:i:s') . "\n";
    $mensaje .= "🌐 IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida') . "\n\n";
    
    $mensaje .= "👤 DATOS DEL CANDIDATO:\n";
    $mensaje .= "----------------------------------\n";
    $mensaje .= "• Nombre: " . $datos['nombre'] . "\n";
    $mensaje .= "• Email: " . $datos['email'] . "\n";
    $mensaje .= "• WhatsApp: " . $datos['whatsapp'] . "\n";
    $mensaje .= "• Área: " . $datos['area'] . "\n\n";
    
    $mensaje .= "🔗 ENLACES RÁPIDOS:\n";
    $mensaje .= "----------------------------------\n";
    $mensaje .= "📧 Responder: " . $datos['email'] . "\n";
    if (!empty($datos['whatsapp']) && $datos['whatsapp'] !== 'No provisto') {
    $mensaje .= "📱 WhatsApp: https://wa.me/" . str_replace('+', '', $datos['whatsapp']) . "\n";
    }
    
    $mensaje .= "\n--\n";
    $mensaje .= "📤 Enviado desde: " . SITE_URL . "\n";
    $mensaje .= "🕐 Hora: " . date('H:i:s');
    
    return $mensaje;
    }
    

/**

- Enviar email - MANTENIENDO COMPATIBILIDAD
*/
function enviar_email($destinatario, $asunto, $cuerpo, $email_remitente) {
// Headers seguros pero compatibles
$headers = "From: Formulario Web <" . TO_EMAIL . ">\r\n";
$headers .= "Reply-To: " . $email_remitente . "\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
    
    // Codificar asunto para UTF-8
    $asunto_codificado = "=?UTF-8?B?" . base64_encode($asunto) . "?=";
    
    // Intentar envío
    $enviado = @mail($destinatario, $asunto_codificado, $cuerpo, $headers);
    
    // Log simple si falla (sin crear directorios)
    if (!$enviado) {
    $log_msg = date('Y-m-d H:i:s') . " - Error enviando email a: " . $destinatario .
    " | De: " . $email_remitente . "\n";
    @file_put_contents(**DIR** . '/email_errors.log', $log_msg, FILE_APPEND);
    }
    
    return $enviado;
    }
    

// ==============================================
// PROCESAMIENTO PRINCIPAL - COMPATIBLE CON TU JS
// ==============================================

// 1. Detectar bots (silenciosamente)
detectar_bot();

// 2. Controlar rate limiting (no bloqueante)
controlar_rate_limit();

// 3. Recibir datos (manteniendo compatibilidad)
$nombre   = sanitizar_texto($_POST['Nombre'] ?? 'Sin nombre', 50);
$email    = validar_email($_POST['Email'] ?? '');
$whatsapp = validar_whatsapp($_POST['WhatsApp'] ?? 'No provisto');
$area     = sanitizar_texto($_POST['Area'] ?? 'No seleccionada', 30);

// 4. Validaciones básicas (con retrocompatibilidad)
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
die("Email no valido");
}

// Validación adicional de nombre
if (empty($nombre) || $nombre === 'Sin nombre' || strlen($nombre) < 2) {
die("Nombre no valido");
}

// Validación de área
if (empty($area) || $area === 'No seleccionada') {
die("Area no seleccionada");
}

// 5. Preparar WhatsApp para mostrar
$whatsapp_mostrar = $whatsapp;
if (empty($whatsapp) || $whatsapp === 'No provisto') {
$whatsapp_mostrar = 'No provisto';
$whatsapp = 'No provisto';
}

// 6. Crear y enviar email (manteniendo formato original)
$asunto = "Postulación: " . $area;
$mensaje = "NUEVA POSTULACIÓN PARA: $area\n";
$mensaje .= "----------------------------------\n\n";
$mensaje .= "Nombre: $nombre\n";
$mensaje .= "Email: $email\n";
$mensaje .= "WhatsApp: $whatsapp_mostrar\n";
$mensaje .= "Área de interés: $area\n";
$mensaje .= "\n--\n";
$mensaje .= "📅 Enviado el: " . date('d/m/Y H:i:s') . "\n";
$mensaje .= "🌐 Desde IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'Desconocida') . "\n";
$mensaje .= "🔗 Formulario: " . SITE_URL;

// 7. Enviar email (con método mejorado)
$exito = enviar_email(TO_EMAIL, $asunto, $mensaje, $email);

// 8. También guardar en archivo simple como backup
$backup_data = date('Y-m-d H:i:s') . " | " .
($_SERVER['REMOTE_ADDR'] ?? '') . " | " .
$nombre . " | " . $email . " | " .
$whatsapp_mostrar . " | " . $area . "\n";
@file_put_contents(**DIR** . '/postulaciones_backup.txt', $backup_data, FILE_APPEND);

// 9. Responder (MANTENIENDO EXACTAMENTE EL MISMO FORMATO QUE ESPERA TU JS)
if ($exito) {
echo "OK_ENVIADO";
} else {
$lastError = error_get_last();
$error_msg = $lastError['message'] ?? 'Error desconocido del servidor';

```
// Log del error para debugging
@file_put_contents(__DIR__ . '/envios_errors.log',
    date('Y-m-d H:i:s') . " - " . $error_msg . "\\n",
    FILE_APPEND);

echo "ERROR_SERVIDOR - " . $error_msg;

```

}

// 10. Limpiar (opcional)
unset($nombre, $email, $whatsapp, $area, $mensaje, $backup_data);
?>
