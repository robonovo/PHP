<?php if (!defined('validEntry')) define('validEntry', 'ok'); // make sure 'includes' can be accessed

/**
 * @title      menu.php - reports menu
 * @author     Roger Bolser - eNET Innovations, Inc.
 * @copyright  eNET Innovations, Inc. (Roger Bolser) : All Rights Reserved
 *
 */

/** initialization */
$bypassSecurity=false;        // must pass security
$adminOnly=false;             // page open to all
$employeeOnly=true;           // only employees (no clients) allowed
require('initialize.php');    // initialization and security
require(_DATES_HOLIDAYS_);    // date/holiday arrays ... needed for date calcs

/** set page variables */
$vThisPage='Reports Menu';

/** set header navigation */
if ($snAdministrator) { $navArray[]="admin"; }
$navArray[]="logout";
$vNavItems=Tools::setNavigation( $navArray, &$cfaNavTable, 'Functions');

$pVars=Tools::getPostVars();
if (isset($pVars['submitted'])) { // form has been submitted

  // set some initial vars
  $dateChars=array('-','.');    // used for converting mm/dd/yyyy to yyyy-mm-dd
  $haveSelected=false;          // have selected codes (repcodes / rep names / accounts)
  $haveAdditional=false;        // have additional params	
  $ssSelectedCodes=array();     // array of selected repcodes
  $ssSelectedNames=array();     // array of selected rep names (for screen display in reports)
  $ssNameCodes=array();         // array of repcodes associated with the names
  $ssSelectedAccounts="";       // pseudo-array of selected account numbers

  // start to build cookie and other vars
  $ssUserId=$snUserKey;
  $ssReport=$pVars['ffReport'];

  $reportVars[0]=$ssUserId;
  $reportVars[1]=$ssReport;
  $redirect=$cfaReports[$ssReport][1];
  if (isset($pVars['ffDateRange'])) { $reportVars[2]=$pVars['ffDateRange']; }
  else { $reportVars[2]="0"; }

  $selectVars[0]=$ssUserId;
  $selectVars[1]=$ssReport;

  // set repcode selection if any
  if ($pVars['ffAllCodes']=="A") {
    $reportVars[3]="A";
    $selectVars[2]="**all**";
  } elseif ($pVars['ffAllCodes']=="N") {
    $reportVars[3]="N";
    $selectVars[2]="**none**";
  } else {  // we have selected codes
    $reportVars[3]="S";
    $haveSelected=true;
    foreach ($pVars['ffRepcode'] as $key => $val) { $ssSelectedCodes[]=$val; }
    sort($ssSelectedCodes);
    $selectVars[2]=implode("::",$ssSelectedCodes);
  }

  // set repname selection if any
  if ($pVars['ffAllNames']=="A") {
    $reportVars[4]="A";
    $selectVars[3]="**all**";
    $selectVars[4]="**all**";
  } elseif ($pVars['ffAllNames']=="N") {
    $reportVars[4]="N";
    $selectVars[3]="**none**";
    $selectVars[4]="**none**";
  } else {  // we have selected names
    $reportVars[4]="S";
    $haveSelected=true;
    $keysWork=array();
    foreach ($pVars['ffRepname'] as $key => $val) { $keysWork[]="'".trim($val)."'"; }
    $keysIn=implode(",",$keysWork);
    $sql="SELECT * FROM "._TBL_REPCODES_." WHERE rtKey IN (".$keysIn.") ORDER BY rtName ASC";
    $rtTable=$dbi->getAllRows($sql);
    foreach ($rtTable as $key => $val) {
      $ssSelectedNames[]=$val['rtName'];
      $work=explode('|', $val['rtRepcodes']);

      // pop off first and last entries if blank (which they most likely are) /
      if ($work[0]=="") { array_shift($work); }
      if (end($work)=="") { array_pop($work); }

      $ssNameCodes=array_merge($ssNameCodes, $work);
    }

    sort($ssSelectedNames);
    sort($ssNameCodes);

    $selectVars[3]=implode("::",$ssSelectedNames);
    $selectVars[4]=implode("::",$ssNameCodes);
  }

  // set account number selection if any
  if (isset($pVars['ffAcctNumbers']) && $pVars['ffAcctNumbers']!="") {
    $reportVars[5]="S";
    $haveSelected=true;
    $workArray=explode(",",$pVars['ffAcctNumbers']);
    foreach ($workArray as $val) {
      $wAcct=trim($val);
      if ($wAcct!="") { $ssAccounts[]=$wAcct; }
    }
    sort($ssAccounts);
    $selectVars[5]=implode("::",$ssAccounts);
  } else {
    $reportVars[5]="A";
    $selectVars[5]="**all**";
  }

  // set additional params, if any
  $reportVars[6]="N";
  $selectVars[6]="**none**";
  $addlCheck="ffAdditional".$ssReport;
  if (isset($pVars[$addlCheck])) {
    $addlParams="";
    $workArray=explode(",",$pVars[$addlCheck]);
    foreach ($workArray as $val) {
      $wAdditional=trim($val);
      if ($wAdditional!="") { $ssAdditional[]=$wAdditional; }
    }
    if (isset($ssAdditional)) {
      $haveSelected=true;
      $reportVars[6]="Y";
      $selectVars[6]=implode("::",$ssAdditional);
    }
  } // end if (isset($pVars[$addlCheck]))

  // set 'from' date to yyyy-mm-dd format
  $workDateF=str_replace($dateChars,'/',$pVars['ffDateFrom']);
  $workDateT=str_replace($dateChars,'/',$pVars['ffDateTo']);

  $reportVars[7]=date("Y-m-d",strtotime($workDateF));
  $reportVars[8]=strtotime($workDateF);
  $reportVars[9]=date("Y-m-d",strtotime($workDateT));
  $reportVars[10]=strtotime($workDateT);
  if ($haveSelected) { $reportVars[11]="Y"; }
  else { $reportVars[11]="N"; }

  // set session cookies and redirect to report
  Tools::genericSetCookie (_PARAMS_REPORT_, $reportVars);
  if ($haveSelected) { Tools::genericSetCookie (_PARAMS_SELECTED_, $selectVars); }

  session_write_close();
  header( "Location: $redirect" );
  exit;

} else {

  // delete any report cookies set
  Tools::deleteReportCookies();

} // end if/else (isset($pVars['submitted']))

