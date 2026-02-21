<?php
declare(strict_types=1);

final class RelationshipEngine
{
    private PDO $db;
    private array $people = [];
    private array $ancestorCache = [];
    private array $dictionary = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function resolve(int $personAId, int $personBId): array
    {
        $this->loadPeople();
        $this->loadDictionary();

        if (!isset($this->people[$personAId]) || !isset($this->people[$personBId])) {
            return $this->fromKey('unknown', null, null, 0, 'Direct', null);
        }

        if ($personAId === $personBId) {
            return $this->fromKey('self', null, null, 0, 'Direct', $personAId);
        }

        $a = $this->people[$personAId];
        $b = $this->people[$personBId];

        $direct = $this->resolveDirect($a, $b);
        if ($direct !== null) {
            return $direct;
        }

        $inLaw = $this->resolveInLaw($a, $b);
        if ($inLaw !== null) {
            return $inLaw;
        }

        $lca = $this->findLowestCommonAncestor($personAId, $personBId);
        if ($lca === null) {
            return $this->fromKey('no_blood_relation', null, null, 0, 'Direct', null);
        }

        $x = $lca['distance_a'];
        $y = $lca['distance_b'];
        $cousinLevel = min($x, $y) - 1;
        $removed = abs($x - $y);
        $generationDifference = $y - $x;
        $side = $this->sideFromEdge($lca['first_edge_a']);

        if ($x === 0 && $y > 0) {
            return $this->descendantResult((string)$b['gender'], $y, $generationDifference, $side, $lca['lca_id']);
        }

        if ($y === 0 && $x > 0) {
            return $this->ancestorResult((string)$b['gender'], $x, $side, $generationDifference, $lca['lca_id']);
        }

        if ($x === 1 && $y === 1) {
            $key = $this->siblingKeyByAge($personAId, $personBId, (string)$b['gender']);
            return $this->fromKey($key, null, null, 0, $side, $lca['lca_id']);
        }

        if ($x === 1 && $y === 2) {
            return $this->fromKey($this->uncleAuntKey((string)$b['gender'], $side), null, null, $generationDifference, $side, $lca['lca_id']);
        }

        if ($x === 2 && $y === 1) {
            $key = ((string)$b['gender'] === 'female') ? 'niece' : 'nephew';
            return $this->fromKey($key, null, null, $generationDifference, $side, $lca['lca_id']);
        }

        if ($cousinLevel === 1 && $removed === 0) {
            $key = ((string)$b['gender'] === 'female') ? 'macchini' : 'machan';
            return $this->fromKey($key, null, null, $generationDifference, $side, $lca['lca_id']);
        }

        if ($cousinLevel >= 1) {
            return $this->buildResult(
                $this->cousinTitleEn($cousinLevel, $removed),
                $this->cousinTitleTa($cousinLevel, $removed),
                $cousinLevel,
                $removed,
                $generationDifference,
                $side,
                $lca['lca_id']
            );
        }

        return $this->fromKey('relative', null, null, $generationDifference, $side, $lca['lca_id']);
    }

    private function resolveDirect(array $a, array $b): ?array
    {
        $aid = (int)$a['person_id'];
        $bid = (int)$b['person_id'];

        if ((int)$a['father_id'] === $bid) {
            return $this->fromKey('father', null, null, -1, 'Paternal', $bid);
        }

        if ((int)$a['mother_id'] === $bid) {
            return $this->fromKey('mother', null, null, -1, 'Maternal', $bid);
        }

        if ((int)$b['father_id'] === $aid || (int)$b['mother_id'] === $aid) {
            $key = ((string)$b['gender'] === 'female') ? 'daughter' : 'son';
            return $this->fromKey($key, null, null, 1, 'Direct', $aid);
        }

        if ($this->isMutualSpouse($aid, $bid)) {
            $key = ((string)$b['gender'] === 'female') ? 'wife' : 'husband';
            return $this->fromKey($key, null, null, 0, 'Direct', null);
        }

        if ($this->isSibling($aid, $bid)) {
            $key = $this->siblingKeyByAge($aid, $bid, (string)$b['gender']);
            return $this->fromKey($key, null, null, 0, 'Direct', null);
        }

        return null;
    }

