<?php if (isset($result['transits'])): ?>
<section id="tab-transits" class="tab-content<?= $currentTab !== 'transits' ? ' tab-content--hidden' : '' ?>">
    <div class="card card--large card--transits">
        <h2>Transits — <?= date('d-m-Y H:i') ?></h2>
        <table>
            <thead>
                <tr>
                    <th>Planeet</th>
                    <th>Richting</th>
                    <th>Positie</th>
                    <th>Huis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($result['transits']['planets'] as $name => $data): ?>
                <tr>
                    <td class="text-center">
                        <span class="astro-glyph"><?= \Tijd\Glyph\SymbolGlyph::getPlanetGlyphByName($name) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($data['direction'] === 'R'): ?>
                            <span class="astro-glyph"><?= \Tijd\Glyph\SymbolGlyph::getRetrogradeGlyph() ?></span>
                        <?php else: ?>
                            <?= $data['direction'] ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= \Tijd\Helpers\Formatter::formatLongitudeWithGlyph($data['longitude']) ?></td>
                    <td class="text-center"><?= $data['house'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
