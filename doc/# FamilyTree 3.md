# FamilyTree 3.0 – Full Rebuild Specification

## Project Location

Local:
D:\Projects\3.0 Familytree

Production Deploy Path:
/home1/de2shrnx/family.easi7.in

Git Remote: (respository created, readme file added, if you can deploy, delete all files in git and commit the changes)
https://github.com/ananthsridev12/family.git

---

# 1. Tech Stack (STRICT)

- PHP (no framework)
- MySQL (InnoDB)
- Bootstrap 5
- Front Controller: index.php
- Clean MVC folder structure
- No external heavy frameworks

---

# 2. Folder Structure

Create:

/config  
/controllers  
/models  
/services  
/views  
    /layouts  
    /admin  
    /member  
    /public  
/assets  
    /css  
    /js  
/sql  
/docs  
index.php  
.htaccess  
.cpanel.yml  
deploy.yml  

---

# 3. Core Architecture

Request Flow:

1. index.php?route=...
2. Route validation
3. Role check (admin/member/public)
4. Controller execution
5. Service logic (RelationshipEngine)
6. View rendering

---

# 4. Left Sidebar Layout (Admin + Member)

Sidebar items:

- Dashboard
- Add Person
- Family List
- Tree View
- Ancestors
- Descendants
- Relationship Finder
- Branches
- Reports
- Settings
- Logout

Sidebar collapsible (Bootstrap 5).

---

# 5. AJAX Person Search

Used in:

- Add Parent
- Add Spouse
- Relationship Finder

Endpoint:
/index.php?route=person/search

Requirements:

- Minimum 2 characters
- Debounce 300ms
- Limit 10 results
- Return JSON

Example response:

[
  { "id": 12, "name": "Ramaiah P (1965)" }
]

---

# 6. Relationship Engine (STRICT RULES)

File:
services/RelationshipEngine.php

MUST:

- Use only:
  - father_id
  - mother_id
  - spouse_id
- Never infer from names
- Never infer from branch_id

Algorithm:

1. Validate both persons exist
2. Direct checks:
   - parent
   - child
   - spouse
   - sibling
3. In-law checks
4. Build ancestor maps (max depth 6)
5. Find Lowest Common Ancestor (LCA)
6. Compute:
   - X = distance A → LCA
   - Y = distance B → LCA
7. Determine:

   cousin_level = min(X, Y) - 1
   removed = abs(X - Y)
   generation_difference = Y - X

8. Side detection:
   - first upward edge father → Paternal
   - mother → Maternal
   - spouse → In-Law

Return format:

[
  'title_en' => '',
  'title_ta' => '',
  'cousin_level' => int|null,
  'removed' => int|null,
  'generation_difference' => int,
  'side' => 'Paternal|Maternal|Direct|In-Law',
  'lca_id' => int|null
]

---

# 7. Relationship Dictionary (Tamil + English)

Table: relationship_dictionary

Columns:

- key
- title_en
- title_ta
- category
- side
- generation
- degree

Language switch:

?lang=ta → Tamil
Default → English

Create docs:

/docs/RelationshipMap_EN.md
/docs/RelationshipMap_TA.md

Must support Tamil Nadu kinship system:
- Periyappa
- Chithappa
- Athai
- Mama
- Machan
- etc.

Mapping must depend on:
- gender
- side
- generation
- cousin level

---

# 8. Branch / Lineage Rules

Important:

- branch_id is metadata only.
- lineage_path is UI grouping only.
- Relationship engine must ignore branches.

Branches page:

- Show branch
- Show members
- No relation inference

---

# 9. Tree View

Requirements:

- Expand/collapse
- AJAX load children
- Highlight selected person
- Up to 6 generations
- No full reload

---

# 10. Public Website Pages

Create public pages:

- Home
- About
- How It Works
- Features
- Tamil Relationship System
- Contact

Use:
views/public/

SEO friendly URLs via .htaccess.

---

# 11. Admin Panel

Admin can:

- CRUD persons
- Assign parents
- Assign marriages
- Manage branches
- Manage users
- View logs
- View full tree

---

# 12. Member Panel

Member can:

- Edit profile
- Add family
- View tree
- Find relationships

No global delete rights.

---

# 13. Database Integrity Rules

Enforce:

- father_id != person_id
- mother_id != person_id
- spouse_id != person_id
- Mutual spouse linking

Validate on save.

---

# 14. .cpanel.yml

Create file:

.cpanel.yml

Content:

---
deployment:
  tasks:
    - export DEPLOYPATH=/home1/de2shrnx/family.easi7.in
    - /bin/cp -R * $DEPLOYPATH
---

---

# 15. deploy.yml

Create file:

deploy.yml

Content:

name: Deploy FamilyTree 3.0

on:
  push:
    branches:
      - main

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Deployment Placeholder
        run: echo "Deployment handled via cPanel Git"

---

# 16. Git Setup

Initialize git.

Add remote:
https://github.com/ananthsridev12/asf.git

Commit all.

Push to main.

After push:

Use cPanel Git Version Control → Pull latest.

---

# 17. DO NOT

- Do not reuse old code.
- Do not mix branch logic with blood logic.
- Do not assume relation by name.
- Do not calculate cousin without LCA.

---

# END OF SPEC