<?php if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * MySQL class, Database.php
  *
  * @author Roger Bolser - <roger@eneti.com>
  *
  */

abstract class DB {

  /** @var string Server (eg. localhost) */
  protected $_host;

  /** @var string Database user (eg. root) */
  protected $_user;

  /** @var string Database password (eg. can be empty !) */
  protected $_password;

  /** @var string Database type (MySQL, PgSQL) */
  protected $_type;

  /** @var string Database name */
  protected $_database;

  /** @var string SQL statement to execute */
  public $_query;

  /** @var string ID if inserted record */
  public $_insertId;

  /** @var integer Number rows returned on query */
  public $_numRows;

  /** @var integer Number rows affected on updates */
  public $_affectedRows;

  /** @var mixed Resource link */
  protected $_link;

  /** @var mixed SQL cached result */
  protected $_result;

  /** @var mixed ? */
//  protected static $_db;

  /** @var strings vars used in error e-mails and displays */
  private static $_errSubject;   // e-mail subject
  private static $_errBody;      // e-mail body text
  private static $_errDisplay;   // screen message display
  
  /** @var mixed Object instance for singleton */
  private static $_instance;

  /** @var string Keywords not allowed in SQL string (Possible Hack Attempts) */
  private static $_blacklist = '/UNION|CONCAT|LOAD_FILE|OUTFILE|DUMPFILE|ESCAPED|TERMINATED|CASCADE|INFILE|X509|TRIGGER|REVOKE|DECLARE|EXEC/';

  /**
   * Build a DB object
   */
  public function __construct() {
    $this->_host = _DB_HOST_;
    $this->_user = _DB_USER_;
    $this->_password = _DB_PASSWD_;
    $this->_type = _DB_TYPE_;
    $this->_database = _DB_NAME_;
    $this->connect();
  }

  public function __destruct() {
    $this->disconnect();
  }


  /**
   * Get DB object instance (Singleton)
   *
   * @return object DB instance
   */
  public static function getInstance() {
    if(!isset(self::$_instance)) { self::$_instance = new Database(); }
    return self::$_instance;
  }

  /**
   * Filter SQL query within a blacklist
   *
   * @param string $sqlStmt the sql statement used in db calls
   * @return boolean false if no hack attempt
   */
  public static function blacklist( &$sqlStmt ) {
    if (preg_match(self::$_blacklist, $sqlStmt)) {  // hack attempt
      self::$_errSubject = "Hack Attempt in ".$_SERVER['PHP_SELF'];
      $message  = "A DB Hack Attempt occurred:\n\n";
      $message .= "Host Name: ".$_SERVER['HTTP_HOST']."\n";
      $message .= "Program Name: ".$_SERVER['PHP_SELF']."\n";
      $message .= "Referrer: ".$_SERVER['HTTP_REFERER']."\n";
      $message .= "SQL Statement: ".$sqlStmt."\n\n";
      self::$_errBody = $message;
      self::$_errDisplay = "<b>*** Hack Attempt Has Been Blocked ***</b>";
      self::sqlDie (_EMAIL_HACK_ATTEMPTS_);
    }
    return false;
  }

  /**
   * sqlExit - database SQL error
   *
   * @param string $sqlStmt the SQL statement
   * @param string $sqlErrno SQL Error Number (generated by mySQL)
   * @param string $sqlError SQL Error Text (generated by mySQL)
   */
  public static function sqlExit ( $sqlStmt, $sqlErrno='', $sqlError='' )  {
    self::$_errSubject = _DB_TYPE_." Database Error In ".$_SERVER['PHP_SELF'];
    $message  = "An error occurred in the "._DB_TYPE_." database:\n\n";
    $message .= "Host Name: ".$_SERVER['HTTP_HOST']."\n";
    $message .= "Program Name: ".$_SERVER['PHP_SELF']."\n";
    $message .= "Referrer: ".$_SERVER['HTTP_REFERER']."\n";
    $message .= "SQL Statement: ".$sqlStmt."\n";
    $message .= _DB_TYPE_." Error Code: " .$sqlErrno."\n";
    $message .= _DB_TYPE_." Error Description: ".$sqlError."\n\n";
    self::$_errBody = $message;

    $message  = "<b>*** Database Error - Please Note ***</b><br /><br />\n";
    $message .= "An error has occurred in the Database.<br />\n";
    $message .= "Program Information: ".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."<br />\n";
    $message .= "Please send an e-mail to the Webmaster with this error information:<br />\n";
    $message .= "<a href=\"mailto:"._DB_ERROR_EMAIL_."\">"._DB_ERROR_EMAIL_."</a>.<br /><br />\n";
    $message .= "Please return to this screen later and retry your action.<br />";
    $message .= "We are sorry for the inconvenience";
    self::$_errDisplay = $message;

    self::sqlDie (_EMAIL_DB_ERRORS_);
  }

