<?php
/**
 * ComplexType class file
 *
 * WSDL Complex type (class/array)
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
  * Class ComplexType
  *
  * WSDL Complex type (class/array)
  *
  * @package  PhpWsdl
  */
class ComplexType extends BaseObject {
	/**
	 * Is of type array
	 *
	 * @var boolean
	 */
	public $isArray;
	/**
	 * If is array, type of elements
	 *
	 * @var string|null
	 */
	public $type = NULL;
	/**
	 * If this type is a class list of properties else array elements
	 *
	 * @var array
	 */
	public $elements;
	/**
	 * Disable definition of arrays with the "Array" postfix in the type name
	 *
	 * @var boolean
	 */
	public static $disableArrayPostfix = FALSE;
	/**
	 * Constructor
	 *
	 * @param string $name The name
	 * @param string $type Optional the type name (default: string)
	 * @param array $elements Array of complex type elements
	 * @param array|null   $params
	 * @return void
	 * @access public
	 */
	public function __construct(string $name,?string $type = NULL,array $elements = [],?array $params = NULL) {
		parent::__construct($name,$params);
		$this->type = $type;
		$this->elements = $elements;
		if(!self::$disableArrayPostfix) {
			$this->isArray = strtolower(substr($this->name,-5))=='array';
			if($this->isArray) { $this->type = substr($this->name,0,strlen($this->name)-5); }
		}//if(!self::$disableArrayPostfix)
		if(isset($params) && isset($params['isArray'])) { $this->isArray = $params['isArray']; }
	}//END public function __construct
	/**
	 * Find an element within this type
	 *
	 * @param string $name Element name
	 * @return \PhpWsdl\Element|null The element or NULL
	 * @access public
	 */
	public function getElement(string $name): ?Element {
		foreach($this->elements as $element) { if($element->name==$name) { return $element; } }
		return NULL;
	}//END public function getElement
	/**
	 * Create the part WSDL
	 *
	 * @param \PhpWsdl\Generator $sender The Generator instance
	 * @return string WSDL part string
	 * @access public
	 */
	public function getWsdl(Generator $sender): string {
		$return = '<s:complexType name="'.$this->name.'">';
		if($sender->getIncludeDesc() && !$sender->getOptimize() && strlen($this->desc)) {
			$return .= '<s:annotation>';
			$return .= '<s:documentation><![CDATA['.$this->desc.']]></s:documentation>';
			$return .= '</s:annotation>';
		}//if($sender->getIncludeDesc() && !$sender->getOptimize() && !strlen($this->docs))
		if($this->isArray) {
			$return .= '<s:complexContent>';
			$return .= '<s:restriction base="soapenc:Array">';
			$return .= '<s:attribute ref="soapenc:arrayType" wsdl:arrayType="';
			$return .= (in_array($this->type,Generator::getBasicTypes()) ? 's' : 'tns').':'.$this->type.'[]" />';
			$return .= '</s:restriction>';
			$return .= '</s:complexContent>';
		} else {
			$return .= '<s:sequence>';
			foreach($this->elements as $element) { $return .= $element->getWsdl($sender); }
			$return .= '</s:sequence>';
		}//if($this->isArray)
		$return .= '</s:complexType>';
		return $return;
	}//END public function getWsdl
	/**
	 * Interpret a element keyword
	 *
	 * @param \PhpWsdl\Generator $sender Generator instance
	 * @param array $keyword
	 * @param array $elements
	 * @param array $params
	 * @return \PhpWsdl\ComplexType|null Response
	 * @access public
	 * @static
	 */
	public static function interpretComplexKeyword($sender,array $keyword,array $elements,array $params): ?ComplexType {
		$info = explode(' ',$keyword[1],3);
		if(!count($info)) {
			Debugger::addMessage('WARNING: Invalid complex definition');
			return NULL;
		}//if(!count($info))
		$type = NULL;
		$desc = NULL;
		if(strpos($info[0],'[]')!==FALSE) {
			if(count($info)<2) {
				Debugger::addMessage('WARNING: Invalid array definition!');
				return NULL;
			}//if(count($info)<2)
			$name = substr($info[0],0,strlen($info[0])-2);
			if(!is_null($sender->getType($name))) {
				Debugger::addMessage('WARNING: Double type detected!');
				return NULL;
			}//if(!is_null($sender->getType($name)))
			$type = $info[1];
			if($sender->getIncludeDesc() && count($info)>2) { $desc = $info[2]; }
		} else {
			$name = $info[0];
			if(!is_null($sender->getType($name))) {
				Debugger::addMessage('WARNING: Double type detected!');
				return NULL;
			}//if(!is_null($sender->getType($name)))
			if(!self::$disableArrayPostfix && strtolower(substr($name,-5))=='array') {
				$type = substr($name,0,strlen($name)-5);
			}//if(!self::$disableArrayPostfix && strtolower(substr($name,-5))=='array')
			if($sender->getIncludeDesc() && count($info)>1) {
				$desc = (count($info)>2 ? $info[1].' '.$info[2] : $info[1]);
			}//if($sender->getIncludeDesc() && count($info)>1)
		}//END if(strpos($info[0],'[]')!==FALSE)
		$params['desc'] = $desc;
		return new ComplexType($name,$type,$elements,$params);
	}//END public static function interpretComplexKeyword
}//END class ComplexType extends BaseObject