<?php
$role = app_user_role();
$routePrefix = role_route_prefix();
?>
<div class="offcanvas-lg offcanvas-start border-end" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title" id="sidebarMenuLabel">FamilyTree</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0">
    <ul class="nav flex-column">
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/dashboard">Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/add-person">Add Person</a></li>
      <?php if ($role !== 'admin'): ?>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=member/add-marriage">Add Marriage</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/family-list">Family List</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/tree-view">Tree View</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/ancestors">Ancestors</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/descendants">Descendants</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/relationship-finder">Relationship Finder</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/branches">Branches</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/reports">Reports</a></li>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=<?= $routePrefix ?>/settings">Settings</a></li>
      <?php if ($role === 'admin'): ?>
      <li class="nav-item"><a class="nav-link" href="/index.php?route=admin/users">Users</a></li>
      <?php endif; ?>
      <li class="nav-item"><a class="nav-link text-danger" href="/index.php?route=logout">Logout</a></li>
    </ul>
  </div>
</div>
