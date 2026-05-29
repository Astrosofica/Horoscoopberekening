<section id="tab-midpoints-planet" class="tab-content tab-content--hidden">
    <div class="card card--large card--midpoints">
        <h2>Midpunten per Planeet</h2>
        
        <?php if (isset($midpointsResult) && count($midpointsResult) > 0): ?>
            <div class="split-view-container">
                <div class="split-view-column split-view-column--left">
                    <table>
                        <?php
                        $splitPoint = 39;
                        for ($i = $splitPoint; $i < count($midpointsResult); $i++) {
                            if (isset($midpointsResult[$i]['separator']) && $midpointsResult[$i]['separator']) {
                                $splitPoint = $i + 1;
                                break;
                            }
                        }
                        
                        for ($i = 0; $i < $splitPoint; $i++):
                            $mp = $midpointsResult[$i];
                            if (isset($mp['separator']) && $mp['separator']): ?>
                                <tr class="midpoints-row--separator"><td colspan="2">&nbsp;</td></tr>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($mp['planet1_index']) ?></span> /
                                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($mp['planet2_index']) ?></span>
                                    </td>
                                    <td class="text-right"><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($mp['normalized']) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </table>
                </div>
                <div class="split-view-column split-view-column--right">
                    <table>
                        <?php
                        for ($i = $splitPoint; $i < count($midpointsResult); $i++):
                            $mp = $midpointsResult[$i];
                            if (isset($mp['separator']) && $mp['separator']): ?>
                                <tr class="midpoints-row--separator"><td colspan="2">&nbsp;</td></tr>
                            <?php else: ?>
                                <tr>
                                    <td>
                                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($mp['planet1_index']) ?></span> /
                                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($mp['planet2_index']) ?></span>
                                    </td>
                                    <td class="text-right"><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($mp['normalized']) ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endfor; ?>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <p>Geen horoscoop data beschikbaar. Bereken eerst een horoscoop.</p>
        <?php endif; ?>
    </div>
</section>
