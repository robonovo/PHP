<?php if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * Main abstract class DataModel.php
  * All data handling objects extend this abstract class
  * Data handling ... validation, add, edit, delete, retrieval
  *
  * @author Roger Bolser
  * @version 1.0.0.0
  *
  */

abstract class DataModel {

  /** @var integer auto-increment id on insert */
  protected $_insertid;

 	/** @var string SQL Table name */
	protected $_table = NULL;

	/** @var string Table Key identifier */
	protected $_key = NULL;

  /** @var array common fields used for each function */
 	protected $_fields = array();

  /** @var array sort fields/params for retrievals */
 	protected $_sort = array();

  /** @var array holding table for db inserts and updates, and delete display info */
 	protected $_dbdata = array();

  /** @var string db field that contains Status code */
  protected $_statusfield = NULL;

  /** @var array legend to display Status on success screen */
  protected $_statuslegend = array(); 

  /** @var array data to display on success screen */
  protected $_success = array();



  /**
   * object constructor
   *
   * @param
   */
  public function __construct() {

  } // end function


  /**
	 * common add data method
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function addData ( $pVars,  $fileUpload=false ) {
    $this->setCommonData( $pVars );
    $sIdx=$this->setSuccess ();
    if ($fileUpload) { $this->setFileInfo ( $sIdx ); }
    $this->_insertid=DB::getInstance()->insertRecord ( $this->_table, $this->_dbdata );
  } // end function


  /**
	 * retrieve list based on which page we are on (pagination) ... ie. page 2 of 10
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function retrieveList ( $gVars, $pVars, $pArray, $where='', $thisModule='' ) {
    if (empty($thisModule)) { $thisModule="entries"; }

    $numRecords = DB::getInstance()->getCount ( $this->_table, $where );
    if ($numRecords==0) { // not records found - set display and return false

      $wData="There are no ".$thisModule." currently set up in the database. You may add ";
      $wData.=$thisModule." by clicking 'add' in the main menu.";
      Registry::getInstance()->register('var_mTitle','No '.$thisModule);
      Registry::getInstance()->register('var_mMessage',$wData);
      return false;

    } else {  // retrieve the entire list based on page x of y

      $pArray['totrecs'] = $numRecords;
      $sql="SELECT * FROM ".$this->_table;
      if (!empty($where)) { $sql.=" WHERE ".$where; }
      if (!empty($this->_sort)) {
        $sortParams=implode( ", ", $this->_sort );
        $sql.=" ORDER BY ".$sortParams;
      }
      $pagi=Paginate::getInstance();
      $pagi->setParams( $pArray );
      $pagi->_sql=$sql;
      $dataTable=$pagi->setNavigation ($gVars, $pVars);

    }
    return $dataTable;
  } // end function


  /**
	 * retrieve selected record on edit
   *
   * @param integer $thisKey key of record to retrieve
   * @param boolean $fileupload  true=have file uploads
   */
  function retrieveData ( $thisKey, $fileUpload=false, $where='' ) {
    $dbi=DB::getInstance();
    $sql="SELECT * FROM ".$this->_table ." WHERE ".$this->_key."='".pSQL($thisKey)."'";
    if ($where) { $sql.=" AND ".$where; }
    $dataTable=DB::getInstance()->getOneRow( $sql );

    if (DB::getInstance()->_numRows>0) {
      if (method_exists( $this, 'retrieveDataModule' )) {
        $returnData=$this->retrieveDataModule( $dataTable );
        if (!empty($returnData)) { foreach ($returnData as $key => $val) { $dataTable[$key]=$val; } }
      }

      if ($fileUpload) { $this->setFileChange ( $dataTable); }

    } else {  // not found - set error display and return false

      $wData="Your selected data could not be found in the database. Please contact the ";
      $wData.="<a href=\"mailto:"._ADMIN_EMAIL_."\">Administrator</a> with this error condition. ";
      $wData.="We're sorry for the inconvenience.";
      Registry::getInstance()->register('var_mTitle','System Error');
      Registry::getInstance()->register('var_mMessage',$wData);
      return false;

    }  

    return $dataTable;
  } // end function


