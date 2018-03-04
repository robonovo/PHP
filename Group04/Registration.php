<?php
/**
  * Registration class - Registration handler
  * To handle all functions when a user registers (signs up) with the website
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
  *
  */

class registration {

  var $userTable=array();
  var $userId=0;
  var $uniqueId="";
  var $mapperId="";

  // sets common data in table for db add or update - only called internally in this class
  function setCommonData ( $pVars ) {

    // set common fields in USER table
    $this->userTable['LOGIN_ID']=$pVars['ffLoginId'];
    $this->userTable['PASSWORD']=encryptDecrypt ($pVars['ffPassword']);
    $this->userTable['LAST_NAME']=$pVars['ffLastName'];
    $this->userTable['FIRST_NAME']=$pVars['ffFirstName'];
    $this->userTable['DISPLAY_NAME']=$pVars['ffDisplayName'];
    $this->userTable['EMAIL']=$pVars['ffEmail'];
    $this->userTable['COUNTRY']=$pVars['ffCountry'];
    $this->userTable['STATE']=$pVars['ffState'];
    $this->userTable['CITY']=$pVars['ffCity'];
    $this->userTable['POSTAL_CODE']=$pVars['ffPostalCode'];
    $this->userTable['GENDER']=$pVars['ffGender'];
    $this->userTable['DOB']=$pVars['ffBirthYear']."-".$pVars['ffBirthMonth']."-".$pVars['ffBirthDay'];
    if ($pVars['ffKeepInformed']=="Y") { $this->userTable['NEWSLIST']="Y"; }
    else { $this->userTable['NEWSLIST']="N"; }

  } // end function setCommonData


  // validate registration screen ... validation that can only be done server-side
  // most initial validation (ie. field is required, valid date, etc.) is done via JavaScript
  function validateRegistration ( $pVars, $sMode='add' ) {
    global $dbi,$dbUsers,$snVals,$errTable,$errIdx,$captchaImageCode;

    // Display Name must be unique ... checked on add -or- on edit if user enters a different name
    if (($sMode=="add") || ($sMode=="edit" && $pVars['ffDisplayName']!=$snVals['displayName'])) {
      $dbi->sql="SELECT DISPLAY_NAME FROM $dbUsers WHERE DISPLAY_NAME='".$pVars['ffDisplayName']."'";
      $tempTable=$dbi->getOneRecord();
      if ($dbi->numRecords>0) {
        $errTable[$errIdx++]="<b>Display Name</b> already exists";
      }
    }

    // Login ID must be unique ... checked on add -or- on edit if user enters a different id
    if (($sMode=="add") || ($sMode=="edit" && $pVars['ffLoginId']!=$snVals['loginId'])) {
      $dbi->sql="SELECT LOGIN_ID FROM $dbUsers WHERE LOGIN_ID='".$pVars['ffLoginId']."'";
      $tempTable=$dbi->getOneRecord();
      if ($dbi->numRecords>0) {
        $errTable[$errIdx++]="<b>Login ID</b> already exists";
      }
    }

    // validate e-mail address to a working MX server
    if ($pVars['ffEmail']!="") {
      list($user, $domain) = split("@", $email, 2);
      if (!checkdnsrr($domain, "MX")) {
        $errTable[$errIdx++]="<b>Your E-Mail Address</b> did not resolve to a working server";
      }
    }

    if ($sMode=="add") {  // validate captcha ... only checked on add
      $turingCode = $pVars['captchaTuring'];
      if ( checkCaptcha($turingCode)==1 && $captchaImageCode=="ok") { $tok=true; }
      else {
        $errTable[$errIdx++]="<b>Spam Prevention Characters</b> were not entered correctly";
      }
    }

  } // end function validateRegistration

  // add a new registration entry to the db tables
  function addRegistration ( $pVars ) {
    global $dbi,$dbUsers,$dbUmapper;
    $this->setCommonData( $pVars );

    // add registrant to USERS table
    $this->userTable['USER_ID']="NULL";
    $this->userTable['UNIQUE_ID']=uniqid(rand(0,9));
    $this->uniqueId=$this->userTable['UNIQUE_ID'];
    $this->userTable['EM_CONFIRMED']="N";
    $this->userTable['MEMBER_LEVEL']="Basic";
    $this->userTable['NUMBER_ROUTES']=0;
    $this->userTable['IMAGES']="";
    $this->userTable['SETUP_DATE']=date("Y-m-d H:i:s", time());
    $this->userTable['MODIFY_DATE']=NULL;
    $this->userTable['LAST_LOGIN']=NULL;
    $this->userTable['LOGIN_COUNT']=0;
    $this->userId=$dbi->insertRecord ( $dbUsers, $this->userTable );

    // add user to USERS_MAPPER table
    $mapTable['M_MAPPER_ID']="NULL";
    $mapTable['M_SID']=md5(uniqid(rand(), true));
    $this->mapperId=$mapTable['M_SID'];
    $mapTable['M_FK_UNIQUE_ID']=$this->uniqueId;
    $mapTable['M_FK_USER_ID']=$this->userId;
    $dbi->insertRecord ( $dbUmapper, $mapTable );

    // send confirmation e-mail to user
    $this->sendEmail ( $pVars );

  } // end function addRegistration


