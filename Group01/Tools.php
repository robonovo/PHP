<?php if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * Tools class, Tools.php
  * various function tools used throughout
  *
  * @author Roger Bolser - <roger@eneti.com>
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
      if ($cleanVal) { $val=ereg_replace("[\,\;\'\"\@]+", "", $val); }
      $list[$key] = trim(stripslashes($val));
    }
    return $list;
  } // end function

  /**
   * clear security
   *
   */
  public static function clearSecurity () {
    global $snUserKey,$snUserRole,$snUserEmp,$snUserName,$snUserEmail,$snStartTime;
    global $snIsAdmin,$snIsSuper,$snIsEmp,$roleAdmin,$roleSuper;
    global $ckCoId,$ckLocId,$ckIdentifier,$ckCompany,$ckLocation;
    global $ckAddress1,$ckAddress2,$ckCity,$ckState,$ckPostal;
    global $AuthorizedModules;
    $snIsAdmin=false; $snIsSuper=false; $snIsEmp=false;

    // clear session vars
    $snUserKey=0;
    $snUserRole="";
    $snUserEmp="";
    $snUserName="";
    $snUserEmail="";
    $snStartTime=0;

    // clear info cookie info
    $ckCoId="";
    $ckLocId="";
    $ckIdentifier="";
    $ckCompany="";
    $ckLocation="";
    $ckAddress1="";
    $ckAddress2="";
    $ckCity="";
    $ckState="";
    $ckPostal="";
    $authorizedModules=array();

    return true;
  }

  /**
   * retrieve domain name
   *
   * @return string containing the domain name
   */
  public static function getDomainName () {
    $thisDomain="";
    $URL = strtolower($_SERVER["HTTP_HOST"]);
    $URL = ereg_replace('www\.', '', $URL);
    $URL = parse_url($URL);
    if ($URL["host"]!="") { $thisDomain=$URL['host']; }
    else { $thisDomain = $URL["path"]; }
    return $thisDomain;
  }

  /**
   * retrieve session info
   *
   * @return boolean true/false if session cookie is set
   * @return set 'global' session vars for use in the pages
   */
  public static function getSession () {
    global $snUserKey,$snUserRole,$snUserEmp,$snUserName,$snUserEmail,$snStartTime;
    global $snIsAdmin,$snIsSuper,$snIsEmp,$roleAdmin,$roleSuper;
    $snIsAdmin=false; $snIsSuper=false; $snIsEmp=false;

    $sessionName=_SESSION_KEY_;
    if (isset($_COOKIE[$sessionName])) {
      $vSession=$_COOKIE[$sessionName];
      $wVars=str_replace(" ", "\+", $vSession);

      $snUserKey=$vars['key'];
      $snUserRole=$vars['role'];
      $snUserEmp=$vars['employee'];
      $snUserName=$vars['name_first']." ".$vars['name_last'];
      $snUserEmail=$vars['email'];
      $snStartTime=$vars['time'];

      if ($snUserRole==$roleAdmin) {
        $snIsAdmin=true;
        $adminLog=true;
      } elseif ($snUserRole==$roleSuper) {
        $snIsSuper=true;
        $SuperLog=true;
      }
      if ($snUserEmp=="Y") { $snIsEmp=true; }
      return true;

    } else {
      $snUserKey=0;
      $snUserRole="";
      $snUserEmp="";
      $snUserName="";
      $snUserEmail="";
      $snStartTime=0;
      return false;
    }
  } // end function


  /**
   * retrieve cookie info
   *
   * @return boolean true/false if cookie is set
   * @return set 'global' cookie vars for use in the pages
   */
  public static function getInfoCookie () {
    global $ckCoId,$ckLocId,$ckIdentifier,$ckCompany,$ckLocation;
    global $ckAddress1,$ckAddress2,$ckCity,$ckState,$ckPostal;
    global $authorizedModules;

    $cookieName=_COOKIE_KEY_;
    if (isset($_COOKIE[$cookieName])) {
      $vCookie=$_COOKIE[$cookieName];
      $wVars=str_replace(" ", "\+", $vCookie);

      $ckCoId=$cvars['co_id'];
      $ckLocId=$cvars['loc_id'];
      $ckIdentifier=$cvars['identifier'];
      $ckCompany=$cvars['company'];
      $ckLocation=$cvars['location'];
      $ckAddress1=$cvars['address1'];
      $ckAddress2=$cvars['address2'];
      $ckCity=$cvars['city'];
      $ckState=$cvars['state'];
      $ckPostal=$cvars['postal'];

      if ($cvars['modules']!="") {
	      $authorizedModules=explode("|",$cvars['modules']);
        array_pop ( $authorizedModules );
        array_shift ( $authorizedModules );
      } else { $authorizedModules=array(); }

      return true;
    } else {
      $ckCoId="";
      $ckLocId="";
      $ckIdentifier="";
      $ckCompany="";
      $ckLocation="";
      $ckAddress1="";
      $ckAddress2="";
      $ckCity="";
      $ckState="";
      $ckPostal="";
      $authorizedModules=array();
      return false;
    }
  } // end function

  /**
   * set session (for security)
   *
   * @param array variables for the session cookie
   *
   * @return boolean true=session set
   */
  public static function setSession ($vars) {
    global $snUserKey,$snUserRole,$snUserEmp,$snUserName,$snUserEmail,$snStartTime;
    global $snIsAdmin,$snIsSuper,$snIsEmp,$roleAdmin,$roleSuper;
    $snIsAdmin=false; $snIsSuper=false; $snIsEmp=false;

    $snUserKey=$vars['key'];
    $snUserRole=$vars['role'];
    $snUserEmp=$vars['employee'];
    $snUserName=$vars['name_first']." ".$vars['name_last'];
    $snUserEmail=$vars['email'];
    if ($snUserRole==$roleAdmin) { $snIsAdmin=true; }
    elseif ($snUserRole==$roleSuper) { $snIsSuper=true; }
    if ($snUserEmp=="Y") { $snIsEmp=true; }
    $vars['time']=time();

    $sessName=_SESSION_KEY_;
    setcookie($sessName, $sessParams, time()+86400, "/");

    return true;
  }

  /**
   * set cookie (for information)
   *
   * @param array variables for the cookie
   *
   * @return boolean true=cookie set
   */
  public static function setInfoCookie ($cvars) {
    $cookName=_COOKIE_KEY_;
    setcookie($cookName, $cookParams, time()+86400, "/");

    return true;
  }

  /**
   * delete session cookie
   *
   * @return boolean true = session deleted
   */
  public static function deleteSession () {
    self::clearSecurity();

    $cookieName=_COOKIE_KEY_;
    setcookie ($cookieName, "", time()-3600, "/");

    $sessionName=_SESSION_KEY_;
    setcookie ($sessionName, "", time()-3600, "/");

//    unset($_SESSION[_SESSION_KEY_]);
//    session_destroy();
//    session_write_close();

    return true;
  }

  /**
   * set a random code
   *
   * @param int $min = minumum length for random code
   * @param int $max = maximum length for random code
   *
   * @return string $returnCode = random code
   */
  public static function setRandomCode ($min,$max=0,$type='num') {
    if ($type=="num") { return mt_rand($min,$max); }

    elseif ($type=="all") {
      $src = 'abcdefghjkmnpqrstuvwxyz';  // do not use i, l, o
      $src .= '23456789';                // do not use 1, 0
      $srclen = strlen($src)-1;

      // set the length of the code
      $length = $min;
      $returnCode = '';

      // Fill the string with characters and numbers from $src
      for ($i=0; $i<$length; $i++) { $returnCode .= substr($src, mt_rand(0, $srclen), 1); }
      return $returnCode;

    } else { return false; }

  } // end function

  /**
   * displaySubmit - display the "submit" button on forms, or display an error
   * Used to ensure JavaScript is enabled, when required, in a user's browser
   * @param string $submitOption - 'img'=the button image, 'btn'=html button
   * @param string $submitText - text to use on the button if submitOption='txt'
   * @param bool checkJS - true=check to unsure JS enabled, false=no check
   */  
  public static function displaySubmit ( $submitOption='img', $submitText='Submit the Form', $checkJS=true ) {
  if ($checkJS) {
   echo "<script>\n";
    if ($submitOption=="img") {
     echo "document.write('<input class=\"submit-button\" type=\"image\" src=\""._IMG_DIR_."submit-btn.jpg\" />'); \n";
    } else {
     echo "document.write('<input class=\"submit-button\" type=\"submit\" name=\"submit\" value=\"".$submitText."\" />');\n";
    }
   echo "</script>\n";
   echo "<noscript>\n";
   if ($submitOption=="img") {
    echo "<input class=\"submit-button\" type=\"image\" src=\""._IMG_DIR_."submit-btn.jpg\" disabled=\"disabled\" /><br /><br />\n";
   } else {
    echo "<input class=\"submit-button\" type=\"submit\" name=\"submit\" value=\"".$submitText."\" disabled=\"disabled\" /><br /><br />\n";
   }
   echo "<b>Warning!</b> &nbsp;JavaScript is not enabled in your browser. JavaScript is required for use of this site. The form has been temporarily disabled. &nbsp;Enable JavaScript in your browser then retry your function.<br />\n";
   echo "</noscript>\n";
  } else {
   if ($submitOption=="img") {
    echo "<input class=\"submit-button\" type=\"image\" src=\""._IMG_DIR_."submit-btn.jpg\" /><br />\n";
   } else {
    echo "<input type=\"submit\" name=\"submit\" value=\"".$submitText."\" /><br />\n";
   }
  } // end if/else ($checkJS)
} // end function displaySubmit


  /**
   * displayButtonSubmit
   *  Used as an alternative to the above function displaySubmit
   *  This function will allow you to designate the button image to use
   *  Otherwise, everything is the same
   * Used to ensure JavaScript is enabled, when required, in a user's browser
   * @param string $submitOption - 'img'=the buttom image, 'btn'=html button
   * @param string $submitText - text to use on the button if submitOption='txt'
   * @param bool checkJS - true=check to unsure JS enabled, false=no check
   */  
  public static function displayButtonSubmit ( $submitButton='submit-btn.jpg', $checkJS=true ) {
  if ($checkJS) {
   echo "<script>\n";
    if ($submitButton!="") {
     echo "document.write('<input class=\"submit-button\" type=\"image\" src=\""._IMG_DIR_.$submitButton."\" />'); \n";
    } else {
     echo "document.write('<input class=\"submit-button\" type=\"submit\" name=\"submit\" value=\"".$submitText."\" />');\n";
    }
   echo "</script>\n";
   echo "<noscript>\n";
   if ($submitButton!="") {
    echo "<input class=\"submit-button\" type=\"image\" src=\""._IMG_DIR_.$submitButton."\" disabled=\"disabled\" /><br /><br />\n";
   } else {
    echo "<input class=\"submit-button\" type=\"submit\" name=\"submit\" value=\"".$submitText."\" disabled=\"disabled\" /><br /><br />\n";
   }
   echo "<b>Warning!</b> &nbsp;JavaScript is not enabled in your browser. JavaScript is required for use of this site. The form has been temporarily disabled. &nbsp;Enable JavaScript in your browser then retry your function.<br />\n";
   echo "</noscript>\n";
  } else {
   if ($submitOption=="img") {
    echo "<input class=\"submit-button\" type=\"image\" src=\""._IMG_DIR_."submit-btn.jpg\" /><br />\n";
   } else {
    echo "<input type=\"submit\" name=\"submit\" value=\"".$submitText."\" /><br />\n";
   }
  } // end if/else ($checkJS)
} // end function displaySubmit

  /**
   * displayMessage - common message display to include common formatting
   * @param array $msgTable - array of message(s) to display
   */  
  public function displayMessage ( $msgTable ) {
    if (is_array($msgTable)) {
      // pre-formatting code here
      echo "<div class=\"errorMessage\">\n";

      foreach ($msgTable as $val) { echo $val."<br />"; }

      // post-formatting code here
      echo "</div>\n";
    }    
  }

  /**
   * php validate e-mail routine
   *
   * @param string $email to validate
   * @return boolean true = valid e-mail address
   */
  public static function validateEmail ($email) {
    $reg_exp = "^([_a-z0-9-]+)(\.[_a-z0-9-]+)*@([a-z0-9-]+)(\.[a-z0-9-]+)*(\.[a-z]{2,5})$";

    if (eregi($reg_exp, $email)) { // validate email format
      return true;
      
      /* replace above 'return true' with below code if domain checking needed
      list($username, $domain) = split("@",$email);
      if ($_SERVER["HTTP_HOST"] != "localhost") {
        if (getmxrr($domain,$mxhosts)) { // validate e-mail domain
          // testing code
          // foreach($mxhosts as $mxKey => $mxValue) {
          //   print("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$mxValue<br />");
          // }
          // print("<br />Online host verification Test: PASSED<br /><br />");
          // print("Email Status: VALID<br />");
          return true;
        } else { return false; }
      } else { return true; }
      */

    } else { return false; }
  }

  /**
   *
   * @param integer $thisPage - current page
   * @param integer $totalPages - total number of pages
   * @param string $baseURL - ie. /mydirectory/mypage.php
   * @param array $qryVars  - array of query string variables
   */
  public static function setPrevNext ( $thisPage, $totalPages, $baseURL, $qryVars ) {
    global $openingDiv;

    $thisURL=$baseURL."?";
    if (is_array($qryVars) && count($qryVars)>0) {
      $thisURL.=implode("&", $qryVars)."&";
    }

    if (isset($openingDiv) && $openingDiv!="") { $startingDiv=$openingDiv; }
    else { $startingDiv="<div class=\"pagination-wrapper\">"; }
    if ($totalPages<2) { $onePageCSS=" disabled"; } else { $onePageCSS=""; }

    echo "<!-- previous / next START -->\n";
    echo $startingDiv."\n";
    echo "  <div class=\"pagination-container\">\n";
    if ($thisPage > 1) {
      $prevPage=$thisPage-1;
      $hrefStart="<a href=\"".$thisURL."pg=".$prevPage."\">";
      $hrefEnd="</a>";
      $hrefAlt="Previous Page";
    } else { $hrefStart=""; $hrefEnd=""; $hrefAlt=""; }
    echo "    ".$hrefStart."<div class=\"pagination-cell".$onePageCSS."\"><span class=\"pagination-arrow-left\">&nbsp;</span></div>".$hrefEnd."\n";
    for ($counter=1; $counter<=$totalPages; $counter ++) {
      if ( $counter != $thisPage ) {
        $hrefStart="<a href=\"".$thisURL."pg=".$counter."\">";
        $hrefEnd="</a>";
      } else { $hrefStart=""; $hrefEnd=""; }
      echo "    ".$hrefStart."<div class=\"pagination-cell\">".$counter."</div>".$hrefEnd."\n";
    }
    if ($thisPage != $totalPages) {
      $nextPage = $thisPage+1;
      $hrefStart="<a href=\"".$thisURL."pg=".$nextPage."\">";
      $hrefEnd="</a>";
      $hrefAlt="Next Page";
    } else { $hrefStart=""; $hrefEnd=""; $hrefAlt=""; }
    echo "    ".$hrefStart."<div class=\"pagination-cell".$onePageCSS."\"><span class=\"pagination-arrow-right\">&nbsp;</span></div>".$hrefEnd."\n";
    echo "  </div>\n</div>\n";
    echo "<!-- previous / next END -->\n";
  }

  /**
   *
   * @return array $dateArray - array containing today's mm-dd-yyyy-hh-mm-ampm  
   */
  public static function setTodaysDate () {
    $dateArray['month']=date("m", time());
    $dateArray['day']=date("d", time());
    $dateArray['year']=date("Y", time());
    $dateArray['hour']=date("h", time());
    $dateArray['minute']=date("i", time());
    $dateArray['ampm']=date("A", time());
    return $dateArray;    
  }

  /**
   * retrieve microtime for start/stop times
   *
   * @return integer microtime
   */
  public static function getMicrotime () {
    $thetime = (float) array_sum(explode(' ',microtime()));
    return $thetime;
  } // end function

} // end class
