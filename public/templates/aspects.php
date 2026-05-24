<section id="tab-aspects" class="tab-content tab-content--hidden">
    <div class="card card--aspects">
        <h4>Aspecten (<?= count($result['aspects'] ?? []) ?> totaal)</h4>
        <table>
            <tr>
                <th>Planeet 1</th>
                <th></th>
                <th>Planeet 2</th>
                <th>Orb</th>
                <th>Positie 1</th>
                <th>Positie 2</th>
            </tr>
            <?php foreach ($result['aspects'] as $aspect): ?>
                <tr class="<?= $aspect->isDominant ? 'row--dominant' : '' ?>">
                    <td class="text-center"><span class="astro-glyph"><?= \Tijd\Glyph\SymbolGlyph::getPlanetGlyphByName($aspect->planet1Name) ?></span></td>
                    <td class="text-center"><span class="astro-glyph"><?= \Tijd\Glyph\SymbolGlyph::getAspectGlyph($aspect->aspectDegrees) ?></span></td>
                    <td class="text-center"><span class="astro-glyph"><?= \Tijd\Glyph\SymbolGlyph::getPlanetGlyphByName($aspect->planet2Name) ?></span></td>
                    <td class="text-center<?= $aspect->isOutOfSign ? ' orb--out-of-sign' : '' ?>"><?= number_format($aspect->orb, 2) ?>°<?= $aspect->isOutOfSign ? ' B' : '' ?></td>
                    <td class="text-center"><?= \Tijd\Helpers\Formatter::formatLongitudeWithGlyph($aspect->planet1Longitude) ?></td>
                    <td class="text-center"><?= \Tijd\Helpers\Formatter::formatLongitudeWithGlyph($aspect->planet2Longitude) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="text-muted text-small">B = Aspect buiten teken, orb gemaximeerd op 2°</p>
        <div class="aspecten-toggle-container">
            <label class="aspecten-toggle-label">
                <input type="checkbox" id="toggle-dominant-aspects" onchange="toggleDominantAspects()">
                Toon de dominante aspecten:
            </label>
        </div>
    </div>
</section>
