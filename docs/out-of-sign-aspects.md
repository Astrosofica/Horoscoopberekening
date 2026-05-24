# Out-of-Sign Aspects: Implementatie

**Datum:** 2026-05-23
**Doel:** Documentatie voor code review van de out-of-sign aspect logica in `draw_aspect_lines()`

---

## Theoretische Achtergrond

### Wat is een "buiten teken" aspect?

In de astrologie wordt een aspect bepaald door de graadafstand tussen twee planeten. 
Echter, het teken waarin een planeet staat geeft ook een kwalitatief karakter aan het aspect.

**Voorbeeld:**
- Mars op 8° Kreeft, Venus op 5° Ram
- Verschil: 93° → vierkant (90° ± orb)
- Mars in Kreeft, Venus in Ram: 3 tekens verschil → verwacht 90°
- Dit is een **binnen teken** aspect: de graadafstand en de tekenrelatie komen overeen

**Maar:**
- Zon op 27° Ram, Maan op 2° Leeuw  
- Verschil: 95° → vierkant (90° ± orb)
- Ram(0) → Leeuw(4) = 4 tekens → verwacht 120°
- 90° ≠ 120° → **buiten teken** aspect: de graadafstand matcht niet met de tekenrelatie

### Waarom is dit belangrijk?

Buiten-teken aspecten hebben een ander karakter. De orb wordt daarom strenger 
toegepast — maar uitsluitend voor **zodiacale aspecten** (conjunctie, sextiel,
vierkant, driehoek, inconjunct, oppositie). **Harmonische aspecten** 
(semisquare 45°, sesquiquadraat 135%) zijn gebaseerd op harmonische 
hoekverhoudingen en hebben een vaste orb die onafhankelijk is van de
tekenstructuur — die worden niet geraakt door de out-of-sign logica.

### Beperkingsregel

Buiten-teken zodiacale aspecten worden beperkt tot een **maximale orb van 
2°**, zodat alleen betekenisvolle overgangszones rond tekenranden worden
behouden. De orb wordt niet eerst gehalveerd — alleen gecapt op 2°. Dit 
geeft met name het inconjunct (150°) iets meer ruimte (2° i.p.v. 1.25°).

### De KERN van de juiste oplossing

**Bepaal het teken waarin planeet B zou moeten staan** door de aspecthoek 
op te tellen bij de longitude van planeet A. Als planeet B in dat teken staat,
is het een binnen-teken aspect. Zo niet, dan buiten-teken.

**Voorbeeld:**
- Jupiter(18.8°|Ram) + 135°(sesquiquadraat) = 153.8° → **Maagd**
- Uranus staat op 153.2° → **Maagd** → match → binnen teken!
- Jupiter(18.8°|Ram) + 135° = 153.8° → **Maagd**
- Ascendant(242.4°|Boogschutter) — Geen match → buiten teken

---

## Implementatie

### Bestand: `public/Wheel/functions.php` — `draw_aspect_lines()`

#### Stap 1: Bepaal de teken-per-planeet en de aspect-mapping

```php
$sign_names = ['Ram', 'Stier', 'Tweelingen', 'Kreeft', 'Leeuw', 'Maagd', 
               'Weegschaal', 'Schorpioen', 'Boogschutter', 'Steenbok', 
               'Waterman', 'Vissen'];

$aspect_angles = [1=>0, 7=>45, 6=>60, 4=>90, 3=>120, 8=>135, 5=>150, 2=>180];
```

#### Stap 2: Na aspect-detectie, de out-of-sign check

De check gebeurt na het identificeren van het aspect (`$q > 0`) maar vóór het tekenen:

```php
// Out-of-sign check: bepaal waar planeet B zou staan bij exact aspect
$sign1 = (int)($longitude[$i] / 30);
$sign2 = (int)($longitude[$j] / 30);
$actualAngle = $aspect_angles[$q];

// Voeg de aspecthoek toe aan elke planeet, kijk welk teken dat oplevert
$expectedSignFromI = (int)((($longitude[$i] + $actualAngle) % 360) / 30);
$expectedSignFromJ = (int)((($longitude[$j] + $actualAngle) % 360) / 30);

// Out-of-sign als GEEN van beide checks matcht
$isOutOfSign = ($expectedSignFromI != $sign2 && $expectedSignFromJ != $sign1);
```

**Waarom beide richtingen?** Omdat de kortste weg tussen twee planeten
in beide richtingen kan zijn. Check van planeet A→B dekt één richting,
check van planeet B→A dekt de andere richting. Samen zijn ze volledig.

#### Stap 3: Alleen zodiacale aspecten krijgen out-of-sign check

```php
// Harmonische aspecten (45°, 135°) worden niet beïnvloed door tekenstructuur
$isZodiacal = in_array($q, [1, 2, 3, 4, 5, 6]);
```

Semisquare($q=7) en sesquiquadraat($q=8) worden overgeslagen — ze behouden
altijd hun vaste orb van 2°, ongeacht de tekenrelatie.

#### Stap 4: Bepaal de gebruikte orb

```php
if ($q == 1) {
    $usedOrb = $orb_conj;
} elseif ($q == 6) {
    $usedOrb = $orb_minor;
} elseif (in_array($q, [4, 3, 2])) {
    $usedOrb = $orb_major;
} elseif ($q == 7 or $q == 8) {
    $usedOrb = 2;        // Semisquare en sesquiquadraat: vaste orb
} elseif ($q == 5) {
    $usedOrb = 2.5;      // Inconjunct: vaste orb
}
```

