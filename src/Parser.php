<?php
/**
 * Parser class file
 *
 * WSDL parser class
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
 * Class Parser
 *
 * @package PhpWsdl
 */
class Parser {
	/**
	 * Regular expression to parse the relevant data from a string
	 * 1: Comment block
	 * 4: Method name
	 *
	 * @var string
	 * @access protected
	 * @static
	 */
	protected static $relevantDataRegEx = '/\/\*\*([^\*]*\*+(?:[^\*\/][^\*]*\*+)*)\/(\s*(public\s+)?function\s+([^\s|\(]+)\s*\()?/is';
	/**
	 * Regular expression to parse keywords from a string
	 * 1: Whole line
	 * 2: Keyword
	 * 3: Parameters
	 *
	 * @var string
	 * @access protected
	 * @static
	 */
	protected static $keywordsRegEx = '/^(\s*\*\s*\@([^\s|\n]+)([^\n]*))$/m';
	/**
	 * Regular expression to parse the documentation from the bottom of a comment block string
	 * 1: Documentation
	 *
	 * @var string
	 * @access protected
	 * @static
	 */
	protected static $docsRegEx = '/^[^\*|\n]*\*[ |\t]+([^\*|\s|\@|\/|\n][^\n]*)?$/m';
	/**
	 * Parse a string
	 *
	 * @param string          $str The string to parse
	 * @param \PhpWsdl\Generator $sender
	 * @return array
	 * @access public
	 */
	public static function parse(string $str,$sender) {
		$result = ['types'=>[],'methods'=>[]];
		$pd = [];
		preg_match_all(self::$relevantDataRegEx,$str,$pd);
		for($i = 0;$i<count($pd[0]);$i++) {
			$d = $pd[1][$i];
			$m = $pd[4][$i];
			$k = [];
			$t = [];
			preg_match_all(self::$keywordsRegEx,$d,$t);
			for($j = 0;$j<count($t[0]);$j++) {
				$k[] = [$t[2][$j],trim($t[3][$j])];
			}//END for
			self::interpretDefinition($result,$sender,$k,$m);
		}//END for
		return $result;
	}//END public function parse
	/**
	 * Interpret a WSDL definition
	 *
	 * @param array  $data
	 * @param \PhpWsdl\Generator $sender
	 * @param array  $keywords
	 * @param string $method Method name
	 * @return void
	 * @access public
	 * @static
	 */
	public static function interpretDefinition(&$data,$sender,$keywords,$method) {
		$cfg = [];
		$elements = [];
		$return = NULL;
		foreach($keywords as $keyword) {
			switch($keyword[0]) {
				case 'service':
					if(!$sender->interpretServiceKeyword($keyword)) { return; }
					break;
				case 'pw_set':
					$result = Element::interpretSetKeyword($sender,$keyword);
					if(!$result) { return; }
					$cfg = array_merge($cfg,$result);
					break;
				case 'pw_element':
					$result = Element::interpretElementKeyword($sender,$keyword,$cfg);
					if(!$result) { return; }
					$cfg = [];
					$elements[] = $result;
					break;
				case 'pw_complex':
					$obj = ComplexType::interpretComplexKeyword($sender,$keyword,$elements,$cfg);
					if($obj) { $data['types'][] = $obj; }
					return;
				case 'param':
					$result = Parameter::interpretParamKeyword($sender,$keyword,$method,$cfg);
					if(!$result) { return; }
					$cfg = [];
					$elements[] = $result;
					break;
				case 'return':
					$return = Parameter::interpretReturnKeyword($sender,$keyword,$method,$cfg);
					if(!$return) { return; }
					$cfg = [];
					break;
				case 'pw_ignore':
				case 'ignore':
					return;
				default:
					break;
			}//END switch
		}//END foreach
		if(!strlen($method)) { return; }
		$obj = new Method($method,$elements,$return,$cfg);
		if($obj) { $data['methods'][] = $obj; }
	}//END public static function interpretDefinition
}//END class Parser