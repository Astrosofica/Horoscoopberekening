<section id="tab-midpoints-tree" class="tab-content tab-content--hidden">
    <div class="card card--large card--midpoints">
        <h2>Midpunten Boompjes</h2>
        
        <?php if (isset($treeResult) && count($treeResult) > 0): ?>
            <?php
            $planetNames = [
                0 => 'Zon', 1 => 'Maan', 2 => 'Mercurius', 3 => 'Venus', 4 => 'Mars',
                5 => 'Jupiter', 6 => 'Saturnus', 7 => 'Uranus', 8 => 'Neptunus', 9 => 'Pluto',
                10 => 'Noordknoop', 11 => 'Ascendant', 12 => 'MC'
            ];
            ?>
            <table>
                <?php foreach ($treeResult as $planetIndex => $planetData): ?>
                    <tr class="midpoints-tree-planet-header">
                        <td colspan="5">
                            <strong>
                                <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($planetData['planet_index']) ?></span>
                                <?= $planetNames[$planetData['planet_index']] ?? 'Onbekend' ?>
                                - <?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($planetData['longitude']) ?>
                            </strong>
                        </td>
                    </tr>
                    <?php foreach ($planetData['aspects'] as $aspect): ?>
                        <tr>
                            <td style="padding-left: 2rem;">│—</td>
                            <td>
                                <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($aspect['planet1_index']) ?></span> /
                                <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($aspect['planet2_index']) ?></span>
                            </td>
                            <td><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($aspect['longitude']) ?></td>
                            <td class="text-right">
                                <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getAspectGlyph($aspect['aspect_degrees']) ?></span>
                            </td>
                            <td class="text-right">
                                Orb: <?= \Astro\Helpers\Formatter::formatOrbWithSign($aspect['orb']) ?>
                                <?= $aspect['exact'] ? '<strong>**</strong>' : '' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>Geen horoscoop data beschikbaar. Bereken eerst een horoscoop.</p>
        <?php endif; ?>
    </div>
</section>