/**
 If we are here it is the first time through
 Set the dates for the JavaScript "Select Date Range" buttons
 */
$nowTime=time();  // set here for consistency in the script

/** set 'latest trading day' */
$tradingDay=$nowTime;
$whileLoop=true;

while ($whileLoop):
  $tradingDay-=86400; // subtract 24 hours to get previous day
  $dayNumber=date('w',$tradingDay);
  if ($dayNumber==0 || $dayNumber==6) { continue; }  // Sunday or Saturday ... ignore
  $checkDate=date("Ymd", $tradingDay);
  if (in_array("$checkDate", $tradingHolidays)) { continue; }  // day was a trading holiday ... ignore
  $whileLoop=false;
endwhile;

$latestTradingDate=date("m/d/Y", $tradingDay);
$ffTable['ffDateFrom']=$latestTradingDate;
$ffTable['ffDateTo']=$latestTradingDate;

/** set current calendar month dates */
$currCalendarFrom=date("m/01/Y",$nowTime);
$currCalendarTo=date("m/t/Y",$nowTime);

/** set previous calendar month dates */
$prevCalendarFrom=date("m/01/Y", strtotime("-1 month")) ;
$prevCalendarTo=date("m/t/Y", strtotime("-1 month")) ;

// retrieve rep names and repcodes for the form
$sql="SELECT * FROM "._TBL_REPCODES_." ORDER BY rtName ASC";
$rtTable=$dbi->getAllRows($sql);
$repcodeArray=array();
$numRepcodes=0;  // not currently used ... left in for testing if needed

