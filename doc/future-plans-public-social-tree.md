# Future Plans: Public Social Tree vs Multi-Tenant Private Trees

## Context
We currently run a single shared tree. The product direction may evolve into:
1) A **public social family tree** (shared global graph), or
2) **Multi-tenant private trees** (each signup creates a separate family space).

This document captures both options so we can pick later without losing context.

---

## Option A — Public Social Family Tree (Shared Global Graph)

**Goal:** Anyone can register and add relatives, including already-registered users, in a single shared tree.

### Core Requirements
- Public signup (name, email, password).
- User linked to a `person_id` (already supported).
- Ability to link **existing registered users** as relatives.
- Privacy controls for personal info (phone/email/address/DOB).
- Moderation / ownership rules to prevent abuse.

### Data & Logic
- Keep single `persons` table (no family_id isolation).
- Add:
  - `persons.is_public` (boolean)
  - `persons.hide_contact` (boolean) or per-field flags
  - `users.public_profile` (boolean)
  - `user_person_links` if multiple users can map to same person
- Relationship changes should log to audit table.
- Require approval when linking an existing registered user as a relative.

### UX
- Signup page (public).
- Search & invite existing users as relatives.
- Relationship request flow (accept/decline).
- Public profile view (limited data).

---

## Option B — Multi-Tenant Private Trees (Isolated Family Spaces)

**Goal:** Each signup creates a private family space. Users only see data within their own family.

### Core Requirements
- `families` table (tenant root).
- `family_id` on `users`, `persons`, `marriages`, `audit_log`.
- All queries filtered by `family_id`.
- Signup creates new family + first admin.
- Invite-only or public join with code.

### Data & Logic
- Add `family_id` foreign keys + indices.
- Update all selects/inserts to include `family_id`.
- Ensure admins can manage users within their family only.

### UX
- Create family flow.
- Invite users via email/code.
- Family settings + privacy.

---

## Decision Factors
- **Public social tree:** Good for open community, but needs privacy & moderation.
- **Multi-tenant:** Safer for private family use, requires strict data isolation.

---

## Next Steps (when ready)
1. Decide model (A or B).
2. Design schema changes.
3. Implement signup + onboarding flow.
4. Add privacy/membership controls.
5. Migrate existing data if needed.
