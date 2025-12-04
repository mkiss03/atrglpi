<?php
/**
 * Admin Model
 * ÁTR Beragadt Betegek - Admin User Management
 */

require_once __DIR__ . '/../config/database.php';

class Admin {
    private $pdo;

    public function __construct() {
        $this->pdo = getDbConnection();
    }

    /**
     * Authenticate admin user
     * @param string $username
     * @param string $password
     * @return array|null Admin data or null if authentication fails
     */
    public function authenticate($username, $password) {
        $sql = "SELECT * FROM admins WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password_hash'])) {
            return $admin;
        }

        return null;
    }

    /**
     * Login admin and create session
     * @param string $username
     * @param string $password
     * @return bool
     */
    public function login($username, $password) {
        $admin = $this->authenticate($username, $password);

        if ($admin) {
            startSession();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_display_name'] = $admin['display_name'];
            return true;
        }

        return false;
    }

    /**
     * Logout admin
     */
    public static function logout() {
        startSession();
        session_unset();
        session_destroy();
    }

    /**
     * Create new admin
     * @param array $data
     * @return int|bool Last inserted ID or false
     */
    public function create($data) {
        $sql = "INSERT INTO admins (username, password_hash, display_name)
                VALUES (:username, :password_hash, :display_name)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                ':username' => $data['username'],
                ':password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                ':display_name' => $data['display_name'],
            ]);

            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            // Unique constraint violation
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Get all admins
     * @return array
     */
    public function getAll() {
        $sql = "SELECT id, username, display_name, created_at FROM admins ORDER BY display_name";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get admin by ID
     * @param int $id
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT id, username, display_name, created_at FROM admins WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get admin by username
     * @param string $username
     * @return array|null
     */
    public function getByUsername($username) {
        $sql = "SELECT id, username, display_name, created_at FROM admins WHERE username = :username";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':username' => $username]);

        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update admin
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE admins SET
            username = :username,
            display_name = :display_name";

        $params = [
            ':id' => $id,
            ':username' => $data['username'],
            ':display_name' => $data['display_name'],
        ];

        // Only update password if provided
        if (!empty($data['password'])) {
            $sql .= ", password_hash = :password_hash";
            $params[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete admin
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        // Don't allow deleting yourself if logged in as this admin
        startSession();
        if (isset($_SESSION['admin_id']) && $_SESSION['admin_id'] == $id) {
            return false;
        }

        $sql = "DELETE FROM admins WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Change admin password
     * @param int $id Admin ID
     * @param string $newPassword New password (will be hashed)
     * @param string|null $currentPassword Current password (optional, for verification)
     * @return bool
     */
    public function changePassword($id, $newPassword, $currentPassword = null) {
        // If current password is provided, verify it first
        if ($currentPassword !== null) {
            $admin = $this->getById($id);
            if (!$admin) {
                return false;
            }

            // Get the actual password hash from database
            $sql = "SELECT password_hash FROM admins WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch();

            if (!$result || !password_verify($currentPassword, $result['password_hash'])) {
                return false; // Current password is incorrect
            }
        }

        // Update password
        $sql = "UPDATE admins SET password_hash = :password_hash WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id' => $id,
            ':password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
        ]);
    }
}
