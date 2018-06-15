<?php // if (!defined('validEntry') || !validEntry) die('Not A Valid Entry Point');

/**
  * Upload Processor / Resizer class, Uploader.php
  * @author  Roger Bolser - <roger@eneti.com>
  *
  */

class Uploader {

  /** blacklist of file extensions for uploads */
  private  $_uploadBlacklist = '/bat|exe|cmd|sh|php|pl|cgi|386|dll|com|torrent|js|app|jar|pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|ws|wsc|wsf|wsh/i';

  /** whitelist of file extensions for uploads */
  public $_uploadWhitelist = '/msg|pdf|xls|xlsx|ppt|pptx|doc|docx|jpeg|jpg|png|gif/i';

  /** extension to filename and width (pixels)when creating a thumbnail image */
  public $_thumbExt   = "_thumb";
  public $_thumbWidth = 90;
  
  /** quality number (100=best quality) when creating a resized .jpg image */
  public $_jpgQuality = 90;

  /** min and max numbers when generating a random number for the filename */
  public $_randMin = 100000;
  public $_randMax = 999999;

  /** prefix to use for file uploads (associated with the company)
      will be set in the calling script */	
  public $_prefix = 'prefix';

  /** width in pixels to resize image */
  public $_resizeWidth = 100;

  /** default document/image uploads path */
  public $_uploadsPath = '';

  /** error codes for file uploads */
  protected $_uploadErrors=array (
     0=>'No error ... the file uploaded successfully',
     1=>'File <strong>%filename%</strong> exceeds server maximum filesize',
     2=>'File <strong>%filename%</strong> exceeds form maximum filesize',
     3=>'File <strong>%filename%</strong> was only partially uploaded',
     4=>'No File Uploaded',
     6=>'Missing a temporary folder',
     7=>'Unable to write the file <strong>%filename%</strong> to the server',
     8=>'File upload stopped by extension',
     9=>'File resizing error',
    10=>'Thumbnail resizing error',
		11=>'File <strong>%filename%</strong> type not allowed',
		12=>'File <strong>%filename%</strong> double extensions not allowed'
  );

  /** vars for holding uploaded file data */
  public $_uploadErrCode;        // to hold code when uploading error
  public $_uploadErrText = "";   // to hold text when uploading error
  public $_resizeErrText = "";   // to hold text when error in resizing the image
  public $_newFileName = "";     // the new filename generated to be used in the database
  public $_imageWidth = "";      // width of uploaded or resized image
  public $_imageHeight = "";     // height of uploaded or resized image
  public $_imageType = "";       // image type (1, 2, 3 for images)

  public $_filesArray;           // array to hold file names for multi-upload
 

  /**  __contruct - reset vars if defined in config file */
  public function __construct() {
    if (defined('_THUMB_EXT_')) { $this->_thumbExt=_THUMB_EXT_; }
    if (defined('_THUMB_WIDTH_')) { $this->_thumbWidth=_THUMB_WIDTH_; }
    if (defined('_JPG_QUALITY_')) { $this->_jpgQuality=_JPG_QUALITY_; }
    if (defined('_RAND_MIN_')) { $this->_randMin=_RAND_MIN_; }
    if (defined('_RAND_MAX_')) { $this->_randMax=_RAND_MAX_; }
    if (defined('_RESIZE_WIDTH_')) { $this->_resizeWidth=_RESIZE_WIDTH_; }

    if (defined('_UPLOADS_PATH_')) { $this->_uploadsPath=_UPLOADS_PATH_; }
    else { $this->_uploadsPath=$_SERVER['DOCUMENT_ROOT'].'/uploads/'; }
  } // end __construct()

