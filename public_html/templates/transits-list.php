<section id="tab-transits-list" class="tab-content<?= $currentTab !== 'transits-list' ? ' tab-content--hidden' : '' ?>">
    <div class="card card--large card--transit-events">
        <h2>Transit Events</h2>

        <?php
        $transitPersonName = '';
        $transitBirthDate = '';
        $transitBirthTime = '';
        $transitLocationName = '';
        if (isset($_SESSION['horoscope']['input'])) {
            $tinp = $_SESSION['horoscope']['input'];
            $transitPersonName = trim(($tinp['firstname'] ?? '') . ' ' . ($tinp['infix'] ?? '') . ' ' . ($tinp['lastname'] ?? ''));
            $transitBirthDate = $tinp['birth_date'] ?? '';
            $transitBirthTime = $tinp['birth_time'] ?? '';
            $transitLocationName = $tinp['location_name'] ?? '';
        }
        ?>
        <?php if ($transitPersonName): ?>
        <div class="print-only print-horoscope-info">
            <?= htmlspecialchars($transitPersonName) ?> — <?= $transitBirthDate ? date('d-m-Y', strtotime($transitBirthDate)) : '' ?>
            <?php if ($transitBirthTime): ?>
                <?= htmlspecialchars($transitBirthTime) ?>
            <?php endif; ?>
            <?php if ($transitLocationName): ?>
                <?= htmlspecialchars($transitLocationName) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <p class="form-error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="events-form no-print">
            <div class="events-form-columns">
                    <div class="events-column events-column--tijdvak">
                        <h4>Tijdvak</h4>
                        <div class="events-datepicker">
                            <label>Start:<br>
                                <input type="text" name="transit_start_date_display" id="transit_start_date_display" inputmode="numeric" placeholder="DD-MM-JJJJ"
                                    value="<?= isset($_SESSION['horoscope']['transit_events']['input']['start_date']) ? htmlspecialchars(date('d-m-Y', strtotime($_SESSION['horoscope']['transit_events']['input']['start_date']))) : '' ?>">
                                <input type="hidden" name="transit_start_date" id="transit_start_date"
                                    value="<?= htmlspecialchars($_SESSION['horoscope']['transit_events']['input']['start_date'] ?? date('Y-01-01')) ?>">
                                <div class="form-hint-inline form-hint-inline--error"></div>
                                <div class="form-hint-inline form-hint-inline--hint"></div>
                            </label>
                            <label>Eind:<br>
                                <input type="text" name="transit_end_date_display" id="transit_end_date_display" inputmode="numeric" placeholder="DD-MM-JJJJ"
                                    value="<?= isset($_SESSION['horoscope']['transit_events']['input']['end_date']) ? htmlspecialchars(date('d-m-Y', strtotime($_SESSION['horoscope']['transit_events']['input']['end_date']))) : '' ?>">
                <input type="hidden" name="transit_end_date" id="transit_end_date"
                                                    value="<?= htmlspecialchars($_SESSION['horoscope']['transit_events']['input']['end_date'] ?? date('Y-12-31')) ?>">
                                                <div class="form-hint-inline form-hint-inline--error"></div>
                                                <div class="form-hint-inline form-hint-inline--hint"></div>
                                            </label>
                                        </div>
                                    <div class="section-divider"></div>
                                    <div class="events-sectie">
                                        <h5>Selectie</h5>
                                        <div class="events-quickdates">
                                            <button type="button" onclick="quickTransitCalendarYear()" class="events-quickbtn">📅 Kalenderjaar</button>
                                            <button type="button" onclick="quickTransitTwoYears()" class="events-quickbtn">📅 Twee jaar</button>
                                        </div>
                                    </div>
                                    <div class="section-divider"></div>
                                    <div class="events-sectie">
                                        <h5>Opties</h5>
                                        <div class="events-options">
                                            <label class="events-checkbox-label">
                                <input type="checkbox" name="include_house_ingress"
                                    <?= ($_SESSION['horoscope']['transit_events']['input']['include_house_ingress'] ?? false) ? 'checked' : '' ?>> Huis ingress
                            </label>
                        </div>
                    </div>
                </div>

                <div class="events-column events-column--planets">
                    <h4>Transit</h4>
                    <label class="toggle-all">
                        <input type="checkbox" id="toggle-transit-planets"
                            onchange="toggleAllGroup('transit_planet[]', this.checked)" <?= $allTransitPlanets ? 'checked' : '' ?>> Alle
                    </label>
                    <?php
                    $transitPlanetNames = [
                        5 => 'Jupiter', 6 => 'Saturnus', 7 => 'Uranus', 8 => 'Neptunus', 9 => 'Pluto'
                    ];
                    foreach ($transitPlanetNames as $idx => $tName): ?>
                        <label>
                            <input type="checkbox" name="transit_planet[]" value="<?= $idx ?>"
                                onchange="checkToggleState('transit_planet[]', 'toggle-transit-planets', 5)"
                                <?= in_array($idx, $savedTransitPlanets) ? 'checked' : '' ?>>
                            <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($idx) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="events-column events-column--aspects">
                    <h4>Aspecten</h4>
                    <label class="toggle-all">
                        <input type="checkbox" id="toggle-transit-aspects"
                            onchange="toggleAllGroup('transit_aspect[]', this.checked)" <?= $allTransitAspects ? 'checked' : '' ?>> Alle
                    </label>
                    <?php
                    foreach ([0, 45, 60, 90, 120, 135, 180] as $aspDeg): ?>
                        <label>
                            <input type="checkbox" name="transit_aspect[]" value="<?= $aspDeg ?>"
                                onchange="checkToggleState('transit_aspect[]', 'toggle-transit-aspects', 7)"
                                <?= in_array($aspDeg, $savedTransitAspects) ? 'checked' : '' ?>>
                            <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getAspectGlyph($aspDeg) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="events-column events-column--radix">
                    <h4>Radix</h4>
                    <label class="toggle-all">
                        <input type="checkbox" id="toggle-transit-radix"
                            onchange="toggleAllGroup('radix_target[]', this.checked)" <?= $allTransitRadix ? 'checked' : '' ?>> Alle
                    </label>
                    <?php
                    for ($i = 0; $i <= 9; $i++): ?>
                        <label>
                            <input type="checkbox" name="radix_target[]" value="<?= $i ?>"
                                onchange="checkToggleState('radix_target[]', 'toggle-transit-radix', 13)"
                                <?= in_array($i, $savedRadixTargets) ? 'checked' : '' ?>>
                            <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($i) ?></span>
                        </label>
                    <?php endfor; ?>
                    <div class="section-divider"></div>
                    <?php
                    for ($i = 10; $i <= 12; $i++): ?>
                        <label>
                            <input type="checkbox" name="radix_target[]" value="<?= $i ?>"
                                onchange="checkToggleState('radix_target[]', 'toggle-transit-radix', 13)"
                                <?= in_array($i, $savedRadixTargets) ? 'checked' : '' ?>>
                            <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($i) ?></span>
                        </label>
                    <?php endfor; ?>
                </div>
            </div>

            <button type="submit" name="calculate_transits" class="events-submit">Bereken Transits</button>
        </form>
    </div>

    <?php if (isset($transitEventsResult) && count($transitEventsResult) > 0): ?>
    <div class="card card--large events-results">
        <h4>Resultaten (<?= count($transitEventsResult) ?> events)</h4>
        <table>
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Dir</th>
                    <th>Transit</th>
                    <th>Aspect</th>
                    <th>Radix</th>
                    <th>Transit Pos</th>
                    <th>Radix Pos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transitEventsResult as $event): ?>
                <tr class="<?= $event['event_type'] === 'house_ingress' ? 'row--ingress' : '' ?>">
                    <td><?= date('d-m-Y', $event['timestamp']) ?></td>
                    <td><?= $event['direction'] ?></td>
                    <td class="text-center">
                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getPlanetGlyphByIndex($event['tplanet']) ?></span>
                    </td>
                    <td class="text-center">
                        <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getAspectGlyph($event['aspect']) ?></span>
                    </td>
                    <td class="text-center">
                        <?php if ($event['rplanet'] >= 40 && $event['rplanet'] <= 51): ?>
                            <?= \Astro\Glyph\SymbolGlyph::getGlyphForTarget($event['rplanet']) ?>
                        <?php else: ?>
                            <span class="astro-glyph"><?= \Astro\Glyph\SymbolGlyph::getGlyphForTarget($event['rplanet']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($event['tlong']) ?></td>
                    <td class="text-center"><?= \Astro\Helpers\Formatter::formatLongitudeWithGlyph($event['rlong']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php elseif (isset($transitEventsResult)): ?>
    <div class="card card--large events-results">
        <p>Geen transits gevonden in deze periode.</p>
    </div>
    <?php endif; ?>
</section>
