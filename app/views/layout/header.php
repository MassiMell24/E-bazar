<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>e-bazar</title>
    <link rel="icon" href="/e-bazar/public/assets/favicon.svg" type="image/svg+xml">
    <link rel="shortcut icon" href="/e-bazar/public/assets/favicon.svg">
    <?php
    $cssPath = __DIR__ . '/../../../public/assets/css/style.css';
    $cssVer = file_exists($cssPath) ? filemtime($cssPath) : time();
    ?>
    <link rel="stylesheet" href="/e-bazar/public/assets/css/style.css?v=<?php echo $cssVer; ?>">
</head>
<body>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$userId = $_SESSION['user_id'] ?? null;
$isAdmin = $_SESSION['is_admin'] ?? false;
?>
<header>
    <div class="container">
        <h1>E-Bazar</h1>
        <nav>
            <a href="/e-bazar/index.php?url=ad">Annonces</a>
            <?php if ($userId): ?>
                <?php if (!$isAdmin): ?> | <a href="/e-bazar/index.php?url=ad/create">Déposer une annonce</a><?php endif; ?>
                <?php if (!$isAdmin): ?> | <a href="/e-bazar/index.php?url=dashboard/myAds">Mes annonces</a><?php endif; ?>
                <?php if (!$isAdmin): ?> | <a href="/e-bazar/index.php?url=dashboard/settings">Mon compte</a><?php endif; ?>
                <?php if ($isAdmin): ?> | <a href="/e-bazar/index.php?url=admin/dashboard">Admin</a><?php endif; ?>
                | <a href="/e-bazar/index.php?url=auth/logout">Se déconnecter</a>
            <?php else: ?>
                | <a href="/e-bazar/index.php?url=auth/register">S'inscrire</a>
                | <a href="/e-bazar/index.php?url=auth/login">Se connecter</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
    <div class="container">
