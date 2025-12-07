<?php
/**
 * User Model
 * Handles user authentication, registration, and profile management
 */
class User {
    private $conn;
    private $table = "users";

    // User properties
    public $id;
    public $username;
    public $email;
    public $password;
    public $full_name;
    public $avatar;
    public $bio;
    public $role;
    public $status;
    public $created_at;
    public $updated_at;

    // Valid roles
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';

    /**
     * Constructor
     */
    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * CREATE - Register new user
     */
    public function register() {
        $query = "INSERT INTO " . $this->table . " 
                SET username = :username,
                    email = :email,
                    password = :password,
                    full_name = :full_name,
                    avatar = :avatar,
                    bio = :bio,
                    role = :role,
                    status = 'active'";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        
        // Hash password
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);

        // Bind
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":role", $this->role);

        try {
            if($stmt->execute()) {
                $this->id = $this->conn->lastInsertId();
                return true;
            }
        } catch(PDOException $e) {
            // Handle duplicate entry
            if ($e->getCode() == 23000) {
                return false;
            }
            throw $e;
        }

        return false;
    }

    /**
     * LOGIN - Authenticate user
     */
    public function login($username_or_email, $password) {
        $query = "SELECT * FROM " . $this->table . " 
                WHERE (username = :username OR email = :email) 
                AND status = 'active'
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username_or_email);
        $stmt->bindParam(":email", $username_or_email);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($password, $row['password'])) {
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->avatar = $row['avatar'];
            $this->bio = $row['bio'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            return true;
        }

        return false;
    }

    /**
     * READ ONE - Get user by ID
     */
    public function readOne() {
        $query = "SELECT * FROM " . $this->table . " WHERE id = :id LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->avatar = $row['avatar'];
            $this->bio = $row['bio'];
            $this->role = $row['role'];
            $this->status = $row['status'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }

        return false;
    }

    /**
     * UPDATE - Update user profile
     */
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET full_name = :full_name,
                    avatar = :avatar,
                    bio = :bio
                WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->full_name = htmlspecialchars(strip_tags($this->full_name));
        $this->avatar = htmlspecialchars(strip_tags($this->avatar));
        $this->bio = htmlspecialchars(strip_tags($this->bio));
        $this->id = htmlspecialchars(strip_tags($this->id));

        // Bind
        $stmt->bindParam(":full_name", $this->full_name);
        $stmt->bindParam(":avatar", $this->avatar);
        $stmt->bindParam(":bio", $this->bio);
        $stmt->bindParam(":id", $this->id);

        if($stmt->execute()) {
            return true;
        }

        return false;
    }

    /**
     * Update password
     */
    public function updatePassword($old_password, $new_password) {
        // First verify old password
        $query = "SELECT password FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!password_verify($old_password, $row['password'])) {
            return false;
        }

        // Update password
        $query = "UPDATE " . $this->table . " SET password = :password WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":id", $this->id);

        return $stmt->execute();
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username) {
        $query = "SELECT id FROM " . $this->table . " WHERE username = :username LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if email exists
     */
    public function emailExists($email) {
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Get user's stories with reaction counts
     */
    public function getStories() {
        $query = "SELECT s.*, 
                    COUNT(DISTINCT r.id) as total_reactions
                FROM stories s
                LEFT JOIN reactions r ON s.id = r.story_id
                WHERE s.author_name = :author_name
                GROUP BY s.id
                ORDER BY s.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":author_name", $this->full_name);
        $stmt->execute();

        return $stmt;
    }

    /**
     * Get user's statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(DISTINCT s.id) as total_stories,
                    SUM(s.views) as total_views,
                    COUNT(DISTINCT r.id) as total_reactions,
                    COUNT(DISTINCT CASE WHEN s.status = 'published' THEN s.id END) as published_stories,
                    COUNT(DISTINCT CASE WHEN s.status = 'draft' THEN s.id END) as draft_stories
                FROM stories s
                LEFT JOIN reactions r ON s.id = r.story_id
                WHERE s.author_name = :author_name";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":author_name", $this->full_name);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->role === self::ROLE_ADMIN;
    }
}
?>
