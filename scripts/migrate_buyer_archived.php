<?php
/**
 * Migration script to add history management columns to ads table
 * - buyer_confirmed_reception: tracks if buyer confirmed they received the item
 * - buyer_deleted: allows buyers to hide purchased items from their list without affecting seller
 * - seller_archived: allows sellers to hide sold items from their list without affecting buyer
 * 
 * Run this script once to update the database schema:
 * php scripts/migrate_buyer_archived.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    echo "Starting migration: Adding history management columns...\n";
    
    // Check and add buyer_confirmed_reception column
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'buyer_confirmed_reception'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE ads ADD COLUMN buyer_confirmed_reception BOOLEAN DEFAULT 0 AFTER sold_delivery_mode");
        echo "✓ Successfully added 'buyer_confirmed_reception' column.\n";
    } else {
        echo "- Column 'buyer_confirmed_reception' already exists.\n";
    }
    
    // Check and add buyer_deleted column
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'buyer_deleted'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE ads ADD COLUMN buyer_deleted BOOLEAN DEFAULT 0 AFTER buyer_confirmed_reception");
        echo "✓ Successfully added 'buyer_deleted' column.\n";
    } else {
        echo "- Column 'buyer_deleted' already exists.\n";
    }
    
    // Check and add seller_archived column
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'seller_archived'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE ads ADD COLUMN seller_archived BOOLEAN DEFAULT 0 AFTER buyer_deleted");
        echo "✓ Successfully added 'seller_archived' column.\n";
    } else {
        echo "- Column 'seller_archived' already exists.\n";
    }
    
    // Remove old buyer_archived column if it exists (from previous attempt)
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'buyer_archived'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        $pdo->exec("ALTER TABLE ads DROP COLUMN buyer_archived");
        echo "✓ Removed obsolete 'buyer_archived' column.\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
