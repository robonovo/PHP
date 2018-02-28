<?php
if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * @title    Project Tables Edit routines class, PTEroutines.php
  * @author   Roger Bolser
  * @version  1.0.0.0
  *
  */

class PTEroutines {

  /** @array error codes for file uploads */
  protected $fpErrors=array (
    0=>'No error ... the file uploaded successfully',
    1=>'File <strong>%filename%</strong> exceeds server maximum filesize',
    2=>'File <strong>%filename%</strong> exceeds form maximum filesize',
    3=>'File <strong>%filename%</strong> was only partially uploaded',
    4=>'No file was uploaded',
    6=>'Missing a temporary folder',
    7=>'Unable to write the file <strong>%filename%</strong> to the server',
    8=>'File upload stopped by extension'
  );

  /** @var integer auto-increment id on insert */
  protected $_insertid;

  /**
   * object constructor
   *
   * @param
   */
  public function __construct() {
    /* no construct functions needed */
  } // end function

  /**
	 * retrieve data one table ... retrieval when data contained in one table
   *
   * @param string $tblName the db table name
   * @param array $dbKeys array of db table keys and values (key/value pairs)
   * @param string $toget used to get "all" or get "one"
   * @param string $orderby string containing the "ORDER BY" vars if needed
   *
   * @return array of table data or false if record(s) not found
   */
  public function retrieveData ( $tblName, $dbKeys, $toget='one', $orderby=false ) {
    global $errText;

    // set the WHERE values
    foreach ($dbKeys as $key => $val) { $keyArray[]=$key."='".$val."'"; }
    $whereClause=implode(" AND ",$keyArray);

    // build sql string
    $sql="SELECT * FROM ".$tblName." WHERE ".$whereClause;
    if ($orderby) { $sql.=" ORDER BY ".$orderby; }
   
   if ($toget=="one") { $dtTable=DB::getInstance()->getOneRow ($sql); }
   else { $dtTable=DB::getInstance()->getAllRows ($sql); }
   if (DB::getInstance()->_numRows>0) { return $dtTable; }

    // no record(s) found ... set error message and return false
    $errText="An error has occurred in this application. ";
    $errText.="Data could not be found for this project function. ";
    $errText.="Please contact the Administrator at ";
    $errText.="<a href=\"mailto:"._ADMIN_EMAIL_."\">"._ADMIN_EMAIL_."</a> ";
    $errText.="with this error condition.";
    return false;
  } // end function


