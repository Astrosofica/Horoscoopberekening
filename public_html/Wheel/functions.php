<?php
Function mid($midstring, $midstart, $midlength)
{
  return(substr($midstring, $midstart-1, $midlength));
}


Function Reduce_below_30($longitude)
{
  $lng = $longitude;

  while ($lng >= 30)
  {
    $lng = $lng - 30;
  }

  return $lng;
}





Function Sort_planets_by_descending_longitude($num_planets, $longitude, &$sort, &$sort_pos)
{
// load all $longitude() into sort() and keep track of the planet numbers in $sort_pos()
  for ($i = 0; $i <= $num_planets - 1; $i++)
  {
    $sort[$i] = $longitude[$i];
    $sort_pos[$i] = $i;
  }

// do the actual sort
  for ($i = 0; $i <= $num_planets - 2; $i++)
  {
    for ($j = $i + 1; $j <= $num_planets - 1; $j++)
    {
      if ($sort[$j] > $sort[$i])
      {
        $temp = $sort[$i];
        $temp1 = $sort_pos[$i];

        $sort[$i] = $sort[$j];
        $sort_pos[$i] = $sort_pos[$j];

        $sort[$j] = $temp;
        $sort_pos[$j] = $temp1;
      }
    }
  }
}


Function Count_planets_in_each_house($num_planets, $house_pos, &$sort_pos, &$sort, &$nopih, &$home)
{
// reset and count the number of planets in each house
  for ($i = 1; $i <= 12; $i++)
  {
    $nopih[$i] = 0;
  }

// run through all the planets and see how many planets are in each house
  for ($i = 0; $i <= $num_planets - 1; $i++)
  {
    // get house planet is in
    $temp = $house_pos[$sort_pos[$i]];
    $nopih[$temp]++;
    $home[$i] = $temp;
  }

  // now check for Aries planets in same house as Pisces planets that do not start a new house
  while ($home[$num_planets - 1] == $home[0])
  {
    $temp1 = $sort[$num_planets - 1];
    $temp2 = $sort_pos[$num_planets - 1];
    $temp3 = $home[$num_planets - 1];

    for ($i = $num_planets - 1; $i >= 1; $i--)
    {
      $sort[$i] = $sort[$i - 1];
      $sort_pos[$i] = $sort_pos[$i - 1];
      $home[$i] = $home[$i - 1];
    }

    $sort[0] = $temp1;
    $sort_pos[0] = $temp2;
    $home[0] = $temp3;
  }
}


Function display_house_number($num, $angle, $radii, &$xy)
{
  if ($num < 10)
  {
    $char_width = 10;
  }
  else
  {
  	$char_width = 18;
  }
  $half_char_width = $char_width / 2;
  $char_height = 12;
  $half_char_height = $char_height / 2;

//puts center of character right on circumference of circle
  $xpos0 = -$half_char_width;
  $ypos0 = $char_height;

  if ($num == 1)
  {
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $y_adj = sin(deg2rad($angle)) * $char_height;
  }
  elseif ($num == 2)
  {
    $x_adj = -cos(deg2rad($angle));
    $y_adj = sin(deg2rad($angle)) * $char_height;
  }
  elseif ($num == 3)
  {
    $xpos0 = $half_char_width;
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $y_adj = sin(deg2rad($angle)) * $half_char_height;
  }
  elseif ($num == 4)
  {
    $xpos0 = $char_width;
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $ypos0 = $half_char_height;
    $y_adj = sin(deg2rad($angle)) * $half_char_height;
  }
  elseif ($num == 5)
  {
    $xpos0 = $half_char_width;
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $ypos0 = $half_char_height;
    $y_adj = sin(deg2rad($angle)) * $half_char_height;
  }
  elseif ($num == 6)
  {
    $xpos0 = $half_char_width;
    $x_adj = -cos(deg2rad($angle));
    $y_adj = sin(deg2rad($angle)) * $char_height;
  }
  elseif ($num == 7)
  {
    $x_adj = -cos(deg2rad($angle)) * $char_width;
    $ypos0 = -$half_char_height;
    $y_adj = -sin(deg2rad($angle)) * $char_height;
  }
  elseif ($num == 8)
  {
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $ypos0 = -$half_char_height;
    $y_adj = sin(deg2rad($angle));
  }
  elseif ($num == 9)
  {
    $xpos0 = -$char_width;
    $x_adj = -cos(deg2rad($angle)) * $char_width;
    $ypos0 = -$half_char_height;
    $y_adj = sin(deg2rad($angle));
  }
  elseif ($num == 10)
  {
    $xpos0 = -$char_width;
    $x_adj = -cos(deg2rad($angle)) * $char_width;
    $ypos0 = $half_char_height;
    $y_adj = sin(deg2rad($angle)) * $half_char_height;
  }
  elseif ($num == 11)
  {
    $xpos0 = -$half_char_width;
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $y_adj = sin(deg2rad($angle)) * $char_height;
  }
  elseif ($num == 12)
  {
    $x_adj = -cos(deg2rad($angle)) * $half_char_width;
    $y_adj = sin(deg2rad($angle)) * $char_height;
  }

  $xy[0] = $xpos0 + $x_adj - ($radii * cos(deg2rad($angle + 12)));
  $xy[1] = $ypos0 + $y_adj + ($radii * sin(deg2rad($angle + 12)));;

  return ($xy);
}