  /** process the uploaded file */
  public function processUpload ($formName, $oldFile='', $resize=false) {

    $ulError=$_FILES[$formName]['error'];
    if ($ulError!=0) {
      $this->_uploadErrText=str_replace('%filename%',$_FILES[$formName]['name'],$this->_uploadErrors[$ulError]);
      return $this->_uploadErrCode=$ulError;
    }

    // check the file extension(s) (blacklist, whitelist and double-extensions)
    $uploadName=basename($_FILES[$formName]['name']);
    $fileParts = explode(".", $uploadName);
    $partsCnt=count($fileParts);
    $fileSuffix = strtolower(end($fileParts));

    // see if extension in the blacklist
    $extensionError=false;
    if (preg_match($this->_uploadBlacklist,$fileSuffix)) { $extensionError=true; }
		// not in blacklist (that's good) but MUST be in whitelist
    elseif (!preg_match($this->_uploadWhitelist,$fileSuffix)) { $extensionError=true; }
    if ($extensionError) {
      $this->_uploadErrText=str_replace('%filename%',$uploadName,$this->_uploadErrors['11']);
      return $this->_uploadErrCode="11";
    }

    // now check double-extension
    if ($partsCnt>2) {
      $secondNum=$partsCnt-2;
      $secondExt=strtolower($fileParts[$secondNum]);
      if (preg_match($this->_uploadBlacklist,$secondExt)) { // uh oh - bad 2nd extension
        $this->_uploadErrText=str_replace('%filename%',$uploadName,$this->_uploadErrors['12']);
        return $this->_uploadErrCode="12";
      }
    }

    // we have a valid no-error file - process it
    $tempName=$_FILES[$formName]['tmp_name'];
//    $uploadName=basename($_FILES[$formName]['name']);  // var setting moved to above
    $fileName=$this->generateNewName ($uploadName);
		
    // strip out undesirable characters from uploaded filename
    $nonoChars=array("\#","\"","\'","\@","\+","\$");
    $fileName = str_replace($nonoChars,"",$fileName);
		
    $this->_newFileName=$fileName;
    $serverName=$this->_uploadsPath.$fileName;

    // get width, height and file type
    list($currWidth, $currHeight, $imgType, $attr) = getimagesize($tempName);
    if ($imgType=="1" || $imgType=="2" || $imgType=="3") {  // we have an image
      $isImage=true;
      $this->_imageWidth=$currWidth;
      $this->_imageHeight=$currHeight;
      $this->_imageType=$imgType;
      } else {  // upload is not an image
      $isImage=false;
      $this->_imageWidth="";
      $this->_imageHeight="";
      $this->_imageType="";
    }

    $moveFile=false;
    if ($resize) {
      if ($isImage && $this->_imageWidth > $this->_resizeWidth) {
        if (!$this->resize ($tempName, $fileName, $this->_resizeWidth)) {
          $this->_uploadErrText=$this->_uploadErrors['9']."<br />".$this->_resizeErrText;
          return $this->_uploadErrCode="9";
        }
      } else { $moveFile=true; }
    } else { $moveFile=true; } // just move the file - no resizing

    if ($moveFile) {
      if (! @copy($tempName, "$serverName")) {
        if (! @move_uploaded_file($tempName, "$serverName")) {
          $this->_uploadErrText=str_replace('%filename%',$uploadName,$this->_uploadErrors['7']);
          return $this->_uploadErrCode="7";
        }
      }
    }

    // delete old file if this is a new image upload
    if ($oldFile!="") {
      $serverName=$this->_uploadsPath.$oldFile;
      if (file_exists($serverName)) { $unlinkStatus=unlink ($serverName); }
    }

    return $this->_uploadErrCode="0";

  } // end function processUpload


  /**
   * 
   */
  public function processMultiUploads () {

    // first store all form $_FILE names in a temp array
    foreach ($_FILES as $key => $val) { $upFiles[]=$key; }

    // now loop and process each one
    $haveError=false;
    if (isset($upFiles) && count($upFiles)>0) {
      foreach ($upFiles as $fval) {
        $ret=$this->processUpload ($fval, '', false);
        if ($ret=="0") {
          $fileList[$fval]['file']=$this->_newFileName;
          $fileList[$fval]['name']=$fval."_name";
        } elseif ($ret!="4") { // file upload error
          $haveError=true;
          break;
        }
      } // end foreach
    }

    if ($haveError) {
      if (isset($fileList)) {
        foreach ($fileList as $key => $val) { $deleteList[]=$val['file']; }
        $this->removeFiles ($deleteList );
      } 
      return false;
    } else { 
      $this->_filesArray=$fileList;
      return true;
    }
  }

  /** create thumb image */
  public function createThumb ($imageName) {
    $newName=$this->generateThumbName ($imageName );
    $this->_newFileName=$newName;
    $serverName=$this->_uploadsPath.$imageName;
    list($currWidth, $currHeight, $imgType, $attr) = getimagesize($serverName);
    $this->_imageType=$imgType;
    $this->_imageWidth=$currWidth;
    $this->_imageHeight=$currHeight;
    if (!$this->resize ($serverName, $newName, $this->_thumbWidth )) {
      $this->_uploadErrText=$this->_uploadErrors['10']."<br />".$this->_resizeErrText;
      $this->_uploadErrCode="10";
      return false;
    }
    return true;
  }