  /**
	 * update data method
   *
   * @param string $tblName the db table name
   * @param array $dtData data from the form formatted 
   * @param array $dtKeys db names of the keys to update
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function updateData ( $tblName, $dtData, $dbKeys, $fileUpload=false ) {

    DB::getInstance()->updateRecord ( $tblName, $dtData, $dbKeys );
    return true;
  } // end function


  /**
	 * upload file processing method
   *
   * @param array $uploadArray:  key=>number,db field name
   *        key = major part of form name (ie. 'alt', 'cost','traffic'
   *        .. [0] number = number of file uploads possible
   *        .. [1] db field name = field name of the db field for file info
   *        .. [2] 'Y' or 'N' to keep a placeholder for the file in the db
   * @param array $pVars the $_POST form data
   *
   * @return bool true or false whether we had upload errors or not
   */
  public function processFiles ( $uploadArray, $pVars, $doAll=false ) {
    global $fpArray,$errText,$dbTable;
    $haveErrors=false;

    if ($doAll) { // retrieve and process ALL $_FILES

      foreach ($_FILES as $key => $val) {

        $fname=$tblIdx=$key;
        list($fwhich,$fnum)=explode(":",$key);
        $fpArray[$tblIdx][9]=$uploadArray[$fwhich][1];  // set db table field name
        $placeHolder=$uploadArray[$fwhich][2];          // placeholder or not

        // set name vars
        $oldFileName=$fwhich."OldFile".$fnum;
        $oldServerName=$fwhich."OldServer".$fnum;
        $oldFileSize=$fwhich."OldSize".$fnum;
        $fileDelete=$fwhich."Delete".$fnum;

        if (isset($pVars[$oldFileName]) && $pVars[$oldFileName]!="") {
          $fpArray[$tblIdx][1]=$pVars[$oldFileName];    // save old file name
          $fpArray[$tblIdx][2]=$pVars[$oldServerName];  // save old server name
          $fpArray[$tblIdx][3]=$pVars[$oldFileSize];    // save old file size
        }

        // file delete requested
        if (isset($pVars[$fileDelete]) && $pVars[$fileDelete]=="Y") {
          $fpArray[$tblIdx][0]="N";                     // no error
          $fpArray[$tblIdx][8]="Y";                     // flag to delete this file
          if ($placeHolder=="Y") { $fpArray[$tblIdx][4]="PlaceHolder"; }

        // no file uploaded ... save a space (placeholder)
        }	elseif ($val['error']=="4") {
          $fpArray[$tblIdx][0]="N";            // no error
          if ($placeHolder=="Y") {  // no file but save a space
            $fpArray[$tblIdx][4]="PlaceHolder";
          } else {  // no file and do NOT save a space
            $fpArray[$tblIdx][4]="NoFile";
          }

        // we have an error - log it
        }	elseif ($val['error']!="0") {
          $haveErrors=true;
          $errCode=$val['error'];
          $errName=$val['name'];

          $fpArray[$tblIdx][0]="Y";               // we have an error
          $fpArray[$tblIdx][4]=$errName;          // name of the file
          $fpArray[$tblIdx][10]=$errCode;         // error code
            $fpArray[$tblIdx][11]=str_replace('%filename%',$errName,$this->fpErrors[$errCode]);

        // process the file (no other errors)
        } else {
          $fpArray[$tblIdx][0]="N";               // no error
          $fpArray[$tblIdx][4]=$val['name'];      // file name
          $fpArray[$tblIdx][6]=$val['size'];      // file size
          $fpArray[$tblIdx][7]=$val['tmp_name'];  // temp name

          // generate a new server name
          $tmpFileName=str_replace(" ","",$fpArray[$tblIdx][4]);
          $fna = explode(".", $tmpFileName);
          $fpSuffix=strtolower(array_pop($fna));
          $newServerName=$fwhich."_".uniqid().".".$fpSuffix;
          $fpArray[$tblIdx][5]=$newServerName;   // new name for file on the server

        } // end string of if/else
      }  // end foreach loop

    } else { // retrieve and process by $_FILE name

      foreach ($uploadArray as $key => $val) {
        $xloop=$val[0];
        $placeHolder=$val[2];
        for ($idx=1; $idx<=$xloop; $idx++) {
          $tblIdx=$key.":".$idx;

          // set name vars
          $newFileName=$key."NewFile".$idx;
          $oldFileName=$key."OldFile".$idx;
          $oldServerName=$key."OldServer".$idx;
          $oldFileSize=$key."OldSize".$idx;
          $fileDelete=$key."Delete".$idx;

          $fpArray[$tblIdx][9]=$val[1];  // set db table field name
          if (isset($pVars[$oldFileName]) && $pVars[$oldFileName]!="") {
            $fpArray[$tblIdx][1]=$pVars[$oldFileName];    // save old file name
            $fpArray[$tblIdx][2]=$pVars[$oldServerName];  // save old server name
            $fpArray[$tblIdx][3]=$pVars[$oldFileSize];    // save old file size
          }

          // file delete requested
          if (isset($pVars[$fileDelete]) && $pVars[$fileDelete]=="Y") {
            $fpArray[$tblIdx][0]="N";                     // no error
            $fpArray[$tblIdx][8]="Y";                     // flag to delete this file
            if ($placeHolder=="Y") { $fpArray[$tblIdx][4]="PlaceHolder"; }

          // no file uploaded ... save a space (placeholder)
          }	elseif ($_FILES[$newFileName]['error']=="4") {
            $fpArray[$tblIdx][0]="N";            // no error
            if ($placeHolder=="Y") {  // no file but save a space
              $fpArray[$tblIdx][4]="PlaceHolder";
            } else {  // no file and do NOT save a space
              $fpArray[$tblIdx][4]="NoFile";
            }  

          // we have an error - log it
          }	elseif ($_FILES[$newFileName]['error']!="0") {
            $haveErrors=true;
            $errCode=$_FILES[$newFileName]['error'];
            $errName=$_FILES[$newFileName]['name'];

            $fpArray[$tblIdx][0]="Y";                   // we have an error
            $fpArray[$tblIdx][4]=$errName;              // name of the file
            $fpArray[$tblIdx][10]=$errCode;             // error code
            $fpArray[$tblIdx][11]=str_replace('%filename%',$errName,$this->fpErrors[$errCode]);

          // process the file (no other errors)
          } else {
            $fpArray[$tblIdx][0]="N";                                // no error
            $fpArray[$tblIdx][4]=$_FILES[$newFileName]['name'];      // file name
            $fpArray[$tblIdx][6]=$_FILES[$newFileName]['size'];      // file size
            $fpArray[$tblIdx][7]=$_FILES[$newFileName]['tmp_name'];  // temp name

            // generate a new server name
            $tmpFileName=str_replace(" ","",$fpArray[$tblIdx][4]);
            $fna = explode(".", $tmpFileName);
            $fpSuffix=strtolower(array_pop($fna));
            $newServerName=$key."_".uniqid().".".$fpSuffix;
            $fpArray[$tblIdx][5]=$newServerName;   // new name for file on the server

          } // end string of if/else
        }  // end for loop
      }  // end foreach loop
    } // end if/else ($doAll)


    // if errors loop through array and set error array for display
    if ($haveErrors) {
      $errText="";
      foreach ($fpArray as $key => $val) {
        if ($val[0]=="Y") {
          $errText.="Error: ".$val[11]."<br />";
        }  // end if ($val[0]=="Y"
      } // end foreach loop
      return false;
    }

    // no errors ... loop through and either delete or move files

    // first sort array to make sure types are grouped and in order
    ksort($fpArray);

    // loop through and process the files
    $prevType="";  $prevTable="";  $firstTime=true;
    foreach ($fpArray as $key => $val) {
      list($ktype,$knum)=explode(":",$key);
      if ($firstTime) {
        $firstTime=false;
        $prevType=$ktype;
        $prevTable=$val[9];
      }
      if ($ktype!=$prevType) {
        if (isset($tempArray)) { $dbTable[$prevTable]=implode("||",$tempArray); }
        else { $dbTable[$prevTable]=""; }
        unset($tempArray);
        $prevType=$ktype;
        $prevTable=$val[9];
      }

	    if ($val[8]=="Y") {
        $fpServerName=_UPLOAD_DIR_.$val[2];
        if (file_exists($fpServerName)) { $unlinkStatus=unlink ($fpServerName); }
        if ($val[4]=="PlaceHolder") { $tempArray[]="::::"; }
			
      } elseif ($val[4]=="NoFile" || $val[4]=="PlaceHolder") {
        if ($val[1]!="") { // we have a previous file not replaced ... keep the info
          $tempArray[]=$val[1]."::".$val[2]."::".$val[3];
        } else {
          if ($val[4]=="PlaceHolder") { $tempArray[]="::::"; }
        }

      } else {
        $fpServerName=_UPLOAD_DIR_.$val[5];
        if (! @copy($val[7], "$fpServerName")) {
          move_uploaded_file($val[7], "$fpServerName");
        }
        if ($val[1]!="") { // we have an old file that was replaced ... delete from server
          $fpServerName=_UPLOAD_DIR_.$val[2];
          if (file_exists($fpServerName)) { $unlinkStatus=unlink ($fpServerName); }
        }
        $tempArray[]=$val[4]."::".$val[5]."::".$val[6];
      }  // end if/else ($val[8]=="Y")

    }  // end foreach loop

    // set last dbTable
    if (isset($tempArray)) { $dbTable[$prevTable]=implode("||",$tempArray); }
    else { $dbTable[$prevTable]=""; }
    return true;

  } // end function


} // end class 

