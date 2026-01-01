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

        // List ads with owner email
        $stmt = $this->pdo->query('SELECT a.*, u.email as owner_email FROM ads a LEFT JOIN users u ON a.owner_id = u.id ORDER BY a.created_at DESC');
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
        $categories = $catModel->all();

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
        // Prevent deletion if any ad references this category
        try {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) AS c FROM ads WHERE category_id = :id');
            $stmt->execute([':id' => (int)$id]);
            $c = $stmt->fetch()['c'] ?? 0;
            if ($c > 0) {
                echo "Impossible de supprimer: des annonces utilisent cette catégorie.";
                return;
            }
        } catch (Exception $e) {}
        $catModel->delete($id);

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
}
