<script>
  import Navigation from "./Navigation.svelte";
  import { astroData } from "../stores/astroData.js";
  import Details from "./Details.svelte";
  import { pGlyph, sGlyph, aGlyph } from "../stores/glyph.js";
  import { makeReadable, decodeHtml } from "../utils/utils.js";

  $: planeten = $astroData.planets;
  $: huizen = $astroData.houses;

  let mirrorPoints = [];
  let mirrorPointAspects = [];

  // Initialize spglas
  const spglas = [
    135, 105, 75, 165, 45, 195, 15, 255, 285, 315, 345, 225, 0, 270, 330,
  ];
  // Initialize aspect_graad
  const aspect_graad = [0, 45, 90, 135, 180];

  // Helper functions
  function orbDisplay(orb) {
    if (orb < 0) orb = orb * -1; // make positive
    const degree = Math.floor(orb);
    const rest = (orb - degree) * 60;
    const minute = Math.floor(rest);
    const second = Math.floor((rest - minute) * 60);
    const paddedMinute = minute < 10 ? `0${minute}` : minute;
    const paddedSecond = second < 10 ? `0${second}` : second;
    const readableOrb = `${degree}°${paddedMinute}'${paddedSecond}"`;
    return readableOrb;
  }

  // Reactive statement to calculate mirror points and aspects
  $: if (planeten && huizen) {
    const plloop = [...planeten];
    plloop[11] = { pos: parseFloat(huizen[0].long), naam: "Ascendant" };
    plloop[12] = { pos: parseFloat(huizen[9].long), naam: "Midhemel" };

    const pars =
      parseFloat(plloop[1].pos) +
      parseFloat(plloop[11].pos) -
      parseFloat(plloop[0].pos);

    const adjustedPars = pars > 360 ? pars - 360 : pars < 0 ? pars + 360 : pars;

    // Initialize planetId
    const planetId = [];
    planetId[0] = 0;
    planetId[1] = 1;
    planetId[2] = 2;
    planetId[3] = 2;
    planetId[4] = 3;
    planetId[5] = 3;
    planetId[6] = 4;
    planetId[7] = 5;
    planetId[8] = 6;
    planetId[9] = 7;
    planetId[10] = 8;
    planetId[11] = 9;
    planetId[12] = 11;
    planetId[13] = 12;
    planetId[14] = 14;

    // Initialize spglpl
    let spglpl = [];
    spglpl[0] = plloop[0].pos;
    spglpl[1] = plloop[1].pos;
    spglpl[2] = plloop[2].pos;
    spglpl[3] = plloop[2].pos;
    spglpl[4] = plloop[3].pos;
    spglpl[5] = plloop[3].pos;
    spglpl[6] = plloop[4].pos;
    spglpl[7] = plloop[5].pos;
    spglpl[8] = plloop[6].pos;
    spglpl[9] = plloop[7].pos;
    spglpl[10] = plloop[8].pos;
    spglpl[11] = plloop[9].pos;
    spglpl[12] = plloop[11].pos;
    spglpl[13] = plloop[12].pos;
    spglpl[14] = adjustedPars;

    // Calculate mirror points
    mirrorPoints = spglas.map((value, index) => {
      const spgl1 = value - spglpl[index] + value;
      const spgl2 = spgl1 < 0 ? spgl1 + 360 : spgl1 > 360 ? spgl1 - 360 : spgl1;
      return { name: planetId[index], pos: spgl2 };
    });

    // Initialize spglln
    let spglln = mirrorPoints.map((mirrorPoint) => mirrorPoint.pos);

    // Calculate mirror point aspects
    mirrorPointAspects = []; // Reset mirrorPointAspects
    spglln.forEach((spgl, aa) => {
      // each mirrorpoint longitude (spgl as aa)
      plloop.forEach((planet, i2) => {
        // natal planets (planet as i2) has naam, snel and pos as values
        if (i2 !== 10) {
          // Exclude NK
          const afstand = Math.abs(spgl - planet.pos); // measure distance between mirrorpoint and planet

          for (let iii = 0; iii <= 4; iii++) {
            // loop through all aspects
            let orb = afstand - aspect_graad[iii];

            if (Math.abs(orb) <= 1.5) {
              mirrorPointAspects.push({
                name1: planetId[aa],
                name2: i2,
                degree: aspect_graad[iii],
                orb,
              });
            }

            if (iii !== 4) {
              // No double oppositions so skip last iteration
              orb = afstand - (360 - aspect_graad[iii]);

              if (Math.abs(orb) <= 1.5) {
                mirrorPointAspects.push({
                  name1: planetId[aa],
                  name2: i2,
                  degree: aspect_graad[iii],
                  orb,
                });
              }
            }
          }
        }
      });
    });
  }

  function getGlyph(degValue) {
    // Display glyph for degree value aspect
    degValue = parseInt(degValue);
    const glyphObj = aGlyph.find((item) => item.deg === degValue);
    return glyphObj ? glyphObj.glyph : "";
  }
</script>

<div class="container">
  <Navigation />
  <Details />
  <h3>Spiegelpunten volgens Jan de Jong</h3>
  <div class="duotable-container">
    <table class="alternate duotable1">
      <tr>
        <th colspan="2">Spiegelpunten</th>
      </tr>
      {#each mirrorPoints as point}
        <tr class="table-row">
          <td
            ><span class="planeet astro-font">{@html pGlyph[point.name]}</span> i</td
          >
          <td>{@html makeReadable(point.pos, sGlyph)}</td>
        </tr>
      {/each}
    </table>
    <table class="alternate duotable2">
      <tr>
        <th colspan="4">Aspecten met spiegelpunten</th>
      </tr>
      {#each mirrorPointAspects as aspect}
        <tr class="table-row">
          <td
            ><span class="planeet astro-font">{@html pGlyph[aspect.name1]}</span
            > i</td
          >
          <td class="astro-font">{@html getGlyph([aspect.degree])}</td>
          <td
            ><span class="planeet astro-font">{@html pGlyph[aspect.name2]}</span
            > r</td
          >
          <td align="right">orb: {orbDisplay(aspect.orb)}</td>
        </tr>
      {/each}
    </table>
  </div>
</div>

<style>
  .duotable-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
  }
  .duotable1 {
    flex: 1 0 40%;
    box-sizing: border-box;
  }
  .duotable2 {
    flex: 1 0 60%;
    box-sizing: border-box;
  }

  @media screen and (max-width: 400px) {
    /* 768px is a common breakpoint for tablets and below. Adjust as needed. */
    .duotable1,
    .duotable2 {
      flex: 1 0 100%;
    }
  }

  .table-row:nth-child(even) {
    background-color: #f2f2f2;
  }

  .table-row:hover {
    background-color: #e0e0e0;
  }

  td {
    padding: 1px;
    text-align: left;
    border-bottom: 1px solid #ddd;
  }

  h3 {
    margin-bottom: 12px;
  }
</style>
