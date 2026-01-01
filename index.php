<?php
// Point d'entrée unique
define('ROOT_PATH', __DIR__);

// Activer les erreurs en développement
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure le routeur
require 'app/router.php';