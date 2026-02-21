<?php $role = (string)(app_user()['role'] ?? 'member'); ?>
<div class="offcanvas-lg offcanvas-start border-end" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarMenuLabel">FamilyTree</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/dashboard">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/add-person">Add Person</a></li>
      <?php if ($role === 'member'): ?>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=member/add-marriage">Add Marriage</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/family-list">Family List</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/tree-view">Tree View</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/ancestors">Ancestors</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/descendants">Descendants</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/relationship-finder">Relationship Finder</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/branches">Branches</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/reports">Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $role ?>/settings">Settings</a></li>
      <li class="nav-item"><a class="nav-link text-danger" href="/index.php?route=logout">Logout</a></li>
    </ul>
  </div>
</div>
