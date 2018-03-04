<?php
/*  'require' configuration, private, database and other classes and files needed */

$dbi = new database;
$pReturn="";

$getValues=true;
$gVars=getGetVars ();

if ($gVars['v']=="s") {  // get states
  $dbi->sql="SELECT * FROM $dbStates WHERE stCountry='".$gVars['c']."' AND stStatus='A'";
  $vCode="stCode";
  $vName="stName";
} elseif ($gVars['v']=="c") {  // get cities
  $dbi->sql="SELECT * FROM $dbCities WHERE ytCountry='".$gVars['c']."' AND ytState='".$gVars['s']."' AND ytStatus='A' ORDER BY ytName ASC";
  $vCode="ytCode";
  $vName="ytName";
} elseif ($gVars['v']=="r") {  // get regions
  $dbi->sql="SELECT * FROM $dbRegions WHERE rtCountry='".$gVars['c']."' AND rtState='".$gVars['s']."' AND rtStatus='A' ORDER BY rtName ASC";
  $vCode="rtCode";
  $vName="rtName";
} else { $getValues=false; }

if ($getValues) {
  $axTable=$dbi->getAllRecords();
  if ($dbi->numRecords>0) {
    $wkRegions="";
    foreach ($axTable as $key => $val) {
      if ($gVars['v']=="r") { $wkRegions="::".str_replace("::", ", ", $val['rtCityNames']); }
      $axArray[]=$val[$vCode]."::".$val[$vName].$wkRegions;
    }
    if (isset($axArray)) { $pReturn=implode("||", $axArray); }
  }
}

echo $pReturn;
exit;
?>