    private function resolveInLaw(array $a, array $b): ?array
    {
        $aid = (int)$a['person_id'];
        $bid = (int)$b['person_id'];
        $spouseId = (int)$a['spouse_id'];
        $bSpouseId = (int)$b['spouse_id'];

        // Co-sister: both female, each married to brothers.
        if ((string)$a['gender'] === 'female'
            && (string)$b['gender'] === 'female'
            && $spouseId > 0
            && $bSpouseId > 0
            && $this->isMutualSpouse($aid, $spouseId)
            && $this->isMutualSpouse($bid, $bSpouseId)
            && $this->isSibling($spouseId, $bSpouseId)) {
            return $this->fromKey('co_sister', null, null, 0, 'In-Law', null);
        }

        if ($spouseId > 0 && $this->isMutualSpouse($aid, $spouseId)) {
            $spouse = $this->people[$spouseId] ?? null;
            if ($spouse !== null) {
                if ((int)$spouse['father_id'] === $bid) {
                    return $this->fromKey('father_in_law', null, null, -1, 'In-Law', $bid);
                }
                if ((int)$spouse['mother_id'] === $bid) {
                    return $this->fromKey('mother_in_law', null, null, -1, 'In-Law', $bid);
                }
                if ($this->isSibling($spouseId, $bid)) {
                    $key = $this->spouseSiblingKey((string)$a['gender'], (string)$b['gender']);
                    return $this->fromKey($key, null, null, 0, 'In-Law', null);
                }

                // Spouse's sibling's spouse is also in-law.
                if ((int)$b['spouse_id'] > 0 && $this->isMutualSpouse($bid, (int)$b['spouse_id'])) {
                    if ($this->isSibling($spouseId, (int)$b['spouse_id'])) {
                        $key = ((string)$b['gender'] === 'female')
                            ? $this->sisterInLawKeyForSiblingSpouse($aid, $bid, (int)$b['spouse_id'])
                            : 'brother_in_law';
                        return $this->fromKey($key, null, null, 0, 'In-Law', null);
                    }
                }

                // Spouse's sibling's child (commonly shown as nephew/niece).
                $fatherId = (int)$b['father_id'];
                $motherId = (int)$b['mother_id'];
                if (($fatherId > 0 && $this->isSibling($spouseId, $fatherId))
                    || ($motherId > 0 && $this->isSibling($spouseId, $motherId))) {
                    $key = ((string)$b['gender'] === 'female') ? 'niece' : 'nephew';
                    return $this->fromKey($key, null, null, 1, 'In-Law', null);
                }
            }
        }

        // Parent's sibling's spouse (aunt/uncle by marriage).
        $fatherId = (int)$a['father_id'];
        $motherId = (int)$a['mother_id'];
        if ($this->isSpouseOfSibling($bid, $fatherId)) {
            $key = ((string)$b['gender'] === 'female') ? 'paternal_aunt' : 'paternal_uncle';
            return $this->fromKey($key, null, null, -1, 'In-Law', null);
        }
        if ($this->isSpouseOfSibling($bid, $motherId)) {
            $key = ((string)$b['gender'] === 'female') ? 'maternal_aunt' : 'mama';
            return $this->fromKey($key, null, null, -1, 'In-Law', null);
        }

        if ($bSpouseId > 0 && $this->isMutualSpouse($bid, $bSpouseId)) {
            $bSpouse = $this->people[$bSpouseId] ?? null;
            if ($bSpouse !== null && $this->isSibling($aid, (int)$bSpouse['person_id'])) {
                $key = ((string)$b['gender'] === 'female')
                    ? $this->sisterInLawKeyForSiblingSpouse($aid, $bid, (int)$bSpouse['person_id'])
                    : 'brother_in_law';
                return $this->fromKey($key, null, null, 0, 'In-Law', null);
            }
        }

        // Parent's child spouse -> son/daughter in-law.
        if ((int)$b['spouse_id'] > 0 && $this->isMutualSpouse($bid, (int)$b['spouse_id'])) {
            $bSpouseId = (int)$b['spouse_id'];
            if ($this->isParentOf($aid, $bSpouseId)) {
                $isFemale = ((string)$b['gender'] === 'female');
                return $this->fromKey($isFemale ? 'daughter_in_law' : 'son_in_law', null, null, 1, 'In-Law', null);
            }
        }

        return null;
    }

