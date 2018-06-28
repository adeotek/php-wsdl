<?php
/**
 * Method class file
 *
 * WSDL method definition class
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
 * Class Method
 *
 * WSDL method definition
 *
 * @package  PhpWsdl
 */
class Method extends BaseObject {
	/**
	 * List of parameters
	 *
	 * @var array
	 */
	public $parameters = [];
	/**
	 * The return value
	 *
	 * @var \PhpWsdl\Parameter
	 */
	public $return = NULL;
	/**
	 * A global method?
	 *
	 * @var boolean
	 */
	public $isGlobal = FALSE;
	/**
	 * A new method is global by default
	 *
	 * @var boolean
	 */
	public static $isGlobalDefault = FALSE;
	/**
	 * Constructor
	 *
	 * @param string $name The name
	 * @param array $parameters Array of parameters
	 * @param \PhpWsdl\Parameter|null Return parameter
	 * @param array|null   $params
	 * @return void
	 * @access public
	 */
	public function __construct(string $name,array $parameters = [],?Parameter $return = NULL,?array $params = NULL) {
		parent::__construct($name,$params);
		$this->parameters = $parameters;
		if(isset($return)) { $this->return = $return; }
		$this->isGlobal = isset($params) && isset($params['global']) ? ($params['global']=='true' || $params['global']=='1') : self::$isGlobalDefault;
	}//END public function __construct
	/**
	 * Find an parameter within this method
	 *
	 * @param string $name Parameter name
	 * @return \PhpWsdl\Parameter|null The element or NULL
	 * @access public
	 */
	public function getParameter(string $name): ?Parameter {
		foreach($this->parameters as $parameter) { if($parameter->name==$name) { return $parameter; } }
		return NULL;
	}//END public function getParameter
	/**
	 * Create the port type part WSDL
	 *
	 * @param \PhpWsdl\Generator $sender The Generator instance
	 * @return string WSDL part string
	 * @access public
	 */
	public function getPortTypeWsdl(Generator $sender): string {
		$return = '<wsdl:operation name="'.$this->name.'"';
		if(count($this->parameters)>1) {
			$pOrder = ' parameterOrder="';
			foreach($this->parameters as $parameter) { $pOrder .= $parameter->name.' '; }
			$return .= rtrim($pOrder).'"';
		}//if(count($this->parameters)>1)
		$return .= '>';
		if($sender->getIncludeDesc() && !$sender->getOptimize() && strlen($this->desc)) {
			$return .= '<wsdl:documentation><![CDATA['.$this->desc.']]></wsdl:documentation>';
		}//if($sender->getIncludeDesc() && !$sender->getOptimize() && strlen($this->desc))
		$return .= '<wsdl:input message="tns:'.$this->name.'SoapIn" />';
		$return .= '<wsdl:output message="tns:'.$this->name.'SoapOut" />';
		$return .= '</wsdl:operation>';
		return $return;
	}//END public function getPortTypeWsdl
	/**
	 * Create the binding part WSDL
	 *
	 * @param \PhpWsdl\Generator $sender The Generator instance
	 * @return string WSDL part string
	 * @access public
	 */
	public function getBindingWsdl(Generator $sender): string {
		$result = '<wsdl:operation name="'.$this->name.'">';
		$result .= '<soap:operation soapAction="'.$sender->getNamespace().$this->name.'" />';
		$result .= '<wsdl:input>';
		$result .= '<soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="'.$sender->getNamespace().'"';
		$parts = '';
		foreach($this->parameters as $parameter) { $parts .= $parameter->name.' '; }
		if(strlen($parts)) { $result .= ' parts="'.trim($parts).'"'; }
		$result .= ' />';
		$result .= '</wsdl:input>';
		$result .= '<wsdl:output>';
		$result .= '<soap:body use="encoded" encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" namespace="'.$sender->getNamespace().'"';
		if(isset($this->return)) {
			$result .= ' parts="'.$this->return->name.'"';
		}//if(isset($this->return))
		$result .= ' />';
		$result .= '</wsdl:output>';
		$result .= '</wsdl:operation>';
		return $result;
	}//END public function getBindingWsdl
	/**
	 * Create the messages part WSDL
	 *
	 * @param \PhpWsdl\Generator $sender The Generator instance
	 * @return string WSDL part string
	 * @access public
	 */
	public function getMessagesWsdl(Generator $sender): string {
		$result = '<wsdl:message name="'.$this->name.'SoapIn"';
		if(count($this->parameters)) {
			$result .= '>';
			foreach($this->parameters as $parameter) { $result .= $parameter->getWsdl($sender); }
			$result .= '</wsdl:message>';
		} else {
			$result .= ' />';
		}//if(count($this->parameters))
		if(isset($this->return)) {
			$result .= '<wsdl:message name="'.$this->name.'SoapOut">';
			$result .= $this->return->getWsdl($sender);
			$result .= '</wsdl:message>';
		} else {
			$result .= '<wsdl:message name="'.$this->name.'SoapOut" />';
		}//if(isset($this->return))
		return $result;
	}//END public function getMessagesWsdl
}//END class Method extends BaseObject