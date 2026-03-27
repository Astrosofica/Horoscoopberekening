<?php
$hasResult = $hasResult ?? false;
$isLoggedIn = $isLoggedIn ?? false;
$userEmail = $userEmail ?? null;
$currentTab = $currentTab ?? 'calculate';

$menuItems = [
    'calculate' => 'Berekenen',
];

$resultItems = [
    'horoscope' => 'Horoscoop',
    'planets' => 'Planeten',
    'houses' => 'Huizen',
    'aspects' => 'Aspecten',
];

$futureItems = [
    'transits' => 'Transits',
    'progressions' => 'Progressies',
    'midpoints' => 'Midpunten',
    'antiscia' => 'Spiegelpunten',
];
?>
<button class="mobile-menu-toggle" aria-label="Menu openen">&#9776;</button>
<div class="sidebar__overlay"></div>

<aside class="sidebar">
    <a href="index.php" class="sidebar__logo">Horoscoopberekening</a>
    
    <nav class="sidebar__nav">
        <div class="sidebar__section">
            <?php foreach ($menuItems as $id => $label): ?>
                <a href="#<?= $id ?>" 
                   class="sidebar__item<?= $currentTab === $id ? ' sidebar__item--active' : '' ?>" 
                   data-tab="<?= $id ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="sidebar__separator"></div>
        
        <div class="sidebar__section">
            <?php foreach ($resultItems as $id => $label): ?>
                <a href="#<?= $id ?>" 
                   class="sidebar__item<?= $currentTab === $id ? ' sidebar__item--active' : '' ?><?= !$hasResult ? ' sidebar__item--disabled' : '' ?>" 
                   data-tab="<?= $id ?>">
                    <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="sidebar__separator"></div>
        
        <div class="sidebar__section">
            <?php foreach ($futureItems as $id => $label): ?>
                <span class="sidebar__item sidebar__item--disabled" title="Binnenkort beschikbaar">
                    <?= $label ?>
                </span>
            <?php endforeach; ?>
        </div>
    </nav>
    
    <div class="sidebar__footer">
        <?php if ($isLoggedIn && $userEmail): ?>
            <div class="sidebar__user"><?= htmlspecialchars($userEmail) ?></div>
            <a href="dashboard.php" class="sidebar__footer-item">Dashboard</a>
            <a href="logout.php" class="sidebar__footer-item sidebar__footer-item--danger">Uitloggen</a>
        <?php else: ?>
            <a href="login.php" class="sidebar__footer-item">Inloggen</a>
            <a href="register.php" class="sidebar__footer-item">Registreren</a>
        <?php endif; ?>
    </div>
</aside>