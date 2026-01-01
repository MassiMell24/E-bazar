<?php
// Usage: php scripts/init_db.php
require_once __DIR__ . '/../config/database.php';

echo "Initialisation de la base de données...\n\n";

/**
 * USERS TABLE
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ Table users créée\n";

/**
 * CATEGORIES TABLE
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ Table categories créée\n";

/**
 * ADS TABLE
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT DEFAULT NULL,
    buyer_id INT DEFAULT NULL,
    category_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description VARCHAR(1000) DEFAULT '',
    price DECIMAL(10,2) DEFAULT 0.00,
    delivery_modes VARCHAR(100) DEFAULT '',
    sold BOOLEAN DEFAULT 0,
    sold_at DATETIME DEFAULT NULL,
    sold_delivery_mode VARCHAR(100) DEFAULT NULL,
    buyer_confirmed_reception BOOLEAN DEFAULT 0,
    buyer_deleted BOOLEAN DEFAULT 0,
    seller_archived BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
echo "✓ Table ads créée\n";

/**
 * AD IMAGES TABLE
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS ad_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    is_thumbnail BOOLEAN DEFAULT 0,
    position INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(ad_id)
)");
echo "✓ Table ad_images créée\n";

/**
 * SEED CATEGORIES
 */
$defaultCategories = ['Électronique', 'Meubles', 'Livres', 'Vêtements'];
$count = $pdo->query("SELECT COUNT(*) c FROM categories")->fetch()['c'] ?? 0;

if ($count == 0) {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
    foreach ($defaultCategories as $cat) {
        $stmt->execute([':name' => $cat]);
    }
    echo "✓ Catégories créées: " . implode(', ', $defaultCategories) . "\n";
}

/**
 * CREATE ADMIN
 */
$adminEmail = 'admin@ebazar.local';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $adminEmail]);

if (!$stmt->fetch()) {
    $hash = password_hash('admin', PASSWORD_DEFAULT);
    $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES ('admin', :email, :hash, 1)")
        ->execute([':email' => $adminEmail, ':hash' => $hash]);
    echo "✓ Compte admin créé (admin@ebazar.local / mot de passe: admin)\n";
} else {
    echo "○ Admin existe déjà\n";
}

/**
 * DEMO USERS + ADS WITH PHOTOS
 */
echo "\nCréation des utilisateurs de démonstration...\n";

$categories = $pdo->query("SELECT id FROM categories ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
if (empty($categories)) {
    echo "Aucune catégorie disponible, impossible de créer les annonces de démo.\n";
    exit;
}

$demoUsers = [
    [
        'username' => 'alice',
        'email' => 'alice@demo.local',
        'password' => 'alice123',
        'ads' => [
            ['Vintage console', 'Console rétro en bon état avec deux manettes incluses. Testée et fonctionnelle.', 120.00, 'console.jpg'],
            ['Lampe design', 'Lampe de bureau LED moderne, très bon état, consommation faible.', 35.00, 'lamp.jpg'],
            ['Fauteuil cosy', 'Fauteuil tissu gris anthracite, très confortable pour lecture.', 90.00, 'chair.jpg'],
            ['Vélo électrique', 'Vélo électrique neuf, batterie performante, très rapide et léger.', 450.00, 'bike.jpg'],
        ],
    ],
    [
        'username' => 'bob',
        'email' => 'bob@demo.local',
        'password' => 'bob123',
        'ads' => [
            ['Collection BD', 'Lot de 10 bandes dessinées classiques en très bon état.', 60.00, 'books.jpg'],
            ['Casque audio', 'Casque circum-aural professionnel, son propre et isolation parfaite.', 45.00, 'headphones.jpg'],
            ['Table basse', 'Table basse en bois clair massif, dimensions 100x60 cm.', 80.00, 'table.jpg'],
            ['Montre connectée', 'Montre intelligente avec suivi de santé et notifications.', 200.00, 'watch.jpg'],
        ],
    ],
    [
        'username' => 'carol',
        'email' => 'carol@demo.local',
        'password' => 'carol123',
        'ads' => [
            ['Robot cuisine', 'Robot de cuisine multifonction avec tous les accessoires d\'origine.', 110.00, 'mixer.jpg'],
            ['Guitare folk', 'Guitare acoustique folk pour débutant/intermédiaire, bon état général.', 75.00, 'guitar.jpg'],
            ['Chaise gaming', 'Chaise ergonomique de bureau gaming, légère usure sur les accoudoirs.', 130.00, 'gaming_chair.jpg'],
            ['Appareil photo numérique', 'Reflex Canon EOS, objectif 18-55mm, parfait état de fonctionnement.', 320.00, 'camera.jpg'],
        ],
    ],
];

$insertUser = $pdo->prepare("INSERT INTO users (username, email, password_hash, is_admin) VALUES (:u, :e, :p, 0)");
$findUser = $pdo->prepare("SELECT id FROM users WHERE email = :e");
$countAds = $pdo->prepare("SELECT COUNT(*) c FROM ads WHERE owner_id = :id");
$insertAd = $pdo->prepare("INSERT INTO ads (owner_id, category_id, title, description, price, delivery_modes) VALUES (:o, :c, :t, :d, :p, :dm)");
$insertImg = $pdo->prepare("INSERT INTO ad_images (ad_id, filename, is_thumbnail, position) VALUES (:ad, :f, :thumb, :pos)");

foreach ($demoUsers as $u) {
    $findUser->execute([':e' => $u['email']]);
    $userId = $findUser->fetchColumn();
    
    if (!$userId) {
        $hash = password_hash($u['password'], PASSWORD_DEFAULT);
        $insertUser->execute([':u' => $u['username'], ':e' => $u['email'], ':p' => $hash]);
        $userId = $pdo->lastInsertId();
        echo "✓ Utilisateur créé: {$u['username']} ({$u['email']} / mot de passe: {$u['password']})\n";
    } else {
        echo "○ Utilisateur existe: {$u['username']}\n";
    }

    // Vérifier si l'utilisateur a déjà des annonces
    $countAds->execute([':id' => $userId]);
    $existing = (int)($countAds->fetch()['c'] ?? 0);
    
    if ($existing >= 4) {
        echo "  → Annonces déjà créées\n";
        continue;
    }

    $catIdx = 0;
    foreach ($u['ads'] as $adData) {
        [$title, $desc, $price, $photoFile] = $adData;
        $categoryId = $categories[$catIdx % count($categories)];
        $catIdx++;
        
        $insertAd->execute([
            ':o' => $userId,
            ':c' => $categoryId,
            ':t' => $title,
            ':d' => $desc,
            ':p' => $price,
            ':dm' => 'postal,hand'
        ]);
        $adId = $pdo->lastInsertId();

        // Ajouter la vraie photo
        $insertImg->execute([
            ':ad' => $adId,
            ':f' => 'scripts/' . $photoFile,
            ':thumb' => 1,
            ':pos' => 0
        ]);
        
        echo "  → Annonce créée: {$title} ({$price}€) avec photo scripts/{$photoFile}\n";
    }
}

echo "\n✅ Initialisation terminée avec succès !\n";
echo "\nComptes disponibles:\n";
echo "  • Admin: admin@ebazar.local / admin\n";
echo "  • Alice: alice@demo.local / alice123\n";
echo "  • Bob:   bob@demo.local / bob123\n";
echo "  • Carol: carol@demo.local / carol123\n";
