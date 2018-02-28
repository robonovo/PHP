<?php if (!defined('validEntry')) define('validEntry', 'ok'); // make sure 'includes' can be accessed

/**
 * @title      login.php - log in to the system
 * @author     Roger Bolser - eNET Innovations, Inc.
 * @copyright  eNET Innovations, Inc. (Roger Bolser) : All Rights Reserved
 *
 */

/** initialization */
$bypassSecurity=false;        // security check, but will redirect if logged in
$isLogin=true;                // no security check but redirect to menu if already logged in
require('initialize.php');    // initialization and security

/** set page variables */
$vSubmit='Log In';
$vThisPage='Log In';
$vErrorTimes=false;
$vShowForm=true;

/** see if 'admin' set */
$gVars=Tools::getGetVars();
if (isset($gVars['admin'])) { $adminLog="Y"; }
else { $adminLog="N"; }

$pVars=Tools::getPostVars();
if (!isset($pVars['numTimes'])) { $numTimes=1; } else { $numTimes=$pVars['numTimes']; }

if (isset($pVars['submitted']))  { // form has been submitted

  /**
    validate user id and password
    (already checked via ajax in the login validation but re-validate
     in case user has JS turned off)
   */
//  $dbi=DB::getInstance();  // instantiated in initialize.php
  $sql="SELECT * FROM "._TBL_USERS_." WHERE utUserId='".csSQL($pVars['utUserId'])."'";
  $userTable=$dbi->getOneRow($sql);
  if ($dbi->_numRows==0) {
    $errTable[]="Invalid User ID";

  } else {  // valid user id - now validate password

//    $inputPassword=Encryption::decrypt($pVars['utPassword']);
    $inputPassword=$pVars['utPassword'];
    if ($userTable['utPassword']!=$inputPassword) {
      $errTable[]="Invalid Password";

    } else { /** valid password - continue processing */
      if ($userTable['utStatus']!="A") {  // user is suspended
        $msgVar="l04uis";
        $msgLocation="message.php?mc=".$msgVar;
        header( "Location: $msgLocation" );
        exit;
      }

      /** update userTable - increment login count */
      $upTable['utKey']=$userTable['utKey'];
      $upTable['utLoginCount']="utLoginCount+1";
      $upKeys=array('utKey');
      $dbi->updateRecordNative ( _TBL_USERS_, $upTable, $upKeys );

      $wRedirect=$cfaUserType[$userTable['utType']][2];

      // set user session vars
      $vars['key']=$userTable['utKey'];
      $vars['type']=$userTable['utType'];
      $vars['repcode']=$userTable['utRepcode'];
      $vars['develop']=$userTable['utDeveloper'];
      $vars['name']=$userTable['utName'];
      $vars['userid']=$userTable['utUserId'];
      if (isset($pVars['utFormattedDate'])) { $vars['filedate']=$pVars['utFormattedDate']; }
      else { $vars['filedate']=""; }
      Tools::setSession ($vars);

      // log the function
      if (_TRACK_ACTIVITY_ && $snEmployee) {
        $snUserKey=$userTable['utKey'];
        Tools::logActivity ( 'Log In', $userTable['utDeveloper'] );
      } elseif (_TRACK_CLIENTS_ && $snClient) {
        Tools::logClients ( 'Log In' );
      }

      // redirect to the appropriate menu
      session_write_close();
      header( "Location: $wRedirect" );
      exit;
    } // end if valid password

  } // end if/else valid user id

  $numTimes++;
  if ($numTimes > 3) {  // only allow 3 tries
    $numTimes=3;        // reset to 3 for display in the headings
    $vErrorTimes=true;
    $vShowForm=false;
  } else { $dtTable=$pVars; }

}  // endif form submitted
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo _APP_TITLE_; ?> :: Log In</title>
<meta name="resource-type" content="document" />
<meta http-equiv="robot" content="noindex, nofollow" />
<meta http-equiv="pragma" content="no-cache" />
<link rel="stylesheet" href="elements/css/global.css" type="text/css" />
<link rel="stylesheet" href="elements/css/logger.css" type="text/css" />
<link rel="stylesheet" href="elements/css/forms.css" type="text/css" />

<script type="text/javascript" src="elements/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="elements/js/validateCommon.js"></script>
<script type="text/javascript" src="elements/js/validateLogin.js"></script>

<?php if ($adminLog="Y") { // load jquery datepicker files ?>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script type="text/javascript" src="elements/js/jquery-1.10.2.ui.js"></script>
<script type="text/javascript" src="elements/js/loginDatepicker.js"></script>

<style type="text/css">
  .ui-datepicker-current { display: none; }
</style>
<?php } // end load of jquery datepicker files ?>

</head>
<body>

<?php
$loggerNav="Login Attempt &nbsp;&raquo; &nbsp;".$numTimes." of 3";
include('loggerStart.php');

if ((isset($errTable)) && (!$vErrorTimes)) { Display::message ( '1', $errTable ); }

if ($vErrorTimes) { // more than 3 unsuccessful attempts to log in
  $sTitle="User Login Terminated";
  $sData="You have tried three times to log in unsuccessfully. &nbsp;Either:<br />\n";
  $sData.="<ul class=\"messagebullet\">\n";
  $sData.="<li>You are entering an incorrect User ID / Password, &nbsp;<b>or</b></li>\n";
  $sData.="<li>There is a problem with the database, &nbsp;<b>or</b></li>\n";
  $sData.="<li>You are not authorized to access this system</li>\n";
  $sData.="</ul>\n";
  $sData.="If you are an authorized user, please contact the ";
  $sData.="<a href=\"mailto:"._ADMIN_EMAIL_."\">Administrator</a> for assistance.\n";
  Display::message ( '3', $sData, $sTitle, '', 'err' );
  echo "<div style=\"clear:both;\"><br /><br /></div>\n";
} // end if ($vErrorTimes)

if ($vShowForm) { require(_FORMS_PATH_.'xxxxx/xxxxx/form.php'); }

include('loggerEnd.php');
?>

</body>
</html>
