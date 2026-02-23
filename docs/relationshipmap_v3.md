# FamilyTree 3.0 – Engine Safe Relationship Mapping
Tamil Nadu Kinship System (Deterministic Software Version)

============================================================

IMPORTANT PRINCIPLE

1. Always determine relationship TYPE first.
2. Blood relation ALWAYS overrides in-law.
3. Never use ambiguous Tamil terms.
4. One English relation = One Tamil label.
5. Never map by Tamil word directly — use relationship logic.

============================================================

SECTION 1 – DIRECT BLOOD RELATIONS

| English            | Engine Condition                                   | Tamil     |
|--------------------|---------------------------------------------------|-----------|
| Father             | A.father_id == B.id                               | அப்பா     |
| Mother             | A.mother_id == B.id                               | அம்மா     |
| Son                | B.father_id == A.id AND B.gender=male             | மகன்      |
| Daughter           | B.father_id == A.id AND B.gender=female           | மகள்      |
| Elder Brother      | sibling + elder + male                            | அண்ணன்    |
| Younger Brother    | sibling + younger + male                          | தம்பி     |
| Elder Sister       | sibling + elder + female                          | அக்கா     |
| Younger Sister     | sibling + younger + female                        | தங்கை     |

============================================================

SECTION 2 – GRANDPARENTS & DESCENDANTS

| English                   | Generation Difference | Tamil                |
|---------------------------|----------------------|----------------------|
| Grandfather               | -2                   | தாத்தா              |
| Grandmother               | -2                   | பாட்டி              |
| Great Grandfather         | -3                   | பெரிய தாத்தா        |
| Great Grandmother         | -3                   | பெரிய பாட்டி        |
| Great Great Grandfather   | -4                   | முதுமுதுத் தாத்தா   |
| Great Great Grandmother   | -4                   | முதுமுதுப் பாட்டி   |
| Grandson                  | +2                   | பேரன்               |
| Granddaughter             | +2                   | பேத்தி              |
| Great Grandson            | +3                   | பேரப்பேரன்         |
| Great Granddaughter       | +3                   | பேரப்பேத்தி        |

For deeper generations:

Ancestors:
"Xம் தலைமுறை மூதாதையர்"

Descendants:
"Xம் தலைமுறை சந்ததி"

============================================================

SECTION 3 – PATERNAL SIDE (FATHER'S SIDE)

| English                          | Condition                   | Tamil        |
|----------------------------------|-----------------------------|-------------|
| Father's Elder Brother           | paternal + elder            | பெரியப்பா   |
| Father's Younger Brother         | paternal + younger          | சித்தப்பா   |
| Father's Sister                  | paternal                    | அத்தை       |
| Wife of Father's Elder Brother   | paternal spouse             | பெரியம்மா   |
| Wife of Father's Younger Brother | paternal spouse             | சித்தி      |
| Husband of Father's Sister       | paternal spouse             | மாமா       |

============================================================

SECTION 4 – MATERNAL SIDE (MOTHER'S SIDE)

| English                          | Condition                   | Tamil        |
|----------------------------------|-----------------------------|-------------|
| Mother's Brother                 | maternal                    | மாமா       |
| Mother's Elder Sister            | maternal + elder            | பெரியம்மா   |
| Mother's Younger Sister          | maternal + younger          | சித்தி      |
| Wife of Mother's Brother         | maternal spouse             | மாமி       |
| Husband of Mother's Sister       | maternal spouse             | மாமா       |

============================================================

SECTION 5 – NEPHEW & NIECE (ENGINE SAFE)

⚠ DO NOT USE மருமகன் / மருமகள் here.

| English                    | Condition                          | Tamil (Safe)        |
|----------------------------|------------------------------------|---------------------|
| Nephew (brother's son)     | sibling child + male               | சகோதரர் மகன்        |
| Niece (brother's daughter) | sibling child + female             | சகோதரி மகள்         |
| Nephew (sister's son)      | sibling child + male               | சகோதரி மகன்         |
| Niece (sister's daughter)  | sibling child + female             | சகோதரி மகள்         |

============================================================

SECTION 6 – COUSINS (LCA BASED)

Let:
X = distance A → LCA
Y = distance B → LCA

cousin_level = min(X, Y) - 1
removed = abs(X - Y)

| English                  | Condition        | Tamil (Safe)              |
|--------------------------|-----------------|---------------------------|
| First Cousin             | X=2 AND Y=2     | முதல் தலைமுறை உறவு       |
| Second Cousin            | X=3 AND Y=3     | இரண்டாம் தலைமுறை உறவு    |
| Third Cousin             | X=4 AND Y=4     | மூன்றாம் தலைமுறை உறவு    |
| Cousin Once Removed      | removed = 1     | மாற்றுத் தலைமுறை உறவு     |

Avoid:
மச்சான்
மச்சினி

============================================================

SECTION 7 – SPOUSE RELATIONS

| English | Tamil   |
|---------|--------|
| Husband | கணவன் |
| Wife    | மனைவி |

============================================================

SECTION 8 – IN-LAWS (STRICT SPOUSE EDGE ONLY)

| English           | Condition                | Tamil       |
|-------------------|--------------------------|------------|
| Father-in-law     | spouse parent male       | மாமனார்    |
| Mother-in-law     | spouse parent female     | மாமியார்   |
| Son-in-law        | daughter's spouse        | மாப்பிள்ளை |
| Daughter-in-law   | son's spouse             | மருமகள்   |
| Brother-in-law    | spouse sibling male      | மைத்துனர்  |
| Sister-in-law     | spouse sibling female    | மைத்துனி   |

============================================================

SECTION 9 – ENGINE PRIORITY ORDER

1. Same person
2. Parent / Child
3. Sibling
4. Grandparent / Grandchild
5. Uncle / Aunt
6. Nephew / Niece
7. Cousin
8. In-law
9. Numeric generation fallback

Blood relation ALWAYS overrides in-law.

============================================================

SECTION 10 – BUG PREVENTION RULE

If B.father_id == A.id OR B.mother_id == A.id:
→ Relationship = Child
→ Never evaluate in-law logic.

Example:

Magizhvadana
generation_difference = +1
father_id = Ananth

Correct output:
மகள்

Never:
மருமகள்

============================================================

END OF ENGINE SAFE RELATIONSHIP TABLE