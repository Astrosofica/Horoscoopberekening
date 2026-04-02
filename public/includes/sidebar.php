<?php
$hasResult = $hasResult ?? false;
$currentTab = $currentTab ?? 'calculate';

$menuItems = [
    'calculate' => 'Invoer',
];

$resultItems = [
    'horoscope' => 'Horoscoop',
    'planetshouses' => 'Astrodata',
    'aspects' => 'Aspecten',
    'progressions' => 'Progressies',
    'progressions-list' => 'Progressie Events',
    'antiscia' => 'Spiegelpunten',
];

$futureItems = [
    'transits' => 'Transits',
    'midpoints' => 'Midpunten',
];
?>
<button class="mobile-menu-toggle" aria-label="Menu openen">&#9776;</button>
<div class="sidebar__overlay"></div>

<aside class="sidebar">
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
</aside>