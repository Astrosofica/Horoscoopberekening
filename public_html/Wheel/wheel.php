<?php
require_once __DIR__ . '/../../config/bootstrap.php';

// Try to get data from session, otherwise use hardcoded test data
if (isset($_SESSION['wheel_data'])) {
    $house_cusps = $_SESSION['wheel_data']['house_cusps'];
    $planets = $_SESSION['wheel_data']['planets'];
} else {
    // Default test data for development
    $house_cusps = [
        1 => 242.4434837, 2 => 266.2266421, 3 => 299.1805031, 4 => 358.7428395,
        5 => 19.9678896, 6 => 41.0435929, 7 => 62.4434837, 8 => 86.2266421,
        9 => 119.1805031, 10 => 178.7428395, 11 => 199.9678896, 12 => 221.0435929
    ];

    $planets = [
        ['name' => 'Sun', 'longitude' => 116.2546858, 'house' => 8, 'speed' => 0.9856],
        ['name' => 'Moon', 'longitude' => 100.6654060, 'house' => 8, 'speed' => 13.1764],
        ['name' => 'Mercury', 'longitude' => 122.8794288, 'house' => 9, 'speed' => 1.1568],
        ['name' => 'Venus', 'longitude' => 104.9154051, 'house' => 8, 'speed' => 1.3345],
        ['name' => 'Mars', 'longitude' => 175.5075901, 'house' => 9, 'speed' => 0.5223],
        ['name' => 'Jupiter', 'longitude' => 18.7711609, 'house' => 4, 'speed' => 0.0831],
        ['name' => 'Saturn', 'longitude' => 321.5064139, 'house' => 3, 'speed' => -0.0354],
        ['name' => 'Uranus', 'longitude' => 153.2257521, 'house' => 9, 'speed' => 0.0107],
        ['name' => 'Neptune', 'longitude' => 222.8988242, 'house' => 12, 'speed' => -0.0218],
        ['name' => 'Pluto', 'longitude' => 160.3788631, 'house' => 9, 'speed' => 0.0039],
        ['name' => 'TNode', 'longitude' => 110.4502822, 'house' => 8, 'speed' => -0.0522],
    ];
}

// Include the functions and configuration
include_once('configuration.php');
include_once('functions.php');

// Set the content-type
header("Content-type: image/png");

// Set the expiration time to 1 hour from now
$expire_time = time() + 3600;
header('Expires: '.gmdate('D, d M Y H:i:s', $expire_time).' GMT');

// Create the blank image
$im = @imagecreatetruecolor((int)$overall_size, (int)$overall_size) or die("Cannot initialize new GD image stream");

