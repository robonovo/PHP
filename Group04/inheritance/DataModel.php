<?php
/**
  * Main abstract class DataModel.php
  * All data handling objects extend this abstract class
  * Data handling ... validation, add, edit, delete, retrieval
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
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

      $wData="There are no ".$thisModule." currently set up in the database. &nbsp;You may add ";
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
  function retrieveData ( $thisKey, $thisApp='user', $fileUpload=false ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;

    $sql="SELECT * FROM ".$this->_table ." WHERE ".$this->_key."='".pSQL($thisKey)."'";
    if (!$snSuperAdmin) {
      if ($thisApp=="user") { $sql.=" AND utCreator='".$snUserKey."'"; }
      elseif ($thisApp=="ezine") {
        if ($snAdministrator) { $sql.=" AND (etAuthor='".$snUserKey."' OR etAdmin='".$snUserKey."')"; }
        else { $sql.=" AND etAuthor='".$snUserKey."'"; }    
      }      
    }
    $dataTable=DB::getInstance()->getOneRow($sql);

    if (DB::getInstance()->_numRows>0) {
      if (method_exists( $this, 'retrieveDataModule' )) {
        $returnData=$this->retrieveDataModule( $dataTable );
        if (!empty($returnData)) { foreach ($returnData as $key => $val) { $dataTable[$key]=$val; } }
      }

      if ($fileUpload) { $this->setFileChange ( $dataTable); }

    } else {  // not found - set error display and return false

      $wData="Your selected data could not be found in the database. &nbsp;Please contact the ";
      $wData.="<a href=\"mailto:"._ADMIN_EMAIL_."\">Administrator</a> with this error condition. &nbsp;";
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

    if (DB::getInstance()->_numRows==0) { return false; }
    else {
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


  /* defined for later use */
  public function setFileInfo ( $sIdx ) { }
  public function setFileChange ( &$dataTable) { }
  public function setFileDelete ( $sIdx ) { }


} // end class
  
 