// create repcode array for form display and selection
foreach ($rtTable as $key => $val) {
  $work=explode('|', $val['rtRepcodes']);

  // pop off first and last entries if blank (which they most likely are)
  if ($work[0]=="") { array_shift($work); }
  if (end($work)=="") { array_pop($work); }

  $repcodeArray=array_merge($repcodeArray, $work);
  $numRepcodes+=count($work);
}
sort($repcodeArray);  // sort repcode array by repcode in ascending order

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
 "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title><?php echo _APP_TITLE_; ?> :: Reports Menu</title>
<meta name="resource-type" content="document" />
<meta http-equiv="robot" content="noindex, nofollow" />
<meta http-equiv="pragma" content="no-cache" />
<link rel="stylesheet" href="elements/css/global.css" type="text/css" />
<link rel="stylesheet" href="elements/css/inner.css" type="text/css" />
<script type="text/javascript" src="elements/js/jquery-1.10.2.min.js"></script>
<script type="text/javascript" src="elements/js/validateCommon.js"></script>
<script type="text/javascript" src="elements/js/validateMenu.js"></script>

<!-- start calendar css and js  - DO NOT REMOVE -->
<link rel="stylesheet" href="http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css" />
<script type="text/javascript" src="elements/js/jquery-1.10.2.ui.js"></script>
<!--<script src="http://code.jquery.com/ui/1.10.3/jquery-ui.js"></script>-->
<script type="text/javascript" src="elements/js/menuDatepicker.js"></script>
<style type="text/css">
  .ui-datepicker-current { display: none; }
</style>
<!-- end calendar css and js  - DO NOT REMOVE -->

<script type="text/javascript">
$(document).ready(function($){

  $('.cDateRange').click(function() {
	
    var whichDate = $(this).val();
    if (whichDate=="1") {
      $('#idDateFrom').val('<?php echo $currCalendarFrom; ?>');
      $('#idDateTo').val('<?php echo $currCalendarTo; ?>');
    } else if (whichDate=="2") {
      $('#idDateFrom').val('<?php echo $prevCalendarFrom; ?>');
      $('#idDateTo').val('<?php echo $prevCalendarTo; ?>');
    } else if (whichDate=="3") {
      $('#idDateFrom').val('<?php echo $latestTradingDate; ?>');
      $('#idDateTo').val('<?php echo $latestTradingDate; ?>');
    }

  });

  $('.ffDateRange').change(function() {
    $('#idDateRange1').prop('checked', false);
    $('#idDateRange2').prop('checked', false);
    $('#idDateRange3').prop('checked', false);
  });

  $(function () {
    $("#fidAllCheckboxCodes").click(function () {
//      if ($("#fidAllCheckboxCodes").is(':not(:checked)')) {
      if ($("#fidAllCheckboxCodes").is(':checked')) {
        $(".repcode").prop("checked", true);
        $(".repcode").prop("disabled", true);
      } else {
        $(".repcode").prop("checked", false);
        $(".repcode").prop("disabled", false);
      }

    });

  });

  $('#idShowAllCodes').click(function() { $('#fidRepcodes').show(); });
  $('#idHideAllCodes').click(function() { $('#fidRepcodes').hide(); });

  $('#idSelectAllCodes').click(function() {
    $(".repcode").prop("checked", true);
    $(".repcode").prop("disabled", false);
  });

  $('#idUnselectAllCodes').click(function() {
    $(".repcode").prop("checked", false);
    $(".repcode").prop("disabled", false);
  });

  $('#idShowAllNames').click(function() { $('#fidRepnames').show(); });
  $('#idHideAllNames').click(function() { $('#fidRepnames').hide(); });

  $('#idSelectAllNames').click(function() {
    $(".repname").prop("checked", true);
//    $(".repname").prop("disabled", false);
  });

  $('#idUnselectAllNames').click(function() {
    $(".repname").prop("checked", false);
//    $(".repname").prop("disabled", false);
  });

});

</script>
</head>
<body>

<?php require('pageStart.php'); ?>

<div align="center">
 <div class="menucontainer">

<?php require(_FORMS_PATH_.'form.menu.php'); ?>

 </div> <!-- menucontainer -->
</div> <!-- align="center" -->

<?php require('pageEnd.php'); ?>

</body>
</html>
