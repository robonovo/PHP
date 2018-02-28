<?php
/**
 Ajax Check Login
 Will check if the User ID and Password are valid when logging in
 Called from /js/validateLogin.js
 */
if (!defined('EntryAllowed')) define('EntryAllowed', 'ok'); // make sure 'includes' can be accessed
$bypassSecurity=true;        // no security check
require('initialize.php');   // initialization and security

$gVars=Tools::getGetVars();
$returnMsg="ok";

$sql="SELECT * FROM "._TBL_USERS_." WHERE utUserId='".csSQL($gVars['theUID'])."'";
$userTable=$dbi->getOneRow($sql);
if ($dbi->_numRows==0) {
  $returnMsg="uiderror";
} else {  // valid user id - now validate password
  $inputPassword=$gVars['thePW'];
  if ($userTable['utPassword']!=$inputPassword) {
    $returnMsg="pwerror";
  }
}
echo $returnMsg;
exit;

?>
