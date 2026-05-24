<?php
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Tijd\Auth\AuthService;
use Tijd\Database\HoroscopeRepository;

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

$totalHoroscopes = $horoscopeRepo->countByUserId($currentUser->getId());
$perPage = 6; // aantal horoscopen per pagina
$totalPages = max(1, ceil($totalHoroscopes / $perPage));

$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;

$horoscopes = $horoscopeRepo->findByUserIdPaginated(
    $currentUser->getId(),
    $sort,
    $page,
    $perPage
);

$success = $_SESSION['flash_success'] ?? null;
$error = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
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
                <div class="sort-buttons">
                    <span class="sort-label">Sorteren:</span>
                    <a href="?sort=name&page=<?= $page ?>" class="sort-btn<?= $sort === 'name' ? ' sort-btn--active' : '' ?>">A-Z</a>
                    <a href="?sort=name_desc&page=<?= $page ?>" class="sort-btn<?= $sort === 'name_desc' ? ' sort-btn--active' : '' ?>">Z-A</a>
                    <a href="?sort=newest&page=<?= $page ?>" class="sort-btn<?= $sort === 'newest' ? ' sort-btn--active' : '' ?>">Nieuwste</a>
                    <a href="?sort=oldest&page=<?= $page ?>" class="sort-btn<?= $sort === 'oldest' ? ' sort-btn--active' : '' ?>">Oudste</a>
                </div>
            </div>

            <div class="horoscope-list">
                <?php foreach ($horoscopes as $h): ?>
                    <div class="horoscope-card">
                        <div class="horoscope-card__info">
                            <a href="index.php?h=<?= $h->getSlug() ?>" class="horoscope-card__name">
                                <?= htmlspecialchars($h->getName()) ?>
                            </a>
                            <div class="horoscope-card__details">
                                <span class="horoscope-card__detail">
                                    <strong>Geboorte:</strong>
                                    <?= htmlspecialchars($h->getBirthDate()) ?>, 
                                    <?= htmlspecialchars(substr($h->getBirthTime(), 0, 5)) ?>
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
                        <a href="?sort=<?= $sort ?>&page=<?= $page - 1 ?>" class="pagination__link pagination__link--nav">← Vorige</a>
                    <?php else: ?>
                        <span class="pagination__link pagination__link--disabled">← Vorige</span>
                    <?php endif; ?>

                    <div class="pagination__numbers">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="pagination__link pagination__link--current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?sort=<?= $sort ?>&page=<?= $i ?>" class="pagination__link"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                        <a href="?sort=<?= $sort ?>&page=<?= $page + 1 ?>" class="pagination__link pagination__link--nav">Volgende →</a>
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