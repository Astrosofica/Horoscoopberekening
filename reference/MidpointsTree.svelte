<script>
  import Navigation from "./Navigation.svelte";
  import { astroData } from "../stores/astroData.js";
  import Details from "./Details.svelte";
  import { pGlyph, sGlyph, aGlyph } from '../stores/glyph.js';
  import { padding, makeReadable, decodeHtml } from '../utils/utils.js';

  $: planeten = $astroData.planets;
  $: huizen = $astroData.houses;

  let pllon = [];
  let midpoints = [];
  let aspect_orb = 1.2;
let aspect_vlag = 0;
let dom= [0,45,90,135,180]; // dominante aspecten

let weergave = ""; 
let plloop = []; // It seems to be an array, need to be initialized
let mid_graad = []; // It seems to be an array, need to be initialized
let mid_planeet1 = []; // It seems to be an array, need to be initialized
let mid_planeet2 = []; // It seems to be an array, need to be initialized
let mid_naam = []; // It seems to be an array, need to be initialized
let mp_m = []; // It seems to be an array, need to be initialized
let mp_a = []; // It seems to be an array, need to be initialized
let mp_o = []; // It seems to be an array, need to be initialized

  $: if (planeten && huizen) {
    // Initialize pllon and hoek arrays

    pllon = [...planeten];
    pllon.push({ pos: huizen[0].long, naam: "Ascendant" });
    pllon.push({ pos: huizen[9].long, naam: "Midhemel" });
    plloop = pllon; // It seems to be an array of objects, need to be initialized

    let teller = 0;
    for (let p1 = 0; p1 <= 11; p1++) {
      for (let p2 = p1 + 1; p2 <= 12; p2++) {
        let graad;
        // check if p1 is a new planet to insert a blank line
        if (p1 > 0 && p2 === p1 + 1) {
          midpoints.push({
            naam1: "",
            naam2: "",
            graad: "",
          });
        }
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

        // push graad into mid_graad array
        mid_graad.push(graad);
        // push p1 into mid_planeet1 array
        mid_planeet1.push(p1);
        // push p2 into mid_planeet2 array
        mid_planeet2.push(p2);
        let naam= pllon[p1].naam + "/" + pllon[p2].naam;
        mid_naam.push(naam);

        teller = teller + 1;
      }
    }





    for (let i2 = 0; i2 <= 12; i2++) { // Planet counter.

let mp_teller = 0; // counter for number of midpoint aspects found
for (let aa = 0; aa <= 77; aa++) { // 77 is the length of the array with midpoints

    let afstand = plloop[i2].pos - mid_graad[aa];
    for (let iii = 0; iii <= 4; iii++) {

        let orb = (afstand < 0) ? afstand + dom[iii] : afstand - dom[iii];

        if (orb <= aspect_orb && orb >= -aspect_orb && i2 !== mid_planeet1[aa] && i2 !== mid_planeet2[aa]) {
            aspect_vlag = 1;
            mp_m[mp_teller] = aa;
            mp_o[mp_teller] = orb;
            mp_a[mp_teller] = dom[iii];
            mp_teller++;
        }

        if (iii < 4) { // no double oppositions

            orb = (afstand < 0) ? afstand + (360 - dom[iii]) : afstand - (360 - dom[iii]);

            if (orb <= aspect_orb && orb >= -aspect_orb && i2 !== mid_planeet1[aa] && i2 !== mid_planeet2[aa]) {
                aspect_vlag = 1;
                mp_m[mp_teller] = aa;
                mp_o[mp_teller] = orb;
                mp_a[mp_teller] = dom[iii];
                mp_teller++;
            }
        }
    }
}
let element = Math.floor(mp_teller - 1);

if (element > 0){
    let mp_o1 = [];
    let mp_o2 = [];
    let volgorde = [];
    let exact = [];

    for (let aloop = 0; aloop <= element; aloop++) {
        mp_o1[aloop] = mp_o[aloop];
    }

    for (let loop3 = 0; loop3 <= element; loop3++) {
        let heel_getal = Math.abs(mp_o[loop3]);
        mp_o2[loop3] = heel_getal;
    }

    mp_o1.sort((a, b) => a - b);
    mp_o2.sort((a, b) => a - b);

    weergave += `<tr style="background-color: #F5F5F5;"><td colspan="6"><br><strong>${plloop[i2].naam} <span class="astro-font">${pGlyph[i2]}</span> - ${makeReadable(plloop[i2].pos, sGlyph)}</strong></td></tr>\n`;


    for (let loop2 = 0; loop2 <= (mp_teller - 1); loop2++) {
        for (let vloop = 0; vloop <= element; vloop++) {
            if (mp_o[loop2] == mp_o1[vloop]) volgorde[loop2] = vloop;
        }
        for (let eloop = 0; eloop <= element; eloop++) {
            let etal = Math.abs(mp_o[loop2]);
            if (etal == mp_o2[eloop]) exact[loop2] = eloop;
        }
    }

    for (let valloop = element; valloop >=0; valloop--) {
        for (let dloop = 0; dloop <= element; dloop++) {
          if (volgorde[dloop] == valloop) {
                weergave += `<tr><td>&nbsp;&nbsp;&nbsp;&#124;&#8212;</td><td><span class="astro-font">${pGlyph[mid_planeet1[mp_m[dloop]]]}</span>/<span class="astro-font">${pGlyph[mid_planeet2[mp_m[dloop]]]}</span></td><td> ${makeReadable(mid_graad[mp_m[dloop]], sGlyph)}</td><td align="right" class="astro-font"> ${getGlyph(mp_a[dloop])}</td><td align="right">Orb : ${orb_weergave(mp_o[dloop])}</td>`;
                if (exact[dloop] == 0) weergave += `<td align="left">**</td></tr>\n`;
                else weergave += `<td>&nbsp;</td></tr>\n`;
            }
        }
    }

    mp_o1 = [];
    mp_o2 = [];
    volgorde = [];
    exact = [];
}
}

if (aspect_vlag == 0) weergave += "Geen midpunten gevonden.";

  }

// Helper functions
function orb_weergave(orb) {
    if (orb<0) { orb=orb*-1; }
    const degree = Math.floor(orb);
    const remainder = (orb - degree) * 60;
    const minute = Math.floor(remainder);
    const second = Math.floor((remainder - minute) * 60);
    return `${degree}°${padding(minute)}'${padding(second)}''`;
}

function getGlyph(degValue) { // Display glyph for degree value aspect
  degValue=parseInt(degValue);
  const glyphObj = aGlyph.find(item => item.deg === degValue);
  return glyphObj ? glyphObj.glyph : '';
}
</script>

<div class="container">
  <Navigation />
  <Details />
  <h3>Midpunten boompjes</h3>
  <table class="alternate">
    {@html weergave}
  </table>
</div>

<style>
  h3 {
    color: #333;
    font-size: 1.5rem;
    margin-bottom: 15px;
  }
</style>
