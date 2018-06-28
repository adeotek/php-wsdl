<?php
/**
 * Debugger class file
 *
 * description
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
  * Debugger class
  *
  * long_description
  *
  * @package  PhpWsdl
  * @access   public
  */
class Debugger {
	/**
	 * Debugging enabled
	 *
	 * @var bool
	 * @access public
	 * @static
	 */
	public static $enabled = FALSE;
	/**
	 * Debug back trace
	 *
	 * @var bool
	 * @access public
	 * @static
	 */
	public static $debugBackTrace = FALSE;
	/**
	 * The debug file
	 *
	 * @var string|null
	 * @access public
	 * @static
	 */
	public static $debugFile = NULL;
	/**
	 * Debugging data
	 *
	 * @var array
	 * @access protected
	 * @static
	 */
	protected static $messages = [];
	/**
	 * Add a debugging message
	 *
	 * @param string $text Debug message
	 * @return void
	 * @access public
	 * @static
	 */
	public static function addMessage(string $text): void {
		if(!self::$enabled) { return; }
		$temp = date('Y-m-d H:i:s')."\t".$text;
		if(self::$debugBackTrace) {
			$trace = debug_backtrace();
			$temp .= " ('".$trace[1]['function']."' in '".basename($trace[1]['file'])."' at line #".$trace[1]['line'].")";
		}
		self::$messages[] = $temp;
		if(strlen(self::$debugFile)) {
			if(file_put_contents(self::$debugFile,$temp."\n",FILE_APPEND)===false) {
				self::addMessage('Could not write to debug file ['.self::$debugFile.']');
				self::$debugFile = NULL;
			}
		}
	}//END public static function addMessage
	/**
	 * Get all debugging messages
	 *
	 * @return array
	 * @access public
	 * @static
	 */
	public static function getMessages(): array {
		return self::$messages;
	}//END public static function getMessages
	/**
	 * Delete all debugging messages
	 *
	 * @return void
	 * @access public
	 * @static
	 */
	public static function clearMessages(): void {
		self::$messages = [];
	}//END public static function clearMessages
}//END class Debugger