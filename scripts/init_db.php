<?php
// Usage: php scripts/init_db.php
require_once __DIR__ . '/../config/database.php';

/**
 * USERS
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) DEFAULT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
");

/**
 * ADS
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
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
");

/**
 * AD IMAGES
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS ad_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    is_thumbnail BOOLEAN DEFAULT 0,
    position INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX(ad_id)
)
");

/**
 * CATEGORIES
 */
$pdo->exec("CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)
");

/**
 * SAFE MIGRATIONS FOR ADS
 */
$adsCols = [
    'owner_id' => "ALTER TABLE ads ADD COLUMN owner_id INT DEFAULT NULL",
    'buyer_id' => "ALTER TABLE ads ADD COLUMN buyer_id INT DEFAULT NULL",
    'category_id' => "ALTER TABLE ads ADD COLUMN category_id INT DEFAULT NULL",
    'description' => "ALTER TABLE ads ADD COLUMN description VARCHAR(1000) DEFAULT ''",
    'delivery_modes' => "ALTER TABLE ads ADD COLUMN delivery_modes VARCHAR(100) DEFAULT ''",
    'sold' => "ALTER TABLE ads ADD COLUMN sold BOOLEAN DEFAULT 0",
    'sold_at' => "ALTER TABLE ads ADD COLUMN sold_at DATETIME DEFAULT NULL",
    'created_at' => "ALTER TABLE ads ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP",
    'sold_delivery_mode' => "ALTER TABLE ads ADD COLUMN sold_delivery_mode VARCHAR(100) DEFAULT NULL"
];

foreach ($adsCols as $col => $sql) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM ads LIKE :col");
        $stmt->execute([':col' => $col]);
        if (!$stmt->fetch()) {
            $pdo->exec($sql);
            echo "Added column ads.$col\n";
        }
    } catch (Exception $e) {}
}

/**
 * SAFE MIGRATION FOR USERS.USERNAME
 */
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'username'");
    if (!$stmt->fetch()) {
        // 1. Add nullable column
        $pdo->exec("ALTER TABLE users ADD COLUMN username VARCHAR(100) DEFAULT NULL");
        echo "Added users.username\n";

        // 2. Backfill usernames from email
        $users = $pdo->query("SELECT id, email FROM users")->fetchAll(PDO::FETCH_ASSOC);
        $check = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $update = $pdo->prepare("UPDATE users SET username = :u WHERE id = :id");

        foreach ($users as $u) {
            $base = preg_replace('/[^a-zA-Z0-9_]/', '_', explode('@', $u['email'])[0] ?: 'user');
            $candidate = $base;
            $i = 1;

            while (true) {
                $check->execute([':u' => $candidate]);
                if (!$check->fetch()) break;
                $candidate = $base . $i++;
            }

            $update->execute([
                ':u' => $candidate,
                ':id' => $u['id']
            ]);
        }

        // 3. Enforce constraints
        $pdo->exec("ALTER TABLE users MODIFY username VARCHAR(100) NOT NULL");
        $pdo->exec("ALTER TABLE users ADD UNIQUE (username)");

        echo "Backfilled and enforced users.username\n";
    }
} catch (Exception $e) {
    echo "Warning: username migration failed: {$e->getMessage()}\n";
}

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
    echo "Seeded categories\n";
}

/**
 * DEFAULT ADMIN
 */
$adminEmail = 'admin@ebazar.local';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $adminEmail]);

if (!$stmt->fetch()) {
    $hash = password_hash('admin', PASSWORD_DEFAULT);
    $pdo->prepare("
        INSERT INTO users (username, email, password_hash, is_admin)
        VALUES ('admin', :email, :hash, 1)
    ")->execute([
        ':email' => $adminEmail,
        ':hash' => $hash
    ]);

    echo "Created admin (admin / admin)\n";
} else {
    echo "Admin already exists\n";
}

echo "DB init / migration complete.\n";
