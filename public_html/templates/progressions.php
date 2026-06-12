<?php if (isset($result['progressions'])): ?>
<section id="tab-progressions" class="tab-content tab-content--hidden">
    <div class="card card--progressions">
        <h4>Secundaire Progressies</h4>
        <p class="progressions-info">
            Leeftijd: <?= \Astro\Helpers\Formatter::formatProgressAge($result['progressions']['progress_days']) ?>
            <span class="progressions-decimal">(<?= round($result['progressions']['progress_days'], 2) ?>)</span>
        </p>
        <table>
            <tr>
                <th>Planeet</th>
                <th>Positie</th>
                <th>Huis</th>
                <th>Richting</th>
            </tr>
            <?php foreach ($result['progressions']['planets'] as $name => $data): ?>
                <?php if ($name === 'Chiron') continue; ?>
                <?php if ($name === 'Ascendant' || $name === 'MC'): ?>
                    <tr class="row--axis">
                        <td class="text-center"><?= htmlspecialchars($name) ?></td>
                        <td class="text-center"><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($data['longitude']) ?></td>
                        <td class="text-center"><?= $data['house'] ?></td>
                        <td class="text-center"><?= $data['direction'] ?></td>
                    </tr>
                <?php else: ?>
                    <tr>
                        <td class="text-center"><span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByName($name) ?></span></td>
                        <td class="text-center"><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($data['longitude']) ?></td>
                        <td class="text-center"><?= $data['house'] ?></td>
                        <td class="text-center">
                            <?php if ($data['direction'] === 'R'): ?>
                                <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getRetrogradeGlyph() ?></span>
                            <?php else: ?>
                                <?= $data['direction'] ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
    </div>
</section>
<?php endif; ?>
