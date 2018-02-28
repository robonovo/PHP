<?php
// session_start();
$commonpath = realpath('../../xxxxxx');
require($commonpath . "/xxxxxx.inc");

function php_include_prefix() {
  $path = substr($_SERVER['PHP_SELF'],1,strlen($_SERVER['PHP_SELF']));
  while (!strpos($path,"/") == false) {
    $cnt++;
    $path = substr(strstr($path,"/"),1,strlen(strstr($path,"/")));
  }
  for ($i=0;$i<$cnt;$i++) { $prefix .= "../"; }
  return $prefix;
}
$incPrefix = php_include_prefix();

require('/home/httpd/vhosts/xxxxxx.com/httpdocs/xxxxxx/xxxxxx/database.class.php');
$dbi = new database;

echo "making the e-mail box connection<br>";
$mbox = imap_open ("{mail.xxxxxx.com:110/pop3/novalidate-cert}INBOX", "email.blast", "xxxxxxxx");

if (!$mbox) {
  echo "mailbox connection failed - process terminated<br>";

} else {

  echo "mailbox connection successful<br>";
  echo "getting number of messages in mailbox<br>";
  $totalMessages=imap_num_msg($mbox);
  echo $totalMessages." messages found in mailbox<br>";
  if ($totalMessages>0) {
    echo "processing ".$totalMessages." messages<br>";
    for ($i=1; $i<=$totalMessages; $i++) {
      $header = imap_header($mbox, $i);
      $oSubject=str_replace("'","",$header->Subject);
      $msgdate=str_replace("'","",$header->Date);
      $oDate=date("Y-m-d H:i:s",strtotime($msgdate));
      $body=imap_fetchbody($mbox,$i,1,FT_PEEK);
      $hbody=htmlentities(imap_fetchbody($mbox,$i,FT_PEEK));
  		$rbody=html_entity_decode($hbody);
      $htmlPos=strpos(strtolower($rbody),'<html>');
      if ($htmlPos==0) {
        echo "message #".$i." skipped - can't decode<br>";
        continue;
      }

      $dOut=strpos(strtolower($rbody),'xxxxx/marketing');
      if ($dOut>0) {
        $dIn=strpos(strtolower($rbody),'</body>');
        $goodHtml=substr($rbody,0,$dOut).substr($rbody,$dIn);
      } else { $goodHtml=$rbody; }
      $sPos=strpos($body,'----------');
      $summText=trim(substr($body,0,$sPos));

      // create db entry
      $oTbl['baKey']="NULL";
      $oTbl['baDate']=$oDate;
      $oTbl['baSubject']=$oSubject;
      $oTbl['baText']=$summText;
      $oTbl['baHtml']=$goodHtml;
      $dbi->insertRecord ( 'blastArchive', $oTbl );
      echo "added message #".$i." to the database<br>";
    } // end for loop

    echo "flagging messages for deletion<br>";
    for ($i=1; $i<=$totalMessages; $i++) {
      imap_delete($mbox,$i);
    }
    echo "removing messages from the mailbox<br>";
    imap_expunge($mbox);

    echo "closing mailbox connection<br>";
    imap_close($mbox);

    echo "process terminated successfully<br>";
	
  } else {

    echo "no messages in mailbox - process terminated";

  } // end if/else ($totalMessages>0)

} // end if/else (!$mbox)
exit;

