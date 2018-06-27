<?php
/**
 * Formatter class file
 *
 * WSDL XML formatter class
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
  * Class Formatter
  *
  * WSDL XML formatter
  *
  * @package  PhpWsdl
  */
class Formatter {
	/**
	 * XML parser
	 *
     * @var resource xml parser
	 * @access protected
     */
	protected $parser = NULL;
	/**
	 * Un-formatted XML input stream
	 *
     * @var resource input stream
	 * @access protected
     */
	protected $input = NULL;
	/**
	 * Formatted XML output stream
	 *
     * @var resource output stream
	 * @access protected
     */
	protected $output = NULL;
	/**
	 * Input stream offset index
	 *
     * @var int stream offset index
	 * @access protected
     */
	protected $offset = 0;
	/**
	 * XML depth index
	 *
     * @var int XML depth index
	 * @access protected
     */
	protected $depth = 0;
	/**
	 * Closed XML element type flag
	 *
     * @var boolean closed XML element type flag
	 * @access protected
     */
	protected $empty = FALSE;
	/**
	 * Input stream buffer
	 *
     * @var string input stream buffer
	 * @access protected
     */
	protected $buffer = '';
	/**
     * Formatter options
     *
     * =====> (int) bufferSize:
     * - Buffer size in kilobytes
     *
     * =====> (string) paddingString:
     * - Padding string used for indentation
     *
	 * =====> (int) paddingMultiplier:
     * - Padding multiplier used to multiply padding string
     *
	 * =====> (boolean) formatCData:
     * - Flag whether to format character data. May be useful in some cases.
     *
	 * =====> (boolean) multipleLineCData:
     * - Flag whether character data consists of multiple lines.
     *
	 * =====> (int|false) wordwrapCData:
     * - Character data wordwrap length
	 * - If false does not wordwrap character data
     *
	 * =====> (string) inputEOL:
     * - Input stream character data end of line string
     *
	 * =====> (string) outputEOL:
     * - Output stream character data end of line string
     *
     * @var array formatter options
	 * @access protected
     */
	protected $options = [
		'bufferSize'=>4096,
		'paddingString'=>' ',
		'paddingMultiplier'=>4,
		'formatCData'=>TRUE,
		'multipleLineCData'=>TRUE,
		'wordwrapCData'=>75,
		'inputEOL'=>"\n",
		'outputEOL'=>"\n"
	];
	/**
	 * Constructor
	 *
	 * @param resource $input Input stream
	 * @param resource $output Output stream
	 * @param array    $options
	 * @return void
	 * @access public
	 */
	public function __construct($input,$output,array $options = []) {
		$this->input = $input;
		$this->output = $output;
		$this->options = array_merge($this->options,$options);
		$this->parser = xml_parser_create();
		xml_set_object($this->parser,$this);
		xml_parser_set_option($this->parser,XML_OPTION_CASE_FOLDING,FALSE);
		xml_parser_set_option($this->parser,XML_OPTION_SKIP_WHITE,0);
		xml_set_element_handler($this->parser,'cbElementStart','cbElementEnd');
		xml_set_character_data_handler($this->parser,'cbCharacterData');
	}//END public function __construct
	/**
     * Get padding string relative to XML depth index
     *
     * @param void
     * @return string padding string
	 * @access protected
     */
	protected function getPaddingStr(): string {
		return str_repeat($this->options["paddingString"],$this->depth*$this->options["paddingMultiplier"]);
	}//END protected function getPaddingStr
	/**
     * Element start callback
     *
     * @param resource $parser xml parser
	 * @param string $name element name
	 * @param array $attributes element attributes
     * @return void
	 * @access protected
     */
	protected function cbElementStart($parser,string $name,array $attributes): void {
		$idx = xml_get_current_byte_index($this->parser);
		$this->empty = $this->buffer[$idx - $this->offset] == '/';
		$attrs = '';
		foreach($attributes as $key=>$val) {
			$attrs .= ' '.$key.'="'.$val.'"';
		}//END foreach
		fwrite($this->output,$this->getPaddingStr().'<'.$name.$attrs.($this->empty ? ' />' : '>')."\n");
		if (!$this->empty) ++$this->depth;
	}//END protected function cbElementStart
	/**
     * Element end callback
     *
     * @param resource $parser xml parser
	 * @param string $name element name
     * @return void
	 * @access protected
     */
	protected function cbElementEnd($parser,string $name): void {
		if(!$this->empty) {
			--$this->depth;
			fwrite($this->output,$this->getPaddingStr()."</".$name.">"."\n");
		} else {
			$this->empty = FALSE;
		}//if(!$this->empty)
	}//END protected function cbElementEnd
	/**
     * Character data callback
     *
     * @param resource $parser xml parser
	 * @param string $data character data
     * @return void
	 * @access protected
     */
	protected function cbCharacterData($parser,string $data): void {
		if(!$this->options["formatCData"]) { return; }
		$data = trim($data);
		if(strlen($data)) {
			$pad = $this->getPaddingStr();
			if($this->options["multipleLineCData"]) {
				// remove all tabs
				$data = str_replace("\t",'',$data);
				// append each line with a padding string
				$data = implode($this->options["inputEOL"].$pad,explode($this->options["inputEOL"],$data));
			}//if($this->options["multipleLineCData"])
			if($this->options["wordwrapCData"]) {
				$data = wordwrap($data, $this->options["wordwrapCData"], $this->options["outputEOL"] . $pad, false);
			}//if($this->options["wordwrapCData"])
			fwrite($this->output,$pad.$data."\n");
		}//if(strlen($data))
	}//END protected function cbCharacterData
	/**
     * Main format method
     *
     * @param void
     * @throws \Exception
     * @return void
	 * @access public
     */
	public function format(): void {
		while($this->buffer = fread($this->input, $this->options["bufferSize"])) {
			if(!xml_parse($this->parser,$this->buffer,feof($this->input))) {
				throw new \Exception(sprintf("XML error: %s at line %d",
                    xml_error_string(xml_get_error_code($this->parser)),
                    xml_get_current_line_number($this->parser)));
			}//if(!xml_parse($this->parser,$this->buffer,feof($this->input)))
			$this->offset += strlen($this->buffer);
		}//END while
		xml_parser_free($this->parser);
	}//END public function format
}//END class Formatter