  /**
	 * common update data method
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function updateData ( $pVars, $dbKeys=array(), $fileUpload=false ) {
    $this->setCommonData( $pVars );
    $sIdx=$this->setSuccess ();
    if ($fileUpload) { $this->setFileInfo ( $sIdx ); }
    if (method_exists( $this, 'updateDataModule' )) { $this->updateDataModule( $pVars ); }
    if (empty($dbKeys)) { $dbKeys=array( $this->_key ); }
    DB::getInstance()->updateRecord ( $this->_table, $this->_dbdata, $dbKeys );
    return true;
  } // end function


  /**
	 * common delete data method
   *
   * @param array $dbKeys keys for select and delete
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function deleteData ( $dbKeys, $fileUpload=false ) {

    if (empty($dbKeys)) { return false; }

    $dbi=DB::getInstance();
    foreach ($dbKeys as $key => $val) { $whereArray[]=$key."='".pSQL($val)."'"; }
    $whereClause=implode( " AND ", $whereArray );

    // retrieve record first for success display
    $sql="SELECT * FROM ".$this->_table." WHERE ".$whereClause;
    $this->_dbdata=DB::getInstance()->getOneRow ( $sql );

    if (DB::getInstance()->_numRows==0) {
      $wData="Your data to delete could not be found in the database. ";
      $wData.="Please contact the ";
      $wData.="<a href=\"mailto:"._ADMIN_EMAIL_."\">Administrator</a> with this error condition. ";
      $wData.="We're sorry for the inconvenience.";
      Registry::getInstance()->register('var_mTitle','System Error');
      Registry::getInstance()->register('var_mMessage',$wData);
      return false;
    } else {
      $sIdx=$this->setSuccess ( 0, false );
      if ($fileUpload) { $this->setFileDelete ( $sIdx ); }

      // delete the entry
      DB::getInstance()->deleteRecord ( $this->_table, $dbKeys );
    }
    return true;
  }  // end function


  /**
	 * Set common field data in hold array
   *
   * @param array $pVars data from the form
   */
  public function setCommonData ( $pVars ) {
    foreach ($this->_fields as $val) {
      if (isset($pVars[$val])) { $this->_dbdata[$val]=$pVars[$val]; }
    }
    return true;
  } // end function


  /**
	 * Set success display fields/values
   *
   * @param integer $sIdx  starting position for data fields in display array
   * @param boolean $showstatus  true=set 'Status' field for success screen
   */
  public function setSuccess ( $sIdx=0, $showstatus=true ) {

    if ($showstatus) {
      $sf=$this->_dbdata[$this->_statusfield];
      foreach ($this->_statuslegend as $key => $val) {
        if ($key == $sf) {
          $sData[$sIdx++]=array('Status',$val);
          break;
        }
      }
    }

    foreach ($this->_success as $key => $val) {
      if (isset($this->_dbdata[$val])) {
        if (strlen($this->_dbdata[$val])>60) {
          $dsplyText=substr($this->_dbdata[$val], 0, 60)." &nbsp;&nbsp;[more]";
        } else { $dsplyText=$this->_dbdata[$val]; }
        $sData[$sIdx++]=array($key,$dsplyText);
      }
    }

    Registry::getInstance()->register('var_sData',$sData);
    return $sIdx;
    
  } // end function