// Define colors
/*
$colors = [
    'white' => imagecolorallocate($im, 255, 255, 255),
    'whitesmoke' => imagecolorallocate($im, 240, 241, 238),

    'red' => imagecolorallocate($im, 160, 78, 88),
    'blue' => imagecolorallocate($im, 88, 122, 164),
    'magenta' => imagecolorallocate($im, 96, 86, 150),

    'yellow' => imagecolorallocate($im, 206, 218, 198),      // buitenste ring, salieachtig
    'cyan' => imagecolorallocate($im, 196, 220, 226),

    'green' => imagecolorallocate($im, 124, 150, 138),       // vergrijsd bladgroen
    'light_green' => imagecolorallocate($im, 182, 204, 214), // middelste ring, blauwgrijs/licht staalblauw
    'another_green' => imagecolorallocate($im, 96, 118, 108),

    'grey' => imagecolorallocate($im, 132, 136, 140),
    'black' => imagecolorallocate($im, 34, 36, 42),

    'lavender' => imagecolorallocate($im, 158, 150, 180),    // stoffig lavendel
    'orange' => imagecolorallocate($im, 176, 124, 92),
    'light_blue' => imagecolorallocate($im, 226, 235, 242),
];
*/
$colors = [
    // Achtergronden en lijnen
    'white'         => imagecolorallocate($im, 255, 255, 255),
    'whitesmoke'    => imagecolorallocate($im, 255, 255, 255),
    'magenta'       => imagecolorallocate($im, 120, 100, 115), // Gedempt purper/taupe (mystiek maar geaard)

    // Ringen en grote vlakken
    'yellow'        => imagecolorallocate($im, 226, 226, 214), // Saliegroen uit foto (#BAC8B1) - Buitenste ring
    'cyan'          => imagecolorallocate($im, 205, 215, 210), // Zeer licht 'seafoam' voor zachte vlakken

    // Groentinten (de basis van je palet)
    'green'         => imagecolorallocate($im, 123, 150, 105), // Mosgroen uit foto (#7B9669)
    'light_green'   => imagecolorallocate($im, 186, 200, 177),

    // Neutrale tinten en tekst
    'grey'          => imagecolorallocate($im, 140, 145, 135), // Warm grijs met groene ondertoon
    'black'         => imagecolorallocate($im, 45, 49, 44),    // Bijna zwart, maar zachter voor de ogen

    // De Elementen (Glyphes op de ring)
    'red'           => imagecolorallocate($im, 166, 91, 82), // Vuur: Terracotta
    'another_green' => imagecolorallocate($im, 64, 78, 59),  // Aarde: Woudgroen (hoog contrast!)
    'orange'        => imagecolorallocate($im, 184, 134, 77), // Lucht: Oker/Goud
    'blue'          => imagecolorallocate($im, 108, 132, 128), // Water: Leigrijs/Blauw

    // Extra accenten voor differentiatie
    'lavender'      => imagecolorallocate($im, 165, 165, 175), // Stoffig grijs-blauw
    'light_blue'    => imagecolorallocate($im, 235, 240, 238), // Zeer lichte 'morning mist'

    // Aspectlijnen (frissere kleuren voor betere zichtbaarheid)
    'aspect_green'  => imagecolorallocate($im, 76, 175, 80),   // Conjunctie, driehoek, sextiel
    'aspect_red'    => imagecolorallocate($im, 211, 47, 47),   // Oppositie, vierkant, semisquare, sesquiquadraat
    'aspect_orange' => imagecolorallocate($im, 255, 152, 0),   // Inconjunct
];

// Specific colors
$planet_color = $colors['black'];
$deg_min_color = $colors['black'];
$sign_color = $colors['magenta'];

// Create background
imagefilledrectangle($im, 0, 0, (int)$size_of_rect, (int)$size_of_rect, $colors['whitesmoke']);

// Prime the font system
imagettftext($im, 10, 0, 0, 0, $colors['black'], ARIAL_FONT, " ");

// Draw the outer-outer border of the chartwheel
imagefilledellipse($im, (int)$center_pt, (int)$center_pt, (int)($outer_outer_diameter + 80), (int)($outer_outer_diameter + 80), $colors['whitesmoke']);
imagefilledellipse($im, (int)$center_pt, (int)$center_pt, (int)$outer_outer_diameter, (int)$outer_outer_diameter, $colors['yellow']);
imageellipse($im, (int)$center_pt, (int)$center_pt, (int)$outer_outer_diameter, (int)$outer_outer_diameter, $colors['black']);

// Draw the outer circle of the chartwheel
imagefilledellipse($im, (int)$center_pt, (int)$center_pt, (int)$diameter, (int)$diameter, $colors['white']);
imageellipse($im, (int)$center_pt, (int)$center_pt, (int)$diameter, (int)$diameter, $colors['black']);

// Draw the inner circle of the chartwheel
imagefilledellipse($im, (int)$center_pt, (int)$center_pt, (int)($diameter - ($inner_diameter_offset_2 * 2)), (int)($diameter - ($inner_diameter_offset_2 * 2)), $colors['light_green']);
imagefilledellipse($im, (int)$center_pt, (int)$center_pt, (int)($diameter - ($inner_diameter_offset * 2)), (int)($diameter - ($inner_diameter_offset * 2)), $colors['white']);
imageellipse($im, (int)$center_pt, (int)$center_pt, (int)($diameter - ($inner_diameter_offset_2 * 2)), (int)($diameter - ($inner_diameter_offset_2 * 2)), $colors['black']);
imageellipse($im, (int)$center_pt, (int)$center_pt, (int)($diameter - ($inner_diameter_offset * 2)), (int)($diameter - ($inner_diameter_offset * 2)), $colors['black']);

