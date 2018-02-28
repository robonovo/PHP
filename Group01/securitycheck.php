<?php
/** security checking variables
 *
 * $bypassSecurity
 *   If true then no security checking takes place
 *   Used primarily for logout page, informational page,
 *   any page that needs the code but no security check
 *                   
 * $logCheckOnly
 *   If true then check the security session cookie but do not
 *   redirect if not logged in.  It is used in places where security
 *   is not needed but we need to see if Admin or Super-Admin is logged in
 *
 * $moduleAccess
 *   If set, will contain the 'key' of the module being accessed (the actual
 *   database ID is contained in the arrModuleKeys array in xxxxxx.php)
 *   to determine if the user has authorization to use the module
 *
 * $userLevelAccess
 *   If set, will contain the role level which the user must be at (or above)
 *   in order to access the function
 *
 * $snIsAdmin and $snIsSuper and $snIsEmp
 *   Set to true if Admin or SuperAdmin is logged in
 *   $snIsEmp set to true if user is an employee
 *
 * $adminOnlyPage - if set to true, only Admins are allowed page access
 * $superOnlyPage - if set to true, only SuperAdmins are allowed page access
 * $empOnlyPage   - if set to true, only Employees are allowed page access
 *
 * $isLoggedIn   - set to true if logged in
 * $isAuthorized - set to true if authorized to use the module
 * 
 */

if (!isset($bypassSecurity))   { $bypassSecurity=false; }
if (!isset($logCheckOnly))     { $logCheckOnly=false; }
if (!isset($moduleAccess))     { $moduleAccess=false; }
if (!isset($userLevelAccess))  { $userLevelAccess=false; }
$isLoggedIn=false;  $isAuthorized=false;  $pgRedirect="";

if ($bypassSecurity) {  // no security check  ie. for logout page, etc.
  Tools::clearSecurity();
} else { // perform security check
  if (Tools::getSession()) { $isLoggedIn=true; Tools::getInfoCookie(); }
  if (!$logCheckOnly) {
    $whichDomain=Tools::getDomainName();
    if (isset($isLoginPage) && $isLoginPage) {
      if ($isLoggedIn) {
        if ($whichDomain=="xxxxxx.com") { $pgRedirect="/xxxxx/xxxxxxlist.php"; }
        else { $pgRedirect="/dashboard.php"; }
      }
    } else {  // not on the login page, check security
      if (!$isLoggedIn) {
        if ($whichDomain=="xxxxxx.com") { $pgRedirect="/xxxx/xxx/login.php"; }
        else { $pgRedirect="/xxxx/xxxx/login.php"; }
      }
      if ($adminOnlyPage && $snUserRole<$roleAdmin) { $pgRedirect="/noaccess.php?r=a"; }
      elseif ( $superOnlyPage && !$snIsSuper) { $pgRedirect="/noaccess.php?r=b"; }
      elseif ( $empOnlyPage && !$snIsEmp) { $pgRedirect="/noaccess.php?r=d"; }
      elseif ( $userLevelAccess && $snUserRole<$userLevelAccess) { $pgRedirect="/noaccess.php"; }
    }
    if ($moduleAccess && !$pgRedirect) { // test module access if no login errors
      $moduleKey=$arrModuleKeys[$moduleAccess];
      if (in_array($moduleKey,$authorizedModules)) { $isAuthorized=true; }
      else { $pgRedirect="/noaccess.php?r=c"; }
    }
  }
}
if ($pgRedirect) {
  header( "Location: $pgRedirect" );
  exit;
}
?>