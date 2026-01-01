<?php

class Category {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function all() {
        $stmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function find($id) {
        if (!is_numeric($id)) return false;
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        return $stmt->fetch();
    }

    public function create($name) {
        $stmt = $this->pdo->prepare("INSERT INTO categories (name) VALUES (:name)");
        $stmt->execute([':name' => $name]);
        return $this->pdo->lastInsertId();
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute([':id' => (int)$id]);
    }

    /**
     * Return categories with count of unsold ads per category
     */
    public function allWithCounts() {
        $stmt = $this->pdo->query(
            "SELECT c.*, COUNT(a.id) AS ads_count
             FROM categories c
             LEFT JOIN ads a ON a.category_id = c.id AND a.sold = 0
             GROUP BY c.id
             ORDER BY c.name"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

