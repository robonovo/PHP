<?php
/**
  * Pagination calculation class Paginate.php
  * Set params for pagination (prev/next) on pages
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
  *
  */

class Paginate {

  /** @var mixed Object instance for singleton */
  private static $_instance;

  /** @var integers for prev/next navigation */
  public $_lcLimitStart=0;    /* for page x of y, used for setting starting record */
  public $_lcLimitNumber=0;   /* for page x of y, used for setting number of recs to retrieve */
  public $_lcDataStart=0;     /* for 'rec x to y of z total recs' this is value x */
  public $_lcDataEnd=0;       /* for 'rec x to y of z total recs' this is value y */
  public $_lcDataTotal=0;     /* for 'rec x to y of z total recs' this is value z */
  public $_lcCurrentPage=0;   /* for 'page a of b' this is value a */
  public $_lcNumberPages=0;   /* for 'page a of b' this is value b */

  /** @var boolean  this class will set to 'true' if multi-page */
  public $_lcDoNavigation;

  /** @var string 'prev/next' text for navigation */
  public $_lcNavLine="";

  /** @var string url GET params */
  public $_lcParams="";

  /** @var mixed parameters needed for pagination calculations */
  public $_lcNavWidth=600;   /* width of the nav line ... default 600px */
  public $_lcCssTotal;       /* css class for 'total recs' line */
  public $_lcCssPage;        /* css class for 'page x of y' line */
  public $_lcMinpp=10;       /* minimum rows per page ... default 10 */
  public $_lcMaxpp=100;      /* maximum rows per page ... default 100 */
  public $_lcTotRecs;        /* total records in the db table */

  /** @var string sql statement for db retrieval ... set in calling script */
  public $_sql="";


  /**
   * Constructor - build object
   */

  public function __construct() { }

  /**
   * Get Paginate object instance (Singleton)
   *
   * @return object instance
   */
  public static function getInstance() {
    if(!isset(self::$_instance)) { self::$_instance = new Paginate(); }
    return self::$_instance;
  }

  /**
   * Set Paginate params
   *
   * @param array parameters for this page
   */
  public function setParams ( $pArray=array() ) {
    if (isset($pArray['width'])) { $this->_lcNavWidth=$pArray['width']; }
    if (isset($pArray['csstotal'])) { $this->_lcCssTotal=$pArray['csstotal']; }
    if (isset($pArray['csspage'])) { $this->_lcCssPage=$pArray['csspage']; }
    if (isset($pArray['minpp'])) { $this->_lcMinpp=$pArray['minpp']; }
    if (isset($pArray['maxpp'])) { $this->_lcMaxpp=$pArray['maxpp']; }
    if (isset($pArray['totrecs'])) { $this->_lcTotRecs=$pArray['totrecs']; }
  } // end function


  /**
   * set navigation method
   *
   * @param array $gVars page $_GET values
   * @param array $pvars page $_POST values
   * @param boolean $vSetParams 'true'=set params for url/form posts
   * @return array db rows
   */
  public function setNavigation ($gVars, $pVars, $vSetParams=true ) {
    $vMaxPage=$this->_lcMaxpp;
    $vMinPage=$this->_lcMinpp;
    $vMaxCheck=$vMinPage+$vMaxPage-1;
    $vTotRecs=$this->_lcTotRecs;

    $this->_lcDoNavigation=false;
    if ($vSetParams) {
      foreach ($gVars as $key => $val) {
        if ($key=="pg") { continue; }
        $wVars[]=$key."=".$val;
      }
      if ($wVars) { $this->_lcParams=implode( "&", $wVars ); }
    }
    if (isset($gVars['pg'])) { $vPage=$gVars['pg']; }
    elseif (isset($pVars['pg'])) { $vPage=$pVars['pg']; }
    else { $vPage=0; }
    if ($this->checkMulti ( $vPage, $vTotRecs, $vMaxPage, $vMaxCheck )) {
      $this->buildNavLine ();
      $this->_lcDoNavigation=true;
      $this->_sql.=" LIMIT ".$this->_lcLimitStart.",".$this->_lcLimitNumber;
    }
    $dTable=DB::getInstance()->getAllRows( $this->_sql );
    return $dTable;
  } // end function setNavigation


