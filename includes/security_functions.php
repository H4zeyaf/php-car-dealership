<?php
/**
 * Comprehensive Security Functions for MHB Automobiles
 * Includes CSRF, XSS, input validation, rate limiting, and file upload security
 */

// ==================== CSRF PROTECTION ====================

/**
 * Generate a CSRF token and store it in the session
 * @return string The generated CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate a CSRF token against the session token
 * @param string $token The token to validate
 * @return bool True if valid, false otherwise
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Generate a hidden CSRF input field for forms
 * @return string HTML input field
 */
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

// ==================== INPUT SANITIZATION ====================

/**
 * Sanitize user input to prevent XSS attacks
 * @param string $data The input data
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Escape output for safe HTML display
 * @param string $data The data to escape
 * @return string Escaped data
 */
function escapeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize numeric input
 * @param mixed $value The value to sanitize
 * @return int|float Sanitized numeric value
 */
function sanitizeNumeric($value, $type = 'int') {
    if ($type === 'int') {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    } else {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
}

// ==================== INPUT VALIDATION ====================

/**
 * Validate email address
 * @param string $email The email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (French format: 10 digits)
 * @param string $phone The phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return preg_match('/^[0-9]{10}$/', $phone);
}

/**
 * Validate password strength
 * @param string $password The password to validate
 * @param int $minLength Minimum password length
 * @return array ['valid' => bool, 'errors' => array]
 */
function validatePassword($password, $minLength = 8) {
    $errors = [];
    
    if (strlen($password) < $minLength) {
        $errors[] = "Le mot de passe doit contenir au moins $minLength caractères";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre majuscule";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une lettre minuscule";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Validate date format
 * @param string $date The date to validate
 * @param string $format Expected date format
 * @return bool True if valid, false otherwise
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate numeric range
 * @param mixed $value The value to validate
 * @param int|float $min Minimum value
 * @param int|float $max Maximum value
 * @return bool True if valid, false otherwise
 */
function validateRange($value, $min, $max) {
    return is_numeric($value) && $value >= $min && $value <= $max;
}

// ==================== FILE UPLOAD SECURITY ====================

/**
 * Validate image file upload
 * @param array $file The $_FILES array element
 * @param int $maxSize Maximum file size in bytes (default 5MB)
 * @return array ['valid' => bool, 'error' => string]
 */
function validateImageUpload($file, $maxSize = 5242880) {
    // Check if file was uploaded
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'Aucun fichier téléchargé'];
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'Erreur lors du téléchargement'];
    }
    
    // Validate file size
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'Fichier trop volumineux (max 5MB)'];
    }
    
    // Validate MIME type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return ['valid' => false, 'error' => 'Type de fichier non autorisé'];
    }
    
    // Validate image dimensions (optional)
    $imageInfo = getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['valid' => false, 'error' => 'Fichier image invalide'];
    }
    
    return ['valid' => true, 'error' => ''];
}

/**
 * Generate a safe filename
 * @param string $originalName The original filename
 * @return string Safe filename
 */
function generateSafeFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    $safeName = bin2hex(random_bytes(16));
    return $safeName . '.' . strtolower($extension);
}

// ==================== RATE LIMITING ====================

/**
 * Check and enforce rate limiting
 * @param string $identifier Unique identifier (e.g., IP address, user ID)
 * @param int $maxAttempts Maximum allowed attempts
 * @param int $timeWindow Time window in seconds
 * @return array ['allowed' => bool, 'remaining' => int, 'reset_time' => int]
 */
function checkRateLimit($identifier, $maxAttempts = 5, $timeWindow = 300) {
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $now = time();
    $key = 'rl_' . $identifier;
    
    // Initialize or reset if time window expired
    if (!isset($_SESSION['rate_limit'][$key]) || 
        ($now - $_SESSION['rate_limit'][$key]['time']) > $timeWindow) {
        $_SESSION['rate_limit'][$key] = [
            'count' => 1,
            'time' => $now
        ];
        return [
            'allowed' => true,
            'remaining' => $maxAttempts - 1,
            'reset_time' => $now + $timeWindow
        ];
    }
    
    $data = $_SESSION['rate_limit'][$key];
    
    // Check if limit exceeded
    if ($data['count'] >= $maxAttempts) {
        $resetTime = $data['time'] + $timeWindow;
        return [
            'allowed' => false,
            'remaining' => 0,
            'reset_time' => $resetTime,
            'wait_seconds' => $resetTime - $now
        ];
    }
    
    // Increment counter
    $_SESSION['rate_limit'][$key]['count']++;
    
    return [
        'allowed' => true,
        'remaining' => $maxAttempts - $_SESSION['rate_limit'][$key]['count'],
        'reset_time' => $data['time'] + $timeWindow
    ];
}

/**
 * Get client IP address (considering proxies)
 * @return string IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

// ==================== SECURE REDIRECTS ====================

/**
 * Perform a safe redirect
 * @param string $url The URL to redirect to
 * @param array $allowedHosts List of allowed hosts (optional)
 */
function safeRedirect($url, $allowedHosts = []) {
    // If no allowed hosts specified, only allow same-origin redirects
    if (empty($allowedHosts)) {
        $allowedHosts = [$_SERVER['HTTP_HOST']];
    }
    
    $parsedUrl = parse_url($url);
    
    // Allow relative URLs
    if (!isset($parsedUrl['host'])) {
        header("Location: " . $url);
        exit();
    }
    
    // Check if host is allowed
    if (in_array($parsedUrl['host'], $allowedHosts)) {
        header("Location: " . $url);
        exit();
    }
    
    // Redirect to home if URL is not safe
    header("Location: index.php");
    exit();
}

// ==================== ERROR LOGGING ====================

/**
 * Log security events
 * @param string $event Event description
 * @param array $context Additional context
 */
function logSecurityEvent($event, $context = []) {
    $logFile = __DIR__ . '/../logs/security.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = getClientIP();
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $userId = $_SESSION['user_id'] ?? 'Guest';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'event' => $event,
        'ip' => $ip,
        'user_id' => $userId,
        'user_agent' => $userAgent,
        'context' => $context
    ];
    
    $logLine = json_encode($logEntry) . PHP_EOL;
    file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
}

/**
 * Sanitize error messages for user display
 * @param string $error The error message
 * @return string Sanitized error message
 */
function sanitizeErrorMessage($error) {
    // Remove sensitive information from error messages
    $safeError = preg_replace('/\b(?:password|token|key|secret)\b/i', '[REDACTED]', $error);
    return htmlspecialchars($safeError, ENT_QUOTES, 'UTF-8');
}

// ==================== SESSION SECURITY ====================

/**
 * Initialize secure session settings
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Secure session configuration
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS'])); // Enable if using HTTPS
        ini_set('session.cookie_samesite', 'Strict');
        
        session_start();
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
}

/**
 * Destroy session securely
 */
function destroySecureSession() {
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

?>
