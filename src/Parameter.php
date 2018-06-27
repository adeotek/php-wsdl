<?php
/**
 * Parameter class file
 *
 * WSDL parameter definition class (method argument or return value)
 *
 * @package    PhpWsdl
 * @author     George Benjamin-Schonberger
 * @copyright  Copyright (c) 2013 - 2018 AdeoTEK
 * @license    LICENSE
 * @version    1.0.0
 * @filesource
 */
namespace PhpWsdl;
/**
 * Class Parameter
 *
 * WSDL parameter definition (method argument or return value)
 *
 * @package  PhpWsdl
 * @access   public
 */
class Parameter extends BaseObject {
	/**
	 * The parameter type name
	 *
	 * @var string
	 */
	public $type;
	/**
	 * The default name for the return value object
	 * Use %method% as placeholder for the method name
	 *
	 * @var string
	 */
	public static $defaultReturnName = 'return';
	/**
	 * Constructor
	 *
	 * @param string $name The name
	 * @param string $type Optional the type name (default: string)
	 * @param array|null   $params
	 * @return void
	 * @access public
	 */
	public function __construct(string $name,string $type = 'string',?array $params = NULL) {
		parent::__construct($name,$params);
		$this->type = $type;
	}//END public function __construct
	/**
	 * Create the part WSDL
	 *
	 * @param \PhpWsdl\Generator $sender The Generator instance
	 * @return string WSDL part string
	 * @access public
	 */
	public function getWsdl(Generator $sender): string {
		$return = '<wsdl:part name="'.$this->name.'" type="';
		$return .= $sender->translateType($this->type).'"';
		if($sender->getIncludeDesc() && !$sender->getOptimize() && strlen($this->desc)) {
			$return .= '>'."\n";
			$return .= '<s:documentation><![CDATA['.$this->desc.']]></s:documentation>'."\n";
			$return .= '</wsdl:part>';
		} else {
			$return .= ' />';
		}//if($sender->getIncludeDesc() && !$sender->getOptimize() && strlen($this->desc))
		return $return;
	}//END public function getWsdl
	/**
	 * Interpret a param keyword
	 *
	 * @param $sender Generator instance
	 * @param array $keyword
	 * @param string $method
	 * @param array $params
	 * @return \PhpWsdl\Parameter|null Response
	 */
	public static function interpretParamKeyword(Generator $sender,array $keyword,string $method,array $params): ?Parameter {
		if(!strlen($method)) { return NULL; }
		$info = explode(' ',$keyword[1],3);
		if(count($info)<2) {
			Debugger::addMessage('WARNING: Invalid param definition');
			return NULL;
		}//if(count($info)<2)
		$name = rtrim(substr($info[1],1),';');
		if($sender->getIncludeDesc() && count($info)>2) { $params['desc'] = trim($info[2]); }
		return new Parameter($name,$info[0],$params);
	}//END public static function interpretParamKeyword
	/**
	 * Interpret a return keyword
	 *
	 * @param $sender Generator instance
	 * @param array $keyword
	 * @param string $method
	 * @param array $params
	 * @return \PhpWsdl\Parameter|null Response
	 */
	public static function interpretReturnKeyword(Generator $sender,array $keyword,string $method,array $params): ?Parameter {
		if(!strlen($method)) { return NULL; }
		$info = explode(' ',$keyword[1],3);
		if(count($info)<2) {
			Debugger::addMessage('WARNING: Invalid return definition');
			return NULL;
		}//if(count($info)<2)
		$name = str_replace('%method%',$method,self::$defaultReturnName);
		if($sender->getIncludeDesc() && count($info)>2) { $params['desc'] = trim($info[2]); }
		return new Parameter($name,$info[0],$params);
	}//END public static function interpretReturnKeyword
}//END class Parameter extends BaseObject