// Draw the horizontal line for the Ascendant
draw_line($im, $center_pt, $radius - $inner_diameter_offset, $radius, 0, $colors['black']);

// Draw the arrow for the Ascendant
draw_ascendant_arrow($im, $center_pt, $radius, $colors);

// Draw house cusp numbers and signs
draw_house_cusps($im, $center_pt, $house_cusps, $middle_radius, $colors);

// Draw the lines for the house cusps
draw_house_spokes($im, $center_pt, $house_cusps, $radius, $inner_diameter_offset, $colors);

// Draw the MC (10th house) line and arrow
draw_mc_line($im, $center_pt, $house_cusps, $radius, $inner_diameter_offset, $colors);

// Put planets in chartwheel (also returns positions for aspect lines)
$planet_data = draw_planets($im, $center_pt, $planets, $house_cusps, $radius, $inner_diameter_offset, $spacing, $colors);

// Draw aspect lines (if requested) with optional debug output
if (isset($_GET['aspects']) && $_GET['aspects'] === '1' || isset($_GET['debug_aspects'])) {
    // Add Ascendant and MC for aspect calculation
    $aspect_planets = $planets;
    $aspect_angles = $planet_data['planet_angle'];

    $asc_idx = count($aspect_planets);
    $aspect_planets[$asc_idx] = ['name' => 'Ascendant', 'longitude' => $house_cusps[1], 'house' => 1, 'speed' => 0];
    $aspect_angles[$asc_idx] = $house_cusps[1];

    $mc_idx = $asc_idx + 1;
    $aspect_planets[$mc_idx] = ['name' => 'MC', 'longitude' => $house_cusps[10], 'house' => 10, 'speed' => 0];
    $aspect_angles[$mc_idx] = $house_cusps[10];

    $drawAspects = isset($_GET['aspects']) && $_GET['aspects'] === '1';
    $debug = isset($_GET['debug_aspects']);
    $debug_log = draw_aspect_lines($im, $center_pt, $radius, $inner_diameter_offset, $aspect_planets, $aspect_angles, $house_cusps[1], $colors, $drawAspects, $debug);
}

if (isset($_GET['debug_aspects'])) {
    header('Content-Type: text/plain; charset=utf-8');
    if (!empty($debug_log)) {
        echo "=== Aspect Debug Output ===\n\n";
        echo implode("\n", $debug_log) . "\n";
    } else {
        echo "=== Aspect Debug: No aspects found ===\n";
        echo "(aspect lines not requested? add &aspects=1)\n";
    }
    exit();
}

// Output the image
imagepng($im);
imagedestroy($im);

exit();


// ============================================================================
// Helper Functions
// ============================================================================

/**
 * Draw a line between two points on the circle
 */
function draw_line($im, $center_pt, $inner_radius, $outer_radius, $angle_deg, $color) {
    $x1 = -$inner_radius * cos(deg2rad($angle_deg));
    $y1 = -$inner_radius * sin(deg2rad($angle_deg));
    $x2 = -$outer_radius * cos(deg2rad($angle_deg));
    $y2 = -$outer_radius * sin(deg2rad($angle_deg));
    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $color);
}

/**
 * Draw the Ascendant arrow
 */
function draw_ascendant_arrow($im, $center_pt, $radius, $colors) {
    $x1 = -$radius;
    $y1 = 30 * sin(deg2rad(0));
    $x2 = -($radius - 12);
    $y2 = 12 * sin(deg2rad(-15));
    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $colors['black']);
    $y2 = 12 * sin(deg2rad(15));
    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $colors['black']);
}

/**
 * Draw house cusp numbers and signs
 */
