<section id="tab-progressions-list" class="tab-content<?= $currentTab !== 'progressions-list' ? ' tab-content--hidden' : '' ?>">
    <div class="card card--large card--progression-events">
        <h2>Progressie Events</h2>

        <?php
        $progPersonName = '';
        $progBirthDate = '';
        $progBirthTime = '';
        $progLocationName = '';
        if (isset($_SESSION['horoscope']['input'])) {
            $pinp = $_SESSION['horoscope']['input'];
            $progPersonName = trim(($pinp['firstname'] ?? '') . ' ' . ($pinp['infix'] ?? '') . ' ' . ($pinp['lastname'] ?? ''));
            $progBirthDate = $pinp['birth_date'] ?? '';
            $progBirthTime = $pinp['birth_time'] ?? '';
            $progLocationName = $pinp['location_name'] ?? '';
        }
        ?>
        <?php if ($progPersonName): ?>
        <div class="print-only print-horoscope-info">
            <?= htmlspecialchars($progPersonName) ?> — <?= $progBirthDate ? date('d-m-Y', strtotime($progBirthDate)) : '' ?>
            <?php if ($progBirthTime): ?>
                <?= htmlspecialchars($progBirthTime) ?>
            <?php endif; ?>
            <?php if ($progLocationName): ?>
                <?= htmlspecialchars($progLocationName) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <form method="POST" class="events-form no-print">
            <div class="events-form-columns">
            <div class="events-column events-column--tijdvak">
                <h4>Tijdvak</h4>
                <div class="events-datepicker">
                    <label>Start:<br>
                        <?php
                        $progStartDisplay = '';
                        if (isset($_SESSION['horoscope']['progression_events']['input']['start_date'])) {
                            $progStartDisplay = date('d-m-Y', strtotime($_SESSION['horoscope']['progression_events']['input']['start_date']));
                        }
                        ?>
                        <input type="text" name="prog_start_date_display" id="prog_start_date_display" inputmode="numeric" placeholder="DD-MM-JJJJ" value="<?= htmlspecialchars($progStartDisplay) ?>" autocomplete="off">
                        <input type="hidden" name="prog_start_date" id="prog_start_date" value="<?= htmlspecialchars($_SESSION['horoscope']['progression_events']['input']['start_date'] ?? date('Y-01-01')) ?>">
                        <div class="form-hint-inline form-hint-inline--error"></div>
                        <div class="form-hint-inline form-hint-inline--hint"></div>
                    </label>
                    <label>Eind:<br>
                        <?php
                        $progEndDisplay = '';
                        if (isset($_SESSION['horoscope']['progression_events']['input']['end_date'])) {
                            $progEndDisplay = date('d-m-Y', strtotime($_SESSION['horoscope']['progression_events']['input']['end_date']));
                        }
                        ?>
                        <input type="text" name="prog_end_date_display" id="prog_end_date_display" inputmode="numeric" placeholder="DD-MM-JJJJ" value="<?= htmlspecialchars($progEndDisplay) ?>" autocomplete="off">
                        <input type="hidden" name="prog_end_date" id="prog_end_date" value="<?= htmlspecialchars($_SESSION['horoscope']['progression_events']['input']['end_date'] ?? date('Y-12-31')) ?>">
                        <div class="form-hint-inline form-hint-inline--error"></div>
                        <div class="form-hint-inline form-hint-inline--hint"></div>
                    </label>
                </div>
                <div class="section-divider"></div>
                <div class="events-sectie">
                    <h5>Selectie</h5>
                    <div class="events-quickdates">
                        <button type="button" id="quick-calyear" onclick="quickCalendarYear()" class="events-quickbtn">📅 Kalenderjaar</button>
                        <button type="button" id="quick-twoyear" onclick="quickTwoYears()" class="events-quickbtn">📅 Twee jaar</button>
                    </div>
                </div>
                <div class="section-divider"></div>
                <div class="events-sectie">
                    <h5>Opties</h5>
                    <div class="events-options">
                        <label class="events-checkbox-label">
                            <input type="checkbox" name="include_house_ingress" <?= isset($_SESSION['horoscope']['progression_events']['input']['include_house_ingress']) && $_SESSION['horoscope']['progression_events']['input']['include_house_ingress'] ? 'checked' : '' ?>> Huis ingress
                        </label>
                        <label class="events-checkbox-label">
                            <input type="checkbox" name="include_sign_ingress" <?= isset($_SESSION['horoscope']['progression_events']['input']['include_sign_ingress']) && $_SESSION['horoscope']['progression_events']['input']['include_sign_ingress'] ? 'checked' : '' ?>> Teken ingress
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="events-column events-column--planets">
                <h4>Progressief</h4>
                <label class="toggle-all">
                    <input type="checkbox" id="toggle-progressive" onchange="toggleAllGroup('progressive_planet[]', this.checked)" <?= $allProgressive ? 'checked' : '' ?>>
                    Alle
                </label>
                <?php for ($i = 0; $i <= 9; $i++): ?>
                    <label>
                        <input type="checkbox" name="progressive_planet[]" value="<?= $i ?>" onchange="checkToggleState('progressive_planet[]', 'toggle-progressive', 10)" <?= in_array($i, $selProg) ? 'checked' : '' ?>>
                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($i) ?></span>
                    </label>
                <?php endfor; ?>
            </div>
            
            <div class="events-column events-column--aspects">
                <h4>Aspecten</h4>
                <label class="toggle-all">
                    <input type="checkbox" id="toggle-aspects" onchange="toggleAllGroup('aspect_type[]', this.checked)" <?= $allAspects ? 'checked' : '' ?>>
                    Alle
                </label>
                <?php 
                $aspectOptions = [0, 45, 60, 90, 120, 135, 180];
                foreach ($aspectOptions as $aspDeg): ?>
                    <label>
                        <input type="checkbox" name="aspect_type[]" value="<?= $aspDeg ?>" onchange="checkToggleState('aspect_type[]', 'toggle-aspects', 7)" <?= in_array($aspDeg, $selAspects) ? 'checked' : '' ?>>
                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getAspectGlyph($aspDeg) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            
            <div class="events-column events-column--radix">
                <h4>Radix</h4>
                <label class="toggle-all">
                    <input type="checkbox" id="toggle-radix" onchange="toggleAllGroup('radix_target[]', this.checked)" <?= $allRadix ? 'checked' : '' ?>>
                    Alle
                </label>
                <?php 
                $radixPlanetLabels = [
                    0 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(0), 'name' => 'Zon'],
                    1 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(1), 'name' => 'Maan'],
                    2 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(2), 'name' => 'Mercurius'],
                    3 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(3), 'name' => 'Venus'],
                    4 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(4), 'name' => 'Mars'],
                    5 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(5), 'name' => 'Jupiter'],
                    6 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(6), 'name' => 'Saturnus'],
                    7 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(7), 'name' => 'Uranus'],
                    8 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(8), 'name' => 'Neptunus'],
                    9 => ['glyph' => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(9), 'name' => 'Pluto'],
                ];
                foreach ($radixPlanetLabels as $idx => $planet): ?>
                    <label>
                        <input type="checkbox" name="radix_target[]" value="<?= $idx ?>" onchange="checkToggleState('radix_target[]', 'toggle-radix', 13)" <?= in_array($idx, $selRadix) ? 'checked' : '' ?>>
                        <span class="astro-glyph"><?= $planet['glyph'] ?></span>
                    </label>
                <?php endforeach; ?>
                <div class="section-divider"></div>
                <?php 
                $radixAxisLabels = [
                    10 => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(10),
                    11 => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(11),
                    12 => \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex(12),
                ];
                foreach ($radixAxisLabels as $idx => $glyph): ?>
                    <label>
                        <input type="checkbox" name="radix_target[]" value="<?= $idx ?>" onchange="checkToggleState('radix_target[]', 'toggle-radix', 13)" <?= in_array($idx, $selRadix) ? 'checked' : '' ?>>
                        <span class="astro-glyph"><?= $glyph ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            </div>
            
            <button type="submit" name="calculate_progressions" class="events-submit">Bereken Progressie Events</button>
        </form>
        
        <?php if (isset($error)): ?>
            <p class="form-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
    </div>
    
    <?php if (isset($progEventsResult) && count($progEventsResult) > 0): ?>
    <div class="card card--large events-results">
        <h4>Resultaten (<?= count($progEventsResult) ?> events)</h4>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Dir</th>
                    <th>Progressief</th>
                    <th>Aspect</th>
                    <th>Radix</th>
                    <th>Prog Pos</th>
                    <th>Radix Pos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($progEventsResult as $event): ?>
                    <tr class="<?= $event['event_type'] === 'rd_transition' ? 'row--rd' : '' ?><?= ($event['event_type'] === 'house_ingress' || $event['event_type'] === 'sign_ingress') ? 'row--ingress' : '' ?>">
                        <td><?= date('d-m-Y', $event['timestamp']) ?></td>
                        <td><?= $event['direction'] ?></td>
                        <td><span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($event['progressive_index']) ?></span></td>
                        <td><span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getAspectGlyph($event['aspect']) ?></span></td>
                        <td>
                            <?php if ($event['radix_index'] >= 40 && $event['radix_index'] <= 51): ?>
                                <?= \Astro\Glyph\SymbolGlyph::getGlyphForTarget($event['radix_index']) ?>
                            <?php elseif ($event['event_type'] === 'rd_transition'): ?>
                                <?= htmlspecialchars($event['radix_target']) ?>
                            <?php else: ?>
                                <span class="astro-glyph">
                                    <?= \Astro\Glyph\SymbolGlyph::getGlyphForTarget($event['radix_index']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($event['progressive_position']) ?></td>
                        <td><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($event['radix_position']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif (isset($progEventsResult) && count($progEventsResult) === 0): ?>
    <div class="card card--large">
        <p>Geen events gevonden in de opgegeven periode.</p>
    </div>
    <?php endif; ?>
</section>
