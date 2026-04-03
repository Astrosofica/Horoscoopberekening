<script>
  import Navigation from "./Navigation.svelte";
  import { astroData } from "../stores/astroData.js";
  import Details from "./Details.svelte";
  import { pGlyph, sGlyph } from '../stores/glyph.js';
  import { makeReadable, decodeHtml } from '../utils/utils.js';

  $: planeten = $astroData.planets;
  $: huizen = $astroData.houses;

  let pllon = [];
  let midpoints = [];
  let sortedMidpoints = [];
  // to display in 2 columns we need to split the array in 2 at index 45
  let splitPoint = '';
  let mPoints1 = [];
  let mPoints2 = [];

  $: if (planeten && huizen) {
    // Initialize pllon and hoek arrays

    pllon = [...planeten];
    pllon.push({ pos: huizen[0].long, naam: "Ascendant" });
    pllon.push({ pos: huizen[9].long, naam: "Midhemel" });

    let teller = 0;
    for (let p1 = 0; p1 <= 11; p1++) {
      for (let p2 = p1 + 1; p2 <= 12; p2++) {
        let graad;
   
        if (parseFloat(pllon[p1].pos) > parseFloat(pllon[p2].pos)) {
          graad = parseFloat(pllon[p1].pos) - parseFloat(pllon[p2].pos);
          if (graad > 180) {
            graad = parseFloat(pllon[p2].pos) + 360 - parseFloat(pllon[p1].pos);
            graad = graad / 2 + parseFloat(pllon[p1].pos);
          } else {
            graad = graad / 2 + parseFloat(pllon[p2].pos);
          }
        } else {
          graad = parseFloat(pllon[p2].pos) - parseFloat(pllon[p1].pos);
          if (graad > 180) {
            graad = parseFloat(pllon[p1].pos) + 360 - parseFloat(pllon[p2].pos);
            graad = graad / 2 + parseFloat(pllon[p2].pos);
          } else {
            graad = graad / 2 + parseFloat(pllon[p1].pos);
          }
        }

        midpoints.push({
          plan1: p1,
          plan2: p2,
          graad: get360(graad),
        });

        teller = teller + 1;
      }
    }

    let sortedMidpoints = midpoints.sort((a, b) => a.graad - b.graad); // some magic to sort the midpoints

    for (let i = 1; i < sortedMidpoints.length; i++) { // sort midponts and add empty rows between signs
      if (Math.floor(sortedMidpoints[i].graad / 30) !== Math.floor(sortedMidpoints[i - 1].graad / 30)) {
        sortedMidpoints.splice(i, 0, {
          plan1: "",
          plan2: "",
          graad: "",
        });
        i++;
      }
    }

    // midpoints are sorted now we need to split them in 2 arrays to display in 2 columns
    splitPoint = Math.floor(midpoints.length / 2);
    while (splitPoint < midpoints.length) {    // find next empty row starting from splitPoint
      if (midpoints[splitPoint].plan1 === "" && midpoints[splitPoint].plan2 === "" && midpoints[splitPoint].graad === "") {
          break;
        }
      splitPoint++;
    }
    mPoints1 = midpoints.slice(0, splitPoint);
    mPoints2 = midpoints.slice(splitPoint);
    // Check if the first row of mPoints2 is empty and remove it if so
    if (mPoints2.length > 0 && mPoints2[0].plan1 === "" && mPoints2[0].plan2 === "" && mPoints2[0].graad === "") {
      mPoints2.shift();
    }
  }

  // Helper functions
  // return graad within 360 degree
  function get360(pos) {
    if (pos > 360) pos -= 360;
    if (pos < 0) pos += 360;
    return pos;
  }
</script>

<div class="container">
  <Navigation />
  <Details />
  <h3>Midpunten per teken</h3>
  <div style="display: flex; justify-content: space-between;">
  <table class="alternate">
    {#each mPoints1 as midpoint, i}
      {#if midpoint.plan1 === "" && midpoint.plan2 === "" && midpoint.graad === ""}
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      {:else}
        <tr>
          <td>
            <span class="planeet astro-font">{@html pGlyph[midpoint.plan1]}</span> /
            <span class="planeet astro-font">{@html pGlyph[midpoint.plan2]}</span>
          </td>
          <td>{@html makeReadable(midpoint.graad, sGlyph)}</td>
        </tr>
      {/if}
    {/each}
  </table>
  <table class="alternate">
    {#each mPoints2 as midpoint, i}
      {#if midpoint.plan1 === "" && midpoint.plan2 === "" && midpoint.graad === ""}
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
      {:else}
        <tr>
          <td>
            <span class="planeet astro-font">{@html pGlyph[midpoint.plan1]}</span> /
            <span class="planeet astro-font">{@html pGlyph[midpoint.plan2]}</span>
          </td>
          <td>{@html makeReadable(midpoint.graad, sGlyph)}</td>
        </tr>
      {/if}
    {/each}
  </table>
  </div>
</div>

<style>
  h3 {
    color: #333;
    font-size: 1.5rem;
    margin-bottom: 15px;
  }

  .alternate {
    border-collapse: collapse;
    width: 100%;
  }

  .alternate td {
    border: 1px solid #ddd;
    padding: 2px;
    text-align: left;
  }

  .alternate tr {
    background-color: #fff;
  }

  .alternate tr:nth-child(even) {
    background-color: #f2f2f2;
  }

  .alternate tr:hover {
    background-color: #ddd;
  }

  .planeet {
    font-weight: bold;
  }
</style>
