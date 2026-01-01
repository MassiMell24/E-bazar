<?php

class User {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create user. If $username provided it is validated, otherwise left NULL.
     * Returns new user id or false on duplicate.
     */
    public function create($email, $password, $isAdmin = false, $username = null) {
        // If username provided, validate format; if not, we'll set a default after insert
        if ($username !== null && $username !== '' && !preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
            throw new InvalidArgumentException('Invalid username');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        // If username is empty/null, generate a temporary unique placeholder to satisfy NOT NULL + UNIQUE
        if ($username === null || $username === '') {
            try {
                $username = 'user_tmp_' . bin2hex(random_bytes(4));
            } catch (Exception $e) {
                $username = 'user_tmp_' . uniqid();
            }
        }

        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash, is_admin) VALUES (:username, :email, :hash, :admin)');
        try {
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':hash' => $hash,
                ':admin' => $isAdmin ? 1 : 0
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }
            throw $e;
        }

        $id = (int)$this->pdo->lastInsertId();

        // If original username was empty, set it to the required format 'user<ID>'
        if (strpos($username, 'user_tmp_') === 0) {
            $final = 'user' . $id;
            $upd = $this->pdo->prepare('UPDATE users SET username = :u WHERE id = :id');
            try {
                $upd->execute([':u' => $final, ':id' => $id]);
            } catch (PDOException $e) {
                // In case of extremely rare collision, append a random suffix
                try {
                    $final = 'user' . $id . '_' . bin2hex(random_bytes(2));
                } catch (Exception $ex) {
                    $final = 'user' . $id . '_' . uniqid();
                }
                $upd->execute([':u' => $final, ':id' => $id]);
            }
        }

        return $id;
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function findByUsername($username) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function delete($id) {
        if (!is_numeric($id)) return false;
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        return $stmt->execute([':id' => (int)$id]);
    }

    /**
     * Update user email, username and/or password.
     * Returns true on success, false on duplicate username/email, or throws exception.
     */
    public function update($id, $email = null, $username = null, $password = null) {
        if (!is_numeric($id)) return false;

        $id = (int)$id;
        $updates = [];
        $params = [':id' => $id];

        // Check for duplicate username if provided
        if ($username !== null && $username !== '') {
            if (!preg_match('/^[a-zA-Z0-9_]{3,}$/', $username)) {
                throw new InvalidArgumentException('Invalid username format');
            }
            $check = $this->pdo->prepare('SELECT id FROM users WHERE username = :u AND id != :id');
            $check->execute([':u' => $username, ':id' => $id]);
            if ($check->fetch()) {
                return false; // Username already exists
            }
            $updates[] = 'username = :username';
            $params[':username'] = $username;
        }

        if ($email !== null) {
            $updates[] = 'email = :email';
            $params[':email'] = $email;
        }

        if ($password !== null) {
            $updates[] = 'password_hash = :hash';
            $params[':hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (empty($updates)) {
            return true;
        }

        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete user and all associated ads/images.
     * Must be called before deleting the user from DB to get their ads.
     */
    public function deleteWithAds($id) {
        if (!is_numeric($id)) return false;

        $id = (int)$id;
        $this->pdo->beginTransaction();

        try {
            // Get all ads owned by this user
            require_once 'app/models/Ad.php';
            $adModel = new Ad($this->pdo);

            $stmt = $this->pdo->prepare('SELECT id FROM ads WHERE owner_id = :uid');
            $stmt->execute([':uid' => $id]);
            $ads = $stmt->fetchAll();

            // Delete each ad with its images
            foreach ($ads as $a) {
                $adModel->deleteWithImages($a['id']);
            }

            // Delete the user
            $userDel = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
            $userDel->execute([':id' => $id]);

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log('User::deleteWithAds error: ' . $e->getMessage());
            return false;
        }
    }
}

