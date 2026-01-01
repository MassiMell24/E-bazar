<?php
require_once 'app/models/User.php';

class AuthController {
    private $pdo;
    private $userModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->userModel = new User($pdo);
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $csrf = $_POST['csrf_token'] ?? '';

            if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                $error = 'Token CSRF invalide';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }

            // Basic validations
            // Username optional: if provided, must match pattern; if empty, will be set to user<ID>
            if ($username !== '' && !preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
                $error = 'Nom d\'utilisateur invalide (>=3, lettres/chiffres/_ )';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                $error = 'Email invalide ou mot de passe trop court (>=6)';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }

            if ($password !== $passwordConfirm) {
                $error = 'La confirmation du mot de passe ne correspond pas';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }

            if ($this->userModel->findByEmail($email)) {
                $error = 'Email déjà utilisé';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }

            if ($username !== '' && $this->userModel->findByUsername($username)) {
                $error = 'Nom d\'utilisateur déjà utilisé';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }

            $newId = $this->userModel->create($email, $password, false, $username === '' ? null : $username);
            if (!$newId) {
                $error = 'Inscription impossible (email ou nom déjà utilisé)';
                require 'app/views/layout/header.php';
                require 'app/views/auth/register.php';
                require 'app/views/layout/footer.php';
                return;
            }
            $_SESSION['success_message'] = '🎉 Inscription réussie ! Vous pouvez maintenant vous connecter.';
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        // GET
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        require 'app/views/layout/header.php';
        require 'app/views/auth/register.php';
        require 'app/views/layout/footer.php';
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $csrf = $_POST['csrf_token'] ?? '';

            if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                $error = 'Token CSRF invalide';
                require 'app/views/layout/header.php';
                require 'app/views/auth/login.php';
                require 'app/views/layout/footer.php';
                return;
            }

            $user = $this->userModel->findByEmail($email);
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $error = 'Identifiants invalides';
                require 'app/views/layout/header.php';
                require 'app/views/auth/login.php';
                require 'app/views/layout/footer.php';
                return;
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['is_admin'] = (bool)$user['is_admin'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            header('Location: /e-bazar/');
            exit;
        }

        // GET
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        require 'app/views/layout/header.php';
        require 'app/views/auth/login.php';
        require 'app/views/layout/footer.php';
    }

    public function logout() {
        session_unset();
        session_destroy();
        header('Location: /e-bazar/');
        exit;
    }
}
