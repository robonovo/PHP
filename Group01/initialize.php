<?php /* initialization functions */

/** include configuration file */
require(dirname(__FILE__).'/xxxxxx/xxxxxx/xxxxxx.php');

/** make sure 'private' settings file is present and load it */
if (file_exists(_PRIVATE_PATH_.'xxxxxx.xxxxxx.php')) {
  include(_PRIVATE_PATH_.'xxxxxx.xxxxxx.php');
} else { die("Error: 'settings' file is missing"); }

/** Autoload */
function __autoload($className) {
  if (!class_exists($className, false)) { require_once(_OBJECTS_PATH_.$className.'.php'); }
}

/** clean and sanitize data used in SQL statements to prevent SQL injection */
function csSQL ( $string, $htmlOK = true ) {
  if (_MAGIC_QUOTES_GPC_) { $string = stripslashes($string); }
  if (!is_numeric($string)) {
    $string = _MYSQL_REAL_ESCAPE_STRING_ ? mysql_real_escape_string($string) : addslashes($string);
    if (!$htmlOK) { $string = strip_tags(nl2br2($string)); }
  }
  return $string;
}

/** convert \n and \r to <br /> */
function nl2br2 ( $string ) {
  return str_replace(array("\r\n", "\r", "\n"), '<br />', $string);
}

/**  load Database class and create an instance of the database object
     since it is used (almost) everywhere
*/
require_once(_OBJECTS_PATH_.'Database.php');
$dbi=DB::getInstance();  

session_start();
ini_set('session.gc_maxlifetime', '86400');

require(_PRIVATE_PATH_.'securitycheck.php');

?>
