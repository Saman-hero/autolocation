<?php
/**
 * config/lang.php
 * Internationalization helper
 */

$lang = 'fr';
$translations = [];

/**
 * Load language translations
 */
function loadLang(string $code): void {
    global $lang, $translations;
    $lang = $code;
    $file = __DIR__ . "/../lang/{$code}.php";
    if (file_exists($file)) {
        $translations = require $file;
    } else {
        $translations = require __DIR__ . "/../lang/fr.php";
        $lang = 'fr';
    }
    $_SESSION['lang'] = $lang;
}

/**
 * Translate a string
 */
function __(string $key): string {
    global $translations;
    return $translations[$key] ?? $key;
}

/**
 * Get supported languages
 */
function getLanguages(): array {
    return [
        'fr' => ['label' => 'Français', 'flag' => '🇫🇷', 'dir' => 'ltr'],
        'en' => ['label' => 'English',  'flag' => '🇬🇧', 'dir' => 'ltr'],
        'ar' => ['label' => 'العربية',   'flag' => '🇲🇦', 'dir' => 'rtl'],
    ];
}

// Auto-load language from session or browser
if (!empty($_SESSION['lang'])) {
    loadLang($_SESSION['lang']);
} else {
    // Auto-detect from browser
    $browserLang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'fr', 0, 2);
    loadLang(in_array($browserLang, ['fr', 'en', 'ar']) ? $browserLang : 'fr');
}

// Handle language switch
if (isset($_GET['lang']) && in_array($_GET['lang'], ['fr', 'en', 'ar'])) {
    loadLang($_GET['lang']);
    $redirect = str_replace(['?lang=fr', '?lang=en', '?lang=ar', '&lang=fr', '&lang=en', '&lang=ar'], '', $_SERVER['REQUEST_URI']);
    header("Location: $redirect");
    exit;
}
