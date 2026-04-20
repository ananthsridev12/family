<?php
$role = app_user_role();
$routePrefix = role_route_prefix();
$currentRoute = (string)($_GET['route'] ?? '');
function _nav_active(string $route, string $current): string {
    return ($current === $route || strpos($current, $route) === 0) ? ' active' : '';
}
?>
<div class="offcanvas-lg offcanvas-start border-0" tabindex="-1" id="sidebarMenu" aria-labelledby="sidebarMenuLabel">
  <div class="offcanvas-header px-3 pt-3 pb-0">
    <span class="offcanvas-title" id="sidebarMenuLabel">FamilyTree</span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body p-0 d-flex flex-column">
    <a href="/index.php?route=<?= $routePrefix ?>/dashboard" class="sidebar-brand">
      <span class="brand-icon">&#127968;</span>
      FamilyTree
    </a>
    <ul class="nav flex-column flex-grow-1">
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/dashboard', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/dashboard"><span>&#9783;</span> Dashboard</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/add-person', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/add-person"><span>&#43;</span> Add Person</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active('member/add-marriage', $currentRoute) ?>" href="/index.php?route=member/add-marriage"><span>&#9825;</span> Add Marriage</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/family-list', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/family-list"><span>&#9776;</span> Family List</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/tree-view', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/tree-view"><span>&#127803;</span> Tree View</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/ancestors', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/ancestors"><span>&#8679;</span> Ancestors</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/descendants', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/descendants"><span>&#8681;</span> Descendants</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/relationship-finder', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/relationship-finder"><span>&#128279;</span> Relationships</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/branches', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/branches"><span>&#127807;</span> Branches</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/reports', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/reports"><span>&#128202;</span> Reports</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active($routePrefix.'/settings', $currentRoute) ?>" href="/index.php?route=<?= $routePrefix ?>/settings"><span>&#9881;</span> Settings</a></li>
      <?php if ($role === 'admin'): ?>
      <div class="nav-divider"></div>
      <li class="nav-item"><a class="nav-link<?= _nav_active('admin/users', $currentRoute) ?>" href="/index.php?route=admin/users"><span>&#128101;</span> Users</a></li>
      <li class="nav-item"><a class="nav-link<?= _nav_active('admin/proposals', $currentRoute) ?>" href="/index.php?route=admin/proposals"><span>&#128196;</span> Edit Proposals</a></li>
      <?php endif; ?>
      <div class="nav-divider"></div>
      <li class="nav-item"><a class="nav-link<?= _nav_active('notifications', $currentRoute) ?>" href="/index.php?route=notifications"><span>&#128276;</span> Notifications</a></li>
    </ul>
    <ul class="nav flex-column pb-2">
      <li class="nav-item"><a class="nav-link text-danger" href="/index.php?route=logout"><span>&#8594;</span> Logout</a></li>
    </ul>
  </div>
</div>
