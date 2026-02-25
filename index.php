<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/PersonModel.php';
require_once __DIR__ . '/models/BranchModel.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/services/RelationshipEngine.php';
require_once __DIR__ . '/controllers/BaseController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/PublicController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/MemberController.php';
require_once __DIR__ . '/controllers/PersonController.php';

function app_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $cfg = require __DIR__ . '/config/database.php';
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $cfg['host'], $cfg['dbname'], $cfg['charset']);
    $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function app_user(): array
{
    return $_SESSION['user'] ?? [];
}

function app_user_role(): string
{
    $role = (string)(app_user()['role'] ?? '');
    return $role === 'member' ? 'limited_member' : $role;
}

function role_route_prefix(): string
{
    return app_user_role() === 'admin' ? 'admin' : 'member';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['csrf_token'];
}

function verify_csrf(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals((string)$_SESSION['csrf_token'], $token);
}

function current_pov_id(): int
{
    $sessionPov = (int)($_SESSION['pov_person_id'] ?? 0);
    if ($sessionPov > 0) {
        return $sessionPov;
    }
    return (int)(app_user()['person_id'] ?? 0);
}

function available_pov_people(): array
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (empty(app_user())) {
        $cached = [];
        return $cached;
    }
    $stmt = app_db()->query('SELECT person_id, full_name FROM persons ORDER BY full_name ASC LIMIT 500');
    $cached = $stmt->fetchAll() ?: [];
    return $cached;
}

function require_auth(): void
{
    if (empty(app_user())) {
        header('Location: /index.php?route=login');
        exit;
    }
}

function require_role(string $role): void
{
    require_auth();
    if (app_user_role() !== $role) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
}

function require_any_role(array $roles): void
{
    require_auth();
    $currentRole = app_user_role();
    if (!in_array($currentRole, $roles, true)) {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
}

$route = (string)($_GET['route'] ?? 'home');
$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');

$db = app_db();
$authController = new AuthController($db);
$publicController = new PublicController($db);
$adminController = new AdminController($db);
$memberController = new MemberController($db);
$personController = new PersonController($db);

switch ($route) {
    case 'home':
        $publicController->home();
        break;
    case 'about':
        $publicController->about();
        break;
    case 'how-it-works':
        $publicController->howItWorks();
        break;
    case 'features':
        $publicController->features();
        break;
    case 'tamil-relationship-system':
        $publicController->tamilRelationshipSystem();
        break;
    case 'contact':
        $publicController->contact();
        break;

    case 'login':
        if ($method === 'POST') {
            $authController->login();
        } else {
            $authController->showLogin();
        }
        break;
    case 'logout':
        $authController->logout();
        break;
    case 'set-pov':
        require_auth();
        if ($method !== 'POST' || !verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(400);
            echo 'Invalid request';
            break;
        }
        $povId = (int)($_POST['pov_person_id'] ?? 0);
        $_SESSION['pov_person_id'] = $povId > 0 ? $povId : (int)(app_user()['person_id'] ?? 0);
        $redirect = (string)($_POST['redirect_to'] ?? '/index.php?route=member/family-list');
        header('Location: ' . $redirect);
        exit;

    case 'person/search':
        require_auth();
        $personController->search();
        break;
    case 'person/children':
        require_auth();
        $personController->children();
        break;
    case 'admin/person-children':
        require_role('admin');
        $personController->children();
        break;
    case 'member/person-children':
        require_any_role(['limited_member', 'full_editor']);
        $personController->children();
        break;

    case 'admin/dashboard':
        require_role('admin');
        $adminController->dashboard();
        break;
    case 'admin/add-person':
        require_role('admin');
        $adminController->addPerson();
        break;
    case 'admin/family-list':
        require_role('admin');
        $adminController->familyList();
        break;
    case 'admin/person-view':
        require_role('admin');
        $adminController->viewPerson();
        break;
    case 'admin/delete-person':
        require_role('admin');
        $adminController->deletePerson();
        break;
    case 'admin/tree-view':
        require_role('admin');
        $adminController->treeView();
        break;
    case 'admin/ancestors':
        require_role('admin');
        $adminController->ancestors();
        break;
    case 'admin/descendants':
        require_role('admin');
        $adminController->descendants();
        break;
    case 'admin/edit-person':
        require_role('admin');
        $adminController->editPerson();
        break;
    case 'admin/relationship-finder':
        require_role('admin');
        $adminController->relationshipFinder();
        break;
    case 'admin/branches':
        require_role('admin');
        $adminController->branches();
        break;
    case 'admin/reports':
        require_role('admin');
        $adminController->reports();
        break;
    case 'admin/settings':
        require_role('admin');
        $adminController->settings();
        break;
    case 'admin/users':
        require_role('admin');
        $adminController->users();
        break;

    case 'member/dashboard':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->dashboard();
        break;
    case 'member/add-person':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->addPerson();
        break;
    case 'member/edit-person':
        require_any_role(['limited_member', 'full_editor', 'admin']);
        $memberController->editPerson();
        break;
    case 'member/add-marriage':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->addMarriage();
        break;
    case 'member/edit-marriage':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->editMarriage();
        break;
    case 'member/person-search':
        require_any_role(['limited_member', 'full_editor']);
        $personController->search();
        break;
    case 'member/family-list':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->familyList();
        break;
    case 'member/person-view':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->viewPerson();
        break;
    case 'member/tree-view':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->treeView();
        break;
    case 'member/ancestors':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->ancestors();
        break;
    case 'member/descendants':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->descendants();
        break;
    case 'member/relationship-finder':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->relationshipFinder();
        break;
    case 'member/branches':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->branches();
        break;
    case 'member/reports':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->reports();
        break;
    case 'member/settings':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->settings();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}