  /**
   *  sqlDie - Database Error
   *
   *  send an e-mail message to the administrator (if configured)
   *  display the error on the screen
   *  end the script
   * 
   *  @param boolean $sendEamil true=send e-mail ... false do NOT send e-mail
   */
  public static function sqlDie ( $sendEmail=true )  {
    $mailError=false;
    if ($sendEmail) {
      $from = _DB_ERROR_EMAIL_;
      $to = _DB_ERROR_EMAIL_;
      $subject = self::$_errSubject;
      $message = self::$_errBody;
      if (!mail($to, $subject, $message, "From: $from")) $mailError=true;
    }
    $message  = "<br />\n";
    $message .= "<div align=\"center\">\n";
    $message .= self::$_errDisplay."<br /><br />";
    $message .= "Proceed to the <a href=\""._THIS_URL_."\">Home Page</a><br /><br />";
    $message .= "</div>";
    echo $message;
    exit;
  }

  /*********************************************************
   * ABSTRACT METHODS
   *********************************************************/

  abstract public function connect();

  abstract public function disconnect();

}

class Database extends DB {

  public function connect() {
    $this->_link = @mysql_connect( $this->_host, $this->_user, $this->_password );
    if (!$this->_link) { $this->mySqlDie('db connect'); }
    mysql_select_db( $this->_database, $this->_link ) or $this->mySqlDie('db select');

    /* UTF-8 support */
    mysql_query('SET NAMES \'utf8\'', $this->_link);
    return $this->_link;
  }

  public function disconnect() {
    if ($this->_link) { mysql_close($this->_link); }
    $this->_link = false;
  }

  public function getOneRow ( $query ) {
    if (parent::blacklist($query)) { return false; }
    if ($this->_link) {
      $this->_result = mysql_query($query.' LIMIT 1', $this->_link ) or $this->mySqlDie( $query );
      $this->_numRows = mysql_num_rows($this->_result);
      $list = mysql_fetch_assoc ( $this->_result );
      mysql_free_result( $this->_result );
      return $list;
    }
    return false;
  }

  public function getAllRows ( $query, $limit=false ) {
    if (parent::blacklist($query)) { return false; }
    if ($limit) { $query.=' LIMIT '.$limit; }
    if ($this->_link) {
      $this->_result = mysql_query( $query, $this->_link ) or $this->mySqlDie( $query );
      $this->_numRows = mysql_num_rows( $this->_result );
      $list = array();
      while ($rows = mysql_fetch_assoc ( $this->_result )) {
        $list[] = $rows;
      }
      mysql_free_result( $this->_result );
      return $list;
    }
    return false;
  }

  public function getCount ( $table, $where='' ) {
    $query="SELECT COUNT(*) as number FROM ".$table;
    if (!empty($where)) { $query.=" WHERE ".$where; }
    if ($this->_link) {
      $this->_result = mysql_query($query, $this->_link ) or $this->mySqlDie( $query );
      $result = mysql_fetch_assoc ( $this->_result );
      return $result['number'];
    }
    return false;
  }

  public function qry ( $query ) 	{
    if (parent::blacklist($query)) { return false; }
    $this->_result = false;
    if ($this->_link) {
      $qryresult = mysql_query($query, $this->_link) or $this->mySqlDie($query);
      return $qryresult;
    }
    return false;
  }

  public function qryCount ( $sql ) {
    if ($this->_result = $this->qry($sql)) {
      $this->_numRows = mysql_num_rows($this->_result);
      return $this->_numRows;
    }
    return false;
  }

