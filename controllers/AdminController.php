<?php
declare(strict_types=1);

final class AdminController extends BaseController
{
    private PersonModel $people;
    private BranchModel $branchesModel;
    private RelationshipEngine $engine;
    private UserModel $users;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->people = new PersonModel($db);
        $this->branchesModel = new BranchModel($db);
        $this->engine = new RelationshipEngine($db);
        $this->users = new UserModel($db);
    }

    public function dashboard(): void
    {
        $this->render('admin/dashboard', ['title' => 'Admin Dashboard']);
    }

    public function addPerson(): void
    {
        $this->render('admin/person_add', ['title' => 'Add Person']);
    }

    public function familyList(): void
    {
        $items = $this->people->all(500);
        foreach ($items as &$item) {
            $item['age'] = $this->calculateAge($item);
        }
        unset($item);
        $this->render('admin/family_list', [
            'title' => 'Family List',
            'items' => $items,
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function treeView(): void
    {
        $rootId = (int)($_GET['person_id'] ?? current_pov_id());
        $root = $rootId > 0 ? $this->people->findById($rootId) : null;
        $this->render('admin/tree_view', [
            'title' => 'Tree View',
            'root_id' => $rootId,
            'root_name' => (string)($root['full_name'] ?? ''),
        ]);
    }

    public function ancestors(): void
    {
        $personId = (int)($_GET['person_id'] ?? current_pov_id());
        $side = (string)($_GET['side'] ?? 'any');
        if (!in_array($side, ['any', 'paternal', 'maternal'], true)) {
            $side = 'any';
        }
        $person = $personId > 0 ? $this->people->findById($personId) : null;
        $rows = $person ? $this->buildAncestors($personId, 6, $side) : [];
        $this->render('admin/ancestors', [
            'title' => 'Ancestors',
            'route_prefix' => 'admin',
            'person_id' => $personId,
            'person_name' => (string)($person['full_name'] ?? ''),
            'side' => $side,
            'rows' => $rows,
        ]);
    }

    public function descendants(): void
    {
        $personId = (int)($_GET['person_id'] ?? current_pov_id());
        $person = $personId > 0 ? $this->people->findById($personId) : null;
        $rows = $person ? $this->buildDescendants($personId, 6) : [];
        $this->render('admin/descendants', [
            'title' => 'Descendants',
            'route_prefix' => 'admin',
            'person_id' => $personId,
            'person_name' => (string)($person['full_name'] ?? ''),
            'rows' => $rows,
        ]);
    }

    public function relationshipFinder(): void
    {
        $relation = null;
        $reverseRelation = null;
        $personAId = current_pov_id();
        $personBId = 0;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usePov = isset($_POST['use_pov_as_a']) ? 1 : 0;
            $postedA = (int)($_POST['person_a_id'] ?? 0);
            $personAId = $usePov === 1 ? current_pov_id() : $postedA;
            $personBId = (int)($_POST['person_b_id'] ?? 0);
            if ($personAId > 0 && $personBId > 0) {
                $relation = $this->engine->resolve($personAId, $personBId);
                $reverseRelation = $this->engine->resolve($personBId, $personAId);
            }
        }

        $personA = $personAId > 0 ? $this->people->findById($personAId) : null;
        $personB = $personBId > 0 ? $this->people->findById($personBId) : null;

        $this->render('admin/relationship_finder', [
            'title' => 'Relationship Finder',
            'relation' => $relation,
            'reverse_relation' => $reverseRelation,
            'person_a_id' => $personAId,
            'person_b_id' => $personBId,
            'person_a_name' => (string)($personA['full_name'] ?? ''),
            'person_b_name' => (string)($personB['full_name'] ?? ''),
            'lang' => (string)($_GET['lang'] ?? ($_SESSION['lang'] ?? 'en')),
        ]);
    }

    public function branches(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $action = (string)($_POST['action'] ?? 'add');
            $name = trim((string)($_POST['branch_name'] ?? ''));
            if ($name !== '') {
                if ($action === 'edit') {
                    $id = (int)($_POST['branch_id'] ?? 0);
                    if ($id > 0) {
                        $this->branchesModel->update($id, $name);
                    }
                } else {
                    $this->branchesModel->create($name);
                }
            }
            header('Location: /index.php?route=admin/branches');
            exit;
        }

        $rows = $this->branchesModel->listWithCounts();
        $this->render('admin/branches', ['title' => 'Branches', 'rows' => $rows]);
    }

    public function reports(): void
    {
        $this->render('admin/reports', ['title' => 'Reports']);
    }

    public function settings(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $lang = (string)($_POST['lang'] ?? 'en');
            if (!in_array($lang, ['en', 'ta'], true)) {
                $lang = 'en';
            }
            $_SESSION['lang'] = $lang;
            $_SESSION['flash_success'] = 'Settings updated.';
            header('Location: /index.php?route=admin/settings');
            exit;
        }

        $this->render('admin/settings', ['title' => 'Settings']);
    }

    public function deletePerson(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(400);
            echo 'Invalid request';
            exit;
        }

        $id = (int)($_POST['person_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid person id.';
            header('Location: /index.php?route=admin/family-list');
            exit;
        }

        $this->people->softDelete($id);
        $_SESSION['flash_success'] = 'Person deleted.';
        header('Location: /index.php?route=admin/family-list');
        exit;
    }

    public function users(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
                $_SESSION['flash_error'] = 'Invalid CSRF token.';
                header('Location: /index.php?route=admin/users');
                exit;
            }

            $action = (string)($_POST['action'] ?? 'create');
            if ($action === 'update') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $role = (string)($_POST['role'] ?? 'limited_member');
                $isActive = isset($_POST['is_active']);
                $personId = (int)($_POST['person_id'] ?? 0);
                $personId = $personId > 0 ? $personId : null;

                if ($userId > 0 && $userId !== (int)(app_user()['user_id'] ?? 0)) {
                    if (!in_array($role, ['admin', 'full_editor', 'limited_member'], true)) {
                        $role = 'limited_member';
                    }
                    if ($personId !== null && $this->people->findById($personId) === null) {
                        $_SESSION['flash_error'] = 'Selected person not found.';
                        header('Location: /index.php?route=admin/users');
                        exit;
                    }
                    $this->users->updateRoleStatusAndPerson($userId, $role, $isActive, $personId);
                }
                header('Location: /index.php?route=admin/users');
                exit;
            }

            $name = trim((string)($_POST['name'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $username = trim((string)($_POST['username'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $role = (string)($_POST['role'] ?? 'limited_member');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $personId = (int)($_POST['person_id'] ?? 0);
            $personId = $personId > 0 ? $personId : null;

            if ($name === '' || $email === '' || $password === '') {
                $_SESSION['flash_error'] = 'Name, email, and password are required.';
                header('Location: /index.php?route=admin/users');
                exit;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['flash_error'] = 'Invalid email address.';
                header('Location: /index.php?route=admin/users');
                exit;
            }
            if (!in_array($role, ['admin', 'full_editor', 'limited_member'], true)) {
                $role = 'limited_member';
            }
            if ($personId !== null && $this->people->findById($personId) === null) {
                $_SESSION['flash_error'] = 'Selected person not found.';
                header('Location: /index.php?route=admin/users');
                exit;
            }

            if ($username === '') {
                $username = strstr($email, '@', true) ?: $email;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            try {
                $this->users->create([
                    ':username' => $username,
                    ':name' => $name,
                    ':email' => $email,
                    ':password_hash' => $hash,
                    ':role' => $role,
                    ':is_active' => $isActive,
                    ':person_id' => $personId,
                ]);
                $_SESSION['flash_success'] = 'User created.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = 'Failed to create user: ' . $e->getMessage();
            }
            header('Location: /index.php?route=admin/users');
            exit;
        }

        $rows = $this->users->list(200);
        $personIds = [];
        foreach ($rows as $row) {
            $pid = (int)($row['person_id'] ?? 0);
            if ($pid > 0) {
                $personIds[] = $pid;
            }
        }
        $personMap = [];
        foreach ($this->people->findByIds($personIds) as $p) {
            $personMap[(int)$p['person_id']] = (string)$p['full_name'];
        }

        $this->render('admin/users', [
            'title' => 'Users',
            'rows' => $rows,
            'person_map' => $personMap,
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    private function buildAncestors(int $personId, int $maxDepth, string $side = 'any'): array
    {
        $rows = [];
        $queue = [['id' => $personId, 'depth' => 0]];
        $seen = [];
        while (!empty($queue)) {
            $node = array_shift($queue);
            $id = (int)$node['id'];
            $depth = (int)$node['depth'];
            if ($depth >= $maxDepth) {
                continue;
            }
            $person = $this->people->findById($id);
            if ($person === null) {
                continue;
            }
            $parents = [
                ['id' => (int)($person['father_id'] ?? 0), 'link' => 'Father'],
                ['id' => (int)($person['mother_id'] ?? 0), 'link' => 'Mother'],
            ];
            foreach ($parents as $p) {
                $pid = (int)$p['id'];
                if ($pid <= 0 || isset($seen[$pid])) {
                    continue;
                }
                $seen[$pid] = true;
                $pp = $this->people->findById($pid);
                if ($pp === null) {
                    continue;
                }
                $firstEdge = $depth === 0 ? strtolower((string)$p['link']) : (string)($node['first_edge'] ?? 'direct');
                if ($side === 'any' || ($side === 'paternal' && $firstEdge === 'father') || ($side === 'maternal' && $firstEdge === 'mother')) {
                    $rows[] = [
                        'generation' => $depth + 1,
                        'link' => $this->ancestorLabel($depth + 1, (string)$pp['gender']),
                        'person_id' => $pid,
                        'name' => (string)$pp['full_name'],
                        'gender' => (string)$pp['gender'],
                    ];
                }
                $queue[] = ['id' => $pid, 'depth' => $depth + 1, 'first_edge' => $firstEdge];
            }
        }
        return $rows;
    }

    private function buildDescendants(int $personId, int $maxDepth): array
    {
        $rows = [];
        $queue = [['id' => $personId, 'depth' => 0]];
        while (!empty($queue)) {
            $node = array_shift($queue);
            $id = (int)$node['id'];
            $depth = (int)$node['depth'];
            if ($depth >= $maxDepth) {
                continue;
            }
            $children = $this->people->childrenOf($id);
            foreach ($children as $child) {
                $cid = (int)$child['person_id'];
                $cp = $this->people->findById($cid);
                if ($cp === null) {
                    continue;
                }
                $rows[] = [
                    'generation' => $depth + 1,
                    'person_id' => $cid,
                    'name' => (string)$cp['full_name'],
                    'gender' => (string)$cp['gender'],
                ];
                $queue[] = ['id' => $cid, 'depth' => $depth + 1];
            }
        }
        return $rows;
    }

    private function ancestorLabel(int $distance, string $gender): string
    {
        $isFemale = ($gender === 'female');
        if ($distance <= 1) {
            return $isFemale ? 'Mother' : 'Father';
        }
        if ($distance === 2) {
            return $isFemale ? 'Grandmother' : 'Grandfather';
        }
        if ($distance === 3) {
            return $isFemale ? 'Great Grandmother' : 'Great Grandfather';
        }
        $n = $distance - 2;
        return $n . 'th ' . ($isFemale ? 'Great Grandmother' : 'Great Grandfather');
    }

    private function calculateAge(array $person): ?int
    {
        $dobRaw = trim((string)($person['date_of_birth'] ?? ''));
        $dodRaw = trim((string)($person['date_of_death'] ?? ''));
        if ($dobRaw !== '') {
            try {
                $dob = new DateTimeImmutable($dobRaw);
                $end = $dodRaw !== '' ? new DateTimeImmutable($dodRaw) : new DateTimeImmutable('today');
                if ($end < $dob) {
                    return null;
                }
                return $dob->diff($end)->y;
            } catch (Throwable) {
                return null;
            }
        }
        $by = (int)($person['birth_year'] ?? 0);
        if ($by > 0) {
            return max(0, (int)date('Y') - $by);
        }
        return null;
    }
}
