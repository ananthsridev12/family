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
        $this->render('admin/tree_view', ['title' => 'Tree View']);
    }

    public function ancestors(): void
    {
        $this->render('admin/ancestors', ['title' => 'Ancestors']);
    }

    public function descendants(): void
    {
        $this->render('admin/descendants', ['title' => 'Descendants']);
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
            'lang' => (string)($_GET['lang'] ?? 'en'),
        ]);
    }

    public function branches(): void
    {
        $rows = $this->branchesModel->listWithCounts();
        $this->render('admin/branches', ['title' => 'Branches', 'rows' => $rows]);
    }

    public function reports(): void
    {
        $this->render('admin/reports', ['title' => 'Reports']);
    }

    public function settings(): void
    {
        $this->render('admin/settings', ['title' => 'Settings']);
    }
}
