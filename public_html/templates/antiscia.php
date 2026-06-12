<?php if (isset($result['antiscia'])): ?>
<section id="tab-antiscia" class="tab-content tab-content--hidden">
    <div class="card card--large card--antiscia">
        <h4>Spiegelpunten (Jan de Jong)</h4>
        
        <div class="split-view-container">
            <div class="split-view-column split-view-column--points">
                <table>
                    <tr>
                        <th colspan="2">Spiegelpunten</th>
                    </tr>
                    <?php foreach ($result['antiscia']['mirrorPoints'] as $point): ?>
                        <tr>
                            <td>
                                <span class="astro-glyph">
                                    <?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($point['name']) ?>
                                </span> i
                            </td>
                            <td>
                                <?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($point['pos']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
            <div class="split-view-column split-view-column--aspects">
                <table>
                    <tr>
                        <th colspan="4">Aspecten spiegelpunten</th>
                    </tr>
                    <?php foreach ($result['antiscia']['aspects'] as $aspect): ?>
                        <tr>
                            <td>
                                <span class="astro-glyph">
                                    <?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($aspect['name1']) ?>
                                </span> i
                            </td>
                            <td>
                                <span class="astro-glyph">
                                    <?= \Astro\Glyph\SymbolGlyph::getAspectGlyph($aspect['degree']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="astro-glyph">
                                    <?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($aspect['name2']) ?>
                                </span> r
                            </td>
                            <td>
                                orb: <?= \Astro\Helpers\Formatter::formatOrb($aspect['orb']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
