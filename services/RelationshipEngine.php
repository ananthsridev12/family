<?php
declare(strict_types=1);

final class RelationshipEngine
{
    private PDO $db;
    private array $people = [];
    private array $ancestorCache = [];
    private array $dictionary = [];
    private array $resolving = [];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function resolve(int $personAId, int $personBId): array
    {
        $pairKey = $personAId . ':' . $personBId;
        if (isset($this->resolving[$pairKey])) {
            return $this->fromKey('no_blood_relation', null, null, 0, 'Direct', null);
        }
        $this->resolving[$pairKey] = true;

        try {
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

        $lca = $this->findLowestCommonAncestor($personAId, $personBId);
        if ($lca !== null) {
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
                $key = $this->nephewNieceKeyByContext($personAId, $personBId, (string)$b['gender']);
                return $this->fromKey($key, null, null, $generationDifference, $side, $lca['lca_id']);
            }

            if ($x === 2 && $y === 1) {
                return $this->fromKey($this->uncleAuntKeyByContext($personAId, $personBId, (string)$b['gender'], $side), null, null, $generationDifference, $side, $lca['lca_id']);
            }

            if ($cousinLevel === 1 && $removed === 0) {
                $key = ((string)$b['gender'] === 'female') ? 'first_cousin_female' : 'first_cousin_male';
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
        }

        $inLaw = $this->resolveInLaw($a, $b);
        if ($inLaw !== null) {
            return $inLaw;
        }

        $extendedAffinal = $this->resolveExtendedAffinal($a, $b);
        if ($extendedAffinal !== null) {
            return $extendedAffinal;
        }

        return $this->fromKey('no_blood_relation', null, null, 0, 'Direct', null);
        } finally {
            unset($this->resolving[$pairKey]);
        }
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

        // Parent -> child's spouse must resolve first to avoid broader sibling-spouse matches.
        if ($bSpouseId > 0 && $this->isMutualSpouse($bid, $bSpouseId)) {
            if ($this->isParentOf($aid, $bSpouseId)) {
                $isFemale = ((string)$b['gender'] === 'female');
                return $this->fromKey($isFemale ? 'daughter_in_law' : 'son_in_law', null, null, 1, 'In-Law', null);
            }
        }

        // Co-sister (brothers' wives): both female, each is spouse of male siblings.
        if ($this->isCoSisterBrothersWives($aid, $bid)) {
            return $this->fromKey('co_sister_brothers_wives', null, null, 0, 'In-Law', null);
        }

        // Brother's wife -> sister (A is brother's wife, B is husband's sister).
        if ($this->isHusbandsSisterFromBrothersWife($aid, $bid)) {
            return $this->fromKey('husbands_sister_safe', null, null, 0, 'In-Law', null);
        }

        // Sister -> brother's wife.
        if ($this->isBrothersWifeFromSister($aid, $bid)) {
            return $this->fromKey('brothers_wife_safe', null, null, 0, 'In-Law', null);
        }

        // Sibling -> sister's husband.
        if ($this->isSistersHusband($aid, $bid)) {
            return $this->fromKey('sisters_husband_safe', null, null, 0, 'In-Law', null);
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
                        $key = ((string)$b['gender'] === 'female') ? 'sister_in_law' : 'brother_in_law';
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
            if ((string)$b['gender'] === 'female') {
                $key = $this->inLawAuntByFatherSide($aid, $bid);
            } else {
                $key = 'mama';
            }
            return $this->fromKey($key, null, null, -1, 'In-Law', null);
        }
        if ($this->isSpouseOfSibling($bid, $motherId)) {
            if ((string)$b['gender'] === 'female') {
                $key = 'mami';
            } else {
                $key = 'mama';
            }
            return $this->fromKey($key, null, null, -1, 'In-Law', null);
        }

        if ($bSpouseId > 0 && $this->isMutualSpouse($bid, $bSpouseId)) {
            $bSpouse = $this->people[$bSpouseId] ?? null;
            if ($bSpouse !== null && $this->isSibling($aid, (int)$bSpouse['person_id'])) {
                $key = ((string)$b['gender'] === 'female') ? 'sister_in_law' : 'brother_in_law';
                return $this->fromKey($key, null, null, 0, 'In-Law', null);
            }
        }

        return null;
    }

