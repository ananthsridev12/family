<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/PersonModel.php';
require_once __DIR__ . '/models/BranchModel.php';
require_once __DIR__ . '/models/UserModel.php';
require_once __DIR__ . '/models/AttachmentModel.php';
require_once __DIR__ . '/models/NotificationModel.php';
require_once __DIR__ . '/models/EditProposalModel.php';
require_once __DIR__ . '/models/InviteLinkModel.php';
require_once __DIR__ . '/models/PersonAddProposalModel.php';
require_once __DIR__ . '/models/ViewTokenModel.php';
require_once __DIR__ . '/models/ViewCorrectionModel.php';
require_once __DIR__ . '/models/FamilyEventModel.php';
require_once __DIR__ . '/models/AnnouncementModel.php';
require_once __DIR__ . '/models/MoiModel.php';
require_once __DIR__ . '/services/RelationshipEngine.php';
require_once __DIR__ . '/services/ReminderService.php';
require_once __DIR__ . '/controllers/BaseController.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/PublicController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/MemberController.php';
require_once __DIR__ . '/controllers/PersonController.php';
require_once __DIR__ . '/controllers/JoinController.php';
require_once __DIR__ . '/controllers/ViewController.php';

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

function format_inr(float $amount): string
{
    $negative = $amount < 0;
    $amount   = abs($amount);
    $str      = number_format($amount, 2, '.', '');
    [$whole, $dec] = explode('.', $str);
    if (strlen($whole) > 3) {
        $last3  = substr($whole, -3);
        $rest   = substr($whole, 0, -3);
        $chunks = [];
        while (strlen($rest) > 2) {
            $chunks[] = substr($rest, -2);
            $rest     = substr($rest, 0, -2);
        }
        if ($rest !== '') {
            $chunks[] = $rest;
        }
        $whole = implode(',', array_reverse($chunks)) . ',' . $last3;
    }
    return ($negative ? '-' : '') . '₹' . $whole . '.' . $dec;
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

function user_permissions(): array
{
    $raw = (string)(app_user()['permissions'] ?? '{}');
    $parsed = json_decode($raw, true);
    return is_array($parsed) ? $parsed : [];
}

function user_edit_scope(): string
{
    $role = app_user_role();
    if ($role === 'admin' || $role === 'full_editor') {
        return 'all';
    }
    $scope = (string)(user_permissions()['edit_scope'] ?? 'self');
    return in_array($scope, ['none', 'self', 'children', 'grandchildren', 'all'], true) ? $scope : 'self';
}

function user_can_add_person(): bool
{
    $role = app_user_role();
    if ($role === 'admin' || $role === 'full_editor') {
        return true;
    }
    $perms = user_permissions();
    if (!isset($perms['can_add_person'])) {
        return true; // default: allow submission (goes to approval)
    }
    return (bool)$perms['can_add_person'];
}

$route = (string)($_GET['route'] ?? 'home');
$method = (string)($_SERVER['REQUEST_METHOD'] ?? 'GET');

$db = app_db();
$authController   = new AuthController($db);
$publicController = new PublicController($db);
$adminController  = new AdminController($db);
$memberController = new MemberController($db);
$personController = new PersonController($db);
$joinController   = new JoinController($db);
$viewController   = new ViewController($db);

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

    case 'join':
        if ($method === 'POST') {
            $joinController->submit();
        } else {
            $joinController->show();
        }
        break;
    case 'join/welcome':
        $joinController->welcome();
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
    case 'person/check-duplicate':
        require_auth();
        $personController->checkDuplicate();
        break;
    case 'person/attachment':
        require_auth();
        $personController->serveAttachment();
        break;
    case 'person/upload-attachment':
        require_auth();
        $personController->uploadAttachment();
        break;
    case 'person/delete-attachment':
        require_auth();
        $personController->deleteAttachment();
        break;
    case 'person/node-info':
    case 'admin/person-node-info':
    case 'member/person-node-info':
        require_auth();
        $personController->nodeInfo();
        break;
    case 'person/children':
        require_auth();
        $personController->children();
        break;
    case 'person/node-info':
        require_auth();
        $personController->nodeInfo();
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
    case 'admin/wiki-view':
        require_role('admin');
        $adminController->wikiView();
        break;
    case 'admin/svg-tree':
        require_role('admin');
        $adminController->svgTree();
        break;
    case 'admin/map-view':
        require_role('admin');
        $adminController->mapView();
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
        require_any_role(['limited_member', 'full_editor', 'admin']);
        $memberController->addPerson();
        break;
    case 'member/edit-person':
        require_any_role(['limited_member', 'full_editor', 'admin']);
        $memberController->editPerson();
        break;
    case 'member/add-marriage':
        require_any_role(['limited_member', 'full_editor', 'admin']);
        $memberController->addMarriage();
        break;
    case 'member/edit-marriage':
        require_any_role(['limited_member', 'full_editor', 'admin']);
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
    case 'member/wiki-view':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->wikiView();
        break;
    case 'member/svg-tree':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->svgTree();
        break;
    case 'member/map-view':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->mapView();
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

    case 'notifications':
        require_auth();
        $memberController->viewNotifications();
        break;
    case 'notifications/mark-read':
        require_auth();
        $memberController->markNotificationRead();
        break;
    case 'notifications/mark-all-read':
        require_auth();
        $memberController->markAllNotificationsRead();
        break;

    case 'admin/proposals':
        require_role('admin');
        $adminController->pendingProposals();
        break;
    case 'admin/proposal-review':
        require_role('admin');
        $adminController->reviewProposal();
        break;
    case 'admin/approve-proposal':
        require_role('admin');
        $adminController->approveProposal();
        break;
    case 'admin/reject-proposal':
        require_role('admin');
        $adminController->rejectProposal();
        break;

    case 'admin/update-user-permissions':
        require_role('admin');
        $adminController->updateUserPermissions();
        break;

    case 'admin/add-proposals':
        require_role('admin');
        $adminController->addProposalsList();
        break;
    case 'admin/review-add-proposal':
        require_role('admin');
        $adminController->reviewAddProposal();
        break;
    case 'admin/approve-add-proposal':
        require_role('admin');
        $adminController->approveAddProposal();
        break;
    case 'admin/reject-add-proposal':
        require_role('admin');
        $adminController->rejectAddProposal();
        break;

    case 'admin/invite-links':
        require_role('admin');
        $adminController->inviteLinks();
        break;
    case 'admin/create-invite':
        require_role('admin');
        $adminController->createInvite();
        break;
    case 'admin/deactivate-invite':
        require_role('admin');
        $adminController->deactivateInvite();
        break;
    case 'admin/export-gedcom':
        require_role('admin');
        $adminController->exportGedcom();
        break;
    case 'admin/wiki-view':
        require_role('admin');
        $adminController->wikiView();
        break;
    case 'member/wiki-view':
        require_any_role(['limited_member', 'full_editor']);
        $memberController->wikiView();
        break;

    case 'admin/generate-view-token':
        require_role('admin');
        $adminController->generateViewToken();
        break;
    case 'admin/delete-view-token':
        require_role('admin');
        $adminController->deleteViewToken();
        break;
    case 'admin/view-corrections':
        require_role('admin');
        $adminController->viewCorrectionsList();
        break;
    case 'admin/mark-correction-reviewed':
        require_role('admin');
        $adminController->markCorrectionReviewed();
        break;

    case 'view':
        $viewController->show();
        break;
    case 'view/request-correction':
        $viewController->requestCorrection();
        break;

    // Timeline & Memorial (shared, auth required)
    case 'admin/timeline':
    case 'member/timeline':
        require_auth();
        $adminController->timeline();
        break;
    case 'admin/memorial':
    case 'member/memorial':
        require_auth();
        $adminController->memorial();
        break;

    // Family Events
    case 'admin/family-events':
        require_role('admin');
        $adminController->familyEventsList();
        break;
    case 'admin/create-family-event':
        require_role('admin');
        $adminController->createFamilyEvent();
        break;
    case 'admin/delete-family-event':
        require_role('admin');
        $adminController->deleteFamilyEvent();
        break;

    // Moi Register
    case 'admin/moi-list':
        require_role('admin');
        $adminController->moiList();
        break;
    case 'admin/moi-event':
        require_role('admin');
        $adminController->moiByEvent();
        break;
    case 'admin/create-moi':
        require_role('admin');
        $adminController->createMoi();
        break;
    case 'admin/delete-moi':
        require_role('admin');
        $adminController->deleteMoi();
        break;
    case 'admin/export-moi-csv':
        require_role('admin');
        $adminController->exportMoiCsv();
        break;
    case 'admin/submit-moi-as-member':
        require_role('admin');
        $adminController->submitMoiAsProposal();
        break;
    case 'admin/moi-location-search':
        require_role('admin');
        $adminController->moiLocationSearch();
        break;

    // Announcements
    case 'admin/announcements':
        require_role('admin');
        $adminController->announcementsList();
        break;
    case 'admin/create-announcement':
        require_role('admin');
        $adminController->createAnnouncement();
        break;
    case 'admin/delete-announcement':
        require_role('admin');
        $adminController->deleteAnnouncement();
        break;
    case 'admin/toggle-announcement-pin':
        require_role('admin');
        $adminController->toggleAnnouncementPin();
        break;

    // Bulk Import & Export
    case 'admin/bulk-import':
        require_role('admin');
        $adminController->bulkImport();
        break;
    case 'admin/bulk-import-process':
        require_role('admin');
        $adminController->bulkImportProcess();
        break;
    case 'admin/import-template':
        require_role('admin');
        $adminController->importTemplate();
        break;
    case 'admin/export-persons':
        require_role('admin');
        $adminController->exportPersons();
        break;
    case 'admin/save-sibling-order':
        require_role('admin');
        $adminController->saveSiblingOrder();
        break;
    case 'admin/mark-deceased':
        require_role('admin');
        $adminController->markDeceased();
        break;
    case 'admin/mark-alive':
        require_role('admin');
        $adminController->markAlive();
        break;

    default:
        http_response_code(404);
        echo '404 Not Found';
}
