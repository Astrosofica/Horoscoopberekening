<?php
$hasResult = $hasResult ?? false;
$currentTab = $currentTab ?? 'calculate';
$mode = $mode ?? 'new';

$menuItems = [
    'calculate' => 'Invoer',
];

$resultItems = [
    'horoscope' => 'Horoscoop',
    'planetshouses' => 'Astrodata',
    'aspects' => 'Aspecten',
];

// Verberg navigatie secties als er geen horoscoop is berekend OF in edit mode
$hideNavigation = !$hasResult || $mode === 'edit';

$progressionsGroup = [
    'progressions' => 'vandaag',
    'progressions-list' => 'lijst',
];

$antisciaGroup = [
    'antiscia' => 'Spiegelpunten',
];

$midpointsGroup = [
    'midpoints-planet' => 'per planeet',
    'midpoints-sign' => 'per teken',
    'midpoints-tree' => 'boompjes',
];

$transitsGroup = [
    'transits' => 'vandaag',
    'transits-list' => 'lijst',
];
?>
<button class="mobile-menu-toggle no-print" aria-label="Menu openen">&#9776;</button>
<div class="sidebar__overlay no-print"></div>

<aside class="sidebar no-print">
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
        
        <?php if ($hideNavigation): ?>
            <div class="sidebar__placeholder">
                <?php if ($mode === 'edit'): ?>
                    Je bewerkt een horoscoop.<br><br>
                    Pas de gegevens aan en klik op "Wijzigingen opslaan".
                <?php else: ?>
                    Bereken eerst een horoscoop om alle opties te zien
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sidebar__separator"></div>
            
            <div class="sidebar__section">
                <?php foreach ($resultItems as $id => $label): ?>
                    <a href="#<?= $id ?>" 
                       class="sidebar__item<?= $currentTab === $id ? ' sidebar__item--active' : '' ?>" 
                       data-tab="<?= $id ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar__section">
                <div class="sidebar__section-title">Transits</div>
                <?php foreach ($transitsGroup as $id => $label): ?>
                    <a href="#<?= $id ?>"
                       class="sidebar__item sidebar__item--indent<?= $currentTab === $id ? ' sidebar__item--active' : '' ?>"
                       data-tab="<?= $id ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar__section">
                <div class="sidebar__section-title">Progressies</div>
                <?php foreach ($progressionsGroup as $id => $label): ?>
                    <a href="#<?= $id ?>" 
                       class="sidebar__item sidebar__item--indent<?= $currentTab === $id ? ' sidebar__item--active' : '' ?>" 
                       data-tab="<?= $id ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar__section">
                <div class="sidebar__section-title">Midpunten</div>
                <?php foreach ($midpointsGroup as $id => $label): ?>
                    <a href="#<?= $id ?>" 
                       class="sidebar__item sidebar__item--indent<?= $currentTab === $id ? ' sidebar__item--active' : '' ?>" 
                       data-tab="<?= $id ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="sidebar__section">
                <?php foreach ($antisciaGroup as $id => $label): ?>
                    <a href="#<?= $id ?>" 
                       class="sidebar__item<?= $currentTab === $id ? ' sidebar__item--active' : '' ?>" 
                       data-tab="<?= $id ?>">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php
            $hasNatalSun = isset($_SESSION['horoscope']['core']['planets']['Sun']['longitude'])
                || isset($_SESSION['horoscope']['input']['natal_sun_longitude'])
                || isset($_SESSION['solaar_prefill']);
            ?>
            <?php if ($hasNatalSun): ?>
                <div class="sidebar__section">
                    <a href="?tab=solaar"
                       class="sidebar__item<?= $currentTab === 'solaar' ? ' sidebar__item--active' : '' ?>"
                       data-tab="solaar">
                        Solaar
                    </a>
                </div>
            <?php endif; ?>

            <div class="sidebar__separator"></div>

            <div class="sidebar__section">
                <a href="javascript:window.print()" class="sidebar__item sidebar__item--print">
                    Afdrukken
                </a>
            </div>
        <?php endif; ?>
    </nav>
</aside>