  /**
   * check if multiple pages for this list
   *
   * @param integer $cPages this page number
   * @param integer $cTotrecs total records in the db table
   * @param integer $cMaxRows max rows per page
   * @param integer $cMaxCheck number rows for last page checking
   * @return boolean  'true'=have multiple pages .. 'false'=single page
   */
  public function checkMulti ( $cPage, $cTotRecs, $cMaxRows, $cMaxCheck ) {
    if (!is_numeric($cPage)) { $cPage=0; }
    else { $cPage=(int)$cPage; }

    $this->_lcNumberPages=1;
    $vAddlNumber=$cTotRecs-$cMaxCheck;
    $vRemainder=$vAddlNumber%$cMaxRows;
    if ($vRemainder > 0) { $this->_lcNumberPages+=(int)($vAddlNumber/$cMaxRows+1); }
    else { $this->_lcNumberPages+=(int)($vAddlNumber/$cMaxRows); }

    if (($cPage-1) < 0)  { $cPage=0; }
    elseif ($cPage > $this->_lcNumberPages)  { $cPage=$this->_lcNumberPages-1; }
    else { $cPage--; }

    $this->_lcCurrentPage=$cPage+1;
    if ($this->_lcCurrentPage>$this->_lcNumberPages) { $this->_lcCurrentPage=$this->_lcNumberPages; }

    if ($cTotRecs <= $cMaxCheck) {
      $this->_lcDataTotal=$cTotRecs;
      $this->_lcDataStart=1;
      $this->_lcDataEnd=$cTotRecs;
      return false;
    }
    $this->_lcLimitStart=$cPage*$cMaxRows;
    if (($this->_lcLimitStart+$cMaxCheck) < $cTotRecs) {
      $this->_lcLimitNumber=$cMaxRows;
    } else {
      $this->_lcLimitNumber=$cMaxCheck;
    }

    $this->_lcDataTotal=$cTotRecs;
    $this->_lcDataStart=$this->_lcLimitStart+1;
    $this->_lcDataEnd=($this->_lcDataStart+$this->_lcLimitNumber)-1;
    if ($this->_lcDataEnd > $cTotRecs)  { $this->_lcDataEnd=$cTotRecs; }

    return true;
  } // end function


  /**
   * build prev/next navigation
   *
   * @return boolean 'true' nav line built and stored in $_lcNavLine
   */
  public function buildNavLine () {
    $nLine="";

    if( $this->_lcCurrentPage-1 > 0 )  {
      $nLine.="&laquo; <a ";
      if ($this->_lcCssPage!="") { $nLine.="class=\"".$this->_lcCssPage."\" "; }
      $nLine.="href=\"".$_SERVER['PHP_SELF']."?pg=".($this->_lcCurrentPage-1);
      if ($this->_lcParams!="") { $nLine.="&".$this->_lcParams; }
      $nLine.="\">Prev</a>";
    }  else  { $nLine.="Prev"; }
    $nLine.=" &nbsp;| &nbsp;";

    if( $this->_lcCurrentPage < $this->_lcNumberPages ) {
      $nLine.="<a ";
      if ($this->_lcCssPage!="") { $nLine.="class=\"".$this->_lcCssPage."\" "; }
      $nLine.="href=\"".$_SERVER['PHP_SELF']."?pg=".($this->_lcCurrentPage+1);
      if ($this->_lcParams!="") { $nLine.="&".$this->_lcParams; }
      $nLine.="\">Next</a> &raquo;";
    } else  { $nLine.="Next"; }

    $this->_lcNavLine=$nLine;
    return true;
  } // end function

}  // end class
?>