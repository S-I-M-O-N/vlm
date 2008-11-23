<?php
include_once("functions.php");


$noHeader=htmlentities(quote_smart($_REQUEST['noHeader']));
$boattype=htmlentities(quote_smart($_REQUEST['boattype']));

// Le blindage...
$miws=htmlentities(quote_smart($_REQUEST['minws']));
if ( $miws < 1 ) $miws = 0;
$maws=htmlentities(quote_smart($_REQUEST['maxws']));
if ( $maws > 60 ) $maws = 60;
$minws=min($miws,$maws);
$maxws=max($miws,$maws);

$pas=htmlentities(quote_smart($_REQUEST['pas']));
if ( $pas <= 1 ) $pas=5;

$pas = floor(max($pas , ($maxws-$minws)/10));

header("Cache-Control: no-store, no-cache, must-revalidate");


if ($noHeader !=1)
{
  header("Content-type: image/png");
}
include_once("config.php");


if (isset($boattype))
{
  //get max speed boat value 
  $query="SELECT max(boatspeed) FROM ".$boattype ." where wspeed between " . $minws . " and " . $maxws ; 
  $result = mysql_query($query) or die("Query [$query] failed \n");
  $row = mysql_fetch_array($result, MYSQL_NUM);
  $maxvalue= $row[0];
  $maxchart = nextmultiple($maxvalue, STEP);
  $marginleft = 50;
  $marginright = 30;
  $margintop = 30;
  $marginbottom = 15;
  $font = 2;

  $im = imagecreatetruecolor (  $maxchart* STEPSIZE +$marginleft + $marginright, 
		       $maxchart * 2 * STEPSIZE + $marginbottom + $margintop);
  
  $bgColor = imagecolorallocate($im, 255,255,255);
  imagefill($im , 0,0 , $bgColor);

  $color = imagecolorallocatealpha($im, 0, 0, 0, 0);
  $colorBlack = imagecolorallocatealpha($im, 0, 0, 0, 0);
  $center_x = $marginleft ;
  $center_y = $margintop + $maxchart * STEPSIZE;

  //draw a title
  imagestring ( $im, 3, 30, 5, "Chart for ".$boattype , $color);
  imagestring ( $im, 2, 60, 17, "(wind = " . $minws . " to " . $maxws . " kts)", $color);

  //draw wind direction (arrow)
  imagesetthickness ( $im, 5);
  imageline ( $im, 
	      $marginleft  + 10,
	      $margintop + 10,
	      $marginleft  +10,
	      $margintop + 50,
	      $color );

  imagesetthickness ( $im, 10);
  imageline ( $im, 
	      $marginleft  +2,
	      $margintop + 42,
	      $marginleft  +10,
	      $margintop + 50,
	      $color );
  imageline ( $im, 
	      $marginleft  +18,
	      $margintop + 42,
	      $marginleft  +10,
	      $margintop + 50,
	      $color );

  imagesetthickness ( $im, 1);

  //draw "angle with true wind" near 30
  imagestring ( $im, $font, 70, 45, "Angle with true wind", $color);

  //draw wind speed knts
  imagestring ( $im, $font, imagesx($im) - 100, imagesy($im) - 80, "Wind speed (knts)", $color);

  //line on the left, diameter
  imageline ( $im, 
	      $center_x,
	      $margintop,
	      $center_x,
	      $margintop + $maxchart * 2 * STEPSIZE,
	      $color );


  //draw a "boat speed in knots" in 3 lines
  $textposition = speedchartcoordinates($maxchart-5, 190, $center_x, $center_y);
  imagestring ( $im, $font, $textposition[0], $textposition[1], "boat", $color);
  imagestring ( $im, $font, $textposition[0], $textposition[1] + imagefontheight(1)+1, "speed", $color);
  imagestring ( $im, $font, $textposition[0], $textposition[1] + 2*(imagefontheight(1)+1) , "(knts)", $color);

  //graduations
  for ($boatspeed=$maxchart; $boatspeed>=0; $boatspeed = $boatspeed - STEP)
    {
      imagearc ( $im, $center_x, $center_y, $boatspeed * 2 * STEPSIZE, $boatspeed * 2 * STEPSIZE, 270, 90, $color);
      //draw 2 text for each graduations
      $textposition1 = speedchartcoordinates($boatspeed, 0, $center_x, $center_y);
      imagestring ( $im, $font, $textposition1[0] - 2*imagefontwidth($font), $textposition1[1] , $boatspeed, $color);
      $textposition2 = speedchartcoordinates($boatspeed, 180, $center_x, $center_y);
      imagestring ( $im, $font, $textposition2[0] - 2*imagefontwidth($font), $textposition2[1] , $boatspeed, $color);

    }

  //one line every 30 degrees
  for ($i=30; $i<=180; $i=$i+30)
    {
      drawlinespeedchart(0, $i, $maxchart, $i, $im, $color, $center_x, $center_y);
      //draw a text like "30°"
      //echo "draw $i in ($maxchart +1, $i)\n";
      $textposition = speedchartcoordinates($maxchart+1, $i, $center_x, $center_y);
      //      echo "x = $textposition[0] y = $textposition[1] \n";
      imagestring ( $im, $font, $textposition[0], $textposition[1], $i, $color);
    }

  //find all wind values like 4, 6, 10, 16, 24, 32
  $query2 = "SELECT DISTINCT wspeed from ".$boattype . " where wspeed between " . $minws . " and " . $maxws ;
  //  . " and wspeed/2 = floor(wspeed/2)";
  $result2 = mysql_query($query2) or die("Query [$query2] failed \n");
  $row2 = mysql_fetch_array($result2, MYSQL_NUM);
  //  print_r($result2);

  imagesetthickness ( $im, 2);
  //foreach ($row2 as $wspeed)
  for ($wspeed=$minws; $wspeed<=$maxws; $wspeed+=$pas) {
    $wheadingbefore = 0;
    $boatspeedbefore = 0;
    
    for ( $wheading=0; $wheading<=180; $wheading+=5) {
	$boatspeed = findboatspeed ($wheading, $wspeed, $boattype );

	//exclude points with 0 they confuse the reading
	if ($boatspeedbefore !=0) 
	  $color=windspeedtocolorbeaufort($wspeed , $im);
	  drawlinespeedchart( $boatspeedbefore, $wheadingbefore, $boatspeed, $wheading, $im, $color, $center_x, $center_y);      
	
	//when 135 degrees, draw the windspeed value
	if (($wheadingbefore<135+$wspeed) &&($wheading>=135+$wspeed))
	  {
	    $textposition = speedchartcoordinates($boatspeed , 135, $center_x, $center_y);
	    imagestring ( $im, 1, $textposition[0] +4 , $textposition[1] +4, $wspeed, $colorBlack);
	  }

	$wheadingbefore = $wheading;
	$boatspeedbefore = $boatspeed;
      }

        
  }

imagetruecolortopalette($im, true, 255);
imagepng($im); 
  
}

else
{
  die("must specify a boattype\n");
}

/*from a number, give the next mutliple of shift
ex: if STEP = 3 and value = 17, return 18*/
function nextmultiple($value)
{
  for ($i=ceil($value); $i<=$value+STEP; $i++)
    {
      if( $i%STEP == 0)
	return $i;
    }
}


function drawlinespeedchart($startspeed, $startangle, $stopspeed, $stopangle, $im, $color, $center_x, $center_y)
{

  $start =  speedchartcoordinates($startspeed, $startangle, $center_x, $center_y);
  $stop =  speedchartcoordinates($stopspeed, $stopangle, $center_x, $center_y);

  imageline ( $im, 
	      $start[0],
	      $start[1],
	      $stop[0],
	      $stop[1],
	      $color
	      );

}


/*from an angle and a speed returns a x, y position vector for the speedchart*/
function speedchartcoordinates($speed, $angle, $center_x, $center_y)
{
  $vector =  polar2cartesian(geographic2drawingforspeedchart($angle), ($speed) * STEPSIZE );
  return array(($center_x + $vector[0]), ($center_y + $vector[1]));
}
?>
