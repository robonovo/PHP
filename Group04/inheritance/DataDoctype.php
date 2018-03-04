<?php
/**
  * Doctype Maintenance class DataDoctype.php
  * Doctype Management
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
  *
  */

class DataDoctype extends DataModel {

 	/** @var string SQL Table name */
	protected $_table = _TBL_DOCTYPE_;

	/** @var string Table Key identifier */
	protected $_key = 'dtKey';

  /** @var array common fields used for each function */
 	protected $_fields = array('dtDropDown','dtColumnHead','dtDescription');

  /** @var array sort fields/params for retrievals */
 	protected $_sort = array('dtDropDown ASC');

  /** @var array holding table for db inserts and updates, and delete display info */
 	protected $_dbdata = array();

  /** @var string db field that contains Status code */
    protected $_statusfield = 'etStatus';

  /** @var array legend to display Status on success screen */
    protected $_statuslegend = array('A'=>'Active', 'I'=>'Inactive');

  /** @var array data to display on success screen */
    protected $_success = array('Short Title'=>'dtDropDown', 'Column Header'=>'dtColumnHead');

  /** @var integer doctype date converted to unix time stamp */
    protected $_unixdate = 0;

  /**
   * Validate data ... server-side validation
   *
   * @param array $pVars data from the form
   * @param string $mode to determine how Prev IDs are validated
   */
  public function validateData ( $pVars, $mode='add' ) {
    global $errTable;
      /* no server-side validation for Doctype data */
  } // end function


  /**
   * Add data to the database
   *
   * @param array $pVars data from the form
   */
  public function addData ( $pVars ) {
    $this->_dbdata['dtKey']="NULL";
    parent::addData($pVars);
  } // end function


  /**
   * retrieve list of doctypes... set params and call parent::retrieveList common function
   *
   * @param array $gVars $_GET data
   * @param array $pVars $_POST data (from the form)
   * @param string $thisModule text used for 'no data' display
   */
  public function retrieveList ( $gVars, $pVars, $thisModule='' ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;

    $pArray['minpp'] = _MIN_PERPAGE_;          // default min per page
    $pArray['maxpp'] = _MAX_PERPAGE_;          // default max per page
    $pArray['width'] = _DOCTYPE_WIDTH_;        // width of user list
    $pArray['csstotal'] = _DOCTYPE_CSSTOTAL_;  // css for 'total' nav line
    $pArray['csspage'] = _DOCTYPE_CSSPAGE_;    // css for 'page' navigation line

    $where="";
    if ($dataTable=parent::retrieveList ( $gVars, $pVars, $pArray, $where, $thisModule )) {
      return $dataTable;
    } else { return false; }
  } // end function


  /**
   * update data ... set params and call parent::updateData common function
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function updateData ( $pVars, $fileUpload=false ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;
    $this->_dbdata['dtKey']=$pVars['dtKey'];
    $dbKeys[]=$this->_key;
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
  public function setSuccess ( $sIdx=0, $showstatus=false ) {
    $sIdx=parent::setSuccess( $sIdx, $showstatus);
    $sData=Registry::getInstance()->get('var_sData');
    Registry::getInstance()->register('var_sData',$sData);
    return $sIdx;
  } // end function

} // end class
