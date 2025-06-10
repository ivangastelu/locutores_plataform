<?php
/**
 * Funciones de protección CSRF para el sistema de audios
 */

/**
 * Genera un token CSRF y lo guarda en la sesión
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Verifica que el token CSRF sea válido
 * @param string $token Token enviado en el formulario
 * @return bool True si el token es válido, False si no lo es
 */
function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Verificar que exista un token en la sesión
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Verificar que el token no haya expirado (30 minutos)
    $expireTime = $_SESSION['csrf_token_time'] + (30 * 60);
    if (time() > $expireTime) {
        // El token ha expirado, generar uno nuevo
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    // Verificar que el token coincida con el enviado
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Imprime un campo de formulario oculto con el token CSRF
 */
function csrfField() {
    $token = generateCSRFToken();
    echo '<input type="hidden" name="csrf_token" value="' . $token . '">';
}