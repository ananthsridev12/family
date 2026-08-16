<?php
declare(strict_types=1);

final class AdminController extends BaseController
{
    private PersonModel $people;
    private BranchModel $branchesModel;
    private RelationshipEngine $engine;
    private UserModel $users;
    private AttachmentModel $attachments;
    private NotificationModel $notifications;
    private EditProposalModel $proposals;
    private InviteLinkModel $invites;
    private PersonAddProposalModel $addProposals;
    private ViewTokenModel $viewTokens;
    private ViewCorrectionModel $viewCorrections;
    private FamilyEventModel $familyEvents;
    private AnnouncementModel $announcementsModel;
    private MoiModel $moi;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->people              = new PersonModel($db);
        $this->branchesModel       = new BranchModel($db);
        $this->engine              = new RelationshipEngine($db);
        $this->users               = new UserModel($db);
        $this->attachments         = new AttachmentModel($db);
        $this->notifications       = new NotificationModel($db);
        $this->proposals           = new EditProposalModel($db);
        $this->invites             = new InviteLinkModel($db);
        $this->addProposals        = new PersonAddProposalModel($db);
        $this->viewTokens          = new ViewTokenModel($db);
        $this->viewCorrections     = new ViewCorrectionModel($db);
        $this->familyEvents        = new FamilyEventModel($db);
        $this->announcementsModel  = new AnnouncementModel($db);
        $this->moi                 = new MoiModel($db);
    }

    public function dashboard(): void
    {
        $userId = (int)(app_user()['user_id'] ?? 0);
        try { (new ReminderService($this->db))->generateForUser($userId); } catch (Throwable $e) {}
        $unread = 0;
        $pendingProposals = 0;
        $pendingAddProposals = 0;
        try { $unread = $this->notifications->countUnread($userId); } catch (Throwable $e) {}
        try { $pendingProposals = $this->proposals->countPending(); } catch (Throwable $e) {}
        try { $pendingAddProposals = $this->addProposals->countPending(); } catch (Throwable $e) {}
        $announcements = [];
        try { $announcements = $this->announcementsModel->list(5); } catch (Throwable $e) {}
        $upcomingEvents = $this->collectUpcomingEvents(30);
        $this->render('admin/dashboard', [
            'title'                  => 'Admin Dashboard',
            'stats'                  => $this->collectStats(),
            'unread_count'           => $unread,
            'pending_proposals'      => $pendingProposals,
            'pending_add_proposals'  => $pendingAddProposals,
            'announcements'          => $announcements,
            'upcoming_events'        => $upcomingEvents,
        ]);
    }

    public function addPerson(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->createPersonFromAdmin();
            return;
        }

        $this->render('member/person_add', [
            'title' => 'Add Person',
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function familyList(): void
    {
        $items = $this->people->allWithRelations(500);
        $povId = current_pov_id();
        foreach ($items as &$item) {
            $item['age'] = $this->calculateAge($item);
            if ($povId > 0) {
                $rel = $this->engine->resolve($povId, (int)$item['person_id']);
                $en = trim((string)($rel['title_en'] ?? 'Unknown'));
                $ta = trim((string)($rel['title_ta'] ?? ''));
                $item['relationship_status'] = ($ta !== '' && $ta !== $en) ? ($en . ' / ' . $ta) : $en;
            } else {
                $item['relationship_status'] = '-';
            }
        }
        unset($item);

        $personIds = array_column($items, 'person_id');
        $photoMap = [];
        try { $photoMap = $this->attachments->findFirstPhotosByPersonIds($personIds); } catch (Throwable $e) {}
        foreach ($items as &$item) {
            $item['photo_id'] = (int)($photoMap[(int)$item['person_id']] ?? 0);
        }
        unset($item);

        $this->render('admin/family_list', [
            'title'    => 'Family List',
            'items'    => $items,
            'error'    => $_SESSION['flash_error'] ?? null,
            'success'  => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function editPerson(): void
    {
        $id = (int)($_GET['id'] ?? $_POST['person_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid person id.';
            header('Location: /index.php?route=admin/family-list');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
                $_SESSION['flash_error'] = 'Invalid CSRF token.';
                header('Location: /index.php?route=admin/edit-person&id=' . $id);
                exit;
            }

            $person = $this->people->findById($id);
            if ($person === null) {
                $_SESSION['flash_error'] = 'Person not found.';
                header('Location: /index.php?route=admin/family-list');
                exit;
            }

            $fullName = trim((string)($_POST['full_name'] ?? ''));
            if ($fullName === '') {
                $_SESSION['flash_error'] = 'Full name is required.';
                header('Location: /index.php?route=admin/edit-person&id=' . $id);
                exit;
            }

            $gender = (string)($_POST['gender'] ?? 'unknown');
            if (!in_array($gender, ['male', 'female', 'other', 'unknown'], true)) {
                $gender = 'unknown';
            }
            $fatherPersonId = (int)($_POST['father_person_id'] ?? 0);
            $motherPersonId = (int)($_POST['mother_person_id'] ?? 0);
            $spousePersonId = (int)($_POST['spouse_person_id'] ?? 0);
            $spouseMarriageDate = $this->normalizeDate($_POST['spouse_marriage_date'] ?? null);

            $this->people->update($id, [
                ':full_name' => $fullName,
                ':gender' => $gender,
                ':date_of_birth' => $this->normalizeDate($_POST['date_of_birth'] ?? null),
                ':birth_year' => $this->normalizeInt($_POST['birth_year'] ?? null),
                ':birth_order' => $this->normalizeInt($_POST['birth_order'] ?? null),
                ':date_of_death' => $this->normalizeDate($_POST['date_of_death'] ?? null),
                ':blood_group' => $this->nullableString($_POST['blood_group'] ?? null),
                ':occupation' => $this->nullableString($_POST['occupation'] ?? null),
                ':mobile' => $this->nullableString($_POST['mobile'] ?? null),
                ':email' => $this->nullableString($_POST['email'] ?? null),
                ':address' => $this->nullableString($_POST['address'] ?? null),
                ':current_location' => $this->nullableString($_POST['current_location'] ?? null),
                ':native_location' => $this->nullableString($_POST['native_location'] ?? null),
                ':is_alive' => isset($_POST['is_alive']) ? 1 : 0,
            ]);

            if ($fatherPersonId > 0) {
                $this->linkParentChild($fatherPersonId, $id, 'father');
            }
            if ($motherPersonId > 0) {
                $this->linkParentChild($motherPersonId, $id, 'mother');
            }
            if ($spousePersonId > 0 && $spousePersonId !== $id) {
                $this->applyRelation($id, $spousePersonId, 'spouse', 'father', null, $spouseMarriageDate);
            }

            $_SESSION['flash_success'] = 'Person updated.';
            header('Location: /index.php?route=admin/edit-person&id=' . $id);
            exit;
        }

        $person = $this->people->findById($id);
        if ($person === null) {
            $_SESSION['flash_error'] = 'Person not found.';
            header('Location: /index.php?route=admin/family-list');
            exit;
        }

        $this->render('member/person_edit', [
            'title' => 'Edit Person',
            'person' => $person,
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function viewPerson(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid person id.';
            header('Location: /index.php?route=admin/family-list');
            exit;
        }
        $person = $this->people->findWithRelations($id);
        if ($person === null) {
            $_SESSION['flash_error'] = 'Person not found.';
            header('Location: /index.php?route=admin/family-list');
            exit;
        }
        $attachments = [];
        try { $attachments = $this->attachments->findByPersonId($id); } catch (Throwable $e) {}
        $viewTokens = [];
        try { $viewTokens = $this->viewTokens->listByPerson($id); } catch (Throwable $e) {}
        $children = [];
        try { $children = $this->people->childrenOf($id); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $this->render('admin/person_view', [
            'title'       => 'Person Profile',
            'person'      => $person,
            'attachments' => $attachments,
            'viewTokens'  => $viewTokens,
            'children'    => $children,
            'flash'       => $flash,
        ]);
    }

    public function wikiView(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /index.php?route=admin/family-list');
            exit;
        }
        $person = $this->people->findWithRelations($id);
        if ($person === null) {
            header('Location: /index.php?route=admin/family-list');
            exit;
        }

        $ancestorTree = $this->people->ancestorTree($id, 6);

        $children = [];
        try { $children = $this->people->childrenOf($id); } catch (Throwable $e) {}
        $siblings = [];
        try {
            $siblings = $this->people->siblingsOf(
                $id,
                (int)($person['father_id'] ?? 0),
                (int)($person['mother_id'] ?? 0)
            );
        } catch (Throwable $e) {}

        $profile_photo_id = 0;
        try {
            $photos = $this->attachments->findFirstPhotosByPersonIds([$id]);
            $profile_photo_id = (int)($photos[$id] ?? 0);
        } catch (Throwable $e) {}

        $this->render('shared/wiki_view', [
            'title'             => htmlspecialchars((string)$person['full_name'], ENT_QUOTES, 'UTF-8') . ' — Wiki Profile',
            'person'            => $person,
            'ancestorTree'      => $ancestorTree,
            'children'          => $children,
            'siblings'          => $siblings,
            'profileRoute'      => 'admin/person-view',
            'wikiRoute'         => 'admin/wiki-view',
            'childrenAjaxRoute' => 'admin/person-children',
            'profile_photo_id'  => $profile_photo_id,
        ]);
    }

    private function createPersonFromAdmin(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=admin/add-person');
            exit;
        }

        $existingPersonId = (int)($_POST['existing_person_id'] ?? 0);
        $referencePersonId = (int)($_POST['reference_person_id'] ?? 0);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $gender = (string)($_POST['gender'] ?? 'unknown');
        $dateOfBirth = $this->normalizeDate($_POST['date_of_birth'] ?? null);
        $birthYear = $this->normalizeInt($_POST['birth_year'] ?? null);
        $birthOrder = $this->normalizeInt($_POST['birth_order'] ?? null);
        $dateOfDeath = $this->normalizeDate($_POST['date_of_death'] ?? null);
        $currentLocation = $this->nullableString($_POST['current_location'] ?? null);
        $nativeLocation = $this->nullableString($_POST['native_location'] ?? null);
        $bloodGroup = $this->nullableString($_POST['blood_group'] ?? null);
        $occupation = $this->nullableString($_POST['occupation'] ?? null);
        $mobile = $this->nullableString($_POST['mobile'] ?? null);
        $email = $this->nullableString($_POST['email'] ?? null);
        $address = $this->nullableString($_POST['address'] ?? null);
        $isAlive = isset($_POST['is_alive']) ? 1 : 0;
        $relationType = (string)($_POST['relation_type'] ?? 'none');
        $parentType = (string)($_POST['parent_type'] ?? 'father');
        $parentPersonId = (int)($_POST['parent_person_id'] ?? 0);
        $parentLinkType = (string)($_POST['parent_link_type'] ?? 'father');
        $fatherPersonId = (int)($_POST['father_person_id'] ?? 0);
        $motherPersonId = (int)($_POST['mother_person_id'] ?? 0);
        $spousePersonId = (int)($_POST['spouse_person_id'] ?? 0);
        $spouseMarriageDate = $this->normalizeDate($_POST['spouse_marriage_date'] ?? null);

        if (!in_array($gender, ['male', 'female', 'other', 'unknown'], true)) {
            $gender = 'unknown';
        }
        if (!in_array($relationType, ['none', 'child', 'spouse', 'father', 'mother', 'brother', 'sister', 'grandfather', 'grandmother'], true)) {
            $relationType = 'none';
        }
        if (!in_array($parentType, ['father', 'mother', 'adoptive', 'step'], true)) {
            $parentType = 'father';
        }
        if (!in_array($parentLinkType, ['father', 'mother', 'adoptive', 'step'], true)) {
            $parentLinkType = 'father';
        }

        if ($existingPersonId <= 0 && $fullName === '') {
            $_SESSION['flash_error'] = 'Select existing person or enter new full name.';
            header('Location: /index.php?route=admin/add-person');
            exit;
        }

        $this->db->beginTransaction();
        try {
            $targetPersonId = $existingPersonId;
            if ($targetPersonId <= 0) {
                $targetPersonId = $this->people->create([
                    ':full_name' => $fullName,
                    ':gender' => $gender,
                    ':date_of_birth' => $dateOfBirth,
                    ':birth_year' => $birthYear,
                    ':birth_order' => $birthOrder,
                    ':date_of_death' => $dateOfDeath,
                    ':blood_group' => $bloodGroup,
                    ':occupation' => $occupation,
                    ':mobile' => $mobile,
                    ':email' => $email,
                    ':address' => $address,
                    ':current_location' => $currentLocation,
                    ':native_location' => $nativeLocation,
                    ':is_alive' => $isAlive,
                    ':father_id' => null,
                    ':mother_id' => null,
                    ':spouse_id' => null,
                    ':branch_id' => null,
                    ':created_by' => (int)(app_user()['user_id'] ?? 0) ?: null,
                    ':editable_scope' => 'self_branch',
                    ':is_locked' => 0,
                    ':is_deleted' => 0,
                ]);
            }

            if ($parentPersonId > 0) {
                $this->linkParentChild($parentPersonId, $targetPersonId, $parentLinkType);
            }
            if ($fatherPersonId > 0) {
                $this->linkParentChild($fatherPersonId, $targetPersonId, 'father');
            }
            if ($motherPersonId > 0) {
                $this->linkParentChild($motherPersonId, $targetPersonId, 'mother');
            }
            if ($spousePersonId > 0 && $spousePersonId !== $targetPersonId) {
                $this->applyRelation($targetPersonId, $spousePersonId, 'spouse', 'father', null, $spouseMarriageDate);
            }

            $anchorId = $referencePersonId > 0 ? $referencePersonId : (int)$targetPersonId;
            if ($relationType !== 'none' && $anchorId > 0 && $targetPersonId > 0) {
                $this->applyRelation($anchorId, $targetPersonId, $relationType, $parentType, $birthOrder, $spouseMarriageDate);
            }

            $this->db->commit();
            $_SESSION['flash_success'] = 'Person saved successfully.';
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $_SESSION['flash_error'] = $e->getMessage();
        }

        header('Location: /index.php?route=admin/add-person');
        exit;
    }

    public function treeView(): void
    {
        $rootId = (int)($_GET['person_id'] ?? current_pov_id());
        $root = $rootId > 0 ? $this->people->findById($rootId) : null;
        $this->render('admin/tree_view', [
            'title' => 'Tree View',
            'root_id' => $rootId,
            'root_name' => (string)($root['full_name'] ?? ''),
            'route_prefix' => 'admin',
        ]);
    }

    public function svgTree(): void
    {
        $rootId = (int)($_GET['person_id'] ?? current_pov_id());
        $root = $rootId > 0 ? $this->people->findById($rootId) : null;
        $this->render('shared/svg_tree', [
            'title'             => 'Visual Tree',
            'root_id'           => $rootId,
            'root_name'         => (string)($root['full_name'] ?? ''),
            'profileRoute'      => 'admin/person-view',
            'wikiRoute'         => 'admin/wiki-view',
            'childrenAjaxRoute' => 'admin/person-children',
        ]);
    }

    public function mapView(): void
    {
        $locations = [];
        try {
            $stmt = $this->db->prepare(
                "SELECT person_id, full_name, current_location, native_location
                 FROM persons
                 WHERE (is_deleted=0 OR is_deleted IS NULL)
                   AND (
                     (current_location IS NOT NULL AND current_location != '')
                     OR (native_location IS NOT NULL AND native_location != '')
                   )
                 ORDER BY full_name ASC LIMIT 500"
            );
            $stmt->execute();
            $locations = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {}
        $this->render('shared/map_view', [
            'title'        => 'Family Map',
            'locations'    => $locations,
            'profileRoute' => 'admin/person-view',
            'wikiRoute'    => 'admin/wiki-view',
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
        $path = [];
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

        if ($personAId > 0 && $personBId > 0) {
            try { $path = $this->people->findRelationPath($personAId, $personBId); } catch (Throwable $e) {}
        }

        $personA = $personAId > 0 ? $this->people->findById($personAId) : null;
        $personB = $personBId > 0 ? $this->people->findById($personBId) : null;

        $this->render('admin/relationship_finder', [
            'title'          => 'Relationship Finder',
            'relation'       => $relation,
            'reverse_relation' => $reverseRelation,
            'path'           => $path,
            'person_a_id'    => $personAId,
            'person_b_id'    => $personBId,
            'person_a_name'  => (string)($personA['full_name'] ?? ''),
            'person_b_name'  => (string)($personB['full_name'] ?? ''),
            'lang'           => (string)($_GET['lang'] ?? ($_SESSION['lang'] ?? 'en')),
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
        $this->render('admin/reports', [
            'title' => 'Reports',
            'stats' => $this->collectStats(),
        ]);
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
        $queue = [['id' => $personId, 'depth' => 0, 'first_edge' => 'direct']];
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
                $sideLabel = $firstEdge === 'father' ? 'Paternal' : ($firstEdge === 'mother' ? 'Maternal' : 'Any');
                $matchesSide = $side === 'any'
                    || ($side === 'paternal' && $firstEdge === 'father')
                    || ($side === 'maternal' && $firstEdge === 'mother');
                if ($matchesSide) {
                    $rows[] = [
                        'generation' => $depth + 1,
                        'link' => $this->ancestorLabel($depth + 1, (string)$pp['gender']),
                        'side' => $sideLabel,
                        'person_id' => $pid,
                        'name' => (string)$pp['full_name'],
                        'gender' => (string)$pp['gender'],
                    ];
                }
                if ($matchesSide) {
                    $queue[] = ['id' => $pid, 'depth' => $depth + 1, 'first_edge' => $firstEdge];
                }
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

    private function linkParentChild(int $parentId, int $childId, string $parentType): void
    {
        $parent = $this->people->findById($parentId);
        $spouseId = (int)($parent['spouse_id'] ?? 0);

        if ($parentType === 'father') {
            $stmt = $this->db->prepare('UPDATE persons SET father_id = :pid WHERE person_id = :cid');
            $stmt->execute([':pid' => $parentId, ':cid' => $childId]);
            if ($spouseId > 0 && $spouseId !== $childId) {
                $stmt = $this->db->prepare('UPDATE persons SET mother_id = :mid WHERE person_id = :cid AND (mother_id IS NULL OR mother_id = 0)');
                $stmt->execute([':mid' => $spouseId, ':cid' => $childId]);
            }
        } elseif ($parentType === 'mother') {
            $stmt = $this->db->prepare('UPDATE persons SET mother_id = :pid WHERE person_id = :cid');
            $stmt->execute([':pid' => $parentId, ':cid' => $childId]);
            if ($spouseId > 0 && $spouseId !== $childId) {
                $stmt = $this->db->prepare('UPDATE persons SET father_id = :fid WHERE person_id = :cid AND (father_id IS NULL OR father_id = 0)');
                $stmt->execute([':fid' => $spouseId, ':cid' => $childId]);
            }
        }
    }

    private function applyRelation(int $anchorId, int $targetId, string $relationType, string $parentType, ?int $birthOrder, ?string $spouseMarriageDate = null): void
    {
        if ($anchorId === $targetId && in_array($relationType, ['father', 'mother', 'brother', 'sister', 'child', 'spouse'], true)) {
            return;
        }

        if ($relationType === 'spouse') {
            $u = $this->db->prepare('UPDATE persons SET spouse_id = :spouse WHERE person_id = :id');
            $u->execute([':spouse' => $targetId, ':id' => $anchorId]);
            $u->execute([':spouse' => $anchorId, ':id' => $targetId]);
            $this->upsertMarriageByPair($anchorId, $targetId, $spouseMarriageDate);
            $this->linkMissingOtherParentForChildren($anchorId, $targetId);
            return;
        }

        if ($relationType === 'child') {
            $this->linkParentChild($anchorId, $targetId, $parentType);
            return;
        }

        if ($relationType === 'father') {
            $this->linkParentChild($targetId, $anchorId, 'father');
            return;
        }

        if ($relationType === 'mother') {
            $this->linkParentChild($targetId, $anchorId, 'mother');
            return;
        }

        if ($relationType === 'brother' || $relationType === 'sister') {
            $anchor = $this->people->findById($anchorId);
            if ($anchor !== null) {
                $upd = $this->db->prepare('UPDATE persons SET father_id = :father_id, mother_id = :mother_id WHERE person_id = :id');
                $upd->execute([
                    ':father_id' => $anchor['father_id'] ?: null,
                    ':mother_id' => $anchor['mother_id'] ?: null,
                    ':id' => $targetId,
                ]);
            }
            return;
        }

        if ($relationType === 'grandfather') {
            $anchor = $this->people->findById($anchorId);
            if ($anchor !== null && (int)($anchor['father_id'] ?? 0) > 0) {
                $this->linkParentChild($targetId, (int)$anchor['father_id'], 'father');
            }
            return;
        }

        if ($relationType === 'grandmother') {
            $anchor = $this->people->findById($anchorId);
            if ($anchor !== null && (int)($anchor['mother_id'] ?? 0) > 0) {
                $this->linkParentChild($targetId, (int)$anchor['mother_id'], 'mother');
            }
        }
    }

    private function upsertMarriageByPair(int $person1Id, int $person2Id, ?string $marriageDate): void
    {
        $check = $this->db->prepare(
            'SELECT marriage_id
             FROM marriages
             WHERE (person1_id = :a1 AND person2_id = :b1)
                OR (person1_id = :a2 AND person2_id = :b2)
             LIMIT 1'
        );
        $check->execute([
            ':a1' => $person1Id,
            ':b1' => $person2Id,
            ':a2' => $person2Id,
            ':b2' => $person1Id,
        ]);
        $row = $check->fetch();
        if ($row) {
            if ($marriageDate !== null) {
                $upd = $this->db->prepare('UPDATE marriages SET marriage_date = COALESCE(marriage_date, :md) WHERE marriage_id = :id');
                $upd->execute([':md' => $marriageDate, ':id' => (int)$row['marriage_id']]);
            }
            return;
        }

        $ins = $this->db->prepare(
            'INSERT INTO marriages (person1_id, person2_id, marriage_date, divorce_date, status)
             VALUES (:p1, :p2, :md, NULL, :status)'
        );
        $ins->execute([
            ':p1' => $person1Id,
            ':p2' => $person2Id,
            ':md' => $marriageDate,
            ':status' => 'married',
        ]);
    }

    private function linkMissingOtherParentForChildren(int $anchorId, int $spouseId): void
    {
        $fillFather = $this->db->prepare(
            'UPDATE persons
             SET father_id = :spouse
             WHERE mother_id = :anchor
               AND (father_id IS NULL OR father_id = 0)
               AND person_id <> :spouse2'
        );
        $fillFather->execute([
            ':spouse' => $spouseId,
            ':anchor' => $anchorId,
            ':spouse2' => $spouseId,
        ]);

        $fillMother = $this->db->prepare(
            'UPDATE persons
             SET mother_id = :spouse
             WHERE father_id = :anchor
               AND (mother_id IS NULL OR mother_id = 0)
               AND person_id <> :spouse2'
        );
        $fillMother->execute([
            ':spouse' => $spouseId,
            ':anchor' => $anchorId,
            ':spouse2' => $spouseId,
        ]);
    }

    private function normalizeDate(mixed $value): ?string
    {
        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }
        return $text;
    }

    private function normalizeInt(mixed $value): ?int
    {
        $text = trim((string)$value);
        if ($text === '') {
            return null;
        }
        return (int)$text;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string)$value);
        return $text === '' ? null : $text;
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

    public function pendingProposals(): void
    {
        $proposals = [];
        try { $proposals = $this->proposals->findPending(); } catch (Throwable $e) {}
        $this->render('admin/proposals_pending', [
            'title'     => 'Edit Proposals',
            'proposals' => $proposals,
        ]);
    }

    public function reviewProposal(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        try {
            $proposal = $this->proposals->findById($id);
        } catch (Throwable $e) {
            $proposal = null;
        }
        if ($proposal === null) {
            $_SESSION['flash_error'] = 'Proposal not found.';
            header('Location: /index.php?route=admin/proposals');
            exit;
        }
        $person = $this->people->findById((int)$proposal['person_id']);
        $this->render('admin/proposal_review', [
            'title'    => 'Review Proposal',
            'proposal' => $proposal,
            'person'   => $person ?? [],
            'changes'  => json_decode((string)$proposal['proposed_changes'], true) ?: [],
        ]);
    }

    public function approveProposal(): void
    {
        $id = (int)($_POST['proposal_id'] ?? 0);
        if ($id <= 0 || !verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /index.php?route=admin/proposals');
            exit;
        }
        $proposal = $this->proposals->findById($id);
        if ($proposal === null || $proposal['status'] !== 'pending') {
            $_SESSION['flash_error'] = 'Proposal not found or already reviewed.';
            header('Location: /index.php?route=admin/proposals');
            exit;
        }

        $changes    = json_decode((string)$proposal['proposed_changes'], true) ?: [];
        $personId   = (int)$proposal['person_id'];
        $proposerId = (int)$proposal['proposed_by'];
        $person     = $this->people->findById($personId);

        if ($person !== null && !empty($changes)) {
            $updateData = [];
            $allowedFields = ['full_name','gender','date_of_birth','birth_year','birth_order','date_of_death',
                              'blood_group','occupation','mobile','email','address','current_location','native_location','is_alive'];
            foreach ($changes as $field => $vals) {
                if (in_array($field, $allowedFields, true)) {
                    $updateData[':' . $field] = $vals['new'] === '' ? null : $vals['new'];
                }
            }
            if (!empty($updateData)) {
                $updateData[':full_name']     = $updateData[':full_name']     ?? $person['full_name'];
                $updateData[':gender']        = $updateData[':gender']        ?? $person['gender'];
                $updateData[':date_of_birth'] = $updateData[':date_of_birth'] ?? $person['date_of_birth'];
                $updateData[':birth_year']    = $updateData[':birth_year']    ?? $person['birth_year'];
                $updateData[':birth_order']   = $updateData[':birth_order']   ?? $person['birth_order'];
                $updateData[':date_of_death'] = $updateData[':date_of_death'] ?? $person['date_of_death'];
                $updateData[':blood_group']   = $updateData[':blood_group']   ?? $person['blood_group'];
                $updateData[':occupation']    = $updateData[':occupation']    ?? $person['occupation'];
                $updateData[':mobile']        = $updateData[':mobile']        ?? $person['mobile'];
                $updateData[':email']         = $updateData[':email']         ?? $person['email'];
                $updateData[':address']       = $updateData[':address']       ?? $person['address'];
                $updateData[':current_location'] = $updateData[':current_location'] ?? $person['current_location'];
                $updateData[':native_location']  = $updateData[':native_location']  ?? $person['native_location'];
                $updateData[':is_alive']      = $updateData[':is_alive']      ?? $person['is_alive'];
                $this->people->update($personId, $updateData);
            }
        }

        $adminId = (int)(app_user()['user_id'] ?? 0);
        try { $this->proposals->approve($id, $adminId); } catch (Throwable $e) {}
        try {
            $this->notifications->create(
                $proposerId, 'proposal_approved',
                'Edit approved: ' . ($proposal['person_name'] ?? ''),
                'Your edit proposal for ' . ($proposal['person_name'] ?? 'a person') . ' was approved.',
                $personId, '/index.php?route=member/person-view&id=' . $personId
            );
        } catch (Throwable $e) {}

        $_SESSION['flash_success'] = 'Proposal approved and changes applied.';
        header('Location: /index.php?route=admin/proposals');
        exit;
    }

    public function rejectProposal(): void
    {
        $id = (int)($_POST['proposal_id'] ?? 0);
        if ($id <= 0 || !verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid request.';
            header('Location: /index.php?route=admin/proposals');
            exit;
        }
        $proposal = $this->proposals->findById($id);
        if ($proposal === null || $proposal['status'] !== 'pending') {
            $_SESSION['flash_error'] = 'Proposal not found or already reviewed.';
            header('Location: /index.php?route=admin/proposals');
            exit;
        }

        $notes      = trim((string)($_POST['admin_notes'] ?? ''));
        $adminId    = (int)(app_user()['user_id'] ?? 0);
        $proposerId = (int)$proposal['proposed_by'];
        $personId   = (int)$proposal['person_id'];
        try { $this->proposals->reject($id, $adminId, $notes); } catch (Throwable $e) {}
        try {
            $this->notifications->create(
                $proposerId, 'proposal_rejected',
                'Edit rejected: ' . ($proposal['person_name'] ?? ''),
                'Your edit proposal for ' . ($proposal['person_name'] ?? 'a person') . ' was rejected.' . ($notes !== '' ? ' Note: ' . $notes : ''),
                $personId, '/index.php?route=member/person-view&id=' . $personId
            );
        } catch (Throwable $e) {}

        $_SESSION['flash_success'] = 'Proposal rejected.';
        header('Location: /index.php?route=admin/proposals');
        exit;
    }

    private function collectStats(): array
    {
        $users = (int)$this->db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        $persons = (int)$this->db->query('SELECT COUNT(*) FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL)')->fetchColumn();
        $marriages = (int)$this->db->query('SELECT COUNT(*) FROM marriages')->fetchColumn();
        $familiesWithKids = (int)$this->db->query(
            'SELECT COUNT(DISTINCT m.marriage_id)
             FROM marriages m
             INNER JOIN persons c
               ON (c.father_id = m.person1_id AND c.mother_id = m.person2_id)
               OR (c.father_id = m.person2_id AND c.mother_id = m.person1_id)
             WHERE (c.is_deleted = 0 OR c.is_deleted IS NULL)'
        )->fetchColumn();

        $living   = (int)$this->db->query('SELECT COUNT(*) FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) AND is_alive=1')->fetchColumn();
        $deceased = (int)$this->db->query('SELECT COUNT(*) FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) AND is_alive=0')->fetchColumn();

        // Gender distribution
        $genderRows = $this->db->query('SELECT gender, COUNT(*) as cnt FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) GROUP BY gender')->fetchAll();
        $genderMap = [];
        foreach ($genderRows as $r) { $genderMap[(string)($r['gender'] ?? 'unknown')] = (int)$r['cnt']; }

        // Average age of living members with DOB
        $avgAge = null;
        try {
            $r = $this->db->query("SELECT AVG(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())) FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) AND is_alive=1 AND date_of_birth IS NOT NULL")->fetchColumn();
            if ($r !== false && $r !== null) $avgAge = round((float)$r, 1);
        } catch (Throwable $e) {}

        // Oldest living member
        $oldestLiving = null;
        try {
            $r = $this->db->query("SELECT person_id, full_name, date_of_birth, birth_year FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) AND is_alive=1 AND date_of_birth IS NOT NULL ORDER BY date_of_birth ASC LIMIT 1")->fetch();
            if ($r) {
                $age = (int)(new DateTimeImmutable($r['date_of_birth']))->diff(new DateTimeImmutable('today'))->y;
                $oldestLiving = ['name' => $r['full_name'], 'id' => (int)$r['person_id'], 'age' => $age];
            }
        } catch (Throwable $e) {}

        // Most common first names (top 5)
        $topNames = [];
        try {
            $rows = $this->db->query("SELECT SUBSTRING_INDEX(full_name,' ',1) AS first_name, COUNT(*) AS cnt FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) GROUP BY first_name ORDER BY cnt DESC LIMIT 5")->fetchAll();
            foreach ($rows as $r) { $topNames[] = ['name' => (string)$r['first_name'], 'count' => (int)$r['cnt']]; }
        } catch (Throwable $e) {}

        // Birth decade distribution
        $decadeData = [];
        try {
            $rows = $this->db->query("SELECT FLOOR(birth_year/10)*10 AS decade, COUNT(*) AS cnt FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) AND birth_year IS NOT NULL AND birth_year > 1800 GROUP BY decade ORDER BY decade ASC")->fetchAll();
            foreach ($rows as $r) { $decadeData[(int)$r['decade']] = (int)$r['cnt']; }
        } catch (Throwable $e) {}

        // Branches count
        $branches = 0;
        try { $branches = (int)$this->db->query('SELECT COUNT(*) FROM branches')->fetchColumn(); } catch (Throwable $e) {}

        return [
            'users'         => $users,
            'persons'       => $persons,
            'marriages'     => $marriages,
            'families'      => $familiesWithKids,
            'living'        => $living,
            'deceased'      => $deceased,
            'gender_map'    => $genderMap,
            'avg_age'       => $avgAge,
            'oldest_living' => $oldestLiving,
            'top_names'     => $topNames,
            'decade_data'   => $decadeData,
            'branches'      => $branches,
        ];
    }

    public function exportGedcom(): void
    {
        $persons   = [];
        $marriages = [];
        try {
            $stmt = $this->db->prepare(
                'SELECT p.*, f.full_name AS father_name, m.full_name AS mother_name, s.full_name AS spouse_name
                 FROM persons p
                 LEFT JOIN persons f ON f.person_id=p.father_id
                 LEFT JOIN persons m ON m.person_id=p.mother_id
                 LEFT JOIN persons s ON s.person_id=p.spouse_id
                 WHERE (p.is_deleted=0 OR p.is_deleted IS NULL)
                 ORDER BY p.person_id ASC'
            );
            $stmt->execute();
            $persons = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {}
        try {
            $stmt2 = $this->db->prepare(
                'SELECT m.*, p1.full_name AS n1, p2.full_name AS n2
                 FROM marriages m
                 JOIN persons p1 ON p1.person_id=m.person1_id
                 JOIN persons p2 ON p2.person_id=m.person2_id'
            );
            $stmt2->execute();
            $marriages = $stmt2->fetchAll() ?: [];
        } catch (Throwable $e) {}

        header('Content-Type: text/plain; charset=UTF-8');
        header('Content-Disposition: attachment; filename="family_export_' . date('Y-m-d') . '.ged"');

        $personIndex = [];
        foreach ($persons as $p) { $personIndex[(int)$p['person_id']] = $p; }

        $famKey     = [];
        $fams       = [];
        $famCounter = 0;

        foreach ($marriages as $mar) {
            $p1   = (int)$mar['person1_id'];
            $p2   = (int)$mar['person2_id'];
            $husb = isset($personIndex[$p1]) && ($personIndex[$p1]['gender'] ?? '') === 'male' ? $p1 : $p2;
            $wife = $husb === $p1 ? $p2 : $p1;
            $key  = $husb . ':' . $wife;
            if (!isset($famKey[$key])) {
                $famCounter++;
                $famKey[$key]     = $famCounter;
                $fams[$famCounter] = ['husb' => $husb, 'wife' => $wife, 'children' => [], 'marriage_date' => $mar['marriage_date'] ?? null];
            }
        }

        foreach ($persons as $p) {
            $fid = (int)($p['father_id'] ?? 0);
            $mid = (int)($p['mother_id'] ?? 0);
            if ($fid <= 0 && $mid <= 0) continue;
            $key = $fid . ':' . $mid;
            if (!isset($famKey[$key])) {
                $famCounter++;
                $famKey[$key]     = $famCounter;
                $fams[$famCounter] = ['husb' => $fid, 'wife' => $mid, 'children' => [], 'marriage_date' => null];
            }
            $fams[$famKey[$key]]['children'][] = (int)$p['person_id'];
        }

        $lines   = [];
        $lines[] = '0 HEAD';
        $lines[] = '1 SOUR FamilyTreeApp';
        $lines[] = '1 GEDC';
        $lines[] = '2 VERS 5.5';
        $lines[] = '1 CHAR UTF-8';
        $lines[] = '1 DATE ' . strtoupper(date('d M Y'));

        foreach ($persons as $p) {
            $id      = (int)$p['person_id'];
            $lines[] = '0 @I' . $id . '@ INDI';
            $nameParts = explode(' ', (string)$p['full_name'], 2);
            $given     = $nameParts[0] ?? '';
            $surname   = $nameParts[1] ?? '';
            $lines[]   = '1 NAME ' . $given . ' /' . $surname . '/';
            $gender    = (string)($p['gender'] ?? 'unknown');
            if ($gender === 'male')   $lines[] = '1 SEX M';
            elseif ($gender === 'female') $lines[] = '1 SEX F';
            if (!empty($p['date_of_birth'])) {
                $lines[] = '1 BIRT';
                $lines[] = '2 DATE ' . strtoupper(date('d M Y', strtotime((string)$p['date_of_birth'])));
            } elseif (!empty($p['birth_year'])) {
                $lines[] = '1 BIRT';
                $lines[] = '2 DATE ' . (int)$p['birth_year'];
            }
            if (!empty($p['date_of_death'])) {
                $lines[] = '1 DEAT';
                $lines[] = '2 DATE ' . strtoupper(date('d M Y', strtotime((string)$p['date_of_death'])));
            } elseif ((int)($p['is_alive'] ?? 1) === 0) {
                $lines[] = '1 DEAT Y';
            }
            $fid = (int)($p['father_id'] ?? 0);
            $mid = (int)($p['mother_id'] ?? 0);
            if ($fid > 0 || $mid > 0) {
                $key = $fid . ':' . $mid;
                if (isset($famKey[$key])) $lines[] = '1 FAMC @F' . $famKey[$key] . '@';
            }
            foreach ($fams as $fNum => $fam) {
                if ($fam['husb'] === $id || $fam['wife'] === $id) {
                    $lines[] = '1 FAMS @F' . $fNum . '@';
                }
            }
        }

        foreach ($fams as $fNum => $fam) {
            $lines[] = '0 @F' . $fNum . '@ FAM';
            if ($fam['husb'] > 0) $lines[] = '1 HUSB @I' . $fam['husb'] . '@';
            if ($fam['wife'] > 0) $lines[] = '1 WIFE @I' . $fam['wife'] . '@';
            foreach ($fam['children'] as $cid) $lines[] = '1 CHIL @I' . $cid . '@';
            if (!empty($fam['marriage_date'])) {
                $lines[] = '1 MARR';
                $lines[] = '2 DATE ' . strtoupper(date('d M Y', strtotime((string)$fam['marriage_date'])));
            }
        }

        $lines[] = '0 TRLR';
        echo implode("\r\n", $lines);
        exit;
    }

    public function updateUserPermissions(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403); echo 'Forbidden'; exit;
        }
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0 || $userId === (int)(app_user()['user_id'] ?? 0)) {
            header('Location: /index.php?route=admin/users'); exit;
        }
        $editScope     = (string)($_POST['edit_scope'] ?? 'self');
        $canAddPerson  = isset($_POST['can_add_person']);
        if (!in_array($editScope, ['none', 'self', 'children', 'grandchildren', 'all'], true)) {
            $editScope = 'self';
        }
        $permissions = [
            'edit_scope'     => $editScope,
            'can_add_person' => $canAddPerson,
        ];
        try {
            $this->users->updatePermissions($userId, $permissions);
            $_SESSION['flash_success'] = 'Permissions updated.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed: ' . $e->getMessage();
        }
        header('Location: /index.php?route=admin/users'); exit;
    }

    public function addProposalsList(): void
    {
        $proposals = [];
        try { $proposals = $this->addProposals->findPending(); } catch (Throwable $e) {}
        $this->render('admin/add_proposals_pending', ['title' => 'New Person Proposals', 'proposals' => $proposals]);
    }

    public function reviewAddProposal(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            header('Location: /index.php?route=admin/add-proposals');
            exit;
        }
        $proposal = null;
        try { $proposal = $this->addProposals->findById($id); } catch (Throwable $e) {}
        if ($proposal === null) {
            header('Location: /index.php?route=admin/add-proposals');
            exit;
        }
        $d = json_decode((string)$proposal['proposed_data'], true) ?? [];
        $linkedPersons = [];
        $ids = array_filter([(int)($d['father_person_id'] ?? 0), (int)($d['mother_person_id'] ?? 0)]);
        foreach ($ids as $pid) {
            $p = null;
            try { $p = $this->people->findById($pid); } catch (Throwable $e) {}
            if ($p !== null) $linkedPersons[$pid] = $p;
        }
        $this->render('admin/add_proposal_review', [
            'title'         => 'Review: ' . ($d['full_name'] ?? 'Proposal'),
            'proposal'      => $proposal,
            'linkedPersons' => $linkedPersons,
        ]);
    }

    public function approveAddProposal(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403); echo 'Forbidden'; exit;
        }
        $id = (int)($_POST['proposal_id'] ?? 0);
        $notes = trim((string)($_POST['admin_notes'] ?? ''));
        $proposal = null;
        try { $proposal = $this->addProposals->findById($id); } catch (Throwable $e) {}
        if ($proposal === null) {
            header('Location: /index.php?route=admin/add-proposals'); exit;
        }
        $d = json_decode((string)$proposal['proposed_data'], true) ?? [];
        $adminId = (int)(app_user()['user_id'] ?? 0);

        $this->db->beginTransaction();
        try {
            $fatherId = (int)($d['father_person_id'] ?? 0);
            if ($fatherId <= 0 && !empty($d['father_name'])) {
                $fatherId = $this->people->create([
                    ':full_name' => $d['father_name'], ':gender' => 'male',
                    ':date_of_birth' => null, ':birth_year' => $d['father_birth_year'] ?? null,
                    ':date_of_death' => null, ':blood_group' => null, ':occupation' => null,
                    ':mobile' => null, ':email' => null, ':address' => null,
                    ':current_location' => null, ':native_location' => null, ':is_alive' => 1,
                    ':father_id' => null, ':mother_id' => null, ':spouse_id' => null,
                    ':branch_id' => null, ':birth_order' => null, ':created_by' => $adminId,
                    ':editable_scope' => 'all', ':is_locked' => 0, ':is_deleted' => 0,
                ]);
            }
            $motherId = (int)($d['mother_person_id'] ?? 0);
            if ($motherId <= 0 && !empty($d['mother_name'])) {
                $motherId = $this->people->create([
                    ':full_name' => $d['mother_name'], ':gender' => 'female',
                    ':date_of_birth' => null, ':birth_year' => $d['mother_birth_year'] ?? null,
                    ':date_of_death' => null, ':blood_group' => null, ':occupation' => null,
                    ':mobile' => null, ':email' => null, ':address' => null,
                    ':current_location' => null, ':native_location' => null, ':is_alive' => 1,
                    ':father_id' => null, ':mother_id' => null, ':spouse_id' => null,
                    ':branch_id' => null, ':birth_order' => null, ':created_by' => $adminId,
                    ':editable_scope' => 'all', ':is_locked' => 0, ':is_deleted' => 0,
                ]);
            }
            $personId = $this->people->create([
                ':full_name'        => (string)($d['full_name'] ?? ''),
                ':gender'           => (string)($d['gender'] ?? 'unknown'),
                ':date_of_birth'    => $d['date_of_birth'] ?? null,
                ':birth_year'       => $d['birth_year'] ?? null,
                ':date_of_death'    => $d['date_of_death'] ?? null,
                ':blood_group'      => null,
                ':occupation'       => null,
                ':mobile'           => null,
                ':email'            => null,
                ':address'          => null,
                ':current_location' => null,
                ':native_location'  => null,
                ':is_alive'         => (int)($d['is_alive'] ?? 1),
                ':father_id'        => $fatherId > 0 ? $fatherId : null,
                ':mother_id'        => $motherId > 0 ? $motherId : null,
                ':spouse_id'        => null,
                ':branch_id'        => null,
                ':birth_order'      => (int)($d['person_position'] ?? 1) > 0 ? (int)($d['person_position'] ?? 1) : null,
                ':created_by'       => $adminId,
                ':editable_scope'   => 'all',
                ':is_locked'        => 0,
                ':is_deleted'       => 0,
            ]);

            foreach (($d['siblings'] ?? []) as $sib) {
                $sibName = trim((string)($sib['name'] ?? ''));
                if ($sibName === '') continue;
                $this->people->create([
                    ':full_name'        => $sibName,
                    ':gender'           => in_array($sib['gender'] ?? '', ['male','female','other'], true) ? $sib['gender'] : 'unknown',
                    ':date_of_birth'    => null, ':birth_year' => null, ':date_of_death' => null,
                    ':blood_group'      => null, ':occupation' => null, ':mobile' => null,
                    ':email'            => null, ':address' => null, ':current_location' => null,
                    ':native_location'  => null, ':is_alive' => 1,
                    ':father_id'        => $fatherId > 0 ? $fatherId : null,
                    ':mother_id'        => $motherId > 0 ? $motherId : null,
                    ':spouse_id'        => null, ':branch_id' => null,
                    ':birth_order'      => (int)($sib['pos'] ?? 0) > 0 ? (int)$sib['pos'] : null,
                    ':created_by'       => $adminId, ':editable_scope' => 'all',
                    ':is_locked'        => 0, ':is_deleted' => 0,
                ]);
            }

            $this->addProposals->approve($id, $adminId, $notes);

            try {
                $nm = new NotificationModel($this->db);
                $nm->create(
                    (int)$proposal['proposed_by'],
                    $personId,
                    'proposal_approved',
                    'Your person submission was approved',
                    (string)($d['full_name'] ?? 'The person') . ' has been added to the family tree.',
                    '/index.php?route=admin/person-view&id=' . $personId
                );
            } catch (Throwable $e) {}

            $this->db->commit();
            $_SESSION['flash_success'] = htmlspecialchars((string)($d['full_name'] ?? 'Person'), ENT_QUOTES, 'UTF-8') . ' created successfully (Person #' . $personId . ').';
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $_SESSION['flash_error'] = 'Could not approve: ' . $e->getMessage();
        }
        header('Location: /index.php?route=admin/add-proposals');
        exit;
    }

    public function rejectAddProposal(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403); echo 'Forbidden'; exit;
        }
        $id = (int)($_POST['proposal_id'] ?? 0);
        $notes = trim((string)($_POST['admin_notes'] ?? ''));
        $adminId = (int)(app_user()['user_id'] ?? 0);
        $proposal = null;
        try { $proposal = $this->addProposals->findById($id); } catch (Throwable $e) {}
        if ($proposal !== null) {
            try {
                $this->addProposals->reject($id, $adminId, $notes);
                $d = json_decode((string)$proposal['proposed_data'], true) ?? [];
                $nm = new NotificationModel($this->db);
                $nm->create(
                    (int)$proposal['proposed_by'],
                    null,
                    'proposal_rejected',
                    'Your person submission was not approved',
                    'Submission for "' . ($d['full_name'] ?? 'Unknown') . '": ' . ($notes ?: 'No reason given.'),
                    null
                );
            } catch (Throwable $e) {}
        }
        $_SESSION['flash_success'] = 'Proposal rejected.';
        header('Location: /index.php?route=admin/add-proposals');
        exit;
    }

    public function inviteLinks(): void
    {
        $links = [];
        try { $links = $this->invites->list(); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $this->render('admin/invite_links', ['title' => 'Invite Links', 'links' => $links, 'flash' => $flash]);
    }

    public function createInvite(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $label      = trim((string)($_POST['label'] ?? ''));
        $maxUses    = max(0, (int)($_POST['max_uses'] ?? 0));
        $expiresRaw = trim((string)($_POST['expires_at'] ?? ''));
        $expiresAt  = ($expiresRaw !== '') ? $expiresRaw . ' 23:59:59' : null;
        $editScope  = (string)($_POST['default_edit_scope'] ?? 'children');
        if (!in_array($editScope, ['none', 'self', 'children', 'grandchildren', 'all'], true)) {
            $editScope = 'children';
        }
        $defaultPermissions = [
            'edit_scope'     => $editScope,
            'can_add_person' => isset($_POST['default_can_add_person']),
        ];
        $userId = (int)(app_user()['user_id'] ?? 0);
        try {
            $this->invites->create($userId, $label, $maxUses, $expiresAt, $defaultPermissions);
            $_SESSION['flash_success'] = 'Invite link created successfully.';
        } catch (Throwable $e) {
            $_SESSION['flash_success'] = 'Error creating link: ' . $e->getMessage();
        }
        header('Location: /index.php?route=admin/invite-links');
        exit;
    }

    public function deactivateInvite(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $linkId = (int)($_POST['link_id'] ?? 0);
        if ($linkId > 0) {
            try { $this->invites->deactivate($linkId); } catch (Throwable $e) {}
        }
        header('Location: /index.php?route=admin/invite-links');
        exit;
    }

    public function generateViewToken(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $personId  = (int)($_POST['person_id'] ?? 0);
        $label     = trim((string)($_POST['label'] ?? ''));
        $expiresRaw = trim((string)($_POST['expires_at'] ?? ''));
        $expiresAt  = ($expiresRaw !== '') ? $expiresRaw . ' 23:59:59' : null;
        $userId    = (int)(app_user()['user_id'] ?? 0);
        if ($personId > 0) {
            try {
                $this->viewTokens->generate($personId, $userId, $label, $expiresAt);
                $_SESSION['flash_success'] = 'View link generated successfully.';
            } catch (Throwable $e) {
                $_SESSION['flash_success'] = 'Error generating link.';
            }
        }
        header('Location: /index.php?route=admin/person-view&id=' . $personId);
        exit;
    }

    public function deleteViewToken(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $tokenId  = (int)($_POST['token_id'] ?? 0);
        $personId = (int)($_POST['person_id'] ?? 0);
        if ($tokenId > 0) {
            try { $this->viewTokens->delete($tokenId); } catch (Throwable $e) {}
        }
        header('Location: /index.php?route=admin/person-view&id=' . $personId);
        exit;
    }

    public function viewCorrectionsList(): void
    {
        $requests = [];
        try { $requests = $this->viewCorrections->findPending(); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null;
        unset($_SESSION['flash_success']);
        $this->render('admin/view_corrections', [
            'title'    => 'View Corrections',
            'requests' => $requests,
            'flash'    => $flash,
        ]);
    }

    // ── Timeline ────────────────────────────────────────────────────────
    public function timeline(): void
    {
        $events = [];
        try {
            // Births
            $stmt = $this->db->prepare(
                "SELECT person_id, full_name, gender, date_of_birth, birth_year
                 FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL) AND (date_of_birth IS NOT NULL OR birth_year IS NOT NULL)
                 ORDER BY birth_year ASC, date_of_birth ASC LIMIT 500"
            );
            $stmt->execute();
            foreach ($stmt->fetchAll() as $r) {
                $year = !empty($r['date_of_birth']) ? (int)date('Y', strtotime($r['date_of_birth'])) : (int)$r['birth_year'];
                if ($year < 1) continue;
                $events[] = ['year'=>$year,'type'=>'birthday','icon'=>'&#127874;','label'=>'Born','person_name'=>$r['full_name'],'person_id'=>(int)$r['person_id'],'detail'=>''];
            }
            // Deaths
            $stmt2 = $this->db->prepare(
                "SELECT person_id, full_name, date_of_death FROM persons
                 WHERE (is_deleted=0 OR is_deleted IS NULL) AND date_of_death IS NOT NULL LIMIT 200"
            );
            $stmt2->execute();
            foreach ($stmt2->fetchAll() as $r) {
                $year = (int)date('Y', strtotime((string)$r['date_of_death']));
                $events[] = ['year'=>$year,'type'=>'death','icon'=>'&#x1FAB7;','label'=>'Passed away','person_name'=>$r['full_name'],'person_id'=>(int)$r['person_id'],'detail'=>''];
            }
            // Weddings
            $stmt3 = $this->db->prepare(
                "SELECT m.marriage_date, p1.full_name AS n1, p1.person_id AS id1, p2.full_name AS n2
                 FROM marriages m
                 INNER JOIN persons p1 ON p1.person_id=m.person1_id
                 INNER JOIN persons p2 ON p2.person_id=m.person2_id
                 WHERE m.marriage_date IS NOT NULL AND m.status='married' LIMIT 200"
            );
            $stmt3->execute();
            foreach ($stmt3->fetchAll() as $r) {
                $year = (int)date('Y', strtotime((string)$r['marriage_date']));
                $events[] = ['year'=>$year,'type'=>'wedding','icon'=>'&#128149;','label'=>'Married','person_name'=>$r['n1'].' & '.$r['n2'],'person_id'=>(int)$r['id1'],'detail'=>date('d M Y',strtotime((string)$r['marriage_date']))];
            }
            // Family events
            foreach ($this->familyEvents->list(200) as $fe) {
                if (empty($fe['event_date'])) continue;
                $year = (int)date('Y', strtotime((string)$fe['event_date']));
                $events[] = ['year'=>$year,'type'=>'family_event','icon'=>'&#127881;','label'=>ucfirst((string)$fe['event_type']),'person_name'=>(string)$fe['title'],'person_id'=>0,'detail'=>(string)($fe['description']??'')];
            }
        } catch (Throwable $e) {}

        usort($events, fn($a,$b) => $b['year'] <=> $a['year']);
        $grouped = [];
        foreach ($events as $ev) { $grouped[$ev['year']][] = $ev; }
        krsort($grouped);

        $this->render('shared/timeline', ['title'=>'Family Timeline','grouped'=>$grouped]);
    }

    // ── Memorial ─────────────────────────────────────────────────────────
    public function memorial(): void
    {
        $deceased = [];
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM persons WHERE (is_deleted=0 OR is_deleted IS NULL)
                 AND (is_alive=0 OR date_of_death IS NOT NULL) ORDER BY date_of_death DESC, birth_year ASC LIMIT 300"
            );
            $stmt->execute();
            $deceased = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {}
        $this->render('shared/memorial', ['title'=>'In Memoriam','deceased'=>$deceased]);
    }

    // ── Family Events ────────────────────────────────────────────────────
    public function familyEventsList(): void
    {
        $events = [];
        try { $events = $this->familyEvents->list(); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
        $this->render('admin/family_events', ['title'=>'Family Events','events'=>$events,'flash'=>$flash]);
    }

    public function createFamilyEvent(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token']??''))) { http_response_code(403); exit; }
        $userId = (int)(app_user()['user_id']??0);
        try {
            $this->familyEvents->create(
                $userId,
                trim((string)($_POST['title']??'')),
                (string)($_POST['event_type']??'other'),
                trim((string)($_POST['event_date']??'')) ?: null,
                trim((string)($_POST['description']??'')) ?: null,
                trim((string)($_POST['drive_link']??'')) ?: null,
                []
            );
            $_SESSION['flash_success'] = 'Event added.';
        } catch (Throwable $e) { $_SESSION['flash_success'] = 'Error: '.$e->getMessage(); }
        header('Location: /index.php?route=admin/family-events'); exit;
    }

    public function deleteFamilyEvent(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token']??''))) { http_response_code(403); exit; }
        $id = (int)($_POST['event_id']??0);
        if ($id > 0) { try { $this->familyEvents->delete($id); } catch (Throwable $e) {} }
        header('Location: /index.php?route=admin/family-events'); exit;
    }

    // ── Moi Register ─────────────────────────────────────────────────────
    public function moiList(): void
    {
        $summary = [];
        try { $summary = $this->moi->summaryByEvent(); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
        $this->render('admin/moi_list', ['title' => 'Moi Register', 'summary' => $summary, 'flash' => $flash]);
    }

    public function moiByEvent(): void
    {
        $eventId     = (int)($_GET['event_id'] ?? 0);
        $event       = null;
        $entries     = [];
        $total       = 0.0;
        $giverNames  = [];
        if ($eventId > 0) {
            try { $event   = $this->familyEvents->findById($eventId); } catch (Throwable $e) {}
            try { $entries = $this->moi->byEvent($eventId); } catch (Throwable $e) {}
            try { $total   = $this->moi->totalByEvent($eventId); } catch (Throwable $e) {}
        }
        try { $giverNames = $this->moi->distinctGiverNames(); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
        $this->render('admin/moi_event', [
            'title'       => 'Moi Entries',
            'event'       => $event,
            'event_id'    => $eventId,
            'entries'     => $entries,
            'total'       => $total,
            'giver_names' => $giverNames,
            'flash'       => $flash,
        ]);
    }

    public function createMoi(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); exit; }
        $eventId    = (int)($_POST['event_id'] ?? 0);
        $eventLabel = trim((string)($_POST['event_label'] ?? ''));
        $eventDate  = trim((string)($_POST['event_date'] ?? ''));
        $giverName  = trim((string)($_POST['giver_name'] ?? ''));
        if ($giverName === '') {
            $_SESSION['flash_error'] = 'Giver name is required.';
            $this->redirectToMoiPage($eventId > 0 ? $eventId : null);
        }
        $amount = round((float)str_replace(',', '', (string)($_POST['amount'] ?? '0')), 2);
        try {
            $this->moi->create([
                'event_id'          => $eventId > 0 ? $eventId : null,
                'event_label'       => $eventLabel !== '' ? $eventLabel : 'General',
                'event_date'        => $eventDate !== '' ? $eventDate : null,
                'giver_name'        => $giverName,
                'giver_father_name' => trim((string)($_POST['giver_father_name'] ?? '')) ?: null,
                'giver_location'    => trim((string)($_POST['giver_location'] ?? '')) ?: null,
                'amount'            => $amount,
                'gift_type'         => in_array($_POST['gift_type'] ?? '', ['cash','cheque','gold','gift','other'], true)
                                           ? (string)$_POST['gift_type'] : 'cash',
                'notes'             => trim((string)($_POST['notes'] ?? '')) ?: null,
                'recorded_by'       => (int)(app_user()['user_id'] ?? 0),
            ]);
        } catch (Throwable $e) {}
        $_SESSION['flash_success'] = 'Entry added.';
        $this->redirectToMoiPage($eventId > 0 ? $eventId : null);
    }

    public function deleteMoi(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); exit; }
        $id      = (int)($_POST['moi_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        if ($id > 0) { try { $this->moi->delete($id); } catch (Throwable $e) {} }
        $this->redirectToMoiPage($eventId > 0 ? $eventId : null);
    }

    public function exportMoiCsv(): void
    {
        $eventId = (int)($_GET['event_id'] ?? 0);
        if ($eventId > 0) {
            $rows  = [];
            $label = 'moi-event-' . $eventId;
            try { $rows = $this->moi->byEvent($eventId); } catch (Throwable $e) {}
            $event = null;
            try { $event = $this->familyEvents->findById($eventId); } catch (Throwable $e) {}
            if ($event !== null) {
                $label = preg_replace('/[^a-z0-9_-]/i', '_', (string)$event['title']);
            }
        } else {
            $rows  = [];
            $label = 'moi-all';
            try { $rows = $this->moi->listAll(2000); } catch (Throwable $e) {}
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="moi-' . $label . '.csv"');
        $out = fopen('php://output', 'w');
        if ($out === false) { exit; }
        fputcsv($out, ['#','Event','Date','Giver Name','Father/Husband Name','Location','Amount','Gift Type','Notes','Recorded By','Recorded At']);
        $i = 1;
        foreach ($rows as $r) {
            fputcsv($out, [
                $i++,
                (string)($r['event_label'] ?? ''),
                (string)($r['event_date'] ?? ''),
                (string)($r['giver_name'] ?? ''),
                (string)($r['giver_father_name'] ?? ''),
                (string)($r['giver_location'] ?? ''),
                number_format((float)($r['amount'] ?? 0), 2, '.', ''),
                (string)($r['gift_type'] ?? ''),
                (string)($r['notes'] ?? ''),
                (string)($r['recorder_name'] ?? ''),
                (string)($r['created_at'] ?? ''),
            ]);
        }
        fclose($out);
        exit;
    }

    public function moiLocationSearch(): void
    {
        $q = trim((string)($_GET['q'] ?? ''));
        header('Content-Type: application/json; charset=utf-8');
        if (mb_strlen($q) < 1) { echo '[]'; exit; }
        $rows = [];
        try { $rows = $this->moi->searchLocations($q, 15); } catch (Throwable $e) {}
        echo json_encode(array_values($rows), JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function submitMoiAsProposal(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) { http_response_code(403); exit; }
        $moiId   = (int)($_POST['moi_id'] ?? 0);
        $eventId = (int)($_POST['event_id'] ?? 0);
        $entry   = null;
        if ($moiId > 0) { try { $entry = $this->moi->findById($moiId); } catch (Throwable $e) {} }
        if ($entry === null) {
            $this->redirectToMoiPage($eventId > 0 ? $eventId : null);
        }
        $proposedData = [
            'full_name'   => (string)($entry['giver_name'] ?? ''),
            'father_name' => (string)($entry['giver_father_name'] ?? ''),
            'gender'      => 'unknown',
            'admin_note'  => 'Submitted from Moi Register: ' . (string)($entry['event_label'] ?? ''),
        ];
        $userId = (int)(app_user()['user_id'] ?? 0);
        try { $this->addProposals->create($userId, $proposedData); } catch (Throwable $e) {}
        $_SESSION['flash_success'] = htmlspecialchars((string)($entry['giver_name'] ?? ''), ENT_QUOTES, 'UTF-8') . ' submitted as a pending family member.';
        $this->redirectToMoiPage($eventId > 0 ? $eventId : null);
    }

    private function redirectToMoiPage(?int $eventId): void
    {
        if ($eventId !== null && $eventId > 0) {
            header('Location: /index.php?route=admin/moi-event&event_id=' . $eventId);
        } else {
            header('Location: /index.php?route=admin/moi-list');
        }
        exit;
    }

    // ── Announcements ─────────────────────────────────────────────────────
    public function announcementsList(): void
    {
        $list = [];
        try { $list = $this->announcementsModel->list(); } catch (Throwable $e) {}
        $flash = $_SESSION['flash_success'] ?? null; unset($_SESSION['flash_success']);
        $this->render('admin/announcements', ['title'=>'Announcements','announcements'=>$list,'flash'=>$flash]);
    }

    public function createAnnouncement(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token']??''))) { http_response_code(403); exit; }
        $userId = (int)(app_user()['user_id']??0);
        try {
            $this->announcementsModel->create($userId, trim((string)($_POST['title']??'')), trim((string)($_POST['body']??'')), isset($_POST['is_pinned']));
            $_SESSION['flash_success'] = 'Announcement posted.';
        } catch (Throwable $e) {}
        header('Location: /index.php?route=admin/announcements'); exit;
    }

    public function deleteAnnouncement(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token']??''))) { http_response_code(403); exit; }
        $id = (int)($_POST['announcement_id']??0);
        if ($id > 0) { try { $this->announcementsModel->delete($id); } catch (Throwable $e) {} }
        header('Location: /index.php?route=admin/announcements'); exit;
    }

    public function toggleAnnouncementPin(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token']??''))) { http_response_code(403); exit; }
        $id = (int)($_POST['announcement_id']??0);
        if ($id > 0) { try { $this->announcementsModel->togglePin($id); } catch (Throwable $e) {} }
        header('Location: /index.php?route=admin/announcements'); exit;
    }

    // ── Bulk Import ───────────────────────────────────────────────────────
    public function bulkImport(): void
    {
        $this->render('admin/bulk_import', ['title'=>'Bulk Import','result'=>null,'flash'=>null]);
    }

    public function bulkImportProcess(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token']??''))) { http_response_code(403); exit; }
        $file = $_FILES['csv_file'] ?? null;
        if (!$file || (int)($file['error']??1) !== UPLOAD_ERR_OK) {
            $this->render('admin/bulk_import', ['title'=>'Bulk Import','result'=>['imported'=>0,'skipped'=>0,'errors'=>['No valid file uploaded.']],'flash'=>null]);
            return;
        }
        $handle = fopen((string)$file['tmp_name'], 'r');
        if ($handle === false) {
            $this->render('admin/bulk_import', ['title'=>'Bulk Import','result'=>['imported'=>0,'skipped'=>0,'errors'=>['Could not read file.']],'flash'=>null]);
            return;
        }
        $headers = fgetcsv($handle);
        if ($headers === false) { fclose($handle); $this->render('admin/bulk_import', ['title'=>'Bulk Import','result'=>['imported'=>0,'skipped'=>0,'errors'=>['Empty or invalid CSV.']],'flash'=>null]); return; }
        $headers = array_map('trim', array_map('strtolower', $headers));
        $imported = 0; $skipped = 0; $errors = [];
        $userId = (int)(app_user()['user_id']??0);
        while (($row = fgetcsv($handle)) !== false) {
            $data = [];
            foreach ($headers as $i => $h) { $data[$h] = trim((string)($row[$i]??'')); }
            $name = $data['full_name'] ?? '';
            if ($name === '') { $skipped++; continue; }
            $birthYear = isset($data['birth_year']) && $data['birth_year'] !== '' ? (int)$data['birth_year'] : null;
            $gender = $data['gender'] ?? null;
            $dups = [];
            try { $dups = $this->people->findPotentialDuplicates($name, $birthYear, $gender ?: null); } catch (Throwable $e) {}
            if (!empty($dups)) { $skipped++; continue; }
            try {
                $this->people->create([
                    'full_name'        => $name,
                    'gender'           => $gender ?: null,
                    'birth_year'       => $birthYear,
                    'date_of_birth'    => ($data['date_of_birth']??'') ?: null,
                    'native_location'  => ($data['native_location']??'') ?: null,
                    'current_location' => ($data['current_location']??'') ?: null,
                    'occupation'       => ($data['occupation']??'') ?: null,
                    'mobile'           => ($data['mobile']??'') ?: null,
                    'email'            => ($data['email']??'') ?: null,
                    'blood_group'      => ($data['blood_group']??'') ?: null,
                    'is_alive'         => 1,
                    'created_by'       => $userId,
                ]);
                $imported++;
            } catch (Throwable $e) { $errors[] = "Row '$name': ".$e->getMessage(); }
        }
        fclose($handle);
        $this->render('admin/bulk_import', ['title'=>'Bulk Import','result'=>['imported'=>$imported,'skipped'=>$skipped,'errors'=>$errors],'flash'=>null]);
    }

    public function importTemplate(): void
    {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="family_import_template.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['full_name','gender','birth_year','date_of_birth','native_location','current_location','occupation','mobile','email','blood_group']);
        fputcsv($out, ['Example Name','Male','1980','1980-06-15','Chennai','Bangalore','Engineer','9876543210','example@email.com','O+']);
        fclose($out);
        exit;
    }

    // ── Export ────────────────────────────────────────────────────────────
    public function exportPersons(): void
    {
        $persons = [];
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, f.full_name AS father_name, m.full_name AS mother_name, s.full_name AS spouse_name
                 FROM persons p
                 LEFT JOIN persons f ON f.person_id=p.father_id
                 LEFT JOIN persons m ON m.person_id=p.mother_id
                 LEFT JOIN persons s ON s.person_id=p.spouse_id
                 WHERE (p.is_deleted=0 OR p.is_deleted IS NULL) ORDER BY p.person_id ASC"
            );
            $stmt->execute();
            $persons = $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {}
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="family_export_'.date('Y-m-d').'.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['person_id','full_name','gender','birth_year','date_of_birth','date_of_death','blood_group','occupation','mobile','email','current_location','native_location','address','is_alive','father_name','mother_name','spouse_name']);
        foreach ($persons as $p) {
            fputcsv($out, [
                $p['person_id'], $p['full_name'], $p['gender']??'', $p['birth_year']??'', $p['date_of_birth']??'',
                $p['date_of_death']??'', $p['blood_group']??'', $p['occupation']??'', $p['mobile']??'',
                $p['email']??'', $p['current_location']??'', $p['native_location']??'', $p['address']??'',
                $p['is_alive']??1, $p['father_name']??'', $p['mother_name']??'', $p['spouse_name']??'',
            ]);
        }
        fclose($out);
        exit;
    }

    // ── Helpers ───────────────────────────────────────────────────────────
    private function collectUpcomingEvents(int $days): array
    {
        $upcoming = [];
        try {
            $stmt = $this->db->prepare(
                "SELECT person_id, full_name, date_of_birth FROM persons
                 WHERE (is_deleted=0 OR is_deleted IS NULL) AND is_alive=1 AND date_of_birth IS NOT NULL
                 AND DATE_FORMAT(date_of_birth,'%m-%d') BETWEEN DATE_FORMAT(CURDATE(),'%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL :d DAY),'%m-%d')
                 LIMIT 20"
            );
            $stmt->bindValue(':d', $days, PDO::PARAM_INT);
            $stmt->execute();
            foreach ($stmt->fetchAll() as $r) {
                $upcoming[] = ['type'=>'birthday','icon'=>'&#127874;','name'=>$r['full_name'],'person_id'=>(int)$r['person_id'],'date'=>date('d M',strtotime((string)$r['date_of_birth']))];
            }
        } catch (Throwable $e) {}
        try {
            $stmt2 = $this->db->prepare(
                "SELECT m.marriage_date, p1.full_name AS n1, p1.person_id AS id1, p2.full_name AS n2
                 FROM marriages m INNER JOIN persons p1 ON p1.person_id=m.person1_id INNER JOIN persons p2 ON p2.person_id=m.person2_id
                 WHERE m.status='married' AND m.marriage_date IS NOT NULL
                 AND DATE_FORMAT(m.marriage_date,'%m-%d') BETWEEN DATE_FORMAT(CURDATE(),'%m-%d') AND DATE_FORMAT(DATE_ADD(CURDATE(),INTERVAL :d DAY),'%m-%d')
                 LIMIT 10"
            );
            $stmt2->bindValue(':d', $days, PDO::PARAM_INT);
            $stmt2->execute();
            foreach ($stmt2->fetchAll() as $r) {
                $upcoming[] = ['type'=>'anniversary','icon'=>'&#128149;','name'=>$r['n1'].' & '.$r['n2'],'person_id'=>(int)$r['id1'],'date'=>date('d M',strtotime((string)$r['marriage_date']))];
            }
        } catch (Throwable $e) {}
        return $upcoming;
    }

    public function markCorrectionReviewed(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
        $requestId  = (int)($_POST['request_id'] ?? 0);
        $personId   = (int)($_POST['redirect_person'] ?? 0);
        $reviewerId = (int)(app_user()['user_id'] ?? 0);
        if ($requestId > 0) {
            try { $this->viewCorrections->markReviewed($requestId, $reviewerId); } catch (Throwable $e) {}
        }
        if ($personId > 0) {
            header('Location: /index.php?route=admin/person-view&id=' . $personId);
        } else {
            $_SESSION['flash_success'] = 'Marked as reviewed.';
            header('Location: /index.php?route=admin/view-corrections');
        }
        exit;
    }

    public function markDeceased(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403); exit;
        }
        $personId = (int)($_POST['person_id'] ?? 0);
        $dateOfDeath = trim((string)($_POST['date_of_death'] ?? '')) ?: null;
        if ($personId > 0) {
            try {
                $stmt = $this->db->prepare(
                    'UPDATE persons SET is_alive = 0, date_of_death = :dod WHERE person_id = :id'
                );
                $stmt->execute([':dod' => $dateOfDeath, ':id' => $personId]);
                $_SESSION['flash_success'] = 'Marked as deceased.';
            } catch (Throwable $e) {}
        }
        header('Location: /index.php?route=admin/person-view&id=' . $personId);
        exit;
    }

    public function markAlive(): void
    {
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            http_response_code(403); exit;
        }
        $personId = (int)($_POST['person_id'] ?? 0);
        if ($personId > 0) {
            try {
                $stmt = $this->db->prepare(
                    'UPDATE persons SET is_alive = 1, date_of_death = NULL WHERE person_id = :id'
                );
                $stmt->execute([':id' => $personId]);
                $_SESSION['flash_success'] = 'Marked as alive.';
            } catch (Throwable $e) {}
        }
        header('Location: /index.php?route=admin/person-view&id=' . $personId);
        exit;
    }

    public function saveSiblingOrder(): void
    {
        header('Content-Type: application/json');
        if (!verify_csrf((string)($_POST['csrf_token'] ?? ''))) {
            echo json_encode(['ok' => false, 'error' => 'Invalid CSRF token']);
            exit;
        }
        $parentId = (int)($_POST['parent_id'] ?? 0);
        $orderRaw = (string)($_POST['order_json'] ?? '');
        $ids = json_decode($orderRaw, true);
        if ($parentId <= 0 || !is_array($ids) || empty($ids)) {
            echo json_encode(['ok' => false, 'error' => 'Invalid data']);
            exit;
        }
        try {
            $stmt = $this->db->prepare(
                'UPDATE persons SET birth_order = :order WHERE person_id = :id
                 AND (father_id = :pid OR mother_id = :pid)
                 AND (is_deleted = 0 OR is_deleted IS NULL)'
            );
            foreach ($ids as $position => $personId) {
                $stmt->execute([
                    ':order' => $position + 1,
                    ':id'    => (int)$personId,
                    ':pid'   => $parentId,
                ]);
            }
            echo json_encode(['ok' => true]);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
