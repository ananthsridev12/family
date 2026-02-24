<?php
declare(strict_types=1);

final class MemberController extends BaseController
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
        $this->render('member/dashboard', ['title' => 'Member Dashboard']);
    }

    public function addPerson(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->createPersonFromMember();
            return;
        }

        $this->render('member/person_add', [
            'title' => 'Add Person',
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function editPerson(): void
    {
        $id = (int)($_GET['id'] ?? $_POST['person_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid person id.';
            header('Location: /index.php?route=member/family-list');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->updatePersonFromMember($id);
            return;
        }

        $person = $this->people->findById($id);
        if ($person === null) {
            $_SESSION['flash_error'] = 'Person not found.';
            header('Location: /index.php?route=member/family-list');
            exit;
        }
        if (!$this->canEditPerson($person)) {
            $_SESSION['flash_error'] = 'You cannot edit this record.';
            header('Location: /index.php?route=member/family-list');
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

    public function addMarriage(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->createMarriage();
            return;
        }

        $this->render('member/marriage_add', [
            'title' => 'Add Marriage',
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
            'marriages' => $this->listMarriages(),
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function editMarriage(): void
    {
        $id = (int)($_GET['id'] ?? $_POST['marriage_id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'Invalid marriage id.';
            header('Location: /index.php?route=member/add-marriage');
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->updateMarriage($id);
            return;
        }

        $marriage = $this->findMarriageById($id);
        if ($marriage === null) {
            $_SESSION['flash_error'] = 'Marriage not found.';
            header('Location: /index.php?route=member/add-marriage');
            exit;
        }

        $this->render('member/marriage_edit', [
            'title' => 'Edit Marriage',
            'marriage' => $marriage,
            'error' => $_SESSION['flash_error'] ?? null,
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_error'], $_SESSION['flash_success']);
    }

    public function familyList(): void
    {
        $items = $this->people->all(500);
        $currentUserId = (int)(app_user()['user_id'] ?? 0);
        $currentRole = app_user_role();
        $povId = current_pov_id();
        foreach ($items as &$item) {
            $item['age'] = $this->calculateAge($item);
            $item['can_edit'] = $currentRole === 'admin'
                || ((int)($item['is_locked'] ?? 0) !== 1 && (int)($item['created_by'] ?? 0) === $currentUserId);
            if ($povId > 0) {
                $rel = $this->engine->resolve($povId, (int)$item['person_id']);
                $en = trim((string)($rel['title_en'] ?? 'Unknown'));
                $ta = trim((string)($rel['title_ta'] ?? ''));
                $item['relationship_status'] = ($ta !== '' && $ta !== $en) ? ($en . ' / ' . $ta) : $en;
            } else {
                $item['relationship_status'] = '-';
            }
            $item['marital_status'] = ((int)($item['spouse_id'] ?? 0) > 0) ? 'Married/Linked' : 'Single/Unknown';
        }
        unset($item);
        $this->render('member/family_list', ['title' => 'Family List', 'items' => $items]);
    }

    public function treeView(): void
    {
        $rootId = (int)($_GET['person_id'] ?? current_pov_id());
        $root = $rootId > 0 ? $this->people->findById($rootId) : null;
        $this->render('member/tree_view', [
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
        $rows = [];
        if ($person !== null) {
            $rows = $this->buildAncestors($personId, 6, $side);
        }
        $this->render('member/ancestors', [
            'title' => 'Ancestors',
            'route_prefix' => 'member',
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
        $rows = [];
        if ($person !== null) {
            $rows = $this->buildDescendants($personId, 6);
        }
        $this->render('member/descendants', [
            'title' => 'Descendants',
            'route_prefix' => 'member',
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
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
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

        $this->render('member/relationship_finder', [
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
        $rows = $this->branchesModel->listWithCounts();
        $this->render('member/branches', ['title' => 'Branches', 'rows' => $rows]);
    }

    public function reports(): void
    {
        $this->render('member/reports', ['title' => 'Reports']);
    }

    public function settings(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $lang = (string)($_POST['lang'] ?? 'en');
            if (!in_array($lang, ['en', 'ta'], true)) {
                $lang = 'en';
            }
            $_SESSION['lang'] = $lang;
            $_SESSION['flash_success'] = 'Language updated.';
            header('Location: /index.php?route=member/settings');
            exit;
        }

        $this->render('member/settings', [
            'title' => 'Settings',
            'lang' => (string)($_SESSION['lang'] ?? 'en'),
            'success' => $_SESSION['flash_success'] ?? null,
        ]);
        unset($_SESSION['flash_success']);
    }

    private function createPersonFromMember(): void
    {
        if (!$this->verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member/add-person');
            exit;
        }

        $currentRole = app_user_role();
        $currentUserId = (int)(app_user()['user_id'] ?? 0);

        $existingPersonId = (int)($_POST['existing_person_id'] ?? 0);
        $referencePersonId = (int)($_POST['reference_person_id'] ?? 0);
        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $gender = (string)($_POST['gender'] ?? 'unknown');
        $dateOfBirth = $this->normalizeDate($_POST['date_of_birth'] ?? null);
        $birthYear = $this->normalizeInt($_POST['birth_year'] ?? null);
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
        $parentPersonId = (int)($_POST['parent_person_id'] ?? 0); // backward compatibility
        $parentLinkType = (string)($_POST['parent_link_type'] ?? 'father'); // backward compatibility
        $fatherPersonId = (int)($_POST['father_person_id'] ?? 0);
        $motherPersonId = (int)($_POST['mother_person_id'] ?? 0);
        $birthOrder = $this->normalizeInt($_POST['birth_order'] ?? null);
        $spouseMarriageDate = $this->normalizeDate($_POST['spouse_marriage_date'] ?? null);

        if (!in_array($gender, ['male', 'female', 'other', 'unknown'], true)) {
            $gender = 'unknown';
        }
        if (!in_array($relationType, ['none', 'child', 'spouse', 'father', 'mother', 'brother', 'sister', 'grandfather', 'grandmother'], true)) {
            $relationType = 'none';
        }
        if ($currentRole === 'limited_member' && in_array($relationType, ['brother', 'sister'], true)) {
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
            header('Location: /index.php?route=member/add-person');
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
                    ':created_by' => $currentUserId > 0 ? $currentUserId : null,
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

            $defaultAnchorId = (int)(app_user()['person_id'] ?? 0);
            $anchorId = $referencePersonId > 0 ? $referencePersonId : ($defaultAnchorId > 0 ? $defaultAnchorId : (int)$targetPersonId);
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

        header('Location: /index.php?route=member/add-person');
        exit;
    }

    private function updatePersonFromMember(int $id): void
    {
        if (!$this->verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member/edit-person&id=' . $id);
            exit;
        }

        $person = $this->people->findById($id);
        if ($person === null) {
            $_SESSION['flash_error'] = 'Person not found.';
            header('Location: /index.php?route=member/family-list');
            exit;
        }
        if (!$this->canEditPerson($person)) {
            $_SESSION['flash_error'] = 'You cannot edit this record.';
            header('Location: /index.php?route=member/family-list');
            exit;
        }

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        if ($fullName === '') {
            $_SESSION['flash_error'] = 'Full name is required.';
            header('Location: /index.php?route=member/edit-person&id=' . $id);
            exit;
        }

        $gender = (string)($_POST['gender'] ?? 'unknown');
        if (!in_array($gender, ['male', 'female', 'other', 'unknown'], true)) {
            $gender = 'unknown';
        }
        $fatherPersonId = (int)($_POST['father_person_id'] ?? 0);
        $motherPersonId = (int)($_POST['mother_person_id'] ?? 0);

        $this->people->update($id, [
            ':full_name' => $fullName,
            ':gender' => $gender,
            ':date_of_birth' => $this->normalizeDate($_POST['date_of_birth'] ?? null),
            ':birth_year' => $this->normalizeInt($_POST['birth_year'] ?? null),
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

        $_SESSION['flash_success'] = 'Person updated.';
        header('Location: /index.php?route=member/edit-person&id=' . $id);
        exit;
    }

    private function createMarriage(): void
    {
        if (!$this->verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member/add-marriage');
            exit;
        }

        $person1Id = (int)($_POST['person1_id'] ?? 0);
        $person2Id = (int)($_POST['person2_id'] ?? 0);
        if ($person1Id <= 0 || $person2Id <= 0 || $person1Id === $person2Id) {
            $_SESSION['flash_error'] = 'Select two different persons.';
            header('Location: /index.php?route=member/add-marriage');
            exit;
        }

        $status = (string)($_POST['status'] ?? 'married');
        if (!in_array($status, ['married', 'divorced', 'widowed'], true)) {
            $status = 'married';
        }

        $stmt = $this->db->prepare(
            'INSERT INTO marriages (person1_id, person2_id, marriage_date, divorce_date, status)
             VALUES (:p1, :p2, :md, :dd, :status)'
        );

        try {
            $check = $this->db->prepare(
                'SELECT 1 FROM marriages
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
            if ($check->fetch()) {
                throw new RuntimeException('Marriage already exists between selected persons.');
            }

            $stmt->execute([
                ':p1' => $person1Id,
                ':p2' => $person2Id,
                ':md' => $this->normalizeDate($_POST['marriage_date'] ?? null),
                ':dd' => $this->normalizeDate($_POST['divorce_date'] ?? null),
                ':status' => $status,
            ]);

            $u = $this->db->prepare('UPDATE persons SET spouse_id = :spouse WHERE person_id = :id');
            $u->execute([':spouse' => $person2Id, ':id' => $person1Id]);
            $u->execute([':spouse' => $person1Id, ':id' => $person2Id]);

            $_SESSION['flash_success'] = 'Marriage added.';
        } catch (Throwable $e) {
            $_SESSION['flash_error'] = 'Failed to add marriage: ' . $e->getMessage();
        }

        header('Location: /index.php?route=member/add-marriage');
        exit;
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
            $this->linkParentChild($anchorId, $targetId, $parentType, $birthOrder);
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

    private function linkParentChild(int $parentId, int $childId, string $parentType, ?int $birthOrder = null): void
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

        if ($birthOrder !== null && $birthOrder > 0) {
            // Birth order reserved for UI/extension. No dedicated table in v3 schema.
        }
    }

    private function verifyCsrf(string $token): bool
    {
        return function_exists('verify_csrf') ? verify_csrf($token) : true;
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

    private function listMarriages(): array
    {
        $sql = 'SELECT m.marriage_id, m.person1_id, m.person2_id, m.marriage_date, m.divorce_date, m.status,
                       p1.full_name AS person1_name, p2.full_name AS person2_name
                FROM marriages m
                INNER JOIN persons p1 ON p1.person_id = m.person1_id
                INNER JOIN persons p2 ON p2.person_id = m.person2_id
                ORDER BY m.marriage_id DESC
                LIMIT 200';
        return $this->db->query($sql)->fetchAll() ?: [];
    }

    private function findMarriageById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT m.marriage_id, m.person1_id, m.person2_id, m.marriage_date, m.divorce_date, m.status,
                    p1.full_name AS person1_name, p2.full_name AS person2_name
             FROM marriages m
             INNER JOIN persons p1 ON p1.person_id = m.person1_id
             INNER JOIN persons p2 ON p2.person_id = m.person2_id
             WHERE m.marriage_id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function updateMarriage(int $id): void
    {
        if (!$this->verifyCsrf((string)($_POST['csrf_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'Invalid CSRF token.';
            header('Location: /index.php?route=member/edit-marriage&id=' . $id);
            exit;
        }
        $status = (string)($_POST['status'] ?? 'married');
        if (!in_array($status, ['married', 'divorced', 'widowed'], true)) {
            $status = 'married';
        }

        $stmt = $this->db->prepare(
            'UPDATE marriages
             SET marriage_date = :md, divorce_date = :dd, status = :status
             WHERE marriage_id = :id'
        );
        $stmt->execute([
            ':md' => $this->normalizeDate($_POST['marriage_date'] ?? null),
            ':dd' => $this->normalizeDate($_POST['divorce_date'] ?? null),
            ':status' => $status,
            ':id' => $id,
        ]);
        $_SESSION['flash_success'] = 'Marriage updated.';
        header('Location: /index.php?route=member/edit-marriage&id=' . $id);
        exit;
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
        // If anchor is stored as mother for child and father is missing, fill father with spouse.
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

        // If anchor is stored as father for child and mother is missing, fill mother with spouse.
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

    private function buildAncestors(int $personId, int $maxDepth, string $side = 'any'): array
    {
        $rows = [];
        $queue = [['id' => $personId, 'depth' => 0, 'first_edge' => 'direct']];
        $seen = [];
        while (!empty($queue)) {
            $node = array_shift($queue);
            $id = (int)$node['id'];
            $depth = (int)$node['depth'];
            $firstEdge = (string)($node['first_edge'] ?? 'direct');
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
                $distance = $depth + 1;
                $childFirstEdge = $depth === 0 ? strtolower((string)$p['link']) : $firstEdge;
                $sideLabel = $childFirstEdge === 'father' ? 'Paternal' : ($childFirstEdge === 'mother' ? 'Maternal' : 'Any');
                $matchesSide = $side === 'any'
                    || ($side === 'paternal' && $childFirstEdge === 'father')
                    || ($side === 'maternal' && $childFirstEdge === 'mother');
                if ($matchesSide) {
                    $rows[] = [
                        'generation' => $distance,
                        'link' => $this->ancestorLabel($distance, (string)$pp['gender']),
                        'side' => $sideLabel,
                        'person_id' => $pid,
                        'name' => (string)$pp['full_name'],
                        'gender' => (string)$pp['gender'],
                    ];
                }
                if ($matchesSide) {
                    $queue[] = ['id' => $pid, 'depth' => $distance, 'first_edge' => $childFirstEdge];
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

    private function canEditPerson(array $person): bool
    {
        $role = app_user_role();
        if ($role === 'admin') {
            return true;
        }
        if ((int)($person['is_locked'] ?? 0) === 1) {
            return false;
        }
        $currentUserId = (int)(app_user()['user_id'] ?? 0);
        return $currentUserId > 0 && (int)($person['created_by'] ?? 0) === $currentUserId;
    }
}
