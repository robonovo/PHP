<?php if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * @ttle:   Tools class, Tools.php - various function tools used throughout
  * @author  Roger Bolser
  * @version 1.0.0.0
  *
  */

class Tools {

  /**
   * retrieve $_POST variables
   *
   * @param boolean $stripHtml true = PHP strip_tags on the input
   * @param boolean $skipSubmit true = do not include 'submit' or 'submitted' key/val in return
   
   * @return array $_POST vars in key/val array
   */
  public static function getPostVars ( $stripHtml=true, $skipSubmit=false ) {
    $list = array();
    foreach($_POST as $key => $val) {
      if (($skipSubmit) && ($key == "submit" || $key == "submitted")) { continue; }
      if (is_array($val)) {
        foreach ($val as $nkey => $nval) {
          if ($stripHtml) { $list[$key][$nkey] = trim(strip_tags(stripslashes($nval))); }
          else { $list[$key][$nkey] = trim(stripslashes($nval)); }
        }
      } elseif ($stripHtml) {
        $list[$key] = trim(strip_tags(stripslashes($val)));
      } else { $list[$key] = trim(stripslashes($val)); }
    }
    return $list;
  } // end function


  /**
   * retrieve $_GET variables
   *
   * @param boolean $cleanVal true = strip potentially harmful characters from input
   *
   * @return array $_GET vars in key/val array
   */
  public static function getGetVars ( $cleanVal=true ) {
    $list = array();
    foreach($_GET as $key => $val) {
      if ($cleanVal) { $val=ereg_replace("[\,\;\'\"]+", "", $val); }
      $list[$key] = trim(stripslashes($val));
    }
    return $list;
  } // end function


  /**
   * retrieve session info
   *
   * @return boolean true/false if session cookie is set
   * @return set 'global' session vars for use in the pages
   */
  public static function getSession ( $which='back' ) {
    global $snUserKey,$snUserType,$snNameFirst,$snNameLast,$snStartTime;
    global $snSuperAdmin,$snAdministrator;
    $snAdministrator=false;
    $snSuperAdmin=false;
    $logError=true;

    if (isset($_SESSION[_SESSION_KEY_])) {
      list($snUserKey,$snUserType,$snNameFirst,$snNameLast,$snStartTime) = explode("|",$_SESSION[_SESSION_KEY_]);
      if ($which=="back" && $snUserType!="W") { // front and back-end user
        if ($snUserType=="A") { $snAdministrator=true; }
        if ($snUserType=="S") { $snSuperAdmin=true; $snAdministrator=true; }
        $logError=false;
      } elseif ($which=="front") { $logError=false; }
    }

    if ($logError) {  // not a valid login
      $snUserKey=0;
      $snUserType="";
      $snNameFirst="";
      $snNameLast="";
      $snStartTime=0;
      return false;
    } else { return true; }
  } // end function

  /**
   * set project cookie info
   *
   * @param string $projVars - array containing cookie variables:
   *   $projVars[0] - database 'key' from project record
   *   $projVars[1] - database 'unique value' from project record
   *   $projVars[2] - number of alternatives from project record
   *   $projVars[3] - project title from project record
   */
  public static function setProjectCookie ( $projVars ) {
    global $pckExists,$pckKey,$pckUnique,$pckAlternatives,$pckTitle;
    global $pckExistingYear,$pckFutureYear;
    global $pckNonStandard,$pckExistingIntersects,$pckAlternateIntersects;

    $pckKey=$projVars[0];
    $pckUnique=$projVars[1];
    $pckAlternatives=$projVars[2];
    $pckTitle=$projVars[3];
    $pckExistingYear=$projVars[4];
    $pckFutureYear=$projVars[5];
    $pckNonStandard=$projVars[6];
    $pckExistingIntersects=$projVars[7];
    $pckAlternateIntersects=$projVars[8];

    $cookieValue=$pckKey."|||".$pckUnique."|||".$pckAlternatives."|||".$pckTitle."|||";
    $cookieValue.=$pckExistingYear."|||".$pckFutureYear."|||";
    $cookieValue.=$pckNonStandard."|||".$pckExistingIntersects."|||";
    $cookieValue.=$pckAlternateIntersects;
    setcookie(_COOKIE_KEY_, Encryption::encrypt($cookieValue));

    $pckExists=true;
    return true;
  } // end function


