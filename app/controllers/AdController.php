<?php
// require_once 'app/core/Controller.php'; // Si vous créez Controller.php
require_once 'app/models/Ad.php';

class AdController {
    private $pdo;
    private $adModel;
    
    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        $this->adModel = new Ad($pdo);
    }
    
    public function index() {
        // support filtering by category and sorting via GET params
        $category = isset($_GET['category']) ? (int)$_GET['category'] : null;
        $sort = isset($_GET['sort']) ? trim($_GET['sort']) : null;
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = 10;

        // Load categories with counts for homepage + filter
        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        $categories = $catModel->allWithCounts();

        // Pagination data
        $totalAds = $this->adModel->countAvailable($category);
        $totalPages = max(1, (int)ceil($totalAds / $perPage));
        if ($page > $totalPages) { $page = $totalPages; }

        // Fetch paginated ads
        $ads = $this->adModel->getAvailablePaged($category, $sort, $perPage, ($page - 1) * $perPage);

        // attach thumbnail if exists
        foreach ($ads as &$ad) {
            $images = $this->adModel->getImages($ad['id']);
            $ad['thumbnail'] = (!empty($images) && isset($images[0]['filename'])) ? $images[0]['filename'] : null;
        }
        unset($ad);

        // Latest 4 ads for homepage preview
        $latest = $this->adModel->getLatest(4);
        foreach ($latest as &$l) {
            $imgs = $this->adModel->getImages($l['id']);
            $l['thumbnail'] = (!empty($imgs) && isset($imgs[0]['filename'])) ? $imgs[0]['filename'] : null;
        }
        unset($l);

        // Expose to view
        $selectedCategory = $category;
        $currentPage = $page;

        // Rendu avec header/footer
        require 'app/views/layout/header.php';
        require 'app/views/ads/list.php';
        require 'app/views/layout/footer.php';
    }

    /**
     * Buy flow: GET shows confirmation, POST performs purchase (marks sold)
     */
    public function buy($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        // Admins are not allowed to buy
        if (!empty($_SESSION['is_admin'])) {
            http_response_code(403);
            echo "Les administrateurs ne peuvent pas acheter des annonces.";
            return;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            http_response_code(404);
            echo "Annonce non trouvée";
            return;
        }

        if (!empty($ad['sold'])) {
            echo "Cette annonce a déjà été vendue.";
            return;
        }

        // Don't allow owner to buy their own ad
        if ($ad['owner_id'] == $_SESSION['user_id']) {
            echo "Vous ne pouvez pas acheter votre propre annonce.";
            return;
        }

        // Delivery modes accepted by seller
        $acceptedModes = array_filter(array_map('trim', explode(',', $ad['delivery_modes'] ?? '')));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                echo "Jeton CSRF invalide";
                return;
            }

            $chosenMode = trim($_POST['delivery_mode'] ?? '');
            if (empty($acceptedModes) || !in_array($chosenMode, $acceptedModes, true)) {
                $error = "Veuillez choisir un mode de livraison parmi ceux proposés.";
                if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
                $images = $this->adModel->getImages($ad['id']);
                require 'app/views/layout/header.php';
                require 'app/views/ads/buy.php';
                require 'app/views/layout/footer.php';
                return;
            }

            $ok = $this->adModel->markAsSold($ad['id'], $_SESSION['user_id'], $chosenMode);
            if ($ok) {
                // redirect to purchased list
                header('Location: /e-bazar/index.php?url=dashboard/purchased');
                exit;
            } else {
                echo "Impossible d'effectuer l'achat. Réessayez plus tard.";
                return;
            }
        }

        // GET: render confirmation
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
        $images = $this->adModel->getImages($ad['id']);
        require 'app/views/layout/header.php';
        require 'app/views/ads/buy.php';
        require 'app/views/layout/footer.php';
    }
    
    public function show($id) {
        $ad = $this->adModel->find($id);
        if (!$ad) {
            http_response_code(404);
            echo "Annonce non trouvée";
            return;
        }
        // fetch images for this ad
        $images = $this->adModel->getImages($ad['id']);

        // ensure we have a CSRF token for actions on the show page (delete)
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }

        require 'app/views/layout/header.php';
        require 'app/views/ads/show.php';
        require 'app/views/layout/footer.php';
    }

    public function edit($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            http_response_code(404);
            echo "Annonce non trouvée";
            return;
        }

        // Only owner may edit (admin cannot modify someone else's ad)
        $isOwner = ($_SESSION['user_id'] == $ad['owner_id']);
        if (!$isOwner) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        $images = $this->adModel->getImages($ad['id']);

        // categories for the form from DB
        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        $categories = $catModel->all();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }

        require 'app/views/layout/header.php';
        require 'app/views/ads/edit.php';
        require 'app/views/layout/footer.php';
    }

    public function update($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            http_response_code(404);
            echo "Annonce non trouvée";
            return;
        }

        // Only owner may update (admin cannot modify someone else's ad)
        $isOwner = ($_SESSION['user_id'] == $ad['owner_id']);
        if (!$isOwner) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Invalid CSRF";
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $delivery = implode(',', $_POST['delivery'] ?? []);
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        // Validate category exists
        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        if (!$categoryId || !$catModel->find($categoryId)) {
            $error = 'Catégorie invalide.';
            $categories = $catModel->all();
            require 'app/views/layout/header.php';
            require 'app/views/ads/edit.php';
            require 'app/views/layout/footer.php';
            return;
        }

        // Remove selected existing images
        $remove = $_POST['remove_images'] ?? [];
        if (!is_array($remove)) $remove = [];
        if (!empty($remove)) {
            $this->adModel->removeImagesByBasename($ad['id'], $remove);
        }

        // Recompute remaining images count to enforce max 5 total
        $remainingImages = $this->adModel->getImages($ad['id']);
        $remainingCount = is_array($remainingImages) ? count($remainingImages) : 0;

        // Handle newly uploaded photos (similar processing as store)
        $uploadedFilenames = [];
        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            $files = $_FILES['photos'];
            $count = count($files['name']);
            // use upload directory under public/uploads
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads';
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0775, true)) {
                    $error = "Impossible de créer le dossier d'upload (" . htmlspecialchars($uploadDir) . ").";
                    $images = $this->adModel->getImages($ad['id']);
                    require_once 'app/models/Category.php';
                    $catModel = new Category($this->pdo);
                    $categories = $catModel->all();
                    require 'app/views/layout/header.php';
                    require 'app/views/ads/edit.php';
                    require 'app/views/layout/footer.php';
                    return;
                }
            }
            // Ensure directory is writable
            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0775);
                if (!is_writable($uploadDir)) {
                    $error = "Le dossier d'upload n'est pas accessible en écriture (" . htmlspecialchars($uploadDir) . ").";
                    $images = $this->adModel->getImages($ad['id']);
                    require_once 'app/models/Category.php';
                    $catModel = new Category($this->pdo);
                    $categories = $catModel->all();
                    require 'app/views/layout/header.php';
                    require 'app/views/ads/edit.php';
                    require 'app/views/layout/footer.php';
                    return;
                }
            }

            $allowed = max(0, 5 - $remainingCount);
            if ($allowed <= 0 && $count > 0) {
                $error = 'Limite de 5 photos atteinte. Supprimez des photos pour en ajouter.';
                $images = $this->adModel->getImages($ad['id']);
                require_once 'app/models/Category.php';
                $catModel = new Category($this->pdo);
                $categories = $catModel->all();
                require 'app/views/layout/header.php';
                require 'app/views/ads/edit.php';
                require 'app/views/layout/footer.php';
                return;
            }

            for ($i = 0; $i < $count; $i++) {
                if (count($uploadedFilenames) >= $allowed) break;
                $err = $files['error'][$i] ?? UPLOAD_ERR_NO_FILE;
                if ($err === UPLOAD_ERR_NO_FILE) continue;
                if ($err !== UPLOAD_ERR_OK) continue;
                $tmp = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $size = $files['size'][$i];
                // validate mime
                $mime = '';
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);
                } else {
                    $info = @getimagesize($tmp);
                    $mime = $info ? ($info['mime'] ?? '') : '';
                }
                if (!in_array($mime, ['image/jpeg', 'image/pjpeg'])) continue;
                if ($size > 200 * 1024) continue;

                // secure unique filename
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                try {
                    $base = bin2hex(random_bytes(12)) . '_' . $i . '_' . str_replace('.', '', (string)microtime(true));
                } catch (Exception $e) {
                    $base = uniqid() . '_' . $i . '_' . time();
                }
                $filename = $base . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;
                $tries = 0;
                while (file_exists($destination) && $tries < 5) {
                    try { $base = bin2hex(random_bytes(8)) . '_' . $i . '_' . time(); } catch (Exception $e) { $base = uniqid() . '_' . $i . '_' . time(); }
                    $filename = $base . '.' . $extension;
                    $destination = $uploadDir . '/' . $filename;
                    $tries++;
                }
                // validate that the temp file is a valid uploaded file
                if (!is_uploaded_file($tmp)) { continue; }
                if (@move_uploaded_file($tmp, $destination)) {
                    $uploadedFilenames[] = '/e-bazar/public/uploads/' . $filename;
                }
            }
        }

        // Update ad main fields
        $data = [
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'delivery_modes' => $delivery
        ];
        $this->adModel->update($ad['id'], $data);

        // Append new images if any
        if (!empty($uploadedFilenames)) {
            $this->adModel->addImages($ad['id'], $uploadedFilenames);
        }

        header('Location: /e-bazar/index.php?url=ad/show/' . $ad['id']);
        exit;
    }

    /**
     * Show confirmation (GET) and perform deletion (POST)
     */
    public function delete($id) {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }

        $ad = $this->adModel->find($id);
        if (!$ad) {
            http_response_code(404);
            echo "Annonce non trouvée";
            return;
        }

        // Only owner or admin may delete
        $isOwner = ($_SESSION['user_id'] == $ad['owner_id']);
        $isAdmin = !empty($_SESSION['is_admin']);
        if (!$isOwner && !$isAdmin) {
            http_response_code(403);
            echo "Accès refusé";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_POST['csrf_token'] ?? '';
            if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
                echo "Jeton CSRF invalide";
                return;
            }

            $ok = $this->adModel->deleteWithImages($id);
            if ($ok) {
                header('Location: /e-bazar/index.php?url=ad');
                exit;
            } else {
                $error = 'Impossible de supprimer l\'annonce.';
                require 'app/views/layout/header.php';
                echo '<div class="card"><p>' . htmlspecialchars($error) . '</p></div>';
                require 'app/views/layout/footer.php';
                return;
            }
        }

        // GET: render a simple confirmation page
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        }
        require 'app/views/layout/header.php';
        // lightweight confirmation form
        echo '<main><div class="card"><h2>Confirmer la suppression</h2>';
        echo '<p>Voulez-vous vraiment supprimer l\'annonce "' . htmlspecialchars($ad['title']) . '" ?</p>';
        echo '<form method="post" action="/e-bazar/index.php?url=ad/delete/' . (int)$ad['id'] . '">';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token']) . '">';
        echo '<button class="btn btn-danger" type="submit">Supprimer</button> ';
        echo '<a class="btn" href="/e-bazar/index.php?url=ad/show/' . (int)$ad['id'] . '">Annuler</a>';
        echo '</form></div></main>';
        require 'app/views/layout/footer.php';
    }

    public function create() {
        // only for authenticated users
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        // Admins cannot create ads
        if (!empty($_SESSION['is_admin'])) {
            http_response_code(403);
            echo "Les administrateurs ne peuvent pas déposer des annonces.";
            return;
        }

        // Load categories from DB
        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        $categories = $catModel->all();

        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        require 'app/views/layout/header.php';
        require 'app/views/ads/create.php';
        require 'app/views/layout/footer.php';
    }

    public function store() {
        if (empty($_SESSION['user_id'])) {
            header('Location: /e-bazar/index.php?url=auth/login');
            exit;
        }
        // Admins cannot create ads
        if (!empty($_SESSION['is_admin'])) {
            http_response_code(403);
            echo "Les administrateurs ne peuvent pas déposer des annonces.";
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo "Method not allowed";
            return;
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!isset($_SESSION['csrf_token']) || !$csrf || !hash_equals($_SESSION['csrf_token'], $csrf)) {
            echo "Invalid CSRF";
            return;
        }

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $delivery = implode(',', $_POST['delivery'] ?? []);
        $categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        // Validate category exists
        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        if (!$categoryId || !$catModel->find($categoryId)) {
            $error = 'Catégorie invalide.';
            $categories = $catModel->all();
            require 'app/views/layout/header.php';
            require 'app/views/ads/create.php';
            require 'app/views/layout/footer.php';
            return;
        }

        // Handle uploaded photos
        $uploadedFilenames = [];
        $uploadErrors = [];

        // categories (needed if we re-render the form on error) from DB
        require_once 'app/models/Category.php';
        $catModel = new Category($this->pdo);
        $categories = $catModel->all();

        if (isset($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
            $files = $_FILES['photos'];
            $count = count($files['name']);
            if ($count > 5) {
                $uploadErrors[] = 'Maximum 5 photos autorisées.';
            }

            // use upload directory under public/uploads
            $uploadDir = dirname(__DIR__, 2) . '/public/uploads';

            // 1. Créer le dossier s'il n'existe pas
            if (!is_dir($uploadDir)) {
                if (!mkdir($uploadDir, 0775, true)) {
                    $uploadErrors[] = 'Impossible de créer le dossier d\'upload (' . htmlspecialchars($uploadDir) . ').';
                }
            }
            // Ensure directory is writable
            if (!is_writable($uploadDir)) {
                @chmod($uploadDir, 0775);
                if (!is_writable($uploadDir)) {
                    $uploadErrors[] = 'Le dossier d\'upload n\'est pas accessible en écriture (' . htmlspecialchars($uploadDir) . ').';
                }
            }

            for ($i = 0; $i < $count; $i++) {
                $err = $files['error'][$i];
                if ($err === UPLOAD_ERR_NO_FILE) continue; // user didn't select a file in this slot
                if ($err !== UPLOAD_ERR_OK) {
                    // Map common PHP upload errors
                    $msg = 'Erreur lors de l\'upload du fichier ' . ($files['name'][$i] ?? '') . ' (code ' . $err . ').';
                    if ($err === UPLOAD_ERR_INI_SIZE || $err === UPLOAD_ERR_FORM_SIZE) {
                        $msg .= ' Taille dépassée côté serveur (vérifier upload_max_filesize / post_max_size).';
                    }
                    $uploadErrors[] = $msg;
                    continue;
                }
                $tmp = $files['tmp_name'][$i];
                $name = $files['name'][$i];
                $size = $files['size'][$i];

                // Try fileinfo, fallback to getimagesize
                $mime = '';
                if (function_exists('finfo_open')) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tmp);
                    finfo_close($finfo);
                } else {
                    $info = @getimagesize($tmp);
                    $mime = $info ? ($info['mime'] ?? '') : '';
                }

                // Validate JPEG
                if (!in_array($mime, ['image/jpeg', 'image/pjpeg'])) {
                    $uploadErrors[] = 'Le fichier "' . htmlspecialchars($name) . '" n\'est pas un JPEG.';
                    continue;
                }

                // Validate size <= 200 KiB
                if ($size > 200 * 1024) {
                    $uploadErrors[] = 'Le fichier "' . htmlspecialchars($name) . '" est trop volumineux (' . round($size/1024) . ' KiB). Max 200 KiB.';
                    continue;
                }

                // 2. Sécuriser le nom du fichier et effectuer l'upload
                $tmpFile = $tmp; // temporary uploaded file
                $originalName = $name;
                $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                // create a robust unique filename using random bytes + microtime + index
                try {
                    $base = bin2hex(random_bytes(12)) . '_' . $i . '_' . str_replace('.', '', (string)microtime(true));
                } catch (Exception $e) {
                    $base = uniqid() . '_' . $i . '_' . time();
                }
                $filename = $base . '.' . $extension;
                $destination = $uploadDir . '/' . $filename;

                // avoid accidental overwrite: retry a few times if file exists
                $tries = 0;
                while (file_exists($destination) && $tries < 5) {
                    try {
                        $base = bin2hex(random_bytes(8)) . '_' . $i . '_' . time();
                    } catch (Exception $e) {
                        $base = uniqid() . '_' . $i . '_' . time();
                    }
                    $filename = $base . '.' . $extension;
                    $destination = $uploadDir . '/' . $filename;
                    $tries++;
                }

                // perform upload
                // validate that the temp file is a valid uploaded file
                if (!is_uploaded_file($tmpFile)) {
                    $uploadErrors[] = 'Le fichier temporaire n\'est pas un fichier uploadé valide.';
                    continue;
                }
                if (!@move_uploaded_file($tmpFile, $destination)) {
                    $uploadErrors[] = 'Erreur lors de l\'upload de l\'image ' . htmlspecialchars($originalName) . ' — vérifiez les permissions du dossier uploads.';
                    continue;
                }
                // store web-accessible path
                $uploadedFilenames[] = '/e-bazar/public/uploads/' . $filename;
            }
        }

        // If upload errors, show them and re-render form
        if (!empty($uploadErrors)) {
            $error = implode('<br>', $uploadErrors);
            require 'app/views/layout/header.php';
            require 'app/views/ads/create.php';
            require 'app/views/layout/footer.php';
            return;
        }

        if (strlen($title) < 5 || strlen($title) > 30 || strlen($description) < 5 || strlen($description) > 200) {
            $error = 'Validation failed';
            require 'app/views/layout/header.php';
            require 'app/views/ads/create.php';
            require 'app/views/layout/footer.php';
            return;
        }

        $data = [
            'owner_id' => $_SESSION['user_id'],
            'category_id' => $categoryId,
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'delivery_modes' => $delivery
        ];

        if (!empty($uploadedFilenames)) {
            $data['images'] = $uploadedFilenames;
            $id = $this->adModel->createWithImages($data);
        } else {
            $id = $this->adModel->create($data);
        }
        header('Location: /e-bazar/index.php?url=ad/show/' . $id);
        exit;
    }
}