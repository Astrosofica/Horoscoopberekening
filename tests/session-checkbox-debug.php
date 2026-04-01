<?php
// Simuleer wat er gebeurt bij POST

session_start();

// Simuleer POST data
$_POST['calculate_progressions'] = true;
$_POST['prog_start_date'] = '2024-01-01';
$_POST['prog_end_date'] = '2024-12-31';
$_POST['progressive_planet'] = ['0', '1', '2'];
$_POST['radix_target'] = ['0', '1'];
$_POST['aspect_type'] = ['60', '90'];

// Simuleer session data
$_SESSION['horoscope']['core'] = ['planets' => [], 'houses' => [], 'ascmc' => []];

// Verwerking
$progressivePlanets = array_map('intval', $_POST['progressive_planet'] ?? []);
$radixTargets = array_map('intval', $_POST['radix_target'] ?? []);
$aspects = array_map('intval', $_POST['aspect_type'] ?? []);

echo "POST data:\n";
var_dump($_POST['progressive_planet']);

echo "\nNa array_map('intval', ...):\n";
var_dump($progressivePlanets);

// Opslaan in session
$_SESSION['horoscope']['progression_events'] = [
    'input' => [
        'progressive_planets' => $progressivePlanets,
        'radix_targets' => $radixTargets,
        'aspects' => $aspects,
    ],
];

echo "\nSession na opslaan:\n";
var_dump($_SESSION['horoscope']['progression_events']['input']);

// Simuleer herladen pagina (uitlezen)
$selProg = $_SESSION['horoscope']['progression_events']['input']['progressive_planets'] ?? [];

echo "\nUitgelezen voor formulier:\n";
var_dump($selProg);

// Test in_array
echo "\nin_array test:\n";
echo "in_array(0, \$selProg): " . (in_array(0, $selProg) ? 'true' : 'false') . "\n";
echo "in_array(1, \$selProg): " . (in_array(1, $selProg) ? 'true' : 'false') . "\n";
echo "in_array(3, \$selProg): " . (in_array(3, $selProg) ? 'true' : 'false') . "\n";