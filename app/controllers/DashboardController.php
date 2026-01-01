<?php
class DashboardController {
    private $pdo;
    private $adModel;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        require_once 'app/models/Ad.php';
        $this->adModel = new Ad($pdo);
    }

    public function myAds() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM ads WHERE owner_id = :uid AND sold = 0');
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $ads = $stmt->fetchAll();

        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
        $sold = $this->adModel->getUserSold($_SESSION['user_id']);
        $purchased = $this->adModel->getUserPurchased($_SESSION['user_id']);

        require 'app/views/layout/header.php';
        require 'app/views/dashboard/myAds.php';
        require 'app/views/layout/footer.php';
    }

    public function settings() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        require_once 'app/models/User.php';
        $userModel = new User($this->pdo);
        $user = $userModel->findById($_SESSION['user_id']);

        if (!$user) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
            $csrf = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                $message = 'Token CSRF invalide';
            } else {
                $username = $_POST['username'] ?? '';
                $email = $_POST['email'] ?? '';
                $password = $_POST['password'] ?? '';
                $passwordConfirm = $_POST['password_confirm'] ?? '';

                if (!$username) {
                    $message = 'Le nom d\'utilisateur est requis.';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $message = 'Email invalide.';
                } elseif ($password && $password !== $passwordConfirm) {
                    $message = 'Les mots de passe ne correspondent pas.';
                } else {
                    try {
                        $updateUsername = ($username !== $user['username']) ? $username : null;
                        $updateEmail = ($email !== $user['email']) ? $email : null;
                        $updatePassword = $password ? $password : null;

                        $result = $userModel->update($_SESSION['user_id'], $updateEmail, $updateUsername, $updatePassword);
                        
                        if ($result === false) {
                            $message = 'Ce nom d\'utilisateur est déjà utilisé.';
                        } elseif ($result === true) {
                            $message = 'Vos informations ont été mises à jour avec succès.';
                            $user = $userModel->findById($_SESSION['user_id']);
                        } else {
                            $message = 'Erreur lors de la mise à jour.';
                        }
                    } catch (InvalidArgumentException $e) {
                        $message = 'Nom d\'utilisateur invalide. Minimum 3 caractères alphanumériques ou tiret bas.';
                    }
                }
            }
        }

        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }

        require 'app/views/layout/header.php';
        require 'app/views/dashboard/settings.php';
        require 'app/views/layout/footer.php';
    }

    public function deleteAccount() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }

        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Token CSRF invalide";
            return;
        }

        require_once 'app/models/User.php';
        $userModel = new User($this->pdo);
        
        if ($userModel->deleteWithAds($_SESSION['user_id'])) {
            // Destroy session
            session_destroy();
            header('Location: /e-bazar/index.php?url=ad');
            exit;
        } else {
            http_response_code(500);
            echo "Erreur lors de la suppression du compte.";
        }
    }

    public function sold() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
        $sold = $this->adModel->getUserSold($_SESSION['user_id']);
        require 'app/views/layout/header.php';
        require 'app/views/dashboard/sold.php';
        require 'app/views/layout/footer.php';
    }

    public function purchased() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
        $purchased = $this->adModel->getUserPurchased($_SESSION['user_id']);
        require 'app/views/layout/header.php';
        require 'app/views/dashboard/purchased.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Buyer confirms reception of a purchased ad (marks as received).
     */
    public function confirmReceived($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Token CSRF invalide";
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad || empty($ad['buyer_id']) || (int)$ad['buyer_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        // Mark as received by buyer
        $this->adModel->confirmReception($id, $_SESSION['user_id']);
        header('Location: /e-bazar/index.php?url=dashboard/purchased');
        exit;
    }

    /**
     * Buyer removes a purchased ad from their list (but keeps it for seller).
     */
    public function deletePurchasedAd($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Token CSRF invalide";
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad || empty($ad['buyer_id']) || (int)$ad['buyer_id'] !== (int)$_SESSION['user_id']) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        // Mark as deleted for buyer (won't show in their list but seller keeps it)
        $this->adModel->deleteForBuyer($id, $_SESSION['user_id']);
        header('Location: /e-bazar/index.php?url=dashboard/purchased');
        exit;
    }

    /**
     * Seller archives a sold ad (removes from their sold list but keeps for buyer).
     */
    public function deleteSoldAd($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }
        $csrf = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Token CSRF invalide";
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad || (int)$ad['owner_id'] !== (int)$_SESSION['user_id'] || empty($ad['sold'])) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        // Archive ad for seller (won't show in their sold list but buyer keeps it)
        $this->adModel->archiveForSeller($id, $_SESSION['user_id']);
        header('Location: /e-bazar/index.php?url=dashboard/sold');
        exit;
    }
}

