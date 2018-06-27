<?php
/**
 * Element class file
 *
 * WSDL element (of complex type) definition class
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
 * Class Element
 *
 * WSDL element (of complex type) definition
 *
 * @package  PhpWsdl
 * @access   public
 */
class Element extends Parameter {
	/**
	 * Allow NULL value
	 *
	 * @var boolean
	 */
	public $nillable = TRUE;
	/**
	 * Minimum number of occurrences
	 *
	 * @var int
	 */
	public $minOccurs = 1;
	/**
	 * Maximum number of occurrences
	 *
	 * @var int
	 */
	public $maxOccurs = 1;
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
		parent::__construct($name,$type,$params);
		$this->nillable = !in_array($type,Generator::getNonNillable());
		if(isset($params)) {
			if(isset($params['nillable'])) { $this->nillable = $params['nillable']=='1'|| $params['nillable']=='true'; }
			if(isset($params['minoccurs'])) { $this->minOccurs = $params['minoccurs']; }
			if(isset($params['maxoccurs'])) { $this->maxOccurs = $params['maxoccurs']; }
		}//if(isset($params))
	}//END public function __construct
	/**
	 * Create the part WSDL
	 *
	 * @param \PhpWsdl\Generator $sender The Generator instance
	 * @return string WSDL part string
	 * @access public
	 */
	public function getWsdl(Generator $sender): string {
		$return = '<s:element minOccurs="'.$this->minOccurs.'" maxOccurs="'.$this->maxOccurs.'" nillable="'.($this->nillable ? 'true' : 'false').'" name="'.$this->name.'" type="';
		$return .= $sender->translateType($this->type).'"';
		if($sender->getIncludeDesc() && !$sender->getOptimize() && !strlen($this->desc)) {
			$return .= '>'."\n";
			$return .= '<s:annotation>'."\n";
			$return .= '<s:documentation><![CDATA['.$this->desc.']]></s:documentation>'."\n";
			$return .= '</s:annotation>'."\n";
			$return .= '</s:element>';
		} else {
			$return .= ' />';
		}//if($sender->getIncludeDesc() && !$sender->getOptimize() && !strlen($this->desc))
		return $return;
	}//END public function getWsdl
	/**
	 * Interpret a element keyword
	 *
	 * @param \PhpWsdl\Generator $sender Generator instance
	 * @param array $keyword
	 * @return \PhpWsdl\Element|null Response
	 * @access public
	 * @static
	 */
	public static function interpretElementKeyword($sender,array $keyword): ?Element {
		$info = explode(' ',$keyword[1],3);
		if(!count($info)) {
			Debugger::addMessage('WARNING: Invalid element definition');
			return NULL;
		}//if(!count($info))
		$name = rtrim(substr($info[1],1),';');
		$params = [];
		if($sender->getIncludeDesc() && count($info)>2) { $params['desc'] = trim($info[2]); }
		return new Element($name,$info[0],$params);
	}//END public static function interpretElementKeyword
	/**
	 * Interpret a set keyword
	 *
	 * @param \PhpWsdl\Generator $sender Generator instance
	 * @param array $keyword
	 * @return array|null Response
	 * @access public
	 * @static
	 */
	public static function interpretSetKeyword($sender,array $keyword): ?array {
		$info = explode(' ',$keyword[1],3);
		if(!count($info)) {
			Debugger::addMessage('WARNING: Invalid set definition');
			return NULL;
		}//if(!count($info))
		$data = explode('=',$info[0],2);
		if(count($data)<2) {
			Debugger::addMessage('WARNING: Invalid set definition');
			return NULL;
		}//if(count($data)<2)
		return [$data[0]=>$data[1]];
	}//END public static function interpretSetKeyword
}//END class Element extends Parameter