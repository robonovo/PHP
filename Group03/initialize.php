<?php
/**
 Initialization routine
 Called from every page/script in this application as the first action.
 Sets up various variables and checks if the user is logged in,
 Sets report variables and other miscellaneous start-up activities
 */
if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/** include configuration file */
require(dirname(__FILE__).'/xxxxx/xxxxx/xxxxx/configuration.php');

/** Autoload */
function __autoload($className) {
  if (!class_exists($className, false)) { require_once(_CLASS_PATH_.$className.'.php'); }
}

if (_TRACK_TIME_) { $startTime=Tools::getMicrotime(); }

/** make sure 'private' settings file is present and load it */
if (file_exists(_PRIVATE_PATH_.'xxxxx/xxxxx/private.xxxxx.php')) {
  include(_PRIVATE_PATH_.'xxxxx/xxxxx/private.xxxxx.php');
} else { die("Error: 'settings' file is missing"); }

/** clean and sanitize data to prevent SQL injection */
function csSQL ( $string, $htmlOK = true ) {
  if (_MAGIC_QUOTES_GPC_) { $string = stripslashes($string); }
  if (!is_numeric($string)) {
    $string = _MYSQL_REAL_ESCAPE_STRING_ ? mysql_real_escape_string($string) : addslashes($string);
    if (!$htmlOK) { $string = strip_tags(nl2br2($string)); }
  }
  return $string;
}

/** convert \n to <br /> */
function nl2br2 ( $string ) {
  return str_replace(array("\r\n", "\r", "\n"), '<br />', $string);
}

/** redirect to message page on error */
function errorRedirect ($msgVar) {
  $msgLocation="message.php?mc=".$msgVar;
  header( "Location: $msgLocation" );
  exit;
}

session_start();

// create an instance of the DB object - it is used almost everywhere
$dbi=DB::getInstance(); 

if (!isset($bypassSecurity)) { $bypassSecurity=false; }
if (!isset($employeeOnly))   { $employeeOnly=true; }
if (!isset($adminOnly))      { $adminOnly=false; }
$loggedIn=false;  $redirect="";

/** security checking */
if (!$bypassSecurity) {
  $loggedIn=Tools::getSession();
  if (isset($isLogin)) { // redirect if already logged when trying to log in
    if ($loggedIn) {
      if ($snAdministrator) { $redirect="xxxxx.php"; }
      elseif ($snClient)    { $redirect="xxxxx.php"; }
      else { $redirect="xxxxx.php"; }
      header( "Location: $redirect" );
      exit;
    }
  } else {  // not login page, check security
    if (!$loggedIn) { errorRedirect ('s05nli'); }
    if ($adminOnly && !$snAdministrator) { errorRedirect ('s08aoa'); }
    if ($employeeOnly && !$snEmployee)   { errorRedirect ('s10eao'); }
  }
}

/** retrieve parameters for reports if this is a report function */
if (isset($isReport) && $isReport) { require_once(_INCLUDES_PATH_.'xxxxx/xxxxx/Params.php'); }

/** retrieve parameters for logs if this is a log report function */
if (isset($isLog) && $isLog) { require_once(_INCLUDES_PATH_.'xxxxx/xxxxx/Params.php'); }

?>