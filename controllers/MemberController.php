<?php
declare(strict_types=1);

final class MemberController extends BaseController
{
    private BranchModel $branchesModel;
    private RelationshipEngine $engine;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->branchesModel = new BranchModel($db);
        $this->engine = new RelationshipEngine($db);
    }

    public function dashboard(): void
    {
        $this->render('member/dashboard', ['title' => 'Member Dashboard']);
    }

    public function addPerson(): void
    {
        $this->render('member/person_add', ['title' => 'Add Person']);
    }

    public function familyList(): void
    {
        $this->render('member/family_list', ['title' => 'Family List']);
    }

    public function treeView(): void
    {
        $this->render('member/tree_view', ['title' => 'Tree View']);
    }

    public function ancestors(): void
    {
        $this->render('member/ancestors', ['title' => 'Ancestors']);
    }

    public function descendants(): void
    {
        $this->render('member/descendants', ['title' => 'Descendants']);
    }

    public function relationshipFinder(): void
    {
        $relation = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $a = (int)($_POST['person_a_id'] ?? 0);
            $b = (int)($_POST['person_b_id'] ?? 0);
            if ($a > 0 && $b > 0) {
                $relation = $this->engine->resolve($a, $b);
            }
        }

        $this->render('member/relationship_finder', [
            'title' => 'Relationship Finder',
            'relation' => $relation,
            'lang' => (string)($_GET['lang'] ?? 'en'),
        ]);
    }

    public function branches(): void
    {
        $rows = $this->branchesModel->listWithCounts();
        $this->render('member/branches', ['title' => 'Branches', 'rows' => $rows]);
    }

    public function reports(): void
    {
        $this->render('member/reports', ['title' => 'Reports']);
    }

    public function settings(): void
    {
        $this->render('member/settings', ['title' => 'Settings']);
    }
}