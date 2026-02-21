<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/PersonModel.php';
require_once __DIR__ . '/models/BranchModel.php';
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
    $currentRole = (string)(app_user()['role'] ?? '');
    if ($currentRole !== $role) {
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

    case 'person/search':
        require_auth();
        $personController->search();
        break;
    case 'person/children':
        require_auth();
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

    case 'member/dashboard':
        require_role('member');
        $memberController->dashboard();
        break;
    case 'member/add-person':
        require_role('member');
        $memberController->addPerson();
        break;
    case 'member/edit-person':
        require_role('member');
        $memberController->editPerson();
        break;
    case 'member/add-marriage':
        require_role('member');
        $memberController->addMarriage();
        break;
    case 'member/person-search':
        require_role('member');
        $personController->search();
        break;
    case 'member/family-list':
        require_role('member');
        $memberController->familyList();
        break;
    case 'member/tree-view':
        require_role('member');
        $memberController->treeView();
        break;
    case 'member/ancestors':
        require_role('member');
        $memberController->ancestors();
        break;
    case 'member/descendants':
        require_role('member');
        $memberController->descendants();
        break;
    case 'member/relationship-finder':
        require_role('member');
        $memberController->relationshipFinder();
        break;
    case 'member/branches':
        require_role('member');
        $memberController->branches();
        break;
    case 'member/reports':
        require_role('member');
        $memberController->reports();
        break;
    case 'member/settings':
        require_role('member');
        $memberController->settings();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}
