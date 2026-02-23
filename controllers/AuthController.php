<?php
declare(strict_types=1);

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        $this->render('auth/login', [
            'title' => 'Login',
            'error' => $_SESSION['flash_error'] ?? null,
            'login' => $_SESSION['flash_login'] ?? '',
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_login']);
    }

    public function login(): void
    {
        $login = trim((string)($_POST['login'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $_SESSION['flash_login'] = $login;

        if ($login === '' || $password === '') {
            $_SESSION['flash_error'] = 'Email/username and password are required.';
            header('Location: /index.php?route=login');
            exit;
        }

        $users = new UserModel($this->db);
        $user = $users->findByLogin($login);
        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            $_SESSION['flash_error'] = 'Invalid credentials.';
            header('Location: /index.php?route=login');
            exit;
        }

        if ((int)($user['is_active'] ?? 1) !== 1) {
            $_SESSION['flash_error'] = 'Your account is disabled. Contact admin.';
            header('Location: /index.php?route=login');
            exit;
        }

        $role = (string)($user['role'] ?? 'limited_member');
        if ($role === 'member') {
            $role = 'limited_member';
        }

        $_SESSION['user'] = [
            'user_id' => (int)$user['user_id'],
            'name' => (string)($user['name'] ?? $user['username'] ?? 'Family User'),
            'email' => (string)($user['email'] ?? ''),
            'role' => $role,
            'person_id' => (int)($user['person_id'] ?? 0),
        ];
        if (!empty($_SESSION['user']['person_id'])) {
            $_SESSION['pov_person_id'] = (int)$_SESSION['user']['person_id'];
        }

        $target = $role === 'admin' ? 'admin/dashboard' : 'member/dashboard';
        header('Location: /index.php?route=' . $target);
        exit;
    }

    public function logout(): void
    {
        unset($_SESSION['user']);
        unset($_SESSION['pov_person_id']);
        header('Location: /index.php?route=home');
        exit;
    }
}
