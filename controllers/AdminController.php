<?php
declare(strict_types=1);

final class AdminController extends BaseController
{
    private PersonModel $people;
    private BranchModel $branchesModel;
    private RelationshipEngine $engine;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->people = new PersonModel($db);
        $this->branchesModel = new BranchModel($db);
        $this->engine = new RelationshipEngine($db);
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
        $this->render('admin/family_list', ['title' => 'Family List']);
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
        $person = $personId > 0 ? $this->people->findById($personId) : null;
        $rows = $person ? $this->buildAncestors($personId, 6) : [];
        $this->render('admin/ancestors', [
            'title' => 'Ancestors',
            'route_prefix' => 'admin',
            'person_id' => $personId,
            'person_name' => (string)($person['full_name'] ?? ''),
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

    private function buildAncestors(int $personId, int $maxDepth): array
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
                $rows[] = [
                    'generation' => $depth + 1,
                    'link' => $p['link'],
                    'person_id' => $pid,
                    'name' => (string)$pp['full_name'],
                    'gender' => (string)$pp['gender'],
                ];
                $queue[] = ['id' => $pid, 'depth' => $depth + 1];
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
}
