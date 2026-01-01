<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>e-bazar</title>
    <link rel="icon" href="/e-bazar/public/assets/favicon.jpeg" type="image/jpeg">
    <link rel="shortcut icon" href="/e-bazar/public/assets/favicon.jpeg">
    <?php
    $cssPath = __DIR__ . '/../../../public/assets/css/style.css';
    $cssVer = file_exists($cssPath) ? filemtime($cssPath) : time();
    ?>
    <link rel="stylesheet" href="/e-bazar/public/assets/css/style.css?v=<?php echo $cssVer; ?>">
    <script>
        let confirmForm = null;
        function showConfirm(message, form) {
            confirmForm = form;
            document.getElementById('confirmMessage').textContent = message;
            document.getElementById('modalOverlay').classList.add('active');
        }
        function confirmAction() {
            if (confirmForm) {
                confirmForm.submit();
            }
            closeConfirm();
        }
        function closeConfirm() {
            document.getElementById('modalOverlay').classList.remove('active');
            confirmForm = null;
        }
        function showWarning(title, message) {
            document.getElementById('warningTitle').textContent = title;
            document.getElementById('warningMessage').textContent = message;
            document.getElementById('warningOverlay').classList.add('active');
        }
        function closeWarning() {
            document.getElementById('warningOverlay').classList.remove('active');
        }
    </script>
</head>
<body>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;
$username = $_SESSION['username'] ?? null;
$email = $_SESSION['email'] ?? null;

// Si l'utilisateur est connecté mais qu'on n'a pas son username/email en session, on les récupère
if ($userId && (!$username && !$email)) {
    global $pdo;
    if ($pdo) {
        $stmt = $pdo->prepare('SELECT username, email FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['username'] = $username = $user['username'];
            $_SESSION['email'] = $email = $user['email'];
        }
    }
}
?>
<!-- Modal de confirmation -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h3>Confirmation</h3>
        <p id="confirmMessage"></p>
        <div class="modal-buttons">
            <button class="modal-btn" onclick="closeConfirm()">Annuler</button>
            <button class="modal-btn confirm" onclick="confirmAction()">Confirmer</button>
        </div>
    </div>
</div>
<!-- Modal d'avertissement -->
<div class="modal-overlay" id="warningOverlay">
    <div class="modal">
        <h3 id="warningTitle" style="color:#fca5a5">⚠️ Attention</h3>
        <p id="warningMessage"></p>
        <div class="modal-buttons">
            <button class="modal-btn confirm" onclick="closeWarning()">J'ai compris</button>
        </div>
    </div>
</div>
<header>
    <div class="container">
        <h1><a class="brand" href="/e-bazar/index.php?url=ad">E-Bazar</a></h1>
        <nav>
            <a href="/e-bazar/index.php?url=ad">Annonces</a>
            <?php if ($userId): ?>
                <?php if (!$isAdmin): ?> | <a href="/e-bazar/index.php?url=ad/create">Déposer une annonce</a><?php endif; ?>
                <?php if (!$isAdmin): ?> | <a href="/e-bazar/index.php?url=dashboard/myAds">Mes annonces</a><?php endif; ?>
                | <a href="<?php echo $isAdmin ? '/e-bazar/index.php?url=admin/dashboard' : '/e-bazar/index.php?url=dashboard/settings'; ?>" class="user-badge" style="text-decoration:none;background:linear-gradient(135deg, var(--accent-soft) 0%, rgba(124,58,237,0.18) 100%);padding:6px 14px;border-radius:999px;border:1px solid var(--accent);display:inline-flex;align-items:center;gap:8px;transition:all 0.2s ease;box-shadow:0 2px 8px rgba(124,58,237,0.15)">
                    <span style="font-size:18px;line-height:1"><?php echo $isAdmin ? '👑' : '👤'; ?></span>
                    <span style="font-weight:700;color:var(--accent-strong);letter-spacing:-0.01em"><?php echo htmlspecialchars($username ?? $email ?? 'Utilisateur'); ?></span>
                </a>
                | <a href="/e-bazar/index.php?url=auth/logout">Se déconnecter</a>
            <?php else: ?>
                | <a href="/e-bazar/index.php?url=auth/register">S'inscrire</a>
                | <a href="/e-bazar/index.php?url=auth/login">Se connecter</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
    <div class="container">
