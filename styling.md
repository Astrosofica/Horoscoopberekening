**DOEL**
Verbeter de visuele samenhang en gebruiksvriendelijkheid van een astrologie-webapplicatie.
Focus op: rust, consistentie, leesbaarheid en schaalbaarheid.

---

# 1. KLEURENSCHEMA (IMPLEMENTEREN ALS NIEUWE CSS VARIABELEN)

## 1.1 Basisprincipes

* Gebruik gedempte, licht vergrijsde kleuren (geen felle Material kleuren)
* Zorg dat UI-kleuren aansluiten op de bestaande horoscoop PNG (groen/blauw/grijs/lavendel)
* Beperk het aantal dominante kleuren tot max. 2 + statuskleuren

---

## 1.2 Nieuwe variabelen (vervang huidige set)

```css
:root {
  --color-primary: #5c7a8a;
  --color-primary-dark: #465f6c;
  --color-primary-light: rgba(92, 122, 138, 0.12);

  --color-accent: #8c6f9c;

  --color-success: #6f9a7f;
  --color-warning: #c59a63;
  --color-danger: #c46b6b;

  --color-text: #2a2f33;
  --color-text-muted: #6b737a;
  --color-text-hint: #8a9096;

  --bg-body: #f4f2ee;
  --bg-card: #ffffff;
  --bg-subtle: #f7f6f3;

  --border-color: #e2e0dc;
  --border-light: #eceae6;
}
```

---

## 1.3 Gebruik van kleuren (strikte regels)

* Primary kleur:
  * knoppen (primary actions)
  * links
  * actieve elementen
* Accent kleur:
  * subtiele highlights
  * alternatieve secties (bijv. huizen vs planeten)
* Statuskleuren:
  * alleen voor feedback (success, error, warning)
  * NIET voor layout of grote vlakken
* Verboden:
  * geen felblauwe/paarse/oranje blokken als achtergrond
  * geen meerdere concurrerende accentkleuren naast elkaar

---

# 2. COMPONENT STYLING

## 2.1 Cards (globaal)

* Alle content in witte cards (`--bg-card`)
* Gebruik zachte borders, geen harde schaduwen

```css
.card {
  background: var(--bg-card);
  border: 1px solid var(--border-color);
  border-radius: 12px;
  padding: 1.25rem;
}
```

---

## 2.2 Sectie-onderscheid (belangrijk)

Gebruik GEEN gekleurde achtergronden.
Gebruik in plaats daarvan subtiele top-borders:

```css
.card--planets {
  border-top: 3px solid var(--color-primary);
}

.card--houses {
  border-top: 3px solid var(--color-accent);
}
```

---

## 2.3 Knoppen

* Primary knop:

```css
.btn-primary {
  background: var(--color-primary);
  color: white;
}
```

* Secondary knop:

```css
.btn-secondary {
  background: #e6e3de;
  color: var(--color-text);
}
```

* Danger knop:

```css
.btn-danger {
  background: transparent;
  color: var(--color-danger);
}
.btn-danger:hover {
  background: var(--color-danger);
  color: white;
}
```

---

# 3. RESULTATENPAGINA (HERSTRUCTUREREN)

## 3.1 Algemene regels

* Verwijder alle gekleurde blokken
* Gebruik alleen cards + typografie + spacing
* Zorg voor duidelijke verticale hiërarchie

---

## 3.2 Layout

* Horoscoop bovenaan gecentreerd
* Daaronder grid met 2 kolommen (desktop):

```
[ Planetposities ]   [ Huizen ]
```

* Onder deze sectie:

```
[ Aspecten (volledige breedte) ]
```

---

## 3.3 Spacing

* Minimaal 24px ruimte tussen secties
* Consistente padding binnen cards

---

# 4. DASHBOARD (BELANGRIJKSTE WIJZIGING)

## 4.1 Vervang tabel door card-lijst

NIET meer gebruiken:

* klassieke tabel layout

WEL gebruiken:

* lijst van cards per horoscoop

---

## 4.2 Structuur per item

```text
Naam (bold)
Datum + tijd (1 regel)
Plaats (1 regel, compact)

[ Bekijk ] [ Bewerk ] [ Verwijder ]
```

---

## 4.3 Styling

* Cards onder elkaar
* Hover-effect: lichte achtergrondverandering
* Actieknoppen rechts uitlijnen of onderaan groeperen

---

## 4.4 Responsiveness

* Moet zonder aanpassing goed werken op mobiel
* Geen horizontaal scrollen

---

## 4.5 Alternatief (indien tabel behouden blijft)

Pas tabel aan:

* Naam op 1 regel (no wrap)
* Combineer datum + tijd in 1 kolom
* Beperk tekstlengte van plaats

```css
td {
  white-space: nowrap;
}
```

---

# 5. FRONTPAGE VERBETERING

## 5.1 Visuele aanpassing

* Meer witruimte rond formulier
* Grotere titel
* Knop prominenter maken

---

## 5.2 Inhoudelijke toevoeging

Voeg korte introductieregel toe boven formulier:

"Een horoscoop is een symbolische kaart van mogelijkheden, geen voorspelling."

---

# 6. ALGEMENE RICHTLIJNEN

* Minder kleur = beter
* Gebruik kleur alleen functioneel
* Focus op rust en leesbaarheid
* Laat de horoscoop visueel centraal blijven
* UI mag nooit dominanter zijn dan de inhoud

---

**RESULTAAT**
Een rustige, consistente interface die aansluit bij de inhoudelijke aard van astrologie en schaalbaar is voor toekomstige uitbreidingen (tabs, extra analyses, etc.).
