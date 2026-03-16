<?php
// configuration.php
// varables used in index.php

// fonts are not being found, need to use absolute path
define('FONT_DIR', dirname(__FILE__));
define('ARIAL_FONT', FONT_DIR . '/regular.ttf');
define('HAMBURG_FONT', FONT_DIR . '/symbol.ttf');

define('SE_SUN', 0);
define('SE_MOON', 1);
define('SE_MERCURY', 2);
define('SE_VENUS', 3);
define('SE_MARS', 4);
define('SE_JUPITER', 5);
define('SE_SATURN', 6);
define('SE_URANUS', 7);
define('SE_NEPTUNE', 8);
define('SE_PLUTO', 9);
define('SE_CHIRON', 10);
define('SE_LILITH', 11);
define('SE_TNODE', 12);		//this must be last thing before angle stuff
define('SE_POF', 13);
define('SE_VERTEX', 14);

// Some sizes for the image

$overall_size = 640;
$size_of_rect = $overall_size;		// size of rectangle in which to draw the wheel
$diameter = 520;						// diameter of circle drawn
$outer_outer_diameter = 600;			// diameter of circle drawn
$outer_diameter_distance = ($outer_outer_diameter - $diameter) / 2;	// distance between outer-outer diameter and diameter
$inner_diameter_offset = 125;			// diameter of inner circle drawn
$inner_diameter_offset_2 = 105;		// diameter of nextmost inner circle drawn
$dist_from_diameter1 = 32;			// distance inner planet glyph is from circumference of wheel
$dist_from_diameter1a = 12;			// distance inner planet glyph is from circumference of wheel - for line
$dist_from_diameter2 = 58;			// distance outer planet glyph is from circumference of wheel
$dist_from_diameter2a = 28;			// distance outer planet glyph is from circumference of wheel - for line
$radius = $diameter / 2;				// radius of circle drawn
$middle_radius = ($outer_outer_diameter + $diameter) / 4 - 3;		//the radius for the middle of the two outer circles
$center_pt = $size_of_rect / 2;		// center of circle

$last_planet_num = 10;				//add a planet
$num_planets = $last_planet_num + 1;
$spacing = 4;     // spacing between planet glyphs around wheel - this number is really one more than shown here

// glyphs used for planets - ./HamburgSymbols.ttf - Sun, Moon - Pluto
/*
$pl_glyph[0] = 81;
$pl_glyph[1] = 87;
$pl_glyph[2] = 69;
$pl_glyph[3] = 82;
$pl_glyph[4] = 84;
$pl_glyph[5] = 89;
$pl_glyph[6] = 85;
$pl_glyph[7] = 73;
$pl_glyph[8] = 79;
$pl_glyph[9] = 80;
$pl_glyph[10] = 141; // 77
$pl_glyph[11] = 60; // 96
$pl_glyph[12] = 60; // 141
$pl_glyph[13] = 60; // 60
$pl_glyph[14] = 60; // 109

*/
$pl_glyph[0] = 33;
$pl_glyph[1] = 34;
$pl_glyph[2] = 35;
$pl_glyph[3] = 36;
$pl_glyph[4] = 37;
$pl_glyph[5] = 38;
$pl_glyph[6] = 39;
$pl_glyph[7] = 40;
$pl_glyph[8] = 41;
$pl_glyph[9] = 42;
$pl_glyph[10] = 43; // 77

$pl_glyph[11] = 60; // 96
$pl_glyph[12] = 60; // 141
$pl_glyph[13] = 60; // 60
$pl_glyph[14] = 60; // 109

/*
// glyphs used for planets - ./HamburgSymbols.ttf - Aries - Pisces
$sign_glyph[1] = 97;
$sign_glyph[2] = 115;
$sign_glyph[3] = 100;
$sign_glyph[4] = 102;
$sign_glyph[5] = 103;
$sign_glyph[6] = 104;
$sign_glyph[7] = 106;
$sign_glyph[8] = 107;
$sign_glyph[9] = 108;
$sign_glyph[10] = 122;
$sign_glyph[11] = 120; // 120
$sign_glyph[12] = 99; // 99
*/
$sign_glyph[1] = 80;
$sign_glyph[2] = 81;
$sign_glyph[3] = 82;
$sign_glyph[4] = 83;
$sign_glyph[5] = 84;
$sign_glyph[6] = 85;
$sign_glyph[7] = 86;
$sign_glyph[8] = 87;
$sign_glyph[9] = 88;
$sign_glyph[10] = 89;
$sign_glyph[11] = 90; // 120
$sign_glyph[12] = 91; // 99

?>