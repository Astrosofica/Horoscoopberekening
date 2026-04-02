<?php
echo "=== ANTISCIA CSS DEBUG ===\n\n";

echo "1. HUIDIGE CSS REGELS:\n";
$css = file_get_contents(__DIR__ . '/public/css/_antiscia.css');
$lines = explode("\n", $css);
foreach ($lines as $i => $line) {
    if (strpos($line, 'width') !== false || strpos($line, 'flex') !== false || strpos($line, 'padding') !== false) {
        echo ($i+1) . ": " . trim($line) . "\n";
    }
}

echo "\n2. CARD--LARGE PADDING CHECK:\n";
$cardCss = file_get_contents(__DIR__ . '/public/css/_card.css');
if (preg_match('/\.card--large\s*\{[^}]*padding:\s*([^;]+)/', $cardCss, $matches)) {
    echo "   .card--large padding: " . trim($matches[1]) . " (DIT IS 2rem = 32px!)\n";
}

echo "\n3. PROBLEEM ANALYSE:\n";
echo "   - .card--large heeft padding: 2rem (32px)\n";
echo "   - .card--antiscia .card heeft padding: 0.5rem 0.625rem (8px 10px)\n";
echo "   - MAAR: .card--large.card--antiscia-aspects .card bestaat niet!\n";
echo "   - De card INSIDE de sectie krijgt dubbele padding!\n\n";

echo "4. ASPECT TABEL IN DE HTML:\n";
echo "   <div class=\"card card--antiscia-aspects\">\n";
echo "       <table>...</table>\n";
echo "   </div>\n";
echo "   PROBLEEM: De .card class heeft AL padding, niet de wrapper!\n";
