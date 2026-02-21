<?php
declare(strict_types=1);

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        $name = trim((string)($_POST['name'] ?? ''));
        $role = (string)($_POST['role'] ?? 'member');
        if (!in_array($role, ['admin', 'member'], true)) {
            $role = 'member';
        }

        $_SESSION['user'] = [
            'name' => $name !== '' ? $name : 'Family User',
            'role' => $role,
        ];

        if ($role === 'admin') {
            header('Location: /index.php?route=admin/dashboard');
        } else {
            header('Location: /index.php?route=member/dashboard');
        }
        exit;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        header('Location: /index.php?route=home');
        exit;
    }
}