<?php
// Configuration
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

// ROUTING SANS .htaccess
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// Support both pretty URLs via REQUEST_URI and legacy ?url=... used by frontend fetch
if (!empty($_GET['url'])) {
    // Legacy / compatibility: allow calls like index.php?url=api/ads
    $url = trim($_GET['url'], '/');
} else {
    // Enlever "/e-bazar/" du début
    $base_path = '/e-bazar/';
    if (strpos($request_uri, $base_path) === 0) {
        $url = substr($request_uri, strlen($base_path));
    } else {
        $url = $request_uri;
    }

    // Enlever les paramètres GET et le trailing slash
    $url = trim(strtok($url, '?'), '/');
}

// Découper
$parts = explode('/', $url);

// Déterminer controller/action
$controllerName = !empty($parts[0]) ? $parts[0] : 'ad';
$action = $parts[1] ?? 'index';
$params = array_slice($parts, 2);

// Validation
if (!preg_match('/^[a-zA-Z]+$/', $controllerName)) {
    http_response_code(400);
    die("Nom de contrôleur invalide");
}

// Charger le contrôleur
$controllerClass = ucfirst($controllerName) . 'Controller';
$controllerFile = "app/controllers/$controllerClass.php";

if (!file_exists($controllerFile)) {
    http_response_code(404);
    die("Contrôleur non trouvé");
}

require $controllerFile;

if (!class_exists($controllerClass)) {
    http_response_code(500);
    die("Classe non définie");
}

// Exécuter
$controller = new $controllerClass();

if (!method_exists($controller, $action)) {
    http_response_code(404);
    die("Action non trouvée");
}

call_user_func_array([$controller, $action], $params);