Function drawboldtext($image, $size, $angle, $x_cord, $y_cord, $clr_to_use, $fontfile, $text, $boldness)
{
  $_x = array(1, 0, 1, 0, -1, -1, 1, 0, -1);
  $_y = array(0, -1, -1, 0, 0, -1, 1, 1, 1);

  for ($n = 0; $n <= $boldness; $n++)
  {
    ImageTTFText($image, $size, $angle, $x_cord+$_x[$n], $y_cord+$_y[$n], $clr_to_use, $fontfile, $text);
  }
}


Function display_planet_glyph($our_angle, $angle_to_use, $radii, &$xy, $code)
{
// $code = 0 for planet glyph, 1 for text, 2 for sign glyph, 3 for Rx symbol
// $our_angle in degree, $angle_to_use in radians
  $this_angle = Crunch($our_angle);

  if ($this_angle >= 1 And $this_angle <= 181)
  {
    if ($code == 0)
    {
      $cw_pl_glyph = 17;
      $ch_pl_glyph = 17;
    }
    elseif ($code == 1)
    {
      $cw_pl_glyph = 14;
      $ch_pl_glyph = 12;
    }
    elseif ($code == 2)
    {
      $cw_pl_glyph = 14;
      $ch_pl_glyph = 12;
    }
    else
    {
      $cw_pl_glyph = 8;
      $ch_pl_glyph = 10;
    }
  }
  else
  {
    if ($code == 0)
    {
      $cw_pl_glyph = 13;
      $ch_pl_glyph = 17;
    }
    elseif ($code == 1)
    {
      $cw_pl_glyph = 8;
      $ch_pl_glyph = 8;
    }
    elseif ($code == 2)
    {
      $cw_pl_glyph = 8;
      $ch_pl_glyph = 8;
    }
    else
    {
      $cw_pl_glyph = 6;
      $ch_pl_glyph = 10;
    }
  }

  $gap_pl_glyph = -10;

// take into account the width and height of the glyph, defined below
// get distance we need to shift the glyph so that the absolute middle of the glyph is the start point
  $center_pos_x = -$cw_pl_glyph / 2;
  $center_pos_y = $ch_pl_glyph / 2;

// get the offset we have to move the center point to in order to be properly placed
  $offset_pos_x = $center_pos_x * cos($angle_to_use);
  $offset_pos_y = $center_pos_y * sin($angle_to_use);

// now get the final X, Y coordinates
  $xy[0] = $center_pos_x + $offset_pos_x + ((-$radii + $gap_pl_glyph) * cos($angle_to_use));
  $xy[1] = $center_pos_y + $offset_pos_y + (($radii - $gap_pl_glyph) * sin($angle_to_use));

  return ($xy);
}


Function display_house_cusp($num, $angle, $radii, &$xy)
{
  $char_width = 18;
  $half_char_width = $char_width / 2;
  $char_height = 12;
  $half_char_height = $char_height / 2;

//puts center of character right on circumference of circle
  $xpos0 = -$half_char_width;
  $ypos0 = $half_char_height;

  $x_adj = -cos(deg2rad($angle));
  $y_adj = sin(deg2rad($angle));

  $xy[0] = $xpos0 + $x_adj - ($radii * cos(deg2rad($angle)));
  $xy[1] = $ypos0 + $y_adj + ($radii * sin(deg2rad($angle)));;

  return ($xy);
}


Function Crunch($x)
{
  if ($x >= 0)
  {
    $y = $x - floor($x / 360) * 360;
  }
  else
  {
    $y = 360 + ($x - ((1 + floor($x / 360)) * 360));
  }

  return $y;
}


Function Check_for_overlap($angle, $spot_filled, $spacing)
{
// spacing is really 1 more than we enter with, but we use assign $spacing = 1 less for easier math below
  $result = False;

  for ($i = $angle - $spacing; $i <= $angle + $spacing; $i++)
  {
    if ($spot_filled[Crunch(round($i))] == 1)
    {
      $result = True;
      break;
    }
  }

  return $result;
}
function mysql_escape_mimic($inp) {
    if(is_array($inp))
        return array_map(__METHOD__, $inp);

    if(!empty($inp) && is_string($inp)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }

    return $inp;
}


Function draw_aspect_lines($im, $center_pt, $radius, $inner_diameter_offset, $planets, $planet_angle, $ascendant, $colors, $draw = true, $debug = false)
{
    $num_planets = count($planets);
    $last_planet_num = $num_planets - 1;

    $longitude = [];
    $names = [];
    foreach ($planets as $i => $planet) {
        $longitude[$i] = $planet['longitude'];
        $names[$i] = $planet['name'];
    }

    $excluded_names = ['Vertex', 'Lilith', 'POF', 'ParsFortuna', 'TNode', 'NorthNode', 'Chiron'];

    $sign_names = ['Ram', 'Stier', 'Tweelingen', 'Kreeft', 'Leeuw', 'Maagd', 'Weegschaal', 'Schorpioen', 'Boogschutter', 'Steenbok', 'Waterman', 'Vissen'];

    $aspect_names = [1=>'conjunctie', 7=>'semisquare', 6=>'sextiel', 4=>'vierkant', 3=>'driehoek', 8=>'sesquiquadraat', 5=>'inconjunct', 2=>'oppositie'];

    $aspect_angles = [1=>0, 7=>45, 6=>60, 4=>90, 3=>120, 8=>135, 5=>150, 2=>180];

    $debug_log = [];

    imagesetthickness($im, 2);

    for ($i = 0; $i <= $last_planet_num - 1; $i++) {
        for ($j = $i + 1; $j <= $last_planet_num; $j++) {
            $q = 0;
            $da = abs($longitude[$i] - $longitude[$j]);

            if ($da > 180) {
                $da = 360 - $da;
            }

            if ($names[$i] == 'Sun' or $names[$i] == 'Moon' or $names[$j] == 'Sun' or $names[$j] == 'Moon') {
                $orb_conj = 7;
                $orb_major = 6;
                $orb_minor = 5;
            } else {
                $orb_conj = 5;
                $orb_major = 5;
                $orb_minor = 4;
            }

            if ($da <= $orb_conj) {
                $q = 1;
            } elseif (($da <= (45 + 2)) and ($da >= (45 - 2))) {
                $q = 7;
            } elseif (($da <= (60 + $orb_minor)) and ($da >= (60 - $orb_minor))) {
                $q = 6;
            } elseif (($da <= (90 + $orb_major)) and ($da >= (90 - $orb_major))) {
                $q = 4;
            } elseif (($da <= (120 + $orb_major)) and ($da >= (120 - $orb_major))) {
                $q = 3;
            } elseif (($da <= (135 + 2)) and ($da >= (135 - 2))) {
                $q = 8;
            } elseif (($da <= (150 + 2.5)) and ($da >= (150 - 2.5))) {
                $q = 5;
            } elseif ($da >= (180 - $orb_major)) {
                $q = 2;
            }

            if ($q > 0) {
                if ($q == 1 or $q == 3 or $q == 6) {
                    $aspect_color = $colors['aspect_green'] ?? $colors['green'];
                } elseif ($q == 4 or $q == 2 or $q == 7 or $q == 8) {
                    $aspect_color = $colors['aspect_red'] ?? $colors['red'];
                } elseif ($q == 5) {
                    $aspect_color = $colors['aspect_orange'] ?? $colors['orange'];
                }

                // Out-of-sign check: bepaal waar planeet B zou staan bij exact aspect
                $sign1 = (int)($longitude[$i] / 30);
                $sign2 = (int)($longitude[$j] / 30);
                $actualAngle = $aspect_angles[$q];
                $expectedSignFromI = (int)((($longitude[$i] + $actualAngle) % 360) / 30);
                $expectedSignFromJ = (int)((($longitude[$j] + $actualAngle) % 360) / 30);
                $isOutOfSign = ($expectedSignFromI != $sign2 && $expectedSignFromJ != $sign1);

                // Alleen zodiacale aspecten (0°, 60°, 90°, 120°, 150°, 180°) krijgen
                // een out-of-sign check. Harmonische aspecten (45°, 135°) zijn gebaseerd
                // op harmonische hoekverhoudingen, niet op tekenstructuur — die behouden
                // altijd hun vaste orb.
                $isZodiacal = in_array($q, [1, 2, 3, 4, 5, 6]);

                // Bepaal gebruikte orb voor dit aspect
                if ($q == 1) {
                    $usedOrb = $orb_conj;
                } elseif ($q == 6) {
                    $usedOrb = $orb_minor;
                } elseif (in_array($q, [4, 3, 2])) {
                    $usedOrb = $orb_major;
                } elseif ($q == 7 or $q == 8) {
                    $usedOrb = 2;
                } elseif ($q == 5) {
                    $usedOrb = 2.5;
                }

                if ($isOutOfSign && $isZodiacal) {
                    // Out-of-sign zodiacale aspecten: max orb 2°
                    $reducedOrb = min($usedOrb, 2);
                    $withinReduced = ($actualAngle == 0)
                        ? ($da <= $reducedOrb)
                        : ($da >= ($actualAngle - $reducedOrb) and $da <= ($actualAngle + $reducedOrb));

                    if ($debug) {
                        $debug_log[] = sprintf(
                            "[ASPECT_DEBUG] %s(%s°|%s) x %s(%s°|%s) da=%.1f° q=%d(%s) +%d°→%s +%d°→%s out-of-sign orb=%.1f°→%.1f° → %s",
                            $names[$i], round($longitude[$i], 1), $sign_names[$sign1],
                            $names[$j], round($longitude[$j], 1), $sign_names[$sign2],
                            $da, $q, $aspect_names[$q],
                            (int)$actualAngle, $sign_names[$expectedSignFromI],
                            (int)$actualAngle, $sign_names[$expectedSignFromJ],
                            $usedOrb, $reducedOrb,
                            $withinReduced ? "accept" : "SKIP"
                        );
                    }

                    if (!$withinReduced) {
                        continue;
                    }
                } elseif ($debug) {
                    $tag = $isZodiacal ? "binnen teken" : "harmonic";
                    $debug_log[] = sprintf(
                        "[ASPECT_DEBUG] %s(%s°|%s) x %s(%s°|%s) da=%.1f° q=%d(%s) +%d°→%s +%d°→%s → %s → DRAW",
                        $names[$i], round($longitude[$i], 1), $sign_names[$sign1],
                        $names[$j], round($longitude[$j], 1), $sign_names[$sign2],
                        $da, $q, $aspect_names[$q],
                        (int)$actualAngle, $sign_names[$expectedSignFromI],
                        (int)$actualAngle, $sign_names[$expectedSignFromJ],
                        $tag
                    );
                }

                $i_excluded = in_array($names[$i], $excluded_names);
                $j_excluded = in_array($names[$j], $excluded_names);

                if ($q != 1 and ($i_excluded or $j_excluded)) {
                    if ($debug) {
                        $debug_log[] = "[ASPECT_DEBUG]   → excluded ({$names[$i]}/{$names[$j]})";
                    }
                    continue;
                }

                if ($draw) {
                    $inner_r = $radius - $inner_diameter_offset;
                    $x1 = -$inner_r * cos(deg2rad($planet_angle[$i] - $ascendant));
                    $y1 = $inner_r * sin(deg2rad($planet_angle[$i] - $ascendant));
                    $x2 = -$inner_r * cos(deg2rad($planet_angle[$j] - $ascendant));
                    $y2 = $inner_r * sin(deg2rad($planet_angle[$j] - $ascendant));

                    imageline($im, (int)($x1 + $center_pt), (int)($y1 + $center_pt), (int)($x2 + $center_pt), (int)($y2 + $center_pt), $aspect_color);
                }
            }
        }
    }

    imagesetthickness($im, 1);

    if ($debug) {
        return $debug_log;
    }
}
?>