  /**
	 * format and set file upload information into the database fields
   *
   * @param integer $sIdx  starting position for data fields
   */
  public function setFileInfo ( $sIdx ) {
    global $fpArray,$fcArray,$fpInfo;
    $sData=Registry::getInstance()->get('var_sData');

    foreach ($fpInfo as $key => $val) {
      unset($wFileArray);
      unset($wCapArray);
      if (isset($val['fpCaptions']) && $val['fpCaptions']=="Y") {
        $doCaptions=true;
        $this->_dbdata[$val['fpCapField']]="";
      } else { $doCaptions=false; }
      $this->_dbdata[$val['fpField']]="";

      for ($wIdx=$val['fpStart']; $wIdx<=$val['fpEnd']; $wIdx++) {
        if ($fpArray[$wIdx][12]=="Y") {
          $sData[$sIdx++]=array($val['fpType'].' Deleted',$fpArray[$wIdx][1]);
        } elseif ($fpArray[$wIdx][5]!= "") {
          $wFile=str_replace(":", "", $fpArray[$wIdx][5]);
          $wFileArray[]=$wFile."::".$fpArray[$wIdx][6]."::".$fpArray[$wIdx][7]."::".$fpArray[$wIdx][8];
          $sData[$sIdx++]=array($val['fpType'].' Uploaded',$fpArray[$wIdx][5]);
          if ($doCaptions) { $wCapArray[]=$fcArray[$wIdx]; }
        } elseif ($fpArray[$wIdx][1]!="") {
          $wFileArray[]=$fpArray[$wIdx][1]."::".$fpArray[$wIdx][2]."::".$fpArray[$wIdx][3]."::".$fpArray[$wIdx][4];
          if ($doCaptions) { $wCapArray[]=$fcArray[$wIdx]; }
        }
      }
      if ($wFileArray) { $this->_dbdata[$val['fpField']]=implode( "||", $wFileArray ); }
      if ($wCapArray) { $this->_dbdata[$val['fpCapField']]=implode( "||", $wCapArray ); }
      
    }  // end foreach

    Registry::getInstance()->register('var_sData',$sData);
    return $sIdx;

  } // end function setFileInfo


  /**
	 * format file upload information for display and editing
   *
   * @param array $dataTable  database table
   */
  public function setFileChange ( &$dataTable) { 
    global $fpArray,$fpInfo;

    foreach ($fpInfo as $key => $val) {
      if ($dataTable[$val['fpField']]!="") {
        $wFiles=explode("||", $dataTable[$val['fpField']]);
        $wpIdx=$val['fpStart'];
        foreach ($wFiles as $valEach) {
          $wArray=explode("::", $valEach);
	        $fpArray[$wpIdx][1]=$wArray[0];
          $fpArray[$wpIdx][2]=$wArray[1];
          $fpArray[$wpIdx][3]=$wArray[2];
          $fpArray[$wpIdx][4]=$wArray[3];
          $wpIdx++;
        }
      }
    }

    return true;

	}

  /**
	 * delete all uploaded files when db record with uploads attached has been deleted
   *
   * @param integer $sIdx  starting position for data fields
   */
  public function setFileDelete ( $sIdx ) {
    global $fpArray,$fpInfo,$fpFileParams;
    $sData=Registry::getInstance()->get('var_sData');
    $delFiles=0;

    foreach ($fpInfo as $key => $val) {
      if ($this->_dbdata[$val['fpField']]!="") {
        $fileArray=explode("||", $this->_dbdata[$val['fpField']]);
        foreach ($fileArray as $valEach) {
          $wArray=explode("::", $valEach);
          $sData[$sIdx++]=array($val['fpType'].' Deleted',$wArray[0]);
          $fpArray[$delFiles][1]=$wArray[0];
          $fpArray[$delFiles][2]=$wArray[1];
          $fpArray[$delFiles][12]="Y";
          $fpFileParams[$delFiles]['dir']=$val['fpDir'];
          $delFiles++;
        }
      }
    }  // end foreach

    if ($delFiles>0) {
      FileProcessor::getInstance()->deleteFiles ( $delFiles );
      Registry::getInstance()->register('var_sData',$sData);
    }

    return $sIdx;

  } // end function setFileDelete

} // end class
  
  