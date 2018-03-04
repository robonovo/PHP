<?php
/**
  * User Maintenance class DataUser.php
  * User Management
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
  *
  */

class DataUser extends DataModel {

 	/** @var string SQL Table name */
	protected $_table = _TBL_USERS_;

	/** @var string Table Key identifier */
	protected $_key = 'utKey';

  /** @var array common fields used for each function */
 	protected $_fields = array('utCreator','utStatus','utUserId','utName','utType','utEmail');

  /** @var array sort fields/params for retrievals */
 	protected $_sort = array('utName ASC');

  /** @var array holding table for db inserts and updates, and delete display info */
 	protected $_dbdata = array();

  /** @var string db field that contains Status code */
  protected $_statusfield = 'utStatus';

  /** @var array legend to display Status on success screen */
  protected $_statuslegend = array('A'=>'Active', 'S'=>'Suspended'); 

  /** @var array data to display on success screen */
  protected $_success = array('User Name'=>'utName', 'User Id'=>'utUserId');


  /**
   * Validate data ... server-side validation
   *
   * @param array $pVars data from the form
   * @param string $mode to determine how Prev IDs are validated
   */
  public function validateData ( $pVars, $mode='add' ) {
    global $errTable;

    if (($mode=="add") || ($mode=="chg" && $pVars['utPrevId']!=$pVars['utUserId'])) {
      $tempTable=DB::getInstance()->getOneRow( "SELECT utUserId FROM "._TBL_USERS_." WHERE utUserId='".pSQL($pVars['utUserId'])."'" );
      if (DB::getInstance()->_numRows>0) {
        $errTable[]="User ID already exists";
      }
    }

  } // end function


  /**
   * Add data to the database
   *
   * @param array $pVars data from the form
   */
  public function addData ( $pVars ) {
    $this->_dbdata['utPassword']=Encryption::encrypt($pVars['utPassword']);
    $this->_dbdata['utKey']="NULL";
    $this->_dbdata['utSetupDate']=date("Y-m-d H:i:s", time());
    $this->_dbdata['utLoginCount']=0;
    parent::addData($pVars);
  } // end function


  /**
   * retrieve list of users ... set params and call parent::retrieveList common function
   *
   * @param array $gVars $_GET data
   * @param array $pVars $_POST data (from the form)
   * @param string $thisModule text used for 'no data' display
   */
  public function retrieveList ( $gVars, $pVars, $thisModule='' ) {
    global $snSuperAdmin,$snUserKey;

    $pArray['minpp'] = _MIN_PERPAGE_;       // default min per page
    $pArray['maxpp'] = _MAX_PERPAGE_;       // default max per page
    $pArray['width'] = _USER_WIDTH_;        // width of user list
    $pArray['csstotal'] = _USER_CSSTOTAL_;  // css for 'total' nav line
    $pArray['csspage'] = _USER_CSSPAGE_;    // css for 'page' navigation line

    if (!$snSuperAdmin) { $where="utCreator='".$snUserKey."'"; }
    else { $where=""; }

    if ($dataTable=parent::retrieveList ( $gVars, $pVars, $pArray, $where, $thisModule )) {
      return $dataTable;
    } else { return false; }
  } // end function


  /* retrieve selected data from database  (later use) */
  public function retrieveDataModule ( &$dataTable ) {
    $dcPassword=Encryption::decrypt($dataTable['utPassword']);
    $returnData['utPassword']=$dcPassword;
    return $returnData;
  } // end function


  /**
   * update user data ... set params and call parent::updateData common function
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function updateData ( $pVars, $fileUpload=false ) {
    global $snSuperAdmin,$snUserKey;
    $this->_dbdata['utPassword']=Encryption::encrypt($pVars['utPassword']);
    $this->_dbdata['utKey']=$pVars['utKey'];
    $dbKeys[]=$this->_key;
    if (!$snSuperAdmin) { $dbKeys[]='utCreator'; }
    parent::updateData ( $pVars, $dbKeys, $fileUpload );
    return true;
  } // end function


  /**
   * delete user data ... set params and call parent::deleteData common function
   *
   * @param integer $thisKey key of record to delete
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function deleteData ( $thisKey, $fileUpload=false ) {
    global $snSuperAdmin,$snUserKey;

    $dbKeys[$this->_key]=$thisKey;
    if (!$snSuperAdmin) { $dbKeys['utCreator']=$snUserKey; }

    if (!parent::deleteData ( $dbKeys, $fileUpload )) { return false; }
    return true;
  } // end function


  /**
   * Set success display fields/values
   *   first call parent::setSuccess for standard populations,
   *   then add User Type display to success the Success array
   *
   * @param integer $sIdx  starting position for data fields in display array
   * @param boolean $showstatus  true=set 'Status' field for success screen
   */
  public function setSuccess ( $sIdx=0, $showstatus=true ) {
    global $cfaUserType;

    $sIdx=parent::setSuccess( $sIdx, $showstatus);
    $sData=Registry::getInstance()->get('var_sData');
    $sData[$sIdx++]=array('User Type',$cfaUserType[$this->_dbdata['utType']]);
    Registry::getInstance()->register('var_sData',$sData);
    return $sIdx;
  } // end function


} // end class
