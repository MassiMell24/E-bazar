<?php
class AdminController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    /**
     * Require that the current session user is admin.
     * Returns true if ok, otherwise sends 403 and returns false.
     */
    private function requireAdmin() {
        if (empty($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
            http_response_code(403);
            echo "Accès réservé à l'administrateur";
            return false;
        }
        return true;
    }

    public function dashboard() {
        if (!$this->requireAdmin()) return;

        // Very simple overview
        $stmt = $this->pdo->query('SELECT COUNT(*) as c FROM users');
        $usersCount = $stmt->fetch()['c'];
        // Count only unsold ads so the number decreases when an item is purchased
        $stmt = $this->pdo->query('SELECT COUNT(*) as c FROM ads WHERE sold = 0');
        $adsCount = $stmt->fetch()['c'];

        require 'app/views/layout/header.php';
        require 'app/views/admin/dashboard.php';
        require 'app/views/layout/footer.php';
    }

    public function ads() {
        if (!$this->requireAdmin()) return;

        // List only unsold ads with owner email
        $stmt = $this->pdo->query('SELECT a.*, u.email as owner_email FROM ads a LEFT JOIN users u ON a.owner_id = u.id WHERE a.sold = 0 ORDER BY a.created_at DESC');
        $ads = $stmt->fetchAll();

        // CSRF token for delete actions
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

        require 'app/views/layout/header.php';
        require 'app/views/admin/ads.php';
        require 'app/views/layout/footer.php';
    }

    public function categories() {
        if (!$this->requireAdmin()) return;

        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        $categories = $catModel->allWithCounts();

        // CSRF token
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

        require 'app/views/layout/header.php';
        require 'app/views/admin/categories.php';
        require 'app/views/layout/footer.php';
    }

    public function createCategory() {
        if (!$this->requireAdmin()) return;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        $name = trim($_POST['name'] ?? '');
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Token CSRF invalide";
            return;
        }
        if ($name === '') {
            echo "Nom de catégorie requis";
            return;
        }

        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        $catModel->create($name);

        header('Location: /e-bazar/index.php?url=admin/categories');
        exit;
    }

    public function deleteCategory($id) {
        if (!$this->requireAdmin()) return;
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

        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        // Prevent deletion if any active ad references this category
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) AS c FROM ads WHERE category_id = :id AND sold = 0');
            $stmt->execute([':id' => (int)$id]);
            $c = $stmt->fetch()['c'] ?? 0;
            if ($c > 0) {
                $_SESSION['error_message'] = "⚠️ Impossible de supprimer cette catégorie ! Elle contient encore " . $c . " produit(s) actif(s).";
                header('Location: /e-bazar/index.php?url=admin/categories');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Une erreur est survenue lors de la vérification.";
            header('Location: /e-bazar/index.php?url=admin/categories');
            exit;
        }
        
        $catModel->delete($id);
        $_SESSION['success_message'] = "Catégorie supprimée avec succès.";

        header('Location: /e-bazar/index.php?url=admin/categories');
        exit;
    }

    public function deleteAd($id) {
        if (!$this->requireAdmin()) return;

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

        // Use Ad model to delete ad and its images
        require_once 'app/models/Ad.php';
        $adModel = new Ad($this->pdo);
        $adModel->deleteWithImages($id);

        header('Location: /e-bazar/index.php?url=admin/ads');
        exit;
    }

    public function users() {
        if (!$this->requireAdmin()) return;

        $stmt = $this->pdo->query('SELECT id, username, email, is_admin, created_at FROM users ORDER BY created_at DESC');
        $users = $stmt->fetchAll();

        // CSRF token for delete actions
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

        require 'app/views/layout/header.php';
        require 'app/views/admin/users.php';
        require 'app/views/layout/footer.php';
    }

    public function deleteUser($id) {
        if (!$this->requireAdmin()) return;

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

        require_once 'app/models/User.php';
        require_once 'app/models/Ad.php';
        $userModel = new User($this->pdo);
        $adModel = new Ad($this->pdo);

        $user = $userModel->findById($id);
        if (!$user) {
            echo "Utilisateur introuvable";
            return;
        }

        if ($user['is_admin']) {
            echo "Impossible de supprimer un compte administrateur";
            return;
        }

        // Delete user's ads with images/files, then delete user
        try {
            // Fetch all ads for this user
            $stmt = $this->pdo->prepare('SELECT id FROM ads WHERE owner_id = :id');
            $stmt->execute([':id' => (int)$id]);
            $ads = $stmt->fetchAll();
            foreach ($ads as $a) {
                $adModel->deleteWithImages($a['id']);
            }
            // Finally, delete user
            $userModel->delete($id);
        } catch (Exception $e) {
            echo "Erreur lors de la suppression";
            return;
        }

        header('Location: /e-bazar/index.php?url=admin/users');
        exit;
    }

    public function transactions() {
        if (!$this->requireAdmin()) return;

        // Fetch all sold ads with buyer and seller info
        $stmt = $this->pdo->query('SELECT a.*, u_seller.email as seller_email, u_buyer.email as buyer_email FROM ads a LEFT JOIN users u_seller ON a.owner_id = u_seller.id LEFT JOIN users u_buyer ON a.buyer_id = u_buyer.id WHERE a.sold = 1 ORDER BY a.id DESC');
        $transactions = $stmt->fetchAll();

        // CSRF token for delete actions
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));

        require 'app/views/layout/header.php';
        require 'app/views/admin/transactions.php';
        require 'app/views/layout/footer.php';
    }

    public function deleteTransaction($id) {
        if (!$this->requireAdmin()) return;

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

        // Delete the sold ad permanently with its images
        require_once 'app/models/Ad.php';
        $adModel = new Ad($this->pdo);
        $adModel->deleteWithImages($id);

        $_SESSION['success_message'] = "Transaction supprimée avec succès.";
        header('Location: /e-bazar/index.php?url=admin/transactions');
        exit;
    }
}