  // send confirmation e-mail to user, to validate their e-mail address
  function sendEmail ( $pVars ) {
    global $cfRegEmail,$cfValidationUrl;

    $from = $cfRegEmail;
    $to=$pVars['ffEmail'];
    $subject = "xxxxxxxxxx.com Registration Confirmation";
    $message="Dear ".$pVars['ffFirstName']." ".$pVars['ffLastName'].":\n\n";
    $message.="This message has been generated to confirm your registration with xxxxxxxxxx.com.  ";
    $message.="Your registration details are as follows:\n\n";
    $message.="Login ID: ".$pVars['ffLoginId']."\n";
    $message.="Password: ".$pVars['ffPassword']."\n\n";
    $message.="Please click here (".$cfValidationUrl;
//    $message.=$this->uniqueId.$this->userId;
    $message.=$this->mapperId;
    $message.=") to confirm your e-mail address.  If the above link does not work, ";
    $message.="please copy and paste the entire link into your browser.\n\n";
    $message.="If you feel you were sent this email in error, ";
    $message.="please contact us at ".$cfRegEmail.".\n\n";
    $message.="Welcome to xxxxxxxxxx.com !\n\n";
    if (!mail($to, $subject, $message, "From: $from")) $mailError=true;
  } // end function sendEmail


  // retrieve current registration data from database
  // used for both registration edit --and-- to re-send reg validation e-mail to user  
  function retrieveRegistration ( $vUniqueId, $vUserId ) {
    global $dbi,$dbUsers,$dbCountries,$dbStates;
    global $countryTable,$stateTable;

    // retrieve registration data
    $formTable=array();
    $dbi->sql="SELECT * FROM $dbUsers WHERE UNIQUE_ID='".$vUniqueId."' AND USER_ID='".$vUserId."'";
    $userTable=$dbi->getOneRecord();
    if ($dbi->numRecords>0) {

      // set fields from USERS table
      $formTable['ffLoginId']=$userTable['LOGIN_ID'];
      $formTable['ffUniqueId']=$userTable['UNIQUE_ID'];
      $formTable['ffPassword']=encryptDecrypt($userTable['PASSWORD'],'decrypt');
      $formTable['ffConfirmPassword']=$formTable['ffPassword'];
      $formTable['ffDisplayName']=$userTable['DISPLAY_NAME'];
      $formTable['ffLastName']=$userTable['LAST_NAME'];
      $formTable['ffFirstName']=$userTable['FIRST_NAME'];
      $formTable['ffEmail']=$userTable['EMAIL'];
      $formTable['ffGender']=$userTable['GENDER'];
      $formTable['ffCountry']=$userTable['COUNTRY'];
      $formTable['ffState']=$userTable['STATE'];
      $formTable['ffCity']=$userTable['CITY'];
      $formTable['ffPostalCode']=$userTable['POSTAL_CODE'];
      $bdArray=explode("-",$userTable['DOB']);
      $formTable['ffBirthYear']=$bdArray[0];
      $formTable['ffBirthMonth']=$bdArray[1];
      $formTable['ffBirthDay']=$bdArray[2];
      $formTable['ffKeepInformed']=$userTable['NEWSLIST'];

    } else { return false; }  // not found - return false

    // retrieve states for selected country
    $dbi->sql="SELECT * FROM $dbStates WHERE stCountry='".$userTable['COUNTRY']."' ORDER BY stSequence ASC, stName ASC";
    $stateTable=$dbi->getAllRecords();

    return $formTable;
  } // end function retrieveRegistration 

  // update current user registration data
  function updateRegistration ( $pVars, $resetCookie=false ) {
    global $dbi,$dbUsers;

    // first get the registration record to make sure we have valid keys
    $dbi->sql="SELECT * FROM $dbUsers WHERE UNIQUE_ID='".$pVars['ffUniqueId']."' AND USER_ID='".$pVars['ffUserId']."'";
    $userData=$dbi->getOneRecord();
    if ($dbi->numRecords>0) {
      $this->setCommonData( $pVars );

      // update REGISTRANT table
      $this->userTable['USER_ID']=$userData['USER_ID'];
  	  $userKeys=array('USER_ID');
      $dbi->updateRecord ( $dbUsers, $this->userTable, $userKeys );

      // if display name has changed, update the cookie
      if ($resetCookie) {
        $regValue['UNIQUE_ID']=$userData['UNIQUE_ID'];          // 14-char unique id for user
        $regValue['USER_ID']=$userData['USER_ID'];              // key to tbl_USERS
        $regValue['LOGIN_ID']=$pVars['ffLoginId'];              // new login id
        $regValue['DISPLAY_NAME']=$pVars['ffDisplayName'];      // new display name
        $regValue['MEMBER_LEVEL']=$userData['MEMBER_LEVEL'];    // membership level
        $regValue['NUMBER_ROUTES']=$userData['NUMBER_ROUTES'];  // number of routes entered
        $regValue['SETUP_DATE']=$userData['SETUP_DATE'];        // date registered (for 'member since')
        setCookies ( $regValue, false );
      }

    } // end if ($dbi->numRecords>0)

  } // end function updateRegistration

} // end class registration
?>