<?php
include_once("vlmc.php");

$coast_xingratio = new doublep();
$coast_xinglat   = new doublep();
$coast_xinglong  = new doublep();

echo "\n\tChecking for coast crossing (vlm-c)\n ";
echo "\tUsing: ".GSHHS_FILENAME."\n ";

$coast_crossing_type = "coast";

$crosses_the_coast = VLM_check_cross_coast($latAvant, $lonAvant, $latApres, $lonApres, 
					   $coast_xinglat, $coast_xinglong, 
					   $coast_xingratio);
					   
// ====================================
// Do they cross an exclusion zone line
// Only check if not coast crossing happened
// ====================================
if (!$crosses_the_coast) 
{
	$coast_crossing_type = "Exclusion Zone";
	include "check_exclusion_zone_crossing.php";
}


if ($crosses_the_coast) 
{
  echo "\t*** YES player " . $fullUsersObj->users->idusers . " CROSSED (vlmc), ";
  echo "\n\t\t".$coast_crossing_type." has been crossed : \n\t\t\t" ; 
  printf ("BOAT : %f,%f <----> %f,%f",
    $latAvant/1000,$lonAvant/1000 , $latApres/1000,$lonApres/1000);
  
  $encounter_lat  = doublep_value($coast_xinglat);
  $encounter_long = doublep_value($coast_xinglong);

  echo "\n\t\t\tEncounterCoordinates " . 
    $encounter_lat/1000 . ", " . $encounter_long/1000 . 
    "\n\nGoogleMap http://maps.google.fr/maps?f=q&hl=fr&geocode=&q=".
    $encounter_lat/1000 . "," . $encounter_long/1000 .
    "&ie=UTF8&spn=0.0191,0.082998&t=p&z=11&iwloc=addr. \n";

	$fullUsersObj->users->logUserEvent($coast_crossing_type." has been crossed at " . $encounter_lat/1000 . ", " . $encounter_long/1000);
  
  /* NOTE the encounter coordinates are the real ones */
  
  echo "\nVLMMAP http://v-l-m.org/mercator.img.php?idraces=" . $fullUsersObj->users->engaged ;
  echo "&lat=" . $latAvant/1000;  
  echo "&long=" .$lonAvant/1000;
  echo "&maparea=18&tracks=on&age=6";
  echo "&list=" . $fullUsersObj->users->idusers ;
  echo "&x=1000&y=600&proj=mercator&text=right"; /* ahem, we have a point instead of the segment now :) */
  echo "&seg1=".$encounter_lat/1000 . "," . $encounter_long/1000 . ":" . $encounter_lat/1000 . "," . $encounter_long/1000;
  echo "&seg2=".$latAvant/1000 . "," . $lonAvant/1000 . ":" . $latApres/1000 . "," . $lonApres/1000;
  echo "\n\n";
  
  /*
    echo "\n\t ==> Position Avant " . 
    $latAvant/1000 . ", " . $lonAvant/1000 . 
    "\n http://maps.google.fr/maps?f=q&hl=fr&geocode=&q=".
    $latAvant/1000 . "," . $lonAvant/1000 .
    "&ie=UTF8&spn=0.0191,0.082998&t=p&z=11&iwloc=addr. \n";
    
    echo "\n\t ==> Position Apres " . 
    $latApres/1000 . ", " . $lonApres/1000 . 
    "\n http://maps.google.fr/maps?f=q&hl=fr&geocode=&q=".
    $latApres/1000 . "," . $lonApres/1000 .
    "&ie=UTF8&spn=0.0191,0.082998&t=p&z=11&iwloc=addr. \n";
  */

  // We will stop the player after checking the waypoint, but first we need to 
  // compute the real endpoint (using linear interpolation)
  // We can tune the 0.9 to whatever we want, to avoid putting the boat on the line
  // and have rounding errors having fun with us.
  
	if ($coast_crossing_type == "coast")
	{
		  $latApres = $latAvant + ($encounter_lat - $latAvant) * 0.9;
		  $lonApres = $lonAvant + ($encounter_long - $lonAvant) * 0.9;

		  for ( $coast_ratio = 8; $coast_ratio >= 0; $coast_ratio--) 
			{
		  
				echo "ici".$coast_ratio."\n";
				$npcc = VLM_check_cross_coast($latAvant, $lonAvant, $latApres, $lonApres, 
							  $coast_xinglat, $coast_xinglong, 
							  $coast_xingratio);
				if ($npcc) 
				{
				  echo "*** Safety engaged for new point, moving to ".$coast_ratio."0%\n";
				  $latApres = $latAvant + ($encounter_lat - $latAvant) * ($coast_ratio / 10.);
				  $lonApres = $lonAvant + ($encounter_long - $lonAvant) * ($coast_ratio / 10.);
				} 
				else 
				{
				  break;
				}
			}
	}
	else
	{
		// For exclusion zone boat gets stuck at original point
		$latApres = $latAvant;
		$lonApres = $lonAvant;
	}
  }

  echo "\nVLMMAP corrected position http://v-l-m.org/mercator.img.php?idraces=" . $fullUsersObj->users->engaged ;
  echo "&lat=" . $latAvant/1000;  
  echo "&long=" .$lonAvant/1000;
  echo "&maparea=18&tracks=on&age=6";
  echo "&list=" . $fullUsersObj->users->idusers ;
  echo "&x=1000&y=600&proj=mercator&text=right"; /* ahem, we have a point instead of the segment now :) */
  echo "&seg2=".$latAvant/1000 . "," . $lonAvant/1000 . ":" . $latApres/1000 . "," . $lonApres/1000;
  echo "\n\n";

?>
