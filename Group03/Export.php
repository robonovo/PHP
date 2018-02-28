<?php if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
 * @title      Export class, Export.php
 * @author     Roger Bolser - eNET Innovations, Inc.
 * @copyright  eNET Innovations, Inc. (Roger Bolser) : All Rights Reserved
 *
 */

class Export {

  public $_fields = '';
  public $_where = '';
        
  /**
   * Build a DB object
   */
  public function __construct() { }

  /**
   * Export the entire contents of passed data
   *
   * @param array $thedata the data to export
   * @param array $columns column headers
   * @param string $filename the filename to use foir export
   * @return boolean true on function completion
   */
  public function exportAllCSV( $thedata, $columns='', $filename='data.csv' ) {
    $contents = ob_get_clean();
    while(!empty($contents)) { $contents = ob_get_clean(); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$filename);

    $fh = fopen('php://output', 'w');
    if ($columns!="" && is_array($columns)) {
      $this->putcsv($fh, $columns);
    }

    if (is_array($thedata) && count($thedata)) {
      foreach ($thedata as $key => $val) {
        $values = array();
        foreach ($val as $theitem) { $values[]=$theitem; }
        $this->putcsv($fh, $values);
       } // end foreach ($thedata)
    } // end if (count)

    fclose($fh);
    @ob_end_flush();
    return true;

  }

  /**
   * Start of single-line export - open the file and create headers
   *
   * @param array $columns column headers
   * @param string $filename the filename to use for export
   * @return boolean $fh the file handler for use in other functions
   */
  public function exportOpen ( $columns='', $filename='data.csv' ) {
    $contents = ob_get_clean();
    while(!empty($contents)) { $contents = ob_get_clean(); }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename='.$filename);

    $fh = fopen('php://output', 'w');
    if ($columns!="" && is_array($columns)) {
      $this->putcsv($fh, $columns);
    }

    return $fh;

  }

  /**
   * Export a single record
   *
   * @param object $fh the file handler object
   * @param array $thedata the data to export
   * @return boolean true on function completion
   */
  public function exportItem( $fh, $records ) {
    if (is_array($records) && count($records)) {
      foreach ($records as $record) {
        $values = array();
        foreach ($record as $theitem) { $values[]=$theitem; }
        $this->putcsv($fh, $values);
       } // end foreach ($thedata)
    } // end if (count)

    return true;

  }
	
  /**
   * Close the file handler and flush the contents
   *
   * @param object $fh the file handler object
   * @return boolean true on function completion
   */
  public function exportClose ($fh ) {
     fclose($fh);
    @ob_end_flush();
    return true;
  }

  /**
   * create CSV record
   *
   * @param object $fh - file handler object
   * @param array $fields the data record to export
   * @param string $delimiter the CSV delimiter character (almost always a comma)
   * @param string $enclosure the character to enclose the items (almost always a quote)
   * @return boolean true when function completes
   */
  function putcsv ($fh, array $fields, $delimiter=',', $enclosure='"') {
    $delimiter_esc = preg_quote($delimiter, '/');
    $enclosure_esc = preg_quote($enclosure, '/');

    $output = array();
    foreach ($fields as $field) {
      if ($field === null) {
        $output[] = 'NULL';
        continue;
      }
      $output[]=preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? ( $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure ) : ($enclosure . $field . $enclosure);
    }
    fwrite($fh, implode($delimiter, $output)."\n");
    return true;
  }

} // end of function
?>