  /**
   * retrieve project cookie info
   *
   * @return boolean true/false if project cookie is set
   * @return set 'global' cookie vars for use in the pages
   */
  public static function getProjectCookie () {
    global $pckExists,$pckKey,$pckUnique,$pckAlternatives,$pckTitle;
    global $pckExistingYear, $pckFutureYear;
    global $pckNonStandard,$pckExistingIntersects,$pckAlternateIntersects;

    if(isset($_COOKIE[_COOKIE_KEY_])) {
      $cookieValue = Encryption::decrypt($_COOKIE[_COOKIE_KEY_]);
      $cookieArray=explode("|||",$cookieValue);
      $pckKey=$cookieArray[0];
      $pckUnique=$cookieArray[1];
      $pckAlternatives=$cookieArray[2];
      $pckTitle=$cookieArray[3];
      $pckExistingYear=$cookieArray[4];
      $pckFutureYear=$cookieArray[5];
      $pckNonStandard=$cookieArray[6];
      $pckExistingIntersects=$cookieArray[7];
      $pckAlternateIntersects=$cookieArray[8];
      $pckExists=true;
      return true;
    }
    $pckExists=false;
    return false;  // cookie not set
  } // end function


  /**
   * delete project cookie
   *
   */
  public static function deleteProjectCookie () {
    global $pckExists,$pckKey,$pckUnique,$pckAlternatives,$pckTitle;
    global $pckExistingYear, $pckFutureYear;
    global $pckNonStandard,$pckExistingIntersects,$pckAlternateIntersects;

    $pckKey="";
    $pckUnique="";
    $pckAlternatives="";
    $pckTitle="";
    $pckExistingYear="";
    $pckFutureYear="";
    $pckNonStandard="";
    $pckExistingIntersects="";
    $pckAlternateIntersects="";
    $pckExists=false;
    setcookie(_COOKIE_KEY_, '', -3600);
    return true;

  } // end function



  /**
   * retrieve microtime for start/stop times
   *
   * @return integer microtime
   */
  public static function getMicrotime () {
    $thetime = (float) array_sum(explode(' ',microtime()));
    return $thetime;
  } // end function


  /**
   * set photo resize information
   *
   * @param array $spPhoto  array of un-resized images
   * @param var  name of the array element to retrieve resize parameters
   *
   * @return boolean true/false if resizing was successful
   * NOTE:  the resize params are contained in 'global' $pArray
   */
  public static function setPhoto ($spPhoto,$spCheck) {
    global $pArray,$pIdx,$_PhotoParams;

    if ($spPhoto!="") {
      $wArray=explode("::", $spPhoto);
      $pArray[$pIdx][0]=$wArray[1];
      if ($wArray[2]>$_PhotoParams[$spCheck][0] || $wArray[3]>$_PhotoParams[$spCheck][1]) {
        $wWscale = $wArray[2] / $_PhotoParams[$spCheck][0];
        $wHscale = $wArray[3] / $_PhotoParams[$spCheck][1];
        if (($wHscale > 1) || ($wWscale > 1)) {
          $newScale = ($wHscale > $wWscale)?$wHscale:$wWscale;
        } else { $newScale = 1; }
        $pArray[$pIdx][1]=floor($wArray[2] / $newScale);
        $pArray[$pIdx][2]=floor($wArray[3] / $newScale);
      } else {
        $pArray[$pIdx][1]=$wArray[2];
        $pArray[$pIdx][2]=$wArray[3];
      }
      $pIdx++;
      return true;
    } else { // no uploaded image - use default
      $pArray[$pIdx][0]=$_PhotoParams[$spCheck][2];
      $pArray[$pIdx][1]=$_PhotoParams[$spCheck][3];
      $pArray[$pIdx][2]=$_PhotoParams[$spCheck][4];
      $pIdx++;
      return false;
    }
  } // end function

 /**
   * set navigation items
   *
   * @param array $navItems  array of navigational items for the page
   * @param array $navTable  array containing navigation text and links
   * @param string $navFunctons  test to display to the left of the nav items
   *
   * @return string $links  the navigation area
   */
  public static function setNavigation ( $navItems, $navTable, $navFunction='Functions' ) {
    $links="";

    foreach ($navItems as $val) {
      $navArray[]="<a class=\"navlink\" href=\"".$navTable[$val][2]."\">".$navTable[$val][1]."</a>";
    }
    if (isset($navArray)) {
      $links=implode( " ;&bull; ", $navArray );
      if ($navFunction!="") { $links=$navFunction." &raquo; ".$links; }
    }
    return $links;
  } // end function


} // end class
