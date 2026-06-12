<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Astro\Auth\AuthService;
use Astro\Database\HoroscopeRepository;
use Astro\Helpers\Formatter;

$authService = new AuthService();

if (!$authService->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $authService->getCurrentUser();
$horoscopeRepo = new HoroscopeRepository();

$validSorts = ['newest', 'oldest', 'name', 'name_desc'];
$sort = $_GET['sort'] ?? 'newest';
if (!in_array($sort, $validSorts)) {
    $sort = 'newest';
}

$search = trim($_GET['search'] ?? '');

$perPage = 6;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

if ($search !== '' && strlen($search) >= 3) {
    $totalHoroscopes = $horoscopeRepo->countSearchResults($currentUser->getId(), $search);
    $totalPages = max(1, ceil($totalHoroscopes / $perPage));
    if ($page > $totalPages) $page = $totalPages;

    $horoscopes = $horoscopeRepo->searchByNamePaginated(
        $currentUser->getId(),
        $search,
        $sort,
        $page,
        $perPage
    );
} else {
    $totalHoroscopes = $horoscopeRepo->countByUserId($currentUser->getId());
    $totalPages = max(1, ceil($totalHoroscopes / $perPage));
    if ($page > $totalPages) $page = $totalPages;

    $horoscopes = $horoscopeRepo->findByUserIdPaginated(
        $currentUser->getId(),
        $sort,
        $page,
        $perPage
    );
}

$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

// AJAX request — return only card list fragment
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $search = trim($_GET['search'] ?? '');

    if ($search !== '' && strlen($search) >= 3) {
        $totalHoroscopes = $horoscopeRepo->countSearchResults($currentUser->getId(), $search);
        $totalPages = max(1, ceil($totalHoroscopes / $perPage));
        if ($page > $totalPages) $page = $totalPages;

        $horoscopes = $horoscopeRepo->searchByNamePaginated(
            $currentUser->getId(),
            $search,
            $sort,
            $page,
            $perPage
        );
    }

    $locale = $_SESSION['locale'] ?? 'nl_NL';
    ?><div class="horoscope-count"><?= $totalHoroscopes ?> horoscopen gevonden</div>
    <div class="horoscope-list">
        <?php if (empty($horoscopes)): ?>
            <p class="empty-message">Geen horoscopen gevonden voor "<strong><?= htmlspecialchars($search) ?></strong>".</p>
        <?php else: ?>
            <?php foreach ($horoscopes as $h):
                $birthTs = strtotime($h->getBirthDate() . ' ' . $h->getBirthTime());
                $birthFormatted = Formatter::formatDateTime($birthTs, $locale);
            ?>
                <div class="horoscope-card">
                    <div class="horoscope-card__info">
                        <a href="index.php?h=<?= $h->getSlug() ?>" class="horoscope-card__name">
                            <?= htmlspecialchars($h->getFormalName()) ?>
                        </a>
                        <div class="horoscope-card__details">
                            <span class="horoscope-card__detail">
                                <strong>Geboorte:</strong>
                                <?= htmlspecialchars($birthFormatted['date']) ?>,
                                <?= htmlspecialchars($birthFormatted['time']) ?>
                            </span>
                            <span class="horoscope-card__detail">
                                <strong>Plaats:</strong>
                                <?= htmlspecialchars($h->getLocationName()) ?>
                            </span>
                        </div>
                    </div>
                    <div class="horoscope-card__actions">
                        <a href="index.php?h=<?= $h->getSlug() ?>">Bekijk</a>
                        <a href="index.php?h=<?= $h->getSlug() ?>&edit">Bewerk</a>
                        <form method="POST" action="horoscope/delete.php" class="form--inline" onsubmit="return confirm('Weet je zeker dat je deze horoscoop wilt verwijderen?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="slug" value="<?= $h->getSlug() ?>">
                            <button type="submit" class="link--danger">Verwijder</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?sort=<?= $sort ?>&search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="pagination__link pagination__link--nav">← Vorige</a>
            <?php else: ?>
                <span class="pagination__link pagination__link--disabled">← Vorige</span>
            <?php endif; ?>
            <div class="pagination__numbers">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="pagination__link pagination__link--current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?sort=<?= $sort ?>&search=<?= urlencode($search) ?>&page=<?= $i ?>" class="pagination__link"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
            <?php if ($page < $totalPages): ?>
                <a href="?sort=<?= $sort ?>&search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="pagination__link pagination__link--nav">Volgende →</a>
            <?php else: ?>
                <span class="pagination__link pagination__link--disabled">Volgende →</span>
            <?php endif; ?>
        </div>
        <div class="pagination__info">
            Toon <?= ($page - 1) * $perPage + 1 ?>-<?= min($page * $perPage, $totalHoroscopes) ?> van <?= $totalHoroscopes ?>
        </div>
    <?php endif; ?>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Horoscoopberekening</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <?php if ($success): ?>
        <div class="flash flash--success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card card--large">
        <div class="card-header">
            <h2>Mijn Horoscopen</h2>
            <a href="index.php?new=1" class="btn btn--primary">+ Nieuwe horoscoop</a>
        </div>

        <?php if (empty($horoscopes)): ?>
            <p class="empty-message">Je hebt nog geen horoscopen opgeslagen.</p>
            <p><a href="index.php">Bereken je eerste horoscoop</a></p>
        <?php else: ?>
            <div class="dashboard-controls">
                <span class="horoscope-count"><?= $totalHoroscopes ?> horoscopen</span>
                <input type="text" class="search-input" id="dashboard-search" placeholder="Zoek op naam..." autocomplete="off" value="<?= htmlspecialchars($search) ?>">
                <div class="sort-buttons">
                    <span class="sort-label">Sorteren:</span>
                    <a href="?sort=name&page=<?= $page ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="sort-btn<?= $sort === 'name' ? ' sort-btn--active' : '' ?>">A-Z</a>
                    <a href="?sort=name_desc&page=<?= $page ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="sort-btn<?= $sort === 'name_desc' ? ' sort-btn--active' : '' ?>">Z-A</a>
                    <a href="?sort=newest&page=<?= $page ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="sort-btn<?= $sort === 'newest' ? ' sort-btn--active' : '' ?>">Nieuwste</a>
                    <a href="?sort=oldest&page=<?= $page ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="sort-btn<?= $sort === 'oldest' ? ' sort-btn--active' : '' ?>">Oudste</a>
                </div>
            </div>

            <div class="horoscope-list" data-search-value="<?= htmlspecialchars($search) ?>">
                <?php
                $locale = $_SESSION['locale'] ?? 'nl_NL';
                foreach ($horoscopes as $h):
                    $birthTs = strtotime($h->getBirthDate() . ' ' . $h->getBirthTime());
                    $birthFormatted = Formatter::formatDateTime($birthTs, $locale);
                ?>
                    <div class="horoscope-card">
                        <div class="horoscope-card__info">
                            <a href="index.php?h=<?= $h->getSlug() ?>" class="horoscope-card__name">
                                <?= htmlspecialchars($h->getFormalName()) ?>
                            </a>
                            <div class="horoscope-card__details">
                                <span class="horoscope-card__detail">
                                    <strong>Geboorte:</strong>
                                    <?= htmlspecialchars($birthFormatted['date']) ?>,
                                    <?= htmlspecialchars($birthFormatted['time']) ?>
                                </span>
                                <span class="horoscope-card__detail">
                                    <strong>Plaats:</strong>
                                    <?= htmlspecialchars($h->getLocationName()) ?>
                                </span>
                            </div>
                        </div>
                        <div class="horoscope-card__actions">
                            <a href="index.php?h=<?= $h->getSlug() ?>">Bekijk</a>
                            <a href="index.php?h=<?= $h->getSlug() ?>&edit">Bewerk</a>
                            <form method="POST" action="horoscope/delete.php" class="form--inline" onsubmit="return confirm('Weet je zeker dat je deze horoscoop wilt verwijderen?');">
                                <?= csrfField() ?>
                                <input type="hidden" name="slug" value="<?= $h->getSlug() ?>">
                                <button type="submit" class="link--danger">Verwijder</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?sort=<?= $sort ?>&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination__link pagination__link--nav">← Vorige</a>
                    <?php else: ?>
                        <span class="pagination__link pagination__link--disabled">← Vorige</span>
                    <?php endif; ?>

                    <div class="pagination__numbers">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="pagination__link pagination__link--current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?sort=<?= $sort ?>&page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination__link"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                        <a href="?sort=<?= $sort ?>&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="pagination__link pagination__link--nav">Volgende →</a>
                    <?php else: ?>
                        <span class="pagination__link pagination__link--disabled">Volgende →</span>
                    <?php endif; ?>
                </div>
                
                <div class="pagination__info">
                    Toon <?= ($page - 1) * $perPage + 1 ?>-<?= min($page * $perPage, $totalHoroscopes) ?> van <?= $totalHoroscopes ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <div class="card card--large">
        <h2>Account instellingen</h2>
        <p>
            <strong>E-mail:</strong> <?= htmlspecialchars($currentUser->getEmail()) ?>
            <?php if ($currentUser->isEmailVerified()): ?>
                <span class="verified-badge">Geverifieerd</span>
            <?php else: ?>
                <a href="verify-email.php" class="btn btn--small btn--secondary">Verifiëren</a>
            <?php endif; ?>
        </p>
        <p>
            <a href="change-password.php" class="btn btn--small btn--secondary">Wachtwoord wijzigen</a>
            <a href="delete-account.php" class="btn btn--small btn--danger">Account verwijderen</a>
        </p>
    </div>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>
</div>
</body>
</html>