# FamilyTree Project -- Login & Role-Based Access Plan

*Last Updated: 2026-02-22*

------------------------------------------------------------------------

## 1. Objective

Enable a secure login system where:

-   Admin controls who can access the system.
-   Admin controls data access levels.
-   Regular members can:
    -   Map their already-added parents.
    -   Add their children.
    -   Add their children's spouses (laws).
    -   Add grandchildren.
-   Special full-access users can:
    -   Add/edit parents.
    -   Add siblings.
    -   Add sibling families (children, spouses, etc.).
-   No user should edit/delete data created by another user (unless
    Admin).

------------------------------------------------------------------------

## 2. Core Concept: Role-Based Access Control (RBAC) + Ownership Model

We will implement:

1.  Authentication (Login System)
2.  Roles (Admin, Family Editor, Limited Member)
3.  Ownership Control (created_by logic)
4.  Permission Rules (Edit/Delete restrictions)

------------------------------------------------------------------------

## 3. Database Structure Changes

### 3.1 Users Table

  Field           Type        Description
  --------------- ----------- --------------------------------------
  id              INT         Primary Key
  name            VARCHAR     User Name
  email           VARCHAR     Login Email
  password_hash   VARCHAR     Hashed Password
  role            ENUM        admin / full_editor / limited_member
  is_active       BOOLEAN     Admin can enable/disable login
  created_at      TIMESTAMP   Record time

------------------------------------------------------------------------

### 3.2 Persons Table (Important Changes)

Add:

  Field            Type                Description
  ---------------- ------------------- ---------------------------
  created_by       INT (FK Users.id)   Who created this record
  editable_scope   ENUM                self_branch / full_access
  is_locked        BOOLEAN             Optional admin lock

This allows ownership-based restriction.

------------------------------------------------------------------------

## 4. Roles & Permissions

### 4.1 Admin

-   Create users
-   Enable/disable users
-   Assign roles
-   Edit/Delete any person
-   Lock records
-   Transfer ownership

Full control.

------------------------------------------------------------------------

### 4.2 Limited Member

Can: - Map their already existing parents (only if allowed) - Add: -
Their children - Their children's spouses - Grandchildren - Edit only
persons created_by = their user ID - View entire tree (optional setting)

Cannot: - Edit parents created by others - Edit siblings - Edit other
branches - Delete others' records

------------------------------------------------------------------------

### 4.3 Full Editor

Can: - Add/Edit parents - Add siblings - Add sibling families - Add
children, grandchildren - Edit only records created_by = their user ID

Cannot: - Edit/Delete records created by other full editors - Edit
admin-created locked records

------------------------------------------------------------------------

## 5. Ownership Logic (Critical Part)

Every person record will have:

created_by = user_id

When editing:

IF current_user.role == "admin" Allow ELSE Allow ONLY IF
person.created_by == current_user.id

This ensures:

✔ Users cannot edit others' data\
✔ You don't need complex logic\
✔ Ownership is clearly tracked

------------------------------------------------------------------------

## 6. Optional Advanced Model (Branch-Level Control)

If you want stronger control:

Add:

branch_root_id

Each user is assigned to a specific branch root.

Rules:

-   User can edit anyone under their branch.
-   Cannot edit outside their branch.

This requires tree hierarchy traversal logic.

Use only if needed later.

------------------------------------------------------------------------

## 7. Login Flow

1.  Admin creates user.
2.  Admin assigns role.
3.  User receives credentials.
4.  User logs in.
5.  System loads permissions based on role.

------------------------------------------------------------------------

## 8. API-Level Protection (Very Important)

All Add/Edit/Delete APIs must check:

-   Is user logged in?
-   Is user active?
-   Does user role allow this action?
-   Does ownership match?

Never trust frontend validation alone.

------------------------------------------------------------------------

## 9. Deletion Strategy

Recommended:

Instead of permanent delete, use:

is_deleted = TRUE

Soft delete prevents data loss and accidental tree corruption.

Only Admin can permanently delete.

------------------------------------------------------------------------

## 10. UI Adjustments

Based on role:

-   Hide Edit/Delete buttons for unauthorized users
-   Show "Read Only" mode when needed
-   Show branch indicator

------------------------------------------------------------------------

## 11. Security Best Practices

-   Use password hashing (bcrypt/argon2)
-   Use JWT or session-based authentication
-   Use HTTPS
-   Rate limit login attempts
-   Add audit log table

------------------------------------------------------------------------

## 12. Audit Log Table (Recommended)

  Field       Description
  ----------- ----------------------
  user_id     Who performed action
  action      add/edit/delete
  person_id   Affected record
  timestamp   Time
  old_value   Optional
  new_value   Optional

Helps track misuse.

------------------------------------------------------------------------

## 13. Is This Possible?

Yes.

Both scenarios are completely possible using:

-   Role-Based Access Control
-   Ownership Model
-   API Validation

No complex AI logic required.

------------------------------------------------------------------------

## 14. Implementation Phases

Phase 1: - Create Users table - Add created_by to persons - Basic
login - Role-based restrictions

Phase 2: - Ownership enforcement - UI restrictions

Phase 3: - Audit logs - Branch-level advanced control (optional)

------------------------------------------------------------------------

## 15. Recommendation

Start simple:

✔ Admin\
✔ Limited Member\
✔ Full Editor\
✔ created_by ownership control

Do NOT implement branch-level control initially.

Scale later if required.

------------------------------------------------------------------------

END OF DOCUMENT
