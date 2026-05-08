<?php
class Auth {

    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function check() {
        self::start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: /sport-club/login.php");
            exit();
        }
    }

    public static function login($conn, $identifier, $password) {
        $stmt = $conn->prepare("SELECT id, password, full_name FROM admin WHERE identifier = ?");
        $stmt->bind_param("s", $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            self::start();
            $_SESSION['user_id']   = $admin['id'];
            $_SESSION['user_name'] = $admin['full_name'];
            return true;
        }

        return false;
    }

    public static function logout() {
        self::start();
        session_destroy();
        header("Location: /sport-club/login.php");
        exit();
    }

    public static function user() {
        return $_SESSION['user_name'] ?? '';
    }
}