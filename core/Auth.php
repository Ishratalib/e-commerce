<?php

require_once __DIR__ . '/Session.php';

class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function login(string $email, string $password): array
    {
        $sql = "
            SELECT id, name, email, password, role, is_active
            FROM users
            WHERE email = ?
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$email]);

        $user = $stmt->fetch();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        if ((int)$user['is_active'] !== 1) {
            return [
                'success' => false,
                'message' => 'Your account is inactive.'
            ];
        }

        if (!password_verify($password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        // Prevent session fixation
        session_regenerate_id(true);

        Session::set('user_id', $user['id']);
        Session::set('user_name', $user['name']);
        Session::set('user_email', $user['email']);
        Session::set('user_role', $user['role']);

        return [
            'success' => true,
            'message' => 'Login successful.'
        ];
    }

    public function isLoggedIn(): bool
    {
        return Session::has('user_id');
    }

    public function isAdmin(): bool
    {
        return $this->isLoggedIn()
            && Session::get('user_role') === 'admin';
    }

    public function requireAdmin(): void
    {
        if (!$this->isAdmin()) {
            header('Location: /admin/login.php');
            exit;
        }
    }

    public function logout(): void
    {
        Session::start();
        Session::destroy();
    }
}