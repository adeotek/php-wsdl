<?php
/**
 * BaseObject class file
 *
 * WSDL base object class
 *
 * @package    PhpWsdl
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE
 * @version    1.0.0
 * @filesource
 */
namespace AdeoTEK\PhpWsdl;
/**
  * BaseObject class
  *
  * WSDL base object
  *
  * @package  PhpWsdl
  */
abstract class BaseObject {
	/**
	 * The GUID
	 *
	 * @var string
	 */
	public $uid;
	/**
	 * The name
	 *
	 * @var string
	 */
	public $name;
	/**
	 * Description
	 *
	 * @var string|null
	 */
	public $desc = NULL;
	/**
	 * Config array
	 *
	 * @var array|null
	 */
	public $config = NULL;
	/**
	 * Constructor
	 *
	 * @param string $name The name
	 * @param array|null $params
	 * @return void
	 * @access public
	 */
	public function __construct(string $name,?array $params = NULL) {
		$this->uid = uniqid();
		$this->name = $name;
		Debugger::addMessage('New PhpWsdl\BaseObject "'.$this->name.'" with UID ['.$this->uid.']');
		if(isset($params)) {
			if(isset($params['desc'])) { $this->desc = $params['desc']; }
			if(isset($params['config'])) { $this->config = $params['config']; }
		}//if(isset($params))
	}//END public function __construct
}//END abstract class BaseObject