| Aspect | $q | Normale orb (Zon/Maan) | Normale orb (overig) |
|--------|-----|----------------------|----------------------|
| Conjunctie | 1 | 7° | 5° |
| Semisquare | 7 | 2° (vast) | 2° (vast) |
| Sextiel | 6 | 5° | 4° |
| Vierkant | 4 | 6° | 5° |
| Driehoek | 3 | 6° | 5° |
| Sesquiquadraat | 8 | 2° (vast) | 2° (vast) |
| Inconjunct | 5 | 2.5° (vast) | 2.5° (vast) |
| Oppositie | 2 | 6° | 5° |

#### Stap 5: Reduced orb voor out-of-sign (alleen zodiacale aspecten)

```php
// Out-of-sign check alleen voor zodiacale aspecten
$isZodiacal = in_array($q, [1, 2, 3, 4, 5, 6]);

if ($isOutOfSign && $isZodiacal) {
    // Beperk tot max 2° — niet eerst halveren, alleen cappen
    $reducedOrb = min($usedOrb, 2);
    
    // Check of de afstand binnen gereduceerde orb valt
    $withinReduced = ($actualAngle == 0)
        ? ($da <= $reducedOrb)           // Conjunctie: da ≤ reducedOrb
        : ($da >= ($actualAngle - $reducedOrb) and 
           $da <= ($actualAngle + $reducedOrb));  // Overig: da ∈ [angle±reducedOrb]

    if (!$withinReduced) {
        continue;  // Aspect vervalt (buiten gereduceerde orb)
    }
    // Als withinReduced: aspect blijft behouden met gereduceerde orb
}
```

**Waarom niet halveren?** De consultatie wees uit dat een simpele cap van 2°
voldoende is om overgangszones rond tekenranden te filteren, zonder dat
onnodig complexe logica nodig is. Het inconjunct (150°) krijgt hierdoor
2° i.p.v. 1.25° — een verwaarloosbaar verschil in de praktijk.

---

## Voorbeelden (uit debug-output)

### Binnen teken → GETROKKEN

```
Venus(104.9°|Kreeft) x Jupiter(18.8°|Ram) da=86.1° q=4(vierkant) 
  +90°→Weegschaal +90°→Kreeft → binnen teken → DRAW
```

- Venus in Kreeft + 90° = 194.9° → Weegschaal
- Jupiter staat in Ram (0) ≠ Weegschaal (6) → geen match
- Jupiter in Ram + 90° = 108.8° → Kreeft
- Venus staat in Kreeft (3) → MATCH! → binnen teken

### Buiten teken → SKIP (conjunctie in andere tekens)

```
Sun(116.3°|Kreeft) x Mercury(122.9°|Leeuw) da=6.6° q=1(conjunctie) 
  +0°→Kreeft +0°→Leeuw out-of-sign orb=7.0°→2.0° → SKIP
```

- Mercurius in Leeuw ≠ Kreeft → out-of-sign
- Orb gecapt op 2°, 6.6° > 2° → SKIP

### Buiten teken → SKIP (sextiel over teekengrens)

```
Ascendant(242.4°|Boogschutter) x MC(178.7°|Maagd) da=63.7° q=6(sextiel) 
  +60°→Waterman +60°→Schorpioen out-of-sign orb=4.0°→2.0° → SKIP
```

- Geen van beide checks matcht → out-of-sign  
- Orb gecapt op 2°, 63.7° ∉ [58°,62°] → SKIP

### Binnen teken → DRAW (zodiacal)

```
Venus(104.9°|Kreeft) x Jupiter(18.8°|Ram) da=86.1° q=4(vierkant) 
  +90°→Weegschaal +90°→Kreeft → binnen teken → DRAW
```

- Jupiter + 90° = 108.8° → Kreeft — Venus staat in Kreeft → MATCH

### Harmonic → DRAW (altijd, geen out-of-sign check)

```
Jupiter(18.8°|Ram) x Uranus(153.2°|Maagd) da=134.5° q=8(sesquiquadraat) 
  +135°→Maagd +135°→Steenbok → harmonic → DRAW
```

- Sesquiquadraat is harmonisch — altijd getekend met vaste orb 2°
- 134.5° binnen [133°,137°] → DRAW
- Toont ook aan dat de sign-check wél werkt voor 135° (oude methode faalde hier)

---

## Waarom harmonische aspecten (45°, 135°) zijn uitgezonderd

## Waarom de oude methode niet werkte

**Oude methode:** Bereken tekenafstand en vergelijk met `expected_from_signs[]`:

```php
$signDist = ($sign2 - $sign1 + 12) % 12;
$expectedAngle = [0, 30, 60, 90, 120, 150, 180, 150, 120, 90, 60, 30][$signDist];
$isOutOfSign = ($expectedAngle != $actualAngle);
```

**Probleem:** Deze tabel werkt alleen voor aspecten die een veelvoud van 30° zijn
(sextiel=60°, vierkant=90°, etc.). Voor **semisquare (45°)** en 
**sesquiquadraat (135°)** geeft de tabel altijd de verkeerde waarde
(resp. 30° of 150° — nooit 45° of 135°), waardoor deze aspecten 
**altijd** als out-of-sign werden gemarkeerd, ongeacht de werkelijke 
tekenrelatie.

**Nieuwe methode:** `(longitude[$i] + actualAngle) % 360` → sign
Dit werkt voor **alle** aspecthoeken uniform, inclusief 45° en 135°.

---

## Drempelwaardes Samengevat

| Situatie | Effectieve orbregel |
|----------|-------------------|
| Binnen teken aspect | Volledige orb (onveranderd) |
| Buiten teken, afstand ≤ reducedOrb | Aspect blijft, met gereduceerde orb |
| Buiten teken, afstand > reducedOrb | Aspect vervalt (SKIP) |

Waarbij `reducedOrb = min(normalOrb, 2)` (en alleen voor zodiacale aspecten).