    private function findLowestCommonAncestor(int $aId, int $bId): ?array
    {
        $aAncestors = $this->ancestorMap($aId);
        $bAncestors = $this->ancestorMap($bId);

        $commonIds = array_intersect(array_keys($aAncestors), array_keys($bAncestors));
        if (empty($commonIds)) {
            return null;
        }

        $best = null;
        foreach ($commonIds as $commonId) {
            $distanceA = (int)$aAncestors[$commonId]['distance'];
            $distanceB = (int)$bAncestors[$commonId]['distance'];
            $score = $distanceA + $distanceB;
            if ($best === null || $score < $best['score']) {
                $best = [
                    'lca_id' => (int)$commonId,
                    'distance_a' => $distanceA,
                    'distance_b' => $distanceB,
                    'first_edge_a' => (string)$aAncestors[$commonId]['first_edge'],
                    'score' => $score,
                ];
            }
        }

        return $best;
    }

    private function ancestorMap(int $personId): array
    {
        if (isset($this->ancestorCache[$personId])) {
            return $this->ancestorCache[$personId];
        }

        $map = [
            $personId => ['distance' => 0, 'first_edge' => 'direct'],
        ];

        $queue = [
            ['id' => $personId, 'distance' => 0, 'first_edge' => 'direct'],
        ];

        while (!empty($queue)) {
            $node = array_shift($queue);
            $currentId = (int)$node['id'];
            $distance = (int)$node['distance'];
            $firstEdge = (string)$node['first_edge'];

            if ($distance >= 6) {
                continue;
            }

            $person = $this->people[$currentId] ?? null;
            if ($person === null) {
                continue;
            }

            $parents = [
                ['id' => (int)$person['father_id'], 'edge' => 'father'],
                ['id' => (int)$person['mother_id'], 'edge' => 'mother'],
            ];

            foreach ($parents as $parent) {
                $parentId = (int)$parent['id'];
                if ($parentId <= 0 || !isset($this->people[$parentId])) {
                    continue;
                }

                $nextDistance = $distance + 1;
                $nextFirstEdge = $distance === 0 ? (string)$parent['edge'] : $firstEdge;

                if (!isset($map[$parentId]) || $nextDistance < (int)$map[$parentId]['distance']) {
                    $map[$parentId] = ['distance' => $nextDistance, 'first_edge' => $nextFirstEdge];
                    $queue[] = ['id' => $parentId, 'distance' => $nextDistance, 'first_edge' => $nextFirstEdge];
                }
            }
        }

        $this->ancestorCache[$personId] = $map;
        return $map;
    }

    private function isMutualSpouse(int $aId, int $bId): bool
    {
        if (!isset($this->people[$aId], $this->people[$bId])) {
            return false;
        }

        return (int)$this->people[$aId]['spouse_id'] === $bId
            && (int)$this->people[$bId]['spouse_id'] === $aId;
    }

    private function isSibling(int $aId, int $bId): bool
    {
        if (!isset($this->people[$aId], $this->people[$bId])) {
            return false;
        }

        $a = $this->people[$aId];
        $b = $this->people[$bId];

        $fatherMatch = (int)$a['father_id'] > 0 && (int)$a['father_id'] === (int)$b['father_id'];
        $motherMatch = (int)$a['mother_id'] > 0 && (int)$a['mother_id'] === (int)$b['mother_id'];

        return $fatherMatch || $motherMatch;
    }

    private function isParentOf(int $parentId, int $childId): bool
    {
        if (!isset($this->people[$parentId], $this->people[$childId])) {
            return false;
        }
        $child = $this->people[$childId];
        return (int)$child['father_id'] === $parentId || (int)$child['mother_id'] === $parentId;
    }

    private function isSpouseOfSibling(int $candidateId, int $personId): bool
    {
        if ($candidateId <= 0 || $personId <= 0 || !isset($this->people[$candidateId], $this->people[$personId])) {
            return false;
        }
        $candidateSpouseId = (int)($this->people[$candidateId]['spouse_id'] ?? 0);
        return $candidateSpouseId > 0
            && $this->isMutualSpouse($candidateId, $candidateSpouseId)
            && $this->isSibling($candidateSpouseId, $personId);
    }

    private function sisterInLawKeyForSiblingSpouse(int $aid, int $bid, int $siblingId): string
    {
        $a = $this->people[$aid] ?? null;
        $b = $this->people[$bid] ?? null;
        $sibling = $this->people[$siblingId] ?? null;
        if ($a === null || $b === null || $sibling === null) {
            return 'sister_in_law';
        }
        if ((string)$a['gender'] !== 'male' || (string)$b['gender'] !== 'female' || (string)$sibling['gender'] !== 'male') {
            return 'sister_in_law';
        }
        return $this->isOlderByBirthData($siblingId, $aid) ? 'anni' : 'sister_in_law';
    }

