<?php
declare(strict_types=1);

final class JoinController extends BaseController
{
    private InviteLinkModel $invites;
    private PersonModel $people;
    private UserModel $users;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->invites = new InviteLinkModel($db);
        $this->people  = new PersonModel($db);
        $this->users   = new UserModel($db);
    }

    public function show(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $link  = $this->resolveLink($token);
        $this->render('join/wizard', ['link' => $link, 'token' => $token]);
    }

    public function submit(): void
    {
        $token = trim((string)($_POST['token'] ?? ''));
        $link  = $this->resolveLink($token);

        $errors = $this->validate($_POST);
        if ($errors !== []) {
            $this->render('join/wizard', [
                'link'   => $link,
                'token'  => $token,
                'errors' => $errors,
                'old'    => $_POST,
            ]);
            return;
        }

        $fatherId = $this->resolveParent(
            (int)($_POST['father_person_id'] ?? 0),
            trim((string)($_POST['father_name'] ?? '')),
            (int)($_POST['father_birth_year'] ?? 0),
            'male'
        );

        $motherId = $this->resolveParent(
            (int)($_POST['mother_person_id'] ?? 0),
            trim((string)($_POST['mother_name'] ?? '')),
            (int)($_POST['mother_birth_year'] ?? 0),
            'female'
        );

        $birthYear = (int)($_POST['birth_year'] ?? 0);
        $birthMonth = (int)($_POST['birth_month'] ?? 0);
        $birthDay = (int)($_POST['birth_day'] ?? 0);
        $dob = null;
        if ($birthYear > 0 && $birthMonth > 0 && $birthDay > 0) {
            $dob = sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay);
        }

        $personId = $this->people->create([
            ':full_name'        => trim((string)($_POST['full_name'] ?? '')),
            ':gender'           => (string)($_POST['gender'] ?? 'unknown'),
            ':date_of_birth'    => $dob,
            ':birth_year'       => $birthYear > 0 ? $birthYear : null,
            ':date_of_death'    => null,
            ':blood_group'      => null,
            ':occupation'       => null,
            ':mobile'           => trim((string)($_POST['mobile'] ?? '')) ?: null,
            ':email'            => trim((string)($_POST['email'] ?? '')) ?: null,
            ':address'          => null,
            ':current_location' => null,
            ':native_location'  => null,
            ':is_alive'         => 1,
            ':father_id'        => $fatherId ?: null,
            ':mother_id'        => $motherId ?: null,
            ':spouse_id'        => null,
            ':branch_id'        => null,
            ':birth_order'      => null,
            ':created_by'       => null,
            ':editable_scope'   => 'all',
            ':is_locked'        => 0,
            ':is_deleted'       => 0,
        ]);

        $username = trim((string)($_POST['username'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $name     = trim((string)($_POST['full_name'] ?? ''));

        $newUserId = $this->users->create([
            ':username'      => $username,
            ':name'          => $name,
            ':email'         => $email,
            ':password_hash' => password_hash((string)($_POST['password'] ?? ''), PASSWORD_DEFAULT),
            ':role'          => 'member',
            ':is_active'     => 1,
            ':person_id'     => $personId,
        ]);

        // Apply default permissions set on the invite link
        $defaultPermsRaw = (string)($link['default_permissions'] ?? '');
        if ($defaultPermsRaw !== '' && $newUserId > 0) {
            $defaultPerms = json_decode($defaultPermsRaw, true);
            if (is_array($defaultPerms) && $defaultPerms !== []) {
                try {
                    $this->users->updatePermissions($newUserId, $defaultPerms);
                } catch (Throwable $e) {}
            }
        }

        $this->invites->incrementUsed((int)$link['link_id']);

        $user = $this->users->findByLogin($email);
        if ($user !== null) {
            $_SESSION['user'] = $user;
        }

        header('Location: /index.php?route=join/welcome');
        exit;
    }

    public function welcome(): void
    {
        $this->render('join/welcome', []);
    }

    private function resolveLink(string $token): array
    {
        if ($token === '') {
            $this->abort('No invite token provided. Please use the link you were given.');
        }
        $link = $this->invites->findByToken($token);
        if ($link === null || !$this->invites->isValid($link)) {
            $this->abort('This invite link is invalid, expired, or has reached its usage limit.');
        }
        return $link;
    }

    private function resolveParent(int $existingId, string $name, int $birthYear, string $gender): int
    {
        if ($existingId > 0) {
            return $existingId;
        }
        if ($name === '') {
            return 0;
        }
        return $this->people->create([
            ':full_name'        => $name,
            ':gender'           => $gender,
            ':date_of_birth'    => null,
            ':birth_year'       => $birthYear > 0 ? $birthYear : null,
            ':date_of_death'    => null,
            ':blood_group'      => null,
            ':occupation'       => null,
            ':mobile'           => null,
            ':email'            => null,
            ':address'          => null,
            ':current_location' => null,
            ':native_location'  => null,
            ':is_alive'         => 1,
            ':father_id'        => null,
            ':mother_id'        => null,
            ':spouse_id'        => null,
            ':branch_id'        => null,
            ':birth_order'      => null,
            ':created_by'       => null,
            ':editable_scope'   => 'all',
            ':is_locked'        => 0,
            ':is_deleted'       => 0,
        ]);
    }

    private function validate(array $p): array
    {
        $errors = [];
        $fullName = trim((string)($p['full_name'] ?? ''));
        if (mb_strlen($fullName) < 2) {
            $errors[] = 'Full name must be at least 2 characters.';
        }
        $gender = (string)($p['gender'] ?? '');
        if (!in_array($gender, ['male', 'female', 'other', 'unknown'], true)) {
            $errors[] = 'Please select a gender.';
        }
        $email = trim((string)($p['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        $username = trim((string)($p['username'] ?? ''));
        if (mb_strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters.';
        }
        $password = (string)($p['password'] ?? '');
        if (mb_strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($password !== (string)($p['confirm_password'] ?? '')) {
            $errors[] = 'Passwords do not match.';
        }
        return $errors;
    }

    private function abort(string $msg): void
    {
        http_response_code(400);
        $this->render('join/error', ['message' => $msg]);
        exit;
    }
}