    private function resolveExtendedAffinal(array $a, array $b): ?array
    {
        $aid = (int)$a['person_id'];
        $bid = (int)$b['person_id'];

        $bSpouseId = $this->mutualSpouseId($bid);
        if ($bSpouseId > 0 && $bSpouseId !== $aid) {
            $base = $this->resolve($aid, $bSpouseId);
            if ($this->isUsableAffinalBase($base)) {
                return $this->affinalFromBase($base);
            }
        }

        $aSpouseId = $this->mutualSpouseId($aid);
        if ($aSpouseId > 0 && $aSpouseId !== $bid) {
            $base = $this->resolve($aSpouseId, $bid);
            if ($this->isUsableAffinalBase($base)) {
                return $this->affinalFromBase($base);
            }
        }

        return null;
    }

    private function isUsableAffinalBase(array $base): bool
    {
        $key = (string)($base['key'] ?? '');
        if ($key === '' || in_array($key, ['unknown', 'no_blood_relation', 'self', 'husband', 'wife'], true)) {
            return false;
        }
        return true;
    }

    private function affinalFromBase(array $base): array
    {
        $key = (string)($base['key'] ?? '');
        if (in_array($key, ['son', 'daughter'], true)) {
            return $this->fromKey($key === 'son' ? 'daughter_in_law' : 'son_in_law', null, null, 1, 'In-Law', null);
        }
        if (in_array($key, ['brother', 'sister', 'elder_brother', 'younger_brother', 'elder_sister', 'younger_sister'], true)) {
            $isFemale = str_contains($key, 'sister');
            return $this->fromKey($isFemale ? 'sister_in_law' : 'brother_in_law', null, null, 0, 'In-Law', null);
        }
        $map = [
            'paternal_uncle' => 'mama',
            'periyappa' => 'mama',
            'chithappa' => 'mama',
            'mama' => 'mama',
            'paternal_aunt' => 'athai',
            'athai' => 'athai',
            'maternal_aunt' => 'athai',
            'periyamma' => 'athai',
            'chithi' => 'athai',
            'nephew' => 'nephew',
            'nephew_brother_son' => 'nephew_brother_son',
            'nephew_sister_son' => 'nephew_sister_son',
            'niece' => 'niece',
            'niece_brother_daughter' => 'niece_brother_daughter',
            'niece_sister_daughter' => 'niece_sister_daughter',
        ];
        if (isset($map[$key])) {
            return $this->fromKey($map[$key], null, null, (int)($base['generation_difference'] ?? 0), 'In-Law', null);
        }

        // Exact deterministic fallback for long-distance affinal relatives.
        $baseEn = trim((string)($base['title_en'] ?? 'Relative'));
        $baseTa = trim((string)($base['title_ta'] ?? 'உறவினர்'));
        return $this->buildResult(
            "Spouse's " . $baseEn,
            "துணையின் " . $baseTa,
            $base['cousin_level'] ?? null,
            $base['removed'] ?? null,
            (int)($base['generation_difference'] ?? 0),
            'In-Law',
            null,
            'spouse_of_' . $key
        );
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

    private function mutualSpouseId(int $personId): int
    {
        if (!isset($this->people[$personId])) {
            return 0;
        }
        $sid = (int)($this->people[$personId]['spouse_id'] ?? 0);
        if ($sid > 0 && $this->isMutualSpouse($personId, $sid)) {
            return $sid;
        }
        return 0;
    }

    private function isCoSisterBrothersWives(int $aid, int $bid): bool
    {
        if (!isset($this->people[$aid], $this->people[$bid])) {
            return false;
        }
        if ((string)$this->people[$aid]['gender'] !== 'female' || (string)$this->people[$bid]['gender'] !== 'female') {
            return false;
        }
        $aSpouse = $this->mutualSpouseId($aid);
        $bSpouse = $this->mutualSpouseId($bid);
        if ($aSpouse <= 0 || $bSpouse <= 0) {
            return false;
        }
        if ((string)$this->people[$aSpouse]['gender'] !== 'male' || (string)$this->people[$bSpouse]['gender'] !== 'male') {
            return false;
        }
        return $this->isSibling($aSpouse, $bSpouse);
    }

    private function isHusbandsSisterFromBrothersWife(int $aid, int $bid): bool
    {
        if (!isset($this->people[$aid], $this->people[$bid])) {
            return false;
        }
        $aSpouse = $this->mutualSpouseId($aid);
        if ($aSpouse <= 0) {
            return false;
        }
        if ((string)$this->people[$aid]['gender'] !== 'female' || (string)$this->people[$aSpouse]['gender'] !== 'male') {
            return false;
        }
        return ((string)$this->people[$bid]['gender'] === 'female') && $this->isSibling($aSpouse, $bid);
    }

    private function isBrothersWifeFromSister(int $aid, int $bid): bool
    {
        if (!isset($this->people[$aid], $this->people[$bid])) {
            return false;
        }
        if ((string)$this->people[$aid]['gender'] !== 'female' || (string)$this->people[$bid]['gender'] !== 'female') {
            return false;
        }
        $bSpouse = $this->mutualSpouseId($bid);
        if ($bSpouse <= 0 || (string)$this->people[$bSpouse]['gender'] !== 'male') {
            return false;
        }
        return $this->isSibling($aid, $bSpouse);
    }

    private function isSistersHusband(int $aid, int $bid): bool
    {
        if (!isset($this->people[$aid], $this->people[$bid])) {
            return false;
        }
        if ((string)$this->people[$bid]['gender'] !== 'male') {
            return false;
        }
        foreach ($this->people as $sibling) {
            $sid = (int)$sibling['person_id'];
            if (!$this->isSibling($aid, $sid)) {
                continue;
            }
            if ((string)$sibling['gender'] !== 'female') {
                continue;
            }
            if ($this->mutualSpouseId($sid) === $bid) {
                return true;
            }
        }
        return false;
    }

    private function nephewNieceKeyByContext(int $aid, int $bid, string $targetGender): string
    {
        $a = $this->people[$aid] ?? null;
        $b = $this->people[$bid] ?? null;
        if ($a === null || $b === null) {
            return $targetGender === 'female' ? 'niece' : 'nephew';
        }

        $fromBrother = false;
        $fromSister = false;
        $fatherId = (int)$b['father_id'];
        $motherId = (int)$b['mother_id'];
        if ($fatherId > 0 && $this->isSibling($aid, $fatherId)) {
            $fromBrother = (($this->people[$fatherId]['gender'] ?? '') === 'male');
            $fromSister = (($this->people[$fatherId]['gender'] ?? '') === 'female');
        }
        if ($motherId > 0 && $this->isSibling($aid, $motherId)) {
            $fromBrother = $fromBrother || (($this->people[$motherId]['gender'] ?? '') === 'male');
            $fromSister = $fromSister || (($this->people[$motherId]['gender'] ?? '') === 'female');
        }

        if ($targetGender === 'female') {
            if ($fromBrother) {
                return 'niece_brother_daughter';
            }
            if ($fromSister) {
                return 'niece_sister_daughter';
            }
            return 'niece';
        }

        if ($fromBrother) {
            return 'nephew_brother_son';
        }
        if ($fromSister) {
            return 'nephew_sister_son';
        }
        return 'nephew';
    }

    private function uncleAuntKeyByContext(int $aid, int $bid, string $gender, string $side): string
    {
        if ($gender === 'male') {
            if ($side === 'Maternal') {
                return 'mama';
            }
            if ($side === 'Paternal') {
                $a = $this->people[$aid] ?? null;
                $fatherId = (int)($a['father_id'] ?? 0);
                if ($fatherId > 0) {
                    if ($this->isOlderByBirthData($bid, $fatherId)) {
                        return 'periyappa';
                    }
                    if ($this->isOlderByBirthData($fatherId, $bid)) {
                        return 'chithappa';
                    }
                }
                return 'paternal_uncle';
            }
            return 'paternal_uncle';
        }

        if ($gender === 'female') {
            if ($side === 'Maternal') {
                $a = $this->people[$aid] ?? null;
                $motherId = (int)($a['mother_id'] ?? 0);
                if ($motherId > 0) {
                    if ($this->isOlderByBirthData($bid, $motherId)) {
                        return 'periyamma';
                    }
                    if ($this->isOlderByBirthData($motherId, $bid)) {
                        return 'chithi';
                    }
                }
                return 'maternal_aunt';
            }
            return 'athai';
        }

        return $this->uncleAuntKey($gender, $side);
    }

    private function inLawAuntByFatherSide(int $aid, int $bid): string
    {
        $a = $this->people[$aid] ?? null;
        $fatherId = (int)($a['father_id'] ?? 0);
        $bSpouseId = (int)($this->people[$bid]['spouse_id'] ?? 0);
        if ($fatherId > 0 && $bSpouseId > 0 && $this->isSibling($bSpouseId, $fatherId)) {
            if ($this->isOlderByBirthData($bSpouseId, $fatherId)) {
                return 'periyamma';
            }
            if ($this->isOlderByBirthData($fatherId, $bSpouseId)) {
                return 'chithi';
            }
        }
        return 'paternal_aunt';
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

    private function spouseSiblingKey(string $speakerGender, string $targetGender): string
    {
        if ($targetGender === 'male') {
            return 'brother_in_law';
        }
        if ($targetGender === 'female') {
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
        $leftBirth = $this->personBirthComparable($left);
        $rightBirth = $this->personBirthComparable($right);
        if ($leftBirth === null || $rightBirth === null) {
            return false;
        }
        // First compare year when available from either DOB or birth_year.
        if ($leftBirth['year'] < $rightBirth['year']) {
            return true;
        }
        if ($leftBirth['year'] > $rightBirth['year']) {
            return false;
        }

        // Same year: compare full DOB only when both sides have complete date.
        if ($leftBirth['has_full_dob'] && $rightBirth['has_full_dob']) {
            return $leftBirth['date_key'] < $rightBirth['date_key'];
        }

        // Same year but incomplete date precision: cannot decide elder/younger.
        return false;
    }

    private function personBirthComparable(array $person): ?array
    {
        $dob = trim((string)($person['date_of_birth'] ?? ''));
        if ($dob !== '' && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dob, $m) === 1) {
            $year = (int)$m[1];
            $month = (int)$m[2];
            $day = (int)$m[3];
            if ($year > 0 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return [
                    'year' => $year,
                    'date_key' => $year * 10000 + $month * 100 + $day,
                    'has_full_dob' => true,
                ];
            }
        }

        $by = (int)($person['birth_year'] ?? 0);
        if ($by > 0) {
            return [
                'year' => $by,
                'date_key' => null,
                'has_full_dob' => false,
            ];
        }

        return null;
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
        if ($level === 1) {
            $base = 'முதல் தலைமுறை உறவு';
        } elseif ($level === 2) {
            $base = 'இரண்டாம் தலைமுறை உறவு';
        } elseif ($level === 3) {
            $base = 'மூன்றாம் தலைமுறை உறவு';
        } else {
            $base = $level . 'ஆம் தலைமுறை உறவு';
        }
        if ($removed > 0) {
            return 'மாற்றுத் தலைமுறை உறவு';
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
        ?int $lcaId,
        ?string $key = null
    ): array {
        return [
            'key' => $key,
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
            return $this->buildResult($key, $key, $cousinLevel, $removed, $generationDifference, $side, $lcaId, $key);
        }

        return $this->buildResult(
            $entry['title_en'],
            $entry['title_ta'],
            $cousinLevel,
            $removed,
            $generationDifference,
            $side,
            $lcaId,
            $key
        );
    }
}