function draw_house_cusps($im, $center_pt, $house_cusps, $middle_radius, $colors) {
    global $sign_glyph;
    
    $ascendant = $house_cusps[1];
    
    foreach ($house_cusps as $house_num => $cusp_angle) {
        $angle = -($ascendant - $cusp_angle);
        $sign_pos = get_sign_position($cusp_angle);
        $color = get_sign_color($sign_pos, $colors);
        
        // Draw sign glyph
        $xy = [];
        display_house_cusp($house_num, $angle, $middle_radius, $xy);
        imagettftext($im, 14, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $color, HAMBURG_FONT, chr($sign_glyph[$sign_pos]));
        
        // Draw house cusp degree
        $xy = display_house_cusp_offset($house_num, $angle, $middle_radius, $house_num <= 6 ? -4 : 5);
        $degree = floor(Reduce_below_30($cusp_angle));
        imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['black'], ARIAL_FONT, sprintf("%02d", $degree) . chr(176));
        
        // Draw house cusp minute
        $xy = display_house_cusp_offset($house_num, $angle, $middle_radius, get_minute_offset($house_num));
        $minute = floor(60 * (Reduce_below_30($cusp_angle) - floor(Reduce_below_30($cusp_angle))));
        imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['black'], ARIAL_FONT, sprintf("%02d", $minute) . chr(39));
    }
}

/**
 * Get sign position (1-12) from longitude
 */
function get_sign_position($longitude) {
    return (int)(floor($longitude / 30) + 1);
}

/**
 * Get color for zodiac sign
 */
function get_sign_color($sign_pos, $colors) {
    if ($sign_pos == 1 || $sign_pos == 5 || $sign_pos == 9) return $colors['red'];
    if ($sign_pos == 2 || $sign_pos == 6 || $sign_pos == 10) return $colors['another_green'];
    if ($sign_pos == 3 || $sign_pos == 7 || $sign_pos == 11) return $colors['orange'];
    return $colors['blue'];
}

/**
 * Get offset for minute display based on house number
 */
function get_minute_offset($house_num) {
    if ($house_num >= 1 && $house_num <= 4) return 4;
    if ($house_num == 5 || $house_num == 6) return 5;
    if ($house_num == 7) return -4;
    return -5;
}

/**
 * Display house cusp with offset angle
 */
function display_house_cusp_offset($house_num, $angle, $radii, $offset) {
    $adjusted_angle = $house_num <= 6 ? $angle + $offset : $angle + $offset;
    $xy = [];
    return display_house_cusp($house_num, $adjusted_angle, $radii, $xy);
}

/**
 * Draw house spoke lines
 */
function draw_house_spokes($im, $center_pt, $house_cusps, $radius, $inner_diameter_offset, $colors) {
    foreach ($house_cusps as $house_num => $cusp_angle) {
        $angle = $house_cusps[1] - $cusp_angle;
        $x1 = -$radius * cos(deg2rad($angle));
        $y1 = -$radius * sin(deg2rad($angle));
        $x2 = -($radius - $inner_diameter_offset) * cos(deg2rad($angle));
        $y2 = -($radius - $inner_diameter_offset) * sin(deg2rad($angle));
        
        // Skip Ascendant (1) and MC (10) - they have special lines
        if ($house_num != 1 && $house_num != 10) {
            imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $colors['grey']);
        }
        
        // Display house number
        $xy = [];
        display_house_number($house_num, -$angle, $radius - $inner_diameter_offset, $xy);
        imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['black'], ARIAL_FONT, $house_num);
    }
}

/**
 * Draw MC (Midheaven/10th house) line and arrow
 */
function draw_mc_line($im, $center_pt, $house_cusps, $radius, $inner_diameter_offset, $colors) {
    $angle = $house_cusps[1] - $house_cusps[10];
    $dist_mc_asc = $angle < 0 ? $angle + 360 : $angle;
    $value = 90 - $dist_mc_asc;
    $angle1 = 65 - $value;
    $angle2 = 65 + $value;
    
    $x1 = -($radius - $inner_diameter_offset) * cos(deg2rad($angle));
    $y1 = -($radius - $inner_diameter_offset) * sin(deg2rad($angle));
    $x2 = -$radius * cos(deg2rad($angle));
    $y2 = -$radius * sin(deg2rad($angle));
    
    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $colors['black']);
    
    // Draw MC arrow
    $x1 = $x2 + (15 * cos(deg2rad($angle1)));
    $y1 = $y2 + (15 * sin(deg2rad($angle1)));
    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $colors['black']);
    
    $x1 = $x2 - (15 * cos(deg2rad($angle2)));
    $y1 = $y2 + (15 * sin(deg2rad($angle2)));
    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $colors['black']);
}

