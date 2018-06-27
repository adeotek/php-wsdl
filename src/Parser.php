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
namespace PhpWsdl;
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
		$result = [];
		$data = [];
		preg_match_all(self::$relevantDataRegEx,$str,$data);
		for($i = 0;$i<count($data[0]);$i++) {
			$d = $data[1][$i];
			$m = $data[4][$i];
			$k = [];
			$t = [];
			preg_match_all(self::$keywordsRegEx,$d,$t);
			for($j = 0;$j<count($t[0]);$j++) {
				$k[] = [$t[2][$j],trim($t[3][$j])];
			}//END for
			// if($sender->getIncludeDesc()) {
			// 	$docs = [];
			// 	$t = [];
			// 	preg_match_all(self::$docsRegEx,$d,$t);
			// 	for($j = 0;$j<count($t[0]);$j++) {
			// 		$docs[] = trim($t[1][$j]);
			// 	}//END for
			// 	$docs = trim(implode("\n",$docs));
			// 	if($docs=='') { $docs = NULL; }
			// } else {
			// 	$docs = NULL;
			// }//if($sender->getIncludeDesc())
			// self::interpretDefinition($result,$sender,$k,$m,$d,$docs);
			self::interpretDefinition($result,$sender,$k,$m,$d);
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
	 * @param        $definition
	 * @return void
	 * @access public
	 * @static
	 */
	public static function interpretDefinition(&$data,$sender,$keywords,$method,$definition) {
		$result = [];
		foreach($keywords as $keyword) {
			switch($keyword[0]) {
				case 'service':
					if(!$sender->interpretServiceKeyword($keyword)) { return; }
					break;
				case 'pw_set':
					$result = Element::interpretSetKeyword($sender,$keyword);
					if(!$result) { return; }
					break;
				case 'pw_complex':
					$result = ComplexType::interpretComplexKeyword($sender,$keyword);
					if(!$result) { return; }
					break;
				case 'pw_element':
					$result = Element::interpretElementKeyword($sender,$keyword);
					if(!$result) { return; }
					break;
				case 'param':
					$result = Parameter::interpretParamKeyword($sender,$keyword,$method);
					if(!$result) { return; }
					break;
				case 'return':
					$result = Parameter::interpretReturnKeyword($sender,$keyword,$method);
					if(!$result) { return; }
					break;
				case 'pw_ignore':
				case 'ignore':
					return;
				default:
					break;
			}//END switch
			$result[$definition][$keyword[1]] = $result;
		}//END foreach

		// Method::createMethodObject();

		if($result) { $data[] = $result; }
	}//END public static function interpretDefinition
}//END class Parser