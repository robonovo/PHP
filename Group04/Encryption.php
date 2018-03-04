<?php
/**
  * Encrypt class, Encrypt.php
  * Encryption / Decryption methods
  *
  * @author eNET Innovations, Inc. - Roger Bolser - <roger@eneti.com>
  *
  */

class Encryption {

  /** @var string */
  private static $_ok;

  /** @var string */
  private static $_td;

  /** @var string */
  private static $_algorithm;

  /** @var integer */
  private static $_mode;

  /** @var string */
  private static $_key;

	/**
	 * Initialize encryption params
	 */
  private function setKeys() {
    self::$_algorithm = _MCRYPT_ALGORITHM_;
    self::$_mode = _MCRYPT_MODE_;
    self::$_key = _MCRYPT_KEY_;
    self::$_ok = 'ok';
  }

  public static function encrypt( $data ) {

    if(self::$_ok != 'ok') { self::setKeys(); }

    // open the cipher
    self::$_td = mcrypt_module_open(self::$_algorithm, '', self::$_mode, '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size(self::$_td), MCRYPT_RAND);

    // determine the keysize length
    $ks = mcrypt_enc_get_key_size(self::$_td);

    // create key
    $key = substr(md5(self::$_key), 0, $ks);

    // initialize the encryption module
    $s = mcrypt_generic_init(self::$_td, $key, $iv);
    if( ($s < 0) || ($s === false)) { return false; }

    // encrypt the data
    $encryptedText = mcrypt_generic(self::$_td, $data);
    self::deInit();
    return $encryptedText;

  }

  public static function decrypt( $data ) {

    if(self::$_ok != 'ok') { self::setKeys(); }

    // open the cipher
    self::$_td = mcrypt_module_open(self::$_algorithm, '', self::$_mode, '');
    $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size(self::$_td), MCRYPT_RAND);

    // determine the keysize length
    $ks = mcrypt_enc_get_key_size(self::$_td);

    // create key
    $key = substr(md5(self::$_key), 0, $ks);

    // initialize thge encryption module
    $s = mcrypt_generic_init(self::$_td, $key, $iv);
    if( ($s < 0) || ($s === false)) { return false; }

    // decrypt the data
    $decrypted = mdecrypt_generic(self::$_td, $data);
    $plainText = rtrim($decrypted, "\0");
    self::deInit();
    return $plainText;

  }

  private function deInit () {
    mcrypt_generic_deinit(self::$_td);
    mcrypt_module_close(self::$_td);
  }

}

?>