/**
 * Draw planets on the chart wheel
 */
function draw_planets($im, $center_pt, $planets, $house_cusps, $radius, $inner_diameter_offset, $spacing, $colors) {
    global $sign_glyph;
    
    $num_planets = count($planets);
    $longitude = [];
    $house_pos = [];
    $speeds = [];
    $names = [];
    
    foreach ($planets as $i => $planet) {
        $longitude[$i] = $planet['longitude'];
        $house_pos[$i] = $planet['house'];
        $speeds[$i] = $planet['speed'];
        $names[$i] = $planet['name'];
    }
    
    // Sort planets by descending longitude (original algorithm)
    $sort = $longitude;
    $sort_pos = array_keys($longitude);
    
    // Bubble sort in descending order
    for ($i = 0; $i <= $num_planets - 2; $i++) {
        for ($j = $i + 1; $j <= $num_planets - 1; $j++) {
            if ($sort[$j] > $sort[$i]) {
                $temp = $sort[$i];
                $temp1 = $sort_pos[$i];
                $sort[$i] = $sort[$j];
                $sort_pos[$i] = $sort_pos[$j];
                $sort[$j] = $temp;
                $sort_pos[$j] = $temp1;
            }
        }
    }
    
    // Count planets in each house
    $nopih = [];
    $home = [];
    for ($i = 1; $i <= 12; $i++) {
        $nopih[$i] = 0;
    }
    for ($i = 0; $i <= $num_planets - 1; $i++) {
        $temp = $house_pos[$sort_pos[$i]];
        $nopih[$temp]++;
        $home[$i] = $temp;
    }
    
    // Handle Aries planets in same house as Pisces planets
    while ($home[$num_planets - 1] == $home[0]) {
        $temp1 = $sort[$num_planets - 1];
        $temp2 = $sort_pos[$num_planets - 1];
        $temp3 = $home[$num_planets - 1];
        for ($i = $num_planets - 1; $i >= 1; $i--) {
            $sort[$i] = $sort[$i - 1];
            $sort_pos[$i] = $sort_pos[$i - 1];
            $home[$i] = $home[$i - 1];
        }
        $sort[0] = $temp1;
        $sort_pos[0] = $temp2;
        $home[0] = $temp3;
    }
    
    // Initialize spot tracking
    $spot_filled = array_fill(0, 360, 0);
    $planet_angle = [];
    
    // Draw planets in reverse order (from highest longitude to lowest)
    for ($i = $num_planets - 1; $i >= 0; $i--) {
        $temp = $house_num ?? 0;
        $house_num = $house_pos[$sort_pos[$i]];
        
        if ($temp != $house_num) {
            $planets_done = 1;
        }
        
        // Calculate position within house
        $from_cusp = Crunch($sort[$i] - $house_cusps[$house_num]);
        $house_plus = $house_num + 1;
        if ($house_plus == 13) $house_plus = 1;
        $to_next_cusp = Crunch($house_cusps[$house_plus] - $sort[$i]);
        $next_cusp = $house_cusps[$house_plus];
        
        $angle = $sort[$i];
        $how_many_more_can_fit = floor($to_next_cusp / ($spacing + 1));
        
        if ($nopih[$house_num] - $planets_done > $how_many_more_can_fit) {
            $angle = Crunch($next_cusp - (($nopih[$house_num] - $planets_done) * ($spacing + 1)));
        }
        
        // Find available spot
        while (Check_for_overlap($angle, $spot_filled, $spacing)) {
            $angle++;
        }
        
        // Mark spot as filled
        $spot_filled[(int)round($angle)] = 1;
        $spot_filled[(int)Crunch(round($angle) - 1)] = 1;
        
        // Store planet angle for aspect lines
        $planet_angle[$sort_pos[$i]] = $angle;
        
        // Calculate display angle
        $angle_to_use = Crunch($angle - $house_cusps[1]);
        $our_angle = $angle_to_use;
        $rad_angle = deg2rad($angle_to_use);
        
        $planets_done++;
        
        // Draw planet glyph
        $xy = [];
        display_planet_glyph($our_angle, $rad_angle, $radius - 32, $xy, 0);
        $planet_glyph_code = get_planet_glyph($names[$sort_pos[$i]]);
        imagettftext($im, 16, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['black'], HAMBURG_FONT, chr($planet_glyph_code));
        
        // Draw degree
        $reduced_pos = Reduce_below_30($sort[$i]);
        $int_reduced_pos = floor($reduced_pos);
        $xy = [];
        display_planet_glyph($our_angle, $rad_angle, $radius - 52, $xy, 1);
        imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['black'], ARIAL_FONT, sprintf("%02d", $int_reduced_pos) . chr(176));
        
        // Draw sign
        $sign_pos = (int)(floor($sort[$i] / 30) + 1);
        $xy = [];
        display_planet_glyph($our_angle, $rad_angle, $radius - 72, $xy, 2);
        if ($sign_pos == 1 || $sign_pos == 5 || $sign_pos == 9) {
            $clr_to_use = $colors['red'];
        } elseif ($sign_pos == 2 || $sign_pos == 6 || $sign_pos == 10) {
            $clr_to_use = $colors['another_green'];
        } elseif ($sign_pos == 3 || $sign_pos == 7 || $sign_pos == 11) {
            $clr_to_use = $colors['orange'];
        } else {
            $clr_to_use = $colors['blue'];
        }
        imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $clr_to_use, HAMBURG_FONT, chr($sign_glyph[$sign_pos]));
        
        // Draw minute
        $int_reduced_pos = floor(60 * ($reduced_pos - floor($reduced_pos)));
        $xy = [];
        display_planet_glyph($our_angle, $rad_angle, $radius - 92, $xy, 1);
        imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['black'], ARIAL_FONT, sprintf("%02d", $int_reduced_pos) . chr(39));
        
        // Draw retrograde symbol
        if ($speeds[$sort_pos[$i]] < 0) {
            $xy = [];
            display_planet_glyph($our_angle, $rad_angle, $radius - 109, $xy, 3);
            imagettftext($im, 10, 0, (int)($xy[0] + $center_pt), (int)($xy[1] + $center_pt), $colors['red'], HAMBURG_FONT, chr(118));
        }
    }

    return [
        'planet_angle' => $planet_angle,
        'sort_pos' => $sort_pos
    ];
}

/**
 * Get planet glyph from name
 */
function get_planet_glyph_org($planet_name) {
    $glyphs = [
        'Sun' => 81, 'Moon' => 87, 'Mercury' => 69, 'Venus' => 82,
        'Mars' => 84, 'Jupiter' => 89, 'Saturn' => 85, 'Uranus' => 73,
        'Neptune' => 79, 'Pluto' => 80, 'Chiron' => 77, 'TNode' => 141, 'POF' => 60
    ];
    return $glyphs[$planet_name] ?? 63;
}

function get_planet_glyph($planet_name) {
    $glyphs = [
        'Sun' => 33, 'Moon' => 34, 'Mercury' => 35, 'Venus' => 36,
        'Mars' => 37, 'Jupiter' => 38, 'Saturn' => 39, 'Uranus' => 40,
        'Neptune' => 41, 'Pluto' => 42, 'Chiron' => 51, 'TNode' => 43,
        'NorthNode' => 43, 'ParsFortuna' => 124
    ];
    return $glyphs[$planet_name] ?? 52;
}
/**
 * Check if planet is retrograde based on speed
 */
function is_retrograde($planet_speed) {
    return $planet_speed < 0;
}

?>
