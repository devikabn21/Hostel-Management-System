<?php
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
$navItems = [
    ['dashboard.php', 'Overview', '⌂'],
    ['students.php', 'Students', '♙'],
    ['rooms.php', 'Rooms', '▦'],
    ['allocations.php', 'Allocations', '⇄'],
];
?>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="dashboard.php"><span class="brand-mark">⌂</span><span>Haven<span class="brand-light">Hostel</span></span></a>
    <p class="nav-label">WORKSPACE</p>
    <nav aria-label="Main navigation">
        <?php foreach ($navItems as [$href, $label, $icon]): ?>
            <a class="nav-link <?= $currentPage === $href ? 'active' : '' ?>" href="<?= e($href) ?>">
                <span class="nav-icon" aria-hidden="true"><?= e($icon) ?></span><?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-bottom"><span class="status-dot"></span> System ready</div>
</aside>