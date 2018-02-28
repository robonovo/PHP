<?php
require($_SERVER['DOCUMENT_ROOT'].'/initialize.php');  // initialization

if (isset($authorizedModules) && count($authorizedModules)>0) { $doIcons=true; }
else { $doIcons=false; }

$subnavArray=array (
  '1'=>array ('Dashboard',''),
);
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Dashboard</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">

<?php include (_INCLUDES_PATH_.'externals.php'); ?>

</head>
<body>

<div id="body-container">

<?php include (_INCLUDES_PATH_.'header.php'); ?>

 <!-- content container :: start -->
 <div id="content-container">

<?php include (_INCLUDES_PATH_.'navigation.php'); ?>

   <!-- main content area :: start -->
   <div id="content-right">

   <div class="content-text">	 
	 <a href="*" class="contentlink" style="font-size:15px;">0 New Messages</a><br />
   </div>

<?php
if ($doIcons) {
  foreach ($authorizedModules as $val) {
    $sql="SELECT id, module_name, server_directory, icon, icon_wh FROM `modules` WHERE id='".$val."' AND status='A'";
    $modRow=$dbi->getOneRow ($sql);
    echo "   <div id=\"menu-icons\">\n";
    if ($modRow['icon']!="") {
      list($iconw, $iconh)=explode("::",$modRow['icon_wh']);
      if ($iconw>60) { $iconw=60; }
      echo "    <a href=\"".$modRow['server_directory']."/\"><img src=\""._IMG_DIR_.$modRow['icon']."\" width=\"".$iconw."\" border=\"0\" alt=\"".$modRow['module_name']."\" title=\"".$modRow['module_name']."\" /></a><br />\n";
    }
    echo "    <a class=\"icon-link\" href=\"".$modRow['server_directory']."/\">".$modRow['module_name']."</a>\n";
    echo "   </div> <!-- menu-icons -->\n";
  } // end foreach
} // end if ($doIcons)
?>
   </div> <!-- content-right -->
   <!-- main content area :: end -->

 </div> <!-- content-container -->
 <!-- content container :: end -->

<?php include (_INCLUDES_PATH_.'footer.php'); ?>

</div> <!-- body-container -->

</body>
</html>