  /** generate a new name for server purposes (eliminate duplicate names) */
  public function generateNewName ( $currName ) {
    $tna = explode(".", $currName);
//    $tempName = $tna[0];
    $fileParts=count($tna)-1;
    $tempName="";
    for ($xx=0; $xx<$fileParts; $xx++) { $tempName.=$tna[$xx]; }
    $baseName = str_replace(" ","_",$tempName);
    $suffix = ".".strtolower(end($tna));
    $newName = $this->_prefix."_".$baseName."_".mt_rand($this->_randMin,$this->_randMax).$suffix;
    return $newName;
  } // end function generateNewName

  /** generate a thumbnail image name */
  public function generateThumbName ( $currName ) {
    $tna = explode(".", $currName);
//    $baseName = $tna[0];
    $fileParts=count($tna)-1;
    $tempName="";
    for ($xx=0; $xx<$fileParts; $xx++) { $tempName.=$tna[$xx]; }
    $baseName = str_replace(" ","_",$tempName);
    $suffix = ".".strtolower(end($tna));
    $newName = $this->_prefix."_".$baseName.$this->_thumbExt.$suffix;
    return $newName;
  } // end function generateThumbName

  /** global file delete */
  public function removeFiles ( $fileArray )  {
    if (is_array($fileArray) && count($fileArray)>0) {
      foreach ($fileArray as $val) {
        $serverName=$this->_uploadsPath.$val;
        if (file_exists($serverName)) { $unlinkStatus=unlink ($serverName); }
      }
    }
  }

  /** resize the image */
  public function resize ($currImage, $newImage, $resizeWidth) {
    $resizeError=false;
    $currImageName=$currImage;

//    list($currWidth, $currHeight, $imgType, $attr) = getimagesize($currImageName);
    $imgType=$this->_imageType;
    $currWidth=$this->_imageWidth;
    $currHeight=$this->_imageHeight;
    if ($imgType=="1") { $tSource=imagecreatefromgif($currImageName); }
    elseif ($imgType=="2") { $tSource=imagecreatefromjpeg($currImageName); }
    elseif ($imgType=="3") { $tSource=imagecreatefrompng($currImageName); }
    if (!$tSource) {
      $this->_resizeErrText="Resize Error in Step 1 - Image Type Code = ".$imgType;
      $resizeError=true;
    } else {
      $newWidth=$resizeWidth;
      $newHeight=round(($currHeight/$currWidth)*$newWidth);
      $this->_imageWidth=$newWidth;
      $this->_imageHeight=$newHeight;
      $tImage=imagecreatetruecolor($newWidth,$newHeight);
      if (!$tImage) {
        $this->$_resizeErrText="Resize Error in Step 2";
        $resizeError=true;
      } else {
        if (!imagecopyresampled($tImage,$tSource,0,0,0,0,$newWidth,$newHeight,$currWidth,$currHeight)) {
          $this->_resizeErrText="Resize Error in Step 3";
          $resizeError=true;
        } else {
/** code to add cropping to the image
 *   it's not final (just-uncomment-type) code,
 *   just some things that need to be done

if ($cropImage) {
  $croppedWidth="104";
  $croppedHeight="104";
  $cImage=imagecreatetruecolor($croppedWidth, $croppedHeight);
  imagecopy($cImage,$tImage,0,0,0,0,$croppedWidth,$croppedHeight);
}

then the imageXXX code below needs to change for each image type ... ie.
  if ($imgType=="1" && !imagegif($cImage,$newImage)) {
  } elseif ($imgType=="2" && !imagejpeg($cImage,$newImage,$this->_jpgQuality)) {
  } elseif ($imgType=="3" && !imagepng($cImage,$newImage)) {

*/
          $newImage=$this->_uploadsPath.$newImage;
          if (file_exists($newImage)) { unlink($newImage); }
          if ($imgType=="1" && !imagegif($tImage,$newImage)) {
            $this->_resizeErrText="Resize Error in Step 4 - Creating .gif Image";
            $resizeError=true;
          } elseif ($imgType=="2" && !imagejpeg($tImage,$newImage,$this->_jpgQuality)) {
            $this->_resizeErrText="Resize Error in Step 4 - Creating .jpg Image";
            $resizeError=true;
          } elseif ($imgType=="3" && !imagepng($tImage,$newImage)) {
            $this->_resizeErrText="Resize Error in Step 4 - Creating .png Image";
            $resizeError=true;
          }
        } // end if/else (!imagecopyresampled)
      } // end if/else (!tImage)
    } // end if/else (!$tSource)

    imagedestroy($tSource);
    imagedestroy($tImage);
//    chmod("$serverFileName", 0755);

    if ($resizeError) { return false; }
    return true;;
  } // end function resize

} // end class Uploader

?>