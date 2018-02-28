<?php if (!defined('EntryAllowed') || !EntryAllowed) die('Not A Valid Entry Point');

/**
  * @title   Registry class, Registry.php - registry management
  * @author  Roger Bolser
  * @version 1.0.0.0
  *
  */

class Registry {

	/** @var mixed Object instance for singleton */
	private static $_instance;

	/** @var array to store objects */
  public $_store = array();

  /**
   * save instance if instantiated with 'new Registry()'
   */
  public function __construct() {
    self::$_instance=$this;
  }

  /**
   * Get registry object instance (Singleton)
   *
   * @return object instance
   */
  public static function getInstance() {
    if(!isset(self::$_instance)) { self::$_instance = new Registry(); }
    return self::$_instance;
  }

  public function register($name, $object) { $this->_store[$name] = $object; }

  public function registerArray($parameters = array()) {
    $this->_store = array_merge($this->_store, $parameters);
  }

  public function unregister($name) {
    if (isset($this->_store[$name])) { unset($this->_store[$name]); }
  }

  public function get($name, $default=null) {
    return isset($this->_store[$name]) ? $this->_store[$name] : $default;
  }

  public function getArray() {
    return $this->_store;
  }

  public function isRegistered($name) {
    return isset($this->_store[$name]);
  }

} // end class
?>