    private function spouseSiblingKey(string $speakerGender, string $targetGender): string
    {
        if ($targetGender === 'male') {
            return 'machan';
        }
        if ($targetGender === 'female') {
            if ($speakerGender === 'male') {
                return 'macchini';
            }
            if ($speakerGender === 'female') {
                return 'nathanar';
            }
            return 'sister_in_law';
        }
        return 'relative';
    }

    private function siblingKeyByAge(int $aid, int $bid, string $targetGender): string
    {
        if ($targetGender === 'male') {
            if ($this->isOlderByBirthData($bid, $aid)) {
                return 'elder_brother';
            }
            if ($this->isOlderByBirthData($aid, $bid)) {
                return 'younger_brother';
            }
            return 'brother';
        }
        if ($targetGender === 'female') {
            if ($this->isOlderByBirthData($bid, $aid)) {
                return 'elder_sister';
            }
            if ($this->isOlderByBirthData($aid, $bid)) {
                return 'younger_sister';
            }
            return 'sister';
        }
        return 'relative';
    }

    private function isOlderByBirthData(int $leftId, int $rightId): bool
    {
        $left = $this->people[$leftId] ?? null;
        $right = $this->people[$rightId] ?? null;
        if ($left === null || $right === null) {
            return false;
        }
        $leftY = $this->personComparableYear($left);
        $rightY = $this->personComparableYear($right);
        if ($leftY === null || $rightY === null) {
            return false;
        }
        return $leftY < $rightY;
    }

    private function personComparableYear(array $person): ?int
    {
        $dob = trim((string)($person['date_of_birth'] ?? ''));
        if ($dob !== '') {
            $year = (int)substr($dob, 0, 4);
            if ($year > 0) {
                return $year;
            }
        }
        $by = (int)($person['birth_year'] ?? 0);
        return $by > 0 ? $by : null;
    }

    private function sideFromEdge(string $firstEdge): string
    {
        if ($firstEdge === 'father') {
            return 'Paternal';
        }
        if ($firstEdge === 'mother') {
            return 'Maternal';
        }
        if ($firstEdge === 'spouse') {
            return 'In-Law';
        }
        return 'Direct';
    }

    private function ancestorKey(string $gender, int $distance, string $side): string
    {
        if ($distance === 1) {
            return $gender === 'female' ? 'mother' : 'father';
        }
        if ($distance === 2) {
            if ($side === 'Paternal') {
                return $gender === 'female' ? 'paternal_grandmother' : 'paternal_grandfather';
            }
            if ($side === 'Maternal') {
                return $gender === 'female' ? 'maternal_grandmother' : 'maternal_grandfather';
            }
            return $gender === 'female' ? 'grandmother' : 'grandfather';
        }
        return 'ancestor';
    }

    private function descendantKey(string $gender, int $distance): string
    {
        if ($distance === 1) {
            return $gender === 'female' ? 'daughter' : 'son';
        }
        if ($distance === 2) {
            return $gender === 'female' ? 'granddaughter' : 'grandson';
        }
        return 'descendant';
    }

    private function ancestorResult(string $gender, int $distance, string $side, int $generationDifference, ?int $lcaId): array
    {
        if ($distance <= 2) {
            return $this->fromKey($this->ancestorKey($gender, $distance, $side), null, null, $generationDifference, $side, $lcaId);
        }
        if ($distance === 3) {
            return $this->buildResult(
                $gender === 'female' ? 'Great Grandmother' : 'Great Grandfather',
                $gender === 'female' ? 'பெரிய பாட்டி' : 'பெரிய தாத்தா',
                null,
                null,
                $generationDifference,
                $side,
                $lcaId
            );
        }
        if ($distance === 4) {
            return $this->buildResult(
                $gender === 'female' ? 'Great Great Grandmother' : 'Great Great Grandfather',
                $gender === 'female' ? 'முதுமுதுப் பாட்டி' : 'முதுமுதுத் தாத்தா',
                null,
                null,
                $generationDifference,
                $side,
                $lcaId
            );
        }
        return $this->buildResult(
            ($distance - 2) . 'th Great ' . ($gender === 'female' ? 'Grandmother' : 'Grandfather'),
            'மூதாதையர்',
            null,
            null,
            $generationDifference,
            $side,
            $lcaId
        );
    }

    private function descendantResult(string $gender, int $distance, int $generationDifference, string $side, ?int $lcaId): array
    {
        if ($distance <= 2) {
            return $this->fromKey($this->descendantKey($gender, $distance), null, null, $generationDifference, $side, $lcaId);
        }
        if ($distance === 3) {
            return $this->buildResult(
                $gender === 'female' ? 'Great Granddaughter' : 'Great Grandson',
                $gender === 'female' ? 'பேரப்பேத்தி' : 'பேரப்பேரன்',
                null,
                null,
                $generationDifference,
                $side,
                $lcaId
            );
        }
        if ($distance === 4) {
            return $this->buildResult(
                $gender === 'female' ? 'Great Great Granddaughter' : 'Great Great Grandson',
                $gender === 'female' ? 'கொள்ளுப்பேத்தி' : 'கொள்ளுப்பேரன்',
                null,
                null,
                $generationDifference,
                $side,
                $lcaId
            );
        }
        return $this->fromKey('descendant', null, null, $generationDifference, $side, $lcaId);
    }

    private function uncleAuntKey(string $gender, string $side): string
    {
        if ($gender === 'female') {
            return $side === 'Maternal' ? 'maternal_aunt' : 'paternal_aunt';
        }
        return $side === 'Maternal' ? 'mama' : 'paternal_uncle';
    }

    private function cousinTitleEn(int $level, int $removed): string
    {
        $base = $this->ordinal($level) . ' Cousin';
        if ($removed > 0) {
            $base .= ' ' . $this->removedText($removed);
        }
        return $base;
    }

    private function cousinTitleTa(int $level, int $removed): string
    {
        $base = $level . '-am nilai uravu';
        if ($removed > 0) {
            $base .= ' (' . $removed . ' thalaimurai vilagal)';
        }
        return $base;
    }

    private function ordinal(int $value): string
    {
        $list = [1 => 'First', 2 => 'Second', 3 => 'Third', 4 => 'Fourth', 5 => 'Fifth'];
        return $list[$value] ?? ($value . 'th');
    }

    private function removedText(int $removed): string
    {
        if ($removed === 1) {
            return 'Once Removed';
        }
        if ($removed === 2) {
            return 'Twice Removed';
        }
        return $removed . ' Times Removed';
    }

    private function loadDictionary(): void
    {
        if (!empty($this->dictionary)) {
            return;
        }

        $stmt = $this->db->query('SELECT `key`, title_en, title_ta FROM relationship_dictionary');
        foreach ($stmt->fetchAll() as $row) {
            $key = (string)$row['key'];
            $this->dictionary[$key] = [
                'title_en' => (string)$row['title_en'],
                'title_ta' => (string)$row['title_ta'],
            ];
        }
    }

    private function loadPeople(): void
    {
        if (!empty($this->people)) {
            return;
        }

        $sql = 'SELECT person_id, full_name, gender, date_of_birth, birth_year, father_id, mother_id, spouse_id FROM persons';
        $rows = $this->db->query($sql)->fetchAll();

        foreach ($rows as $row) {
            $id = (int)$row['person_id'];
            $row['father_id'] = (int)($row['father_id'] ?? 0);
            $row['mother_id'] = (int)($row['mother_id'] ?? 0);
            $row['spouse_id'] = (int)($row['spouse_id'] ?? 0);
            if ($row['father_id'] === $id) {
                $row['father_id'] = 0;
            }
            if ($row['mother_id'] === $id) {
                $row['mother_id'] = 0;
            }
            if ($row['spouse_id'] === $id) {
                $row['spouse_id'] = 0;
            }
            $this->people[$id] = $row;
        }
    }

    private function buildResult(
        string $titleEn,
        string $titleTa,
        ?int $cousinLevel,
        ?int $removed,
        int $generationDifference,
        string $side,
        ?int $lcaId
    ): array {
        return [
            'title_en' => $titleEn,
            'title_ta' => $titleTa,
            'cousin_level' => $cousinLevel,
            'removed' => $removed,
            'generation_difference' => $generationDifference,
            'side' => $side,
            'lca_id' => $lcaId,
        ];
    }

    private function fromKey(
        string $key,
        ?int $cousinLevel,
        ?int $removed,
        int $generationDifference,
        string $side,
        ?int $lcaId
    ): array {
        $entry = $this->dictionary[$key] ?? null;
        if ($entry === null) {
            return $this->buildResult($key, $key, $cousinLevel, $removed, $generationDifference, $side, $lcaId);
        }

        return $this->buildResult(
            $entry['title_en'],
            $entry['title_ta'],
            $cousinLevel,
            $removed,
            $generationDifference,
            $side,
            $lcaId
        );
    }
}

