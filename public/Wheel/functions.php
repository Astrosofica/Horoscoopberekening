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


Function safeEscapeString($string)
{
// replace HTML tags '<>' with '[]'
  $temp1 = str_replace("<", "[", $string);
  $temp2 = str_replace(">", "]", $temp1);

// but keep <br> or <br />
// turn <br> into <br /> so later it will be turned into ""
// using just <br> will add extra blank lines
  $temp1 = str_replace("[br]", "<br />", $temp2);
  $temp2 = str_replace("[br /]", "<br />", $temp1);

// Use mysqli_real_escape_string if a connection exists, otherwise use addslashes
  if (function_exists('mysqli_real_escape_string') && ini_get('mysqli.allow_persistent') != '') {
    $temp2 = mysqli_real_escape_string($temp2);
  } else {
    $temp2 = addslashes($temp2);
  }
  return $temp2;
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
?>