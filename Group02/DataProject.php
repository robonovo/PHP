<?php
if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * @title    Project Maintenance class DataProject.php - Project Management
  * @author   Roger Bolser
  * @version  1.0.0.0
  *
  */

class DataProject extends DataModel {

 	/** @var string SQL Table name */
	protected $_table = _TBL_PROJECTS_;

	/** @var string Table Key identifier */
	protected $_key = 'ptKey';

  /** @var array common fields used for each function */
 	protected $_fields = array('ptAlternatives','ptExistingDate','ptFutureDate','ptTitle','ptDescription','ptRevisedBy','ptCompletedBy');

  /** @var array sort fields/params for retrievals */
 	protected $_sort = array('ptTitle ASC');

  /** @var array holding table for db inserts and updates, and delete display info */
 	protected $_dbdata = array();

  /** @var string db field that contains Status code */
  protected $_statusfield = 'ptStatus';

  /** @var array legend to display Status on success screen */
  protected $_statuslegend = array('A'=>'Active', 'I'=>'Inactive', 'C'=>'Completed');

  /** @var array data to display on success screen */
  protected $_success = array('Title'=>'ptTitle','Alternatives'=>'ptAlternatives','LOS Existing Date'=>'ptExistingDate','LOS Future Date'=>'ptFutureDate');


  /**
	 * Validate data ... server-side validation
   *
   * @param array $pVars data from the form
   * @param string $mode to determine how Prev IDs are validated
   */
  public function validateData ( $pVars, $mode='add' ) {
    global $errTable;
    if (($mode=="add") || ($mode=="chg" && $pVars['utPrevEmail']!=$pVars['utEmail'])) {
      $tempTable=DB::getInstance()->getOneRow( "SELECT utEmail FROM "._TBL_USERS_." WHERE utEmail='".pSQL($pVars['utEmail'])."'" );
      if (DB::getInstance()->_numRows>0) {
        $errTable[]="E-Mail already exists";
      }
    }
    return true;
  } // end function


  /**
	 * Add data to the database
   *
   * @param array $pVars data from the form
   */
  public function addData ( $pVars, $snUserKey ) {
    global $cfaProjectTables,$snSuperAdmin,$snAdministrator;

    if ($snSuperAdmin || $snAdministrator) {
      $this->_dbdata['ptStatus']=$pVars['ptStatus'];
      if ($pVars['ptCompletedDate']=="" || $pVars['ptCompletedDate']=="0000-00-00") {
        $this->_dbdata['ptCompletedDate']=NULL;
      } else { $this->_dbdata['ptCompletedDate']=$pVars['ptCompletedDate']; }
    } else { $this->_dbdata['ptStatus']="A"; }
		
    if ($pVars['ptRevisedDate']=="" || $pVars['ptRevisedDate']=="0000-00-00") {
      $this->_dbdata['ptRevisedDate']=NULL;
    } else { $this->_dbdata['ptRevisedDate']=$pVars['ptRevisedDate']; }

    $this->_dbdata['ptKey']="NULL";
    $this->_dbdata['ptUnique']=uniqid('');
    $this->_dbdata['ptSetupDate']=date("Y-m-d H:i:s", time());
    $this->_dbdata['ptCreator']=$snUserKey;

    if (isset($pVars['ptNonStandard']) && $pVars['ptNonStandard']=="Y") {
      $this->_dbdata['ptNonStandard']=$pVars['ptNonStandard'];
      $this->_dbdata['ptExistingIntersections']=$pVars['ptExistingIntersections'];
      $this->_dbdata['ptAlternateIntersections']=$pVars['ptAlternateIntersections'];
    } else {
      $this->_dbdata['ptNonStandard']="";
      $this->_dbdata['ptExistingIntersections']="";
      $this->_dbdata['ptAlternateIntersections']="";
    }

    parent::addData($pVars);

    // save data to registry for use in creating project cookie
    foreach ($this->_dbdata  as $key => $val) {
      $projectData[$key]=$val;
    }
    $projectData['ptKey']=$this->_insertid;
    Registry::getInstance()->register('project_data',$projectData);

    // create the set of blank tables for this project
    $pKey=$projectData['ptKey'];
    $pUnique=$this->_dbdata['ptUnique'];
    $dbi=DB::getInstance();

    foreach ($cfaProjectTables as $key => $val) {
      unset($projTable);
      $projTable[$val['tblkey']]="NULL";
      $projTable[$val['projkey']]=$pKey;
      $projTable[$val['projunique']]=$pUnique;
      $dbi->insertRecord ( $val['tblname'], $projTable );
    }

  } // end function


  /**
	 * retrieve list ... set params and call parent::retrieveList common function
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
    $where="";

    if ($dataTable=parent::retrieveList ( $gVars, $pVars, $pArray, $where, $thisModule )) {
      return $dataTable;
    } else { return false; }
  } // end function


  /**
	 * retrieve selected data ... any special handling before edit screen display
   *
   * @param array $dataTable database table info
   */
  public function retrieveDataModule ( &$dataTable ) {
//    $dcPassword=Encryption::decrypt($dataTable['utPassword']);
//    $returnData['utPassword']=$dcPassword;
//    return $returnData;
    return "";
  } // end function


  /**
	 * update project data ... set params and call parent::updateData common function
   *
   * @param array $pVars data from the form
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function updateData ( $pVars, $fileUpload=false ) {
    global $snSuperAdmin,$snAdministrator,$snUserKey;

    $this->_dbdata['ptKey']=$pVars['ptKey'];
    $dbKeys[]=$this->_key;
    if ($snSuperAdmin || $snAdministrator) {
      $this->_dbdata['ptStatus']=$pVars['ptStatus'];
      if ($pVars['ptCompletedDate']=="" || $pVars['ptCompletedDate']=="0000-00-00") {
        $this->_dbdata['ptCompletedDate']=NULL;
      } else { $this->_dbdata['ptCompletedDate']=$pVars['ptCompletedDate']; }
    }

    if ($pVars['ptRevisedDate']=="" || $pVars['ptRevisedDate']=="0000-00-00") {
      $this->_dbdata['ptRevisedDate']=NULL;
    } else { $this->_dbdata['ptRevisedDate']=$pVars['ptRevisedDate']; }

    if (isset($pVars['ptNonStandard']) && $pVars['ptNonStandard']=="Y") {
      $this->_dbdata['ptNonStandard']=$pVars['ptNonStandard'];
      $this->_dbdata['ptExistingIntersections']=$pVars['ptExistingIntersections'];
      $this->_dbdata['ptAlternateIntersections']=$pVars['ptAlternateIntersections'];
    } else {
      $this->_dbdata['ptNonStandard']="";
      $this->_dbdata['ptExistingIntersections']="";
      $this->_dbdata['ptAlternateIntersections']="";
    }

    parent::updateData ( $pVars, $dbKeys, $fileUpload );
    return true;
  } // end function


  /**
	 * delete project data ... set params and call parent::deleteData common function
   *
   * @param integer $thisKey key of record to delete
   * @param boolean $fileupload  true=have file uploads ... process upload table
   */
  public function deleteData ( $thisKey, $fileUpload=false ) {
    global $snSuperAdmin,$snUserKey;

    $dbKeys[$this->_key]=$thisKey;
    if (!$snSuperAdmin) { $dbKeys['utUserType']="U"; }

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

    $sIdx=parent::setSuccess( $sIdx, $showstatus);
    $sData=Registry::getInstance()->get('var_sData');
    Registry::getInstance()->register('var_sData',$sData);
    return $sIdx;
  } // end function


} // end class
