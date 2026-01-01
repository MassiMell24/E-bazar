<?php

class Ad {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE sold = 0");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // If schema is not migrated yet (missing column), avoid fatal crash and return empty list
            error_log('Ad::getAll error: ' . $e->getMessage());
            return [];
        }
    }

    public function create(array $data) {
        $stmt = $this->pdo->prepare("INSERT INTO ads (owner_id, category_id, title, description, price, delivery_modes) VALUES (:owner_id, :category_id, :title, :description, :price, :delivery_modes)");
        $stmt->execute([
            ':owner_id' => $data['owner_id'] ?? null,
            ':category_id' => $data['category_id'] ?? null,
            ':title' => $data['title'],
            ':description' => $data['description'] ?? '',
            ':price' => $data['price'] ?? 0,
            ':delivery_modes' => $data['delivery_modes'] ?? ''
        ]);
        return $this->pdo->lastInsertId();
    }

    /**
     * Create ad and optionally save images (array of filenames)
     * $data may contain 'images' => array of filenames (already saved on disk)
     */
    public function createWithImages(array $data) {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("INSERT INTO ads (owner_id, category_id, title, description, price, delivery_modes) VALUES (:owner_id, :category_id, :title, :description, :price, :delivery_modes)");
            $stmt->execute([
                ':owner_id' => $data['owner_id'] ?? null,
                ':category_id' => $data['category_id'] ?? null,
                ':title' => $data['title'],
                ':description' => $data['description'] ?? '',
                ':price' => $data['price'] ?? 0,
                ':delivery_modes' => $data['delivery_modes'] ?? ''
            ]);
            $adId = $this->pdo->lastInsertId();

            if (!empty($data['images']) && is_array($data['images'])) {
                $pos = 0;
                foreach ($data['images'] as $idx => $filename) {
                    $isThumb = ($idx === 0) ? 1 : 0;
                    $ins = $this->pdo->prepare("INSERT INTO ad_images (ad_id, filename, is_thumbnail, position) VALUES (:ad_id, :filename, :is_thumbnail, :position)");
                    $ins->execute([
                        ':ad_id' => $adId,
                        ':filename' => $filename,
                        ':is_thumbnail' => $isThumb,
                        ':position' => $pos
                    ]);
                    $pos++;
                }
            }

            $this->pdo->commit();
            return $adId;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Ad::createWithImages error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Return list of images filenames for an ad (ordered)
     */
    public function getImages($adId) {
        $stmt = $this->pdo->prepare("SELECT filename, is_thumbnail FROM ad_images WHERE ad_id = :ad_id ORDER BY position ASC");
        $stmt->execute([':ad_id' => (int)$adId]);
        return $stmt->fetchAll();
    }

    /**
     * Update ad fields
     */
    public function update($id, array $data) {
        $stmt = $this->pdo->prepare("UPDATE ads SET category_id = :category_id, title = :title, description = :description, price = :price, delivery_modes = :delivery_modes WHERE id = :id");
        return $stmt->execute([
            ':category_id' => $data['category_id'] ?? null,
            ':title' => $data['title'] ?? '',
            ':description' => $data['description'] ?? '',
            ':price' => $data['price'] ?? 0,
            ':delivery_modes' => $data['delivery_modes'] ?? '',
            ':id' => (int)$id
        ]);
    }

    /**
     * Remove specific image rows by basenames and unlink files
     * $basenames should be array of basenames (e.g. ['abc.jpg'])
     */
    public function removeImagesByBasename($adId, array $basenames) {
        if (empty($basenames)) return true;
        // Fetch full filenames to unlink
        $stmt = $this->pdo->prepare("SELECT filename FROM ad_images WHERE ad_id = :ad_id");
        $stmt->execute([':ad_id' => $adId]);
        $rows = $stmt->fetchAll();
        $toDelete = [];
        foreach ($rows as $r) {
            $base = basename($r['filename']);
            if (in_array($base, $basenames, true)) {
                $toDelete[] = $base;
                $full = dirname(__DIR__, 2) . '/public/uploads/' . $base;
                if (is_file($full)) {@unlink($full);}                
            }
        }

        if (empty($toDelete)) return true;

        // Delete rows matching basenames
        foreach ($toDelete as $bd) {
            $del2 = $this->pdo->prepare("DELETE FROM ad_images WHERE ad_id = :ad_id AND filename LIKE :filename");
            $del2->execute([':ad_id' => $adId, ':filename' => '%' . $bd]);
        }
        return true;
    }

    /**
     * Append images entries for an ad. Filenames are web-accessible paths.
     */
    public function addImages($adId, array $filenames) {
        if (empty($filenames)) return true;
        // find current max position
        $stmt = $this->pdo->prepare("SELECT MAX(position) as maxpos FROM ad_images WHERE ad_id = :ad_id");
        $stmt->execute([':ad_id' => $adId]);
        $row = $stmt->fetch();
        $pos = ($row && $row['maxpos'] !== null) ? ((int)$row['maxpos'] + 1) : 0;
        foreach ($filenames as $idx => $fn) {
            $isThumb = 0;
            $ins = $this->pdo->prepare("INSERT INTO ad_images (ad_id, filename, is_thumbnail, position) VALUES (:ad_id, :filename, :is_thumbnail, :position)");
            $ins->execute([
                ':ad_id' => $adId,
                ':filename' => $fn,
                ':is_thumbnail' => $isThumb,
                ':position' => $pos
            ]);
            $pos++;
        }
        return true;
    }

    public function find($id) {
        if (!is_numeric($id)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE id = :id LIMIT 1");
        $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Mark an ad as sold to a buyer. Returns true on success, false otherwise.
     */
    public function markAsSold($id, $buyerId, $deliveryMode = null) {
        // Primary path: update with delivery mode
        try {
            $stmt = $this->pdo->prepare("UPDATE ads SET sold = 1, buyer_id = :buyer_id, sold_at = NOW(), sold_delivery_mode = :mode WHERE id = :id AND sold = 0");
            $stmt->execute([':buyer_id' => $buyerId, ':id' => $id, ':mode' => $deliveryMode]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // If the new column is missing (migration not applied), attempt to add it then retry; otherwise fallback without the column
            if ($this->isMissingSoldDeliveryColumn($e)) {
                $this->tryAddSoldDeliveryColumn();
                try {
                    $stmt = $this->pdo->prepare("UPDATE ads SET sold = 1, buyer_id = :buyer_id, sold_at = NOW(), sold_delivery_mode = :mode WHERE id = :id AND sold = 0");
                    $stmt->execute([':buyer_id' => $buyerId, ':id' => $id, ':mode' => $deliveryMode]);
                    return $stmt->rowCount() > 0;
                } catch (PDOException $e2) {
                    error_log('Ad::markAsSold retry error: ' . $e2->getMessage());
                    // continue to fallback
                }
            }
            // Fallback: update without storing delivery mode (avoids blocking purchase if column missing)
            try {
                $stmt = $this->pdo->prepare("UPDATE ads SET sold = 1, buyer_id = :buyer_id, sold_at = NOW() WHERE id = :id AND sold = 0");
                $stmt->execute([':buyer_id' => $buyerId, ':id' => $id]);
                return $stmt->rowCount() > 0;
            } catch (PDOException $e3) {
                error_log('Ad::markAsSold fallback error: ' . $e3->getMessage());
                return false;
            }
        }
    }

    private function isMissingSoldDeliveryColumn(PDOException $e) {
        $msg = $e->getMessage();
        return (strpos($msg, 'sold_delivery_mode') !== false) || ($e->getCode() === '42S22');
    }

    private function tryAddSoldDeliveryColumn() {
        try {
            $this->pdo->exec("ALTER TABLE ads ADD COLUMN sold_delivery_mode VARCHAR(100) DEFAULT NULL");
        } catch (Exception $e) {
            // ignore if fails (already exists or no permissions)
        }
    }

    /**
     * Get available ads (not sold), optional category filter and sort.
     * $sort can be 'price_asc','price_desc','date_asc','date_desc'
     */
    public function getAvailable($categoryId = null, $sort = null) {
        $sql = "SELECT * FROM ads WHERE sold = 0";
        $params = [];
        if ($categoryId) {
            $sql .= " AND category_id = :cat";
            $params[':cat'] = (int)$categoryId;
        }
        $order = '';
        switch ($sort) {
            case 'price_asc': $order = ' ORDER BY price ASC'; break;
            case 'price_desc': $order = ' ORDER BY price DESC'; break;
            case 'date_asc': $order = ' ORDER BY created_at ASC'; break;
            case 'date_desc': $order = ' ORDER BY created_at DESC'; break;
            default: $order = ' ORDER BY created_at DESC';
        }
        $sql .= $order;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Paginated available ads with optional category filter and sorting.
     */
    public function getAvailablePaged($categoryId = null, $sort = null, $limit = 10, $offset = 0) {
        $sql = "SELECT * FROM ads WHERE sold = 0";
        $params = [];
        if ($categoryId) {
            $sql .= " AND category_id = :cat";
            $params[':cat'] = (int)$categoryId;
        }
        switch ($sort) {
            case 'price_asc': $sql .= ' ORDER BY price ASC'; break;
            case 'price_desc': $sql .= ' ORDER BY price DESC'; break;
            case 'date_asc': $sql .= ' ORDER BY created_at ASC'; break;
            case 'date_desc':
            default: $sql .= ' ORDER BY created_at DESC';
        }
        $sql .= ' LIMIT :limit OFFSET :offset';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAvailable($categoryId = null) {
        $sql = "SELECT COUNT(*) AS c FROM ads WHERE sold = 0";
        $params = [];
        if ($categoryId) {
            $sql .= " AND category_id = :cat";
            $params[':cat'] = (int)$categoryId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ? (int)$row['c'] : 0;
    }

    public function getLatest($limit = 4) {
        $stmt = $this->pdo->prepare('SELECT * FROM ads WHERE sold = 0 ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ads sold by a given user (owner) - excluding archived ones
     */
    public function getUserSold($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE owner_id = :uid AND sold = 1 AND seller_archived = 0 ORDER BY sold_at DESC");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // If seller_archived column doesn't exist yet, add it
            if ($this->isMissingSellerArchivedColumn($e)) {
                $this->tryAddSellerArchivedColumn();
                try {
                    $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE owner_id = :uid AND sold = 1 AND seller_archived = 0 ORDER BY sold_at DESC");
                    $stmt->execute([':uid' => $userId]);
                    return $stmt->fetchAll();
                } catch (PDOException $e2) {
                    error_log('Ad::getUserSold retry error: ' . $e2->getMessage());
                }
            }
            // Fallback: show all sold ads
            $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE owner_id = :uid AND sold = 1 ORDER BY sold_at DESC");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll();
        }
    }

    /**
     * Ads purchased by a given user (buyer) - excluding deleted ones
     */
    public function getUserPurchased($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE buyer_id = :uid AND buyer_deleted = 0 ORDER BY sold_at DESC");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // If buyer_deleted column doesn't exist yet, add it
            if ($this->isMissingBuyerDeletedColumn($e)) {
                $this->tryAddBuyerDeletedColumn();
                try {
                    $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE buyer_id = :uid AND buyer_deleted = 0 ORDER BY sold_at DESC");
                    $stmt->execute([':uid' => $userId]);
                    return $stmt->fetchAll();
                } catch (PDOException $e2) {
                    error_log('Ad::getUserPurchased retry error: ' . $e2->getMessage());
                }
            }
            // Fallback: show all purchased ads
            $stmt = $this->pdo->prepare("SELECT * FROM ads WHERE buyer_id = :uid ORDER BY sold_at DESC");
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll();
        }
    }

    /**
     * Mark an ad as received by the buyer
     */
    public function confirmReception($id, $buyerId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE ads SET buyer_confirmed_reception = 1 WHERE id = :id AND buyer_id = :buyer_id");
            $stmt->execute([':id' => $id, ':buyer_id' => $buyerId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // If column doesn't exist, try to add it
            if ($this->isMissingBuyerConfirmedColumn($e)) {
                $this->tryAddBuyerConfirmedColumn();
                try {
                    $stmt = $this->pdo->prepare("UPDATE ads SET buyer_confirmed_reception = 1 WHERE id = :id AND buyer_id = :buyer_id");
                    $stmt->execute([':id' => $id, ':buyer_id' => $buyerId]);
                    return $stmt->rowCount() > 0;
                } catch (PDOException $e2) {
                    error_log('Ad::confirmReception retry error: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('Ad::confirmReception error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Archive ad for seller (hides from their sold list but keeps for buyer)
     */
    public function archiveForSeller($id, $sellerId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE ads SET seller_archived = 1 WHERE id = :id AND owner_id = :seller_id");
            $stmt->execute([':id' => $id, ':seller_id' => $sellerId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // If column doesn't exist, try to add it
            if ($this->isMissingSellerArchivedColumn($e)) {
                $this->tryAddSellerArchivedColumn();
                try {
                    $stmt = $this->pdo->prepare("UPDATE ads SET seller_archived = 1 WHERE id = :id AND owner_id = :seller_id");
                    $stmt->execute([':id' => $id, ':seller_id' => $sellerId]);
                    return $stmt->rowCount() > 0;
                } catch (PDOException $e2) {
                    error_log('Ad::archiveForSeller retry error: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('Ad::archiveForSeller error: ' . $e->getMessage());
            return false;
        }
    }

    private function isMissingBuyerConfirmedColumn(PDOException $e) {
        $msg = $e->getMessage();
        return (strpos($msg, 'buyer_confirmed_reception') !== false) || ($e->getCode() === '42S22');
    }

    private function tryAddBuyerConfirmedColumn() {
        try {
            $this->pdo->exec("ALTER TABLE ads ADD COLUMN buyer_confirmed_reception BOOLEAN DEFAULT 0");
        } catch (Exception $e) {
            // ignore if fails (already exists or no permissions)
        }
    }

    private function isMissingSellerArchivedColumn(PDOException $e) {
        $msg = $e->getMessage();
        return (strpos($msg, 'seller_archived') !== false) || ($e->getCode() === '42S22');
    }

    private function tryAddSellerArchivedColumn() {
        try {
            $this->pdo->exec("ALTER TABLE ads ADD COLUMN seller_archived BOOLEAN DEFAULT 0");
        } catch (Exception $e) {
            // ignore if fails (already exists or no permissions)
        }
    }

    /**
     * Mark an ad as deleted for the buyer (hides from their purchased list)
     */
    public function deleteForBuyer($id, $buyerId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE ads SET buyer_deleted = 1 WHERE id = :id AND buyer_id = :buyer_id");
            $stmt->execute([':id' => $id, ':buyer_id' => $buyerId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            // If column doesn't exist, try to add it
            if ($this->isMissingBuyerDeletedColumn($e)) {
                $this->tryAddBuyerDeletedColumn();
                try {
                    $stmt = $this->pdo->prepare("UPDATE ads SET buyer_deleted = 1 WHERE id = :id AND buyer_id = :buyer_id");
                    $stmt->execute([':id' => $id, ':buyer_id' => $buyerId]);
                    return $stmt->rowCount() > 0;
                } catch (PDOException $e2) {
                    error_log('Ad::deleteForBuyer retry error: ' . $e2->getMessage());
                    return false;
                }
            }
            error_log('Ad::deleteForBuyer error: ' . $e->getMessage());
            return false;
        }
    }

    private function isMissingBuyerDeletedColumn(PDOException $e) {
        $msg = $e->getMessage();
        return (strpos($msg, 'buyer_deleted') !== false) || ($e->getCode() === '42S22');
    }

    private function tryAddBuyerDeletedColumn() {
        try {
            $this->pdo->exec("ALTER TABLE ads ADD COLUMN buyer_deleted BOOLEAN DEFAULT 0");
        } catch (Exception $e) {
            // ignore if fails (already exists or no permissions)
        }
    }

    /**
     * Delete ad and its associated image files and DB rows.
     */
    public function deleteWithImages($id) {
        $this->pdo->beginTransaction();
        try {
            // fetch filenames
            $stmt = $this->pdo->prepare("SELECT filename FROM ad_images WHERE ad_id = :id");
            $stmt->execute([':id' => $id]);
            $rows = $stmt->fetchAll();

            // delete files from filesystem
            foreach ($rows as $r) {
                $file = $r['filename'] ?? '';
                if (!$file) continue;
                // stored paths are web-accessible like '/e-bazar/public/uploads/xxx.jpg'
                $basename = basename($file);
                $full = dirname(__DIR__, 2) . '/public/uploads/' . $basename;
                if (is_file($full)) {
                    @unlink($full);
                }
            }

            // delete ad_images rows
            $del = $this->pdo->prepare("DELETE FROM ad_images WHERE ad_id = :id");
            $del->execute([':id' => $id]);

            // delete ad
            $del2 = $this->pdo->prepare("DELETE FROM ads WHERE id = :id");
            $del2->execute([':id' => $id]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('Ad::deleteWithImages error: ' . $e->getMessage());
            return false;
        }
    }
}
