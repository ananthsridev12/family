<?php include __DIR__ . '/../layouts/app_start.php'; ?>
<div class="page-header">
  <h1>In Memoriam</h1>
</div>

<?php if (empty($deceased)): ?>
<div class="d-flex justify-content-center align-items-center py-5">
  <p class="text-muted fs-5 mb-0">No memorial records yet.</p>
</div>
<?php else: ?>

<div class="row g-4">
  <?php foreach ($deceased as $person): ?>
  <?php
    $pid         = (int)($person['person_id'] ?? 0);
    $fullName    = htmlspecialchars((string)($person['full_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $birthYear   = ($person['birth_year'] ?? null) !== null ? (int)$person['birth_year'] : null;
    $dob         = !empty($person['date_of_birth'])  ? htmlspecialchars((string)$person['date_of_birth'],  ENT_QUOTES, 'UTF-8') : null;
    $dod         = !empty($person['date_of_death'])  ? htmlspecialchars((string)$person['date_of_death'],  ENT_QUOTES, 'UTF-8') : null;
    $native      = htmlspecialchars((string)($person['native_location']  ?? ''), ENT_QUOTES, 'UTF-8');
    $current     = htmlspecialchars((string)($person['current_location'] ?? ''), ENT_QUOTES, 'UTF-8');
    $occupation  = htmlspecialchars((string)($person['occupation']       ?? ''), ENT_QUOTES, 'UTF-8');
    $obituary    = htmlspecialchars((string)($person['obituary']         ?? ''), ENT_QUOTES, 'UTF-8');
    $gender      = strtolower((string)($person['gender'] ?? ''));

    // Compute birth year from date_of_birth if birth_year column absent
    if ($birthYear === null && $dob) {
      $birthYear = (int)date('Y', strtotime($dob));
    }
    $deathYear = $dod ? (int)date('Y', strtotime($dod)) : null;

    $lifespan = ($birthYear !== null ? (string)$birthYear : '?')
              . ' – '
              . ($deathYear !== null ? (string)$deathYear : '?');

    $genderIcon = $gender === 'female' ? '&#9792;&#65039;' : ($gender === 'male' ? '&#9794;&#65039;' : '');
  ?>
  <div class="col-12 col-sm-6 col-lg-4">
    <div class="card memorial-card h-100">
      <!-- Dark header -->
      <div class="memorial-header">
        <div class="memorial-name-row">
          <span class="memorial-name"><?= $fullName ?></span>
          <?php if ($genderIcon): ?>
            <span class="memorial-gender"><?= $genderIcon ?></span>
          <?php endif; ?>
        </div>
        <div class="memorial-lifespan"><?= htmlspecialchars($lifespan, ENT_QUOTES, 'UTF-8') ?></div>
      </div>

      <div class="card-body d-flex flex-column gap-2">
        <!-- Location + Occupation -->
        <div class="memorial-meta">
          <?php if ($native): ?>
          <div class="memorial-meta-row">
            <span class="memorial-meta-icon" title="Native location">&#127968;</span>
            <span><?= $native ?></span>
          </div>
          <?php endif; ?>
          <?php if ($occupation): ?>
          <div class="memorial-meta-row">
            <span class="memorial-meta-icon" title="Occupation">&#128188;</span>
            <span><?= $occupation ?></span>
          </div>
          <?php endif; ?>
          <?php if ($current && $current !== $native): ?>
          <div class="memorial-meta-row">
            <span class="memorial-meta-icon" title="Last known location">&#128205;</span>
            <span><?= $current ?></span>
          </div>
          <?php endif; ?>
        </div>

        <!-- Obituary -->
        <?php if ($obituary): ?>
        <div class="memorial-obituary">
          <em><?= nl2br($obituary) ?></em>
        </div>
        <?php endif; ?>

        <!-- View Profile link -->
        <?php if ($pid > 0): ?>
        <div class="mt-auto pt-2">
          <a href="/index.php?route=admin/person-view&id=<?= $pid ?>"
             class="memorial-profile-link">
            View Profile &rarr;
          </a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<style>
.memorial-card {
  border: 1px solid var(--ft-border, #e5e7eb);
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0,0,0,.06);
}
.memorial-header {
  background: #1e293b;
  color: #f1f5f9;
  padding: 1.1rem 1.25rem .9rem;
}
.memorial-name-row {
  display: flex;
  align-items: center;
  gap: .4rem;
  flex-wrap: wrap;
}
.memorial-name {
  font-size: 1.05rem;
  font-weight: 700;
  line-height: 1.25;
}
.memorial-gender {
  font-size: .9rem;
  opacity: .75;
}
.memorial-lifespan {
  font-size: .82rem;
  color: #94a3b8;
  margin-top: .25rem;
  letter-spacing: .04em;
}
.memorial-meta {
  display: flex;
  flex-direction: column;
  gap: .3rem;
}
.memorial-meta-row {
  display: flex;
  align-items: flex-start;
  gap: .45rem;
  font-size: .85rem;
  color: var(--ft-muted, #6c757d);
}
.memorial-meta-icon {
  font-size: .9rem;
  flex-shrink: 0;
  margin-top: .05rem;
}
.memorial-obituary {
  font-size: .85rem;
  color: var(--ft-muted, #6c757d);
  border-left: 3px solid #e2e8f0;
  padding-left: .75rem;
  line-height: 1.6;
}
.memorial-profile-link {
  font-size: .82rem;
  color: var(--ft-muted, #6c757d);
  text-decoration: none;
  border: 1px solid var(--ft-border, #e5e7eb);
  border-radius: 999px;
  padding: .25rem .75rem;
  display: inline-block;
  transition: color .15s, border-color .15s;
}
.memorial-profile-link:hover {
  color: var(--ft-primary, #4f46e5);
  border-color: var(--ft-primary, #4f46e5);
}
</style>
<?php include __DIR__ . '/../layouts/app_end.php'; ?>