  public function insertRecord ( $db, $table ) {
    $fmtsql = "INSERT INTO $db ( %s ) VALUES ( %s ) ";
    foreach ($table as $k => $v) {
      if (is_array($v) or is_object($v) or $v === NULL) { continue; }
      $fields[] = "`$k`";
      if ($v == 'NULL') { $values[] = $v; }
      else { $values[] = "'" . csSQL( $v ) . "'"; }
    } // end foreach
    $sql = sprintf( $fmtsql, implode( ",", $fields ) ,  implode( ",", $values ) ) ;
    $this->_result = $this->qry($sql);
    $this->_insertId = mysql_insert_id();
    return $this->_insertId;
  }

  public function updateRecord ( $db, $table, $keys, $updateNulls=true ) {
    $fmtsql = "UPDATE $db SET %s WHERE %s";
    foreach ($table as $k => $v) {
      if( is_array($v) or is_object($v)) { continue; }
      if( is_array($keys) && in_array( $k, $keys ) ) {
        $where[] = "$k='" . csSQL( $v ) . "'";
        continue;
      } elseif ( $k == $keys ) {
        $where[] = "$k='" . csSQL( $v ) . "'";
        continue;
      }
      if ($v === NULL && !$updateNulls) { continue; }
      if ($v === NULL) { $val='NULL'; }
      elseif( $v == '' ) { $val = "''"; }
      else { $val = "'" . csSQL( $v ) . "'"; }
      $tmp[] = "`$k`=$val";
    }
    $sql = sprintf( $fmtsql, implode( ",", $tmp ) , implode( " AND ", $where ) );
    $this->_result = $this->qry($sql);
    $this->_affectedRows = mysql_affected_rows();
    return $this->_affectedRows;
  }

  public function updateRecordNative ( $db, $table, $keys, $updateNulls=true ) {
    $fmtsql = "UPDATE $db SET %s WHERE %s";
    foreach ($table as $k => $v) {
      if( is_array($v) or is_object($v)) { continue; }
      if( is_array($keys) && in_array( $k, $keys ) ) {
        $where[] = "$k='" . csSQL( $v ) . "'";
        continue;
      } elseif ( $k == $keys ) {
        $where[] = "$k='" . csSQL( $v ) . "'";
        continue;
      }
      if ($v === NULL && !$updateNulls) { continue; }
      if( $v == '' ) { $val = ""; }
      else { $val = $v; }
      $tmp[] = "$k=$val";
    }
    $sql = sprintf( $fmtsql, implode( ",", $tmp ) , implode( " AND ", $where ) );
    $this->_result = $this->qry($sql);
    $this->_affectedRows = mysql_affected_rows();
    return $this->_affectedRows;
  }

  public function deleteRecord ( $db, $keys ) {
    $fmtsql = "DELETE FROM $db WHERE %s";
    foreach ($keys as $k => $v) {
      if( is_array($v) or is_object($v)) { continue; }
      $val = "'" . csSQL( $v ) . "'";
      $tmp[] = "$k=$val";
    }
    $sql = sprintf( $fmtsql, implode( " AND ", $tmp ) );
    $this->_result = $this->qry($sql);
    $this->_affectedRows = mysql_affected_rows();
    return $this->_affectedRows;
  }

  public function lockTable ( $db, $lock='WRITE' ) {
    $sql="LOCK TABLES $db $lock";
    $this->_result = mysql_query( $sql, $this->_link ) or $this->mySqlDie( $sql );
	return true;
  }

  public function unlockTable ( $db ) {
    $sql="UNLOCK TABLES";
    $this->_result = mysql_query( $sql, $this->_link ) or $this->mySqlDie( $sql );
    return true;
  }

  public function numRows() {
    if ($this->_link) { return $this->_numRows; }
    return false;
  }

  public function insertId() {
    if ($this->_link) { return $this->_insertId; }
    return false;
  }

  public function affectedRows( $find='' ) {
    if ($this->_link) {
      if ($find=='find') { $this->_affectedRows = mysql_affected_rows(); }
      return $this->_affectedRows;
    }
    return false;
  }

  public function errorMessage() {
    return mysql_error();
  }

  public function errorNumber() {
    return mysql_errno();
  }

  private function mySqlDie ( $sql ) {
    parent::sqlExit ( $sql, $this->errorNumber(),  $this->errorMessage() );
  }

}

?>