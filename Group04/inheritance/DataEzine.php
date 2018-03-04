<?php
/**
  * E-Zine Maintenance class DataEzine.php
  * User Management
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
  *
  */

class DataEzine extends DataModel {

 	/** @var string SQL Table name */
	protected $_table = _TBL_EZINES_;

	/** @var string Table Key identifier */
	protected $_key = 'etKey';

  /** @var array common fields used for each function */
 	protected $_fields = array('etStatus','etDoctype','etTitle','etContent');

  /** @var array sort fields/params for retrievals */
 	protected $_sort = array('etDoctype ASC','etDateEzine DESC');

  /** @var array holding table for db inserts and updates, and delete display info */
 	protected $_dbdata = array();

  /** @var string db field that contains Status code */
  protected $_statusfield = 'etStatus';

  /** @var array legend to display Status on success screen */
  protected $_statuslegend = array('A'=>'Active', 'I'=>'Inactive'); 

  /** @var array data to display on success screen */
  protected $_success = array('Date'=>'etDateEzine', 'Title'=>'etTitle');

  /** @var integer e-zine date converted to unix time sdtamp */
  protected $_unixdate = 0;

  /**
   * Validate data ... server-side validation
   *
   * @param array $pVars data from the form
   * @param string $mode to determine how Prev IDs are validated
   */
  public function validateData ( $pVars, $mode='add' ) {
    global $errTable;
      /* no server-side validation for e-zine data */
  } // end function


  /**
   * Add data to the database
   *
   * @param array $pVars data from the form
   */
  public function addData ( $pVars ) {
    global $snUserKey,$snCreatorKey;
    $this->_dbdata['etKey']="NULL";
    $this->_dbdata['etDateAdded']=date("Y-m-d H:i:s", time());
    $this->_dbdata['etDateUpdated']=NULL;
    $this->_dbdata['etAuthor']=$snUserKey;
    $this->_dbdata['etAdmin']=$snCreatorKey;
    $this->_dbdata['etViews']=0;
    $this->_dbdata['etDateEzine']=$this->convertDate( $pVars['etDateEzine'], 'todb' );
    $this->_dbdata['etUnixEzine']=$this->unixdate;
    parent::addData($pVars);
  } // end function


  /**
   * retrieve list of e-zines ... set params and call parent::retrieveList common function
   *
   * @param array $gVars $_GET data
   * @param array $pVars $_POST data (from the form)
   * @param string $thisModule text used for 'no data' display
   */
  public function retrieveList ( $gVars, $pVars, $thisModule='' ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;

    $pArray['minpp'] = _MIN_PERPAGE_;        // default min per page
    $pArray['maxpp'] = _MAX_PERPAGE_;        // default max per page
    $pArray['width'] = _EZINE_WIDTH_;        // width of user list
    $pArray['csstotal'] = _EZINE_CSSTOTAL_;  // css for 'total' nav line
    $pArray['csspage'] = _EZINE_CSSPAGE_;    // css for 'page' navigation line

    if (!$snSuperAdmin) {
      if ($snAdministrator) { $where="etAuthor='".$snUserKey."' OR etAdmin='".$snUserKey."'"; }
      else { $where="etAuthor='".$snUserKey."'"; }
    } else { $where=""; }

    if ($dataTable=parent::retrieveList ( $gVars, $pVars, $pArray, $where, $thisModule )) {
      return $dataTable;
    } else { return false; }
  } // end function


  /* retrieve selected data from database  (later use) */
  public function retrieveDataModule ( &$dataTable ) {
    $returnData['etDateEzine']=$this->convertDate ( $dataTable['etDateEzine'], 'todsply' );
    return $returnData;
  } // end function


  /**
   * update data ... set params and call parent::updateData common function
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function updateData ( $pVars, $fileUpload=false ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;
    $this->_dbdata['etDateEzine']=$this->convertDate( $pVars['etDateEzine'], 'todb' );
    $this->_dbdata['etUnixEzine']=$this->unixdate;
    $this->_dbdata['etDateUpdated']=date("Y-m-d H:i:s", time());
    $this->_dbdata['etKey']=$pVars['etKey'];
    $dbKeys[]=$this->_key;
    if (!$snSuperAdmin) {
      if ($snAdministrator) {
        if ($snUserKey==$pVars['etAuthor']) { $dbKeys[]='etAuthor'; }
        else { $dbKeys[]='etAdmin'; }
      } else { $dbKeys[]='etAuthor'; }
    }
    parent::updateData ( $pVars, $dbKeys, $fileUpload );
    return true;
  } // end function


  /**
   * delete data ... set params and call parent::deleteData common function
   *
   * @param integer $thisKey key of record to delete
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function deleteData ( $thisKey, $fileUpload=false ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;

    $dbKeys[$this->_key]=$thisKey;
    if (!$snSuperAdmin) {
      if (!$snAdministrator) { $dbKeys['etAuthor']=$snUserKey; }
      else {  // need to retrieve to get second delete key
        $dbi=DB::getInstance();
        $query="SELECT etAuthor, etAdmin FROM "._TBL_EZINES_." WHERE etKey='".pSQL($thisKey)."'";
        $ttTable=$dbi->getOneRow( $query );
        if ($dbi->_numRows>0) {
          if ($ttTable['etAuthor']==$snUserKey) { $dbKeys['etAuthor']=$snUserKey; }
          else { $dbKeys['etAdmin']=$snUserKey; }
        } else { return false; }
      }
    }
    if (!parent::deleteData ( $dbKeys, $fileUpload )) { return false; }
    return true;
  } // end function


  /**
   * Set success display fields/values
   *   first call parent::setSuccess for standard populations,
   *   then add any other data to the Success array
   *
   * @param integer $sIdx  starting position for data fields in display array
   * @param boolean $showstatus  true=set 'Status' field for success screen
   */
  public function setSuccess ( $sIdx=0, $showstatus=true ) {
    $sIdx=parent::setSuccess( $sIdx, $showstatus);
    $sData=Registry::getInstance()->get('var_sData');
    Registry::getInstance()->register('var_sData',$sData);
    return $sIdx;
  } // end function


  /**
   * Convert e-zine date mm/dd/yyyy to yyyy-mm-dd for database
   *  or convert yyyy-mm-dd to mm/dd/yyyy for screen display
   *
   * @param string $indate  date to be converted
   * @param string $whichway  'todb'=convert to yyyy-mm-dd ... 'todsply'=cpnvert to mm/dd/yyyy
   */
  public function convertDate ( $indate, $whichway='todb' ) {
    $convertedDate="01/01/1900";
    if ($whichway=="todb") {
      $dateChars=array('-','.');
      $newDate=str_replace($dateChars,"/",$indate);
      $workDate=explode("/",$newDate);
      $convertedDate=$workDate[2]."-".$workDate[0]."-".$workDate[1];
      if (!$this->unixdate=mktime (0, 0, 0, $workDate[0], $workDate[1], $workDate[2])) {
        $this->unixdate=0;
      }
    } elseif ($whichway=="todsply") {
      $workDate=explode("-",$indate);
      $convertedDate=$workDate[1]."/".$workDate[2]."/".$workDate[0];    
    }
    return $convertedDate;
  } // end function

} // end class
