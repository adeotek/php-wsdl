<?php
/**
 * Generator class file
 *
 * WSDL Generator class
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
  * Class Generator
  *
  * WSDL Generator
  *
  * @package  PhpWsdl
  */
class Generator {
	/**
	 * Regular expression for parsing a class name
	 *
	 * @var string
	 * @access protected
	 */
	protected static $classRegEx = '/^.*class\s+([^\s]+)\s*\{.*$/is';
	/**
	 * An array of basic types (these are just some of the XSD defined types
	 * (see http://www.w3.org/TR/2001/PR-xmlschema-2-20010330/)
	 *
	 * @var array
	 * @access protected
	 */
	protected static $basicTypes = [
		'anyType',
		'anyURI',
		'base64Binary',
		'boolean',
		'byte',
		'date',
		'decimal',
		'double',
		'duration',
		'dateTime',
		'float',
		'gDay',
		'gMonthDay',
		'gYearMonth',
		'gYear',
		'hexBinary',
		'int',
		'integer',
		'long',
		'NOTATION',
		'number',
		'QName',
		'short',
		'string',
		'time'
	];
	/**
	 * A list of non-nillable types
	 *
	 * @var array
	 * @access protected
	 */
	protected static $nonNillable = [
		'boolean',
		'decimal',
		'double',
		'float',
		'int',
		'integer',
		'long',
		'number',
		'short'
	];
	/**
	 * The namespace
	 *
	 * @var string
	 * @access protected
	 */
	protected $namespace = NULL;
	/**
	 * Namespaces list
	 *
	 * @var array
	 * @access protected
	 */
	protected $namespaces = [
		'soap'=>'http://schemas.xmlsoap.org/wsdl/soap/',
		's'=>'http://www.w3.org/2001/XMLSchema',
		'wsdl'=>'http://schemas.xmlsoap.org/wsdl/',
		'soapenc'=>'http://schemas.xmlsoap.org/soap/encoding/'
	];
	/**
	 * The SOAP endpoint URI
	 *
	 * @var string
	 * @access protected
	 */
	protected $endPoint = NULL;
	/**
	 * Remove tabs and line breaks
	 * Note: Unoptimized WSDL won't be cached
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $optimize = TRUE;
	/**
	 * Cache WSDL data
	 *
	 * @var bool
	 * @access protected
	 */
	protected $cacheWsdl = FALSE;
	/**
	 * Include descriptions
	 *
	 * @var boolean
	 * @access protected
	 */
	protected $includeDesc = FALSE;
	/**
	 * Description
	 *
	 * @var string
	 * @access protected
	 */
	protected $desc = NULL;
	/**
	 * An array of file names to parse
	 *
	 * @var array
	 * @access protected
	 */
	protected $srcFiles = [];
	/**
	 * Instance configuration
	 *
	 * @var array
	 * @access protected
	 */
	protected $config = [];
	/**
	 * Service name
	 *
	 * @var string
	 * @access protected
	 */
	protected $serviceName = '';
	/**
	 * Sources parse state
	 *
	 * @var bool
	 * @access protected
	 */
	protected $sourcesParsed = FALSE;
	/**
	 * An array of ComplexType objects
	 *
	 * @var array
	 * @access protected
	 */
	protected $types = [];
	/**
	 * An array of Method objects
	 *
	 * @var array
	 * @access protected
	 */
	protected $methods = [];
	/**
	 * Generator constructor.
	 *
	 * @param array $params
	 * @return void
	 * @access public
	 */
	public function __construct(array $params = []) {
		$this->cacheWsdl = isset($params['cacheWsdl']) ? $params['cacheWsdl'] : $this->cacheWsdl;
		$this->optimize = isset($params['optimize']) ? $params['optimize'] : $this->optimize;
		$this->includeDesc = $this->optimize ? FALSE : (isset($params['includeDesc']) ? $params['includeDesc'] : $this->includeDesc);
		$this->namespace = $params['namespace'];
		$this->endPoint = $params['endPoint'];
		if(isset($params['namespaces']) && is_array($params['namespaces'])) { $this->namespaces = $params['namespaces']; }
		if(isset($params['serviceName']) && strlen($params['serviceName'])) { $this->serviceName = $params['serviceName']; }
		if(isset($params['srcFiles']) && $params['srcFiles']) {
			$this->srcFiles = array_merge($this->srcFiles,(is_array($params['srcFiles']) ? $params['srcFiles'] : [$params['srcFiles']]));
		}//if(isset($params['srcFiles']) && $params['srcFiles'])
		$this->methods = isset($params['methods']) && is_array($params['methods']) ? $params['methods'] : [];
		$this->types = isset($params['types']) && is_array($params['types']) ? $params['types'] : [];
		$this->config = $params;
		$this->config['tns'] = 'tns'; // The xmlns name for the target namespace
		$this->config['xsd'] = 's'; // The xmlns name for the XSD namespace
	}//END public function __construct
	/**
	 * Get namespace
	 *
	 * @return string
	 * @access public
	 */
	public function getNamespace(): string {
		return $this->namespace;
	}//END public function getNamespace
	/**
	 * Get endpoint
	 *
	 * @return string
	 * @access public
	 */
	public function getEndPoint(): string {
		return $this->endPoint;
	}//END public function getEndPoint
	/**
	 * Get parse docs
	 *
	 * @return bool
	 * @access public
	 */
	public function getIncludeDesc(): bool {
		return $this->includeDesc;
	}//END public function getIncludeDesc
	/**
	 * Get parse docs
	 *
	 * @return bool
	 * @access public
	 */
	public function getOptimize(): bool {
		return $this->optimize;
	}//END public function getOptimize
	/**
	 * Find a complex type
	 *
	 * @param string $name The type name
	 * @return \PhpWsdl\ComplexType|null The type object or NULL
	 * @access public
	 */
	public function getType(string $name) {
		foreach($this->types as $type) { if($type->name==$name) { return $type; } }
		return NULL;
	}//END public function getType
	/**
	 * Translate a type name for WSDL
	 *
	 * @param string $type The type name
	 * @return string The translates type name
	 * @access public
	 */
	public function translateType(string $type): string {
		return ((in_array($type,self::$basicTypes)) ? $this->config['xsd'] : $this->config['tns']).':'.$type;
	}//END public function translateType
	/**
	 * Interpret the @service keyword
	 *
	 * @param array $keyword The parser data
	 * @return boolean TRUE on success, FALSE otherwise
	 * @access public
	 */
	public function interpretServiceKeyword(array $keyword): bool {
		$info = explode(' ',$keyword[1],2);
		if(!count($info)) {
			Debugger::addMessage('WARNING: Invalid service definition');
			return FALSE;
		}//if(!count($info))
		$this->serviceName = $info[0];
		if($this->includeDesc && count($info)>1 && !strlen($this->desc)) { $this->desc = $info[1]; }
		return TRUE;
	}//END public function interpretServiceKeyword
	/**
	 * Get WSDL header
	 *
	 * @return string WSDL header part
	 * @access public
	 */
	public function getWsdlHeader() {
		$result = '<?xml version="1.0" encoding="UTF-8"?>';
		$nss = [];
		foreach($this->namespaces as $k=>$ns) { $nss[] = 'xmlns:'.$k.'="'.$ns.'"'; }
		$result .= '<wsdl:definitions xmlns:tns="'.$this->namespace.'" targetNamespace="'.$this->namespace.'" '.implode(' ',$nss).'>';
		return $result;
	}//END public function getWsdlHeader
	/**
	 * Get WSDL type schema
	 *
	 * @return string WSDL type schema part
	 * @access public
	 */
	public function getWsdlTypeSchema() {
		$result = '';
		if(count($this->types)) {
			$result .= '<wsdl:types>';
			$result .= '<s:schema targetNamespace="'.$this->namespace.'">';
			foreach($this->types as $type) { $result .= $type->getWsdl($this); }
			$result .= '</s:schema>';
			$result .= '</wsdl:types>';
		}//if(count($this->types))
		return $result;
	}//END public function getWsdlTypeSchema
	/**
	 * Get WSDL messages
	 *
	 * @return string WSDL messages part
	 * @access public
	 */
	public function getWsdlMessages() {
		$result = '';
		foreach($this->methods as $method) { $result .= $method->getMessagesWsdl($this); }
		return $result;
	}//END public function getWsdlMessages
	/**
	 * Get WSDL ports
	 *
	 * @return string WSDL ports part
	 * @access public
	 */
	public function getWsdlPorts() {
		$result = '<wsdl:portType name="'.$this->serviceName.'Soap">';
		foreach($this->methods as $method) {  $result .= $method->getPortTypeWsdl($this); }
		$result .= '</wsdl:portType>';
		return $result;
	}//END public function getWsdlPorts
	/**
	 * Get WSDL bindings
	 *
	 * @return string WSDL bindings part
	 * @access public
	 */
	public function getWsdlBindings() {
		$result = '<wsdl:binding name="'.$this->serviceName.'Soap" type="tns:'.$this->serviceName.'Soap">';
		$result .= '<soap:binding transport="http://schemas.xmlsoap.org/soap/http" style="rpc" />';
		foreach($this->methods as $method) {  $result .= $method->getBindingWsdl($this); }
		$result .= '</wsdl:binding>';
		return $result;
	}//END public function getWsdlBindings
	/**
	 * Get WSDL service
	 *
	 * @return string WSDL service part
	 * @access public
	 */
	public function getWsdlService() {
		$result = '<wsdl:service name="'.$this->serviceName.'">';
		if($this->includeDesc && !$this->optimize && isset($this->desc)) {
			$result .= '<wsdl:documentation><![CDATA['.$this->desc.']]></wsdl:documentation>';
		}//if($this->includeDesc && !$this->optimize && isset($this->desc))
		$result .= '<wsdl:port name="'.$this->serviceName.'Soap" binding="tns:'.$this->serviceName.'Soap">';
		$result .= '<soap:address location="'.$this->endPoint.'" />';
		$result .= '</wsdl:port>';
		$result .= '</wsdl:service>';
		return $result;
	}//END public function getWsdlService
	/**
	 * Get WSDL footer
	 *
	 * @return string WSDL footer part
	 * @access public
	 */
	public function getWsdlFooter() {
		return '</wsdl:definitions>';
	}//END public function getWsdlFooter
	/**
	 * Parse source files for WSDL definitions in comments
	 *
	 * @param bool   $reset Reset previous parse results
	 * @param string $str Source string or NULL to parse the defined files (default: NULL)
	 * @return void
	 * @access protected
	 */
	protected function parseSource(bool $reset = FALSE,?string $str = NULL) {
		if($reset) {
			$this->methods = [];
			$this->types = [];
			$this->sourcesParsed = FALSE;
		}//if($reset)
		if(strlen($str)) {
			$src = [$str];
		} else {
			if($this->sourcesParsed) { return; }
			$this->sourcesParsed = TRUE;
			$src = [];
			foreach($this->srcFiles as $file) {
				if(!file_exists($file)) {
					Debugger::addMessage('Source file ['.$file.'] not found!');
					continue;
				}//if(!file_exists($file))
				$src[] = trim(file_get_contents($file));
			}//END foreach
		}//if(strlen($str))
		$data = Parser::parse(implode("\n",$src),$this);
		if(isset($data['types']) && is_array($data['types'])) { $this->types = $data['types']; }
		if(isset($data['methods']) && is_array($data['methods'])) { $this->methods = $data['methods']; }
	}//END protected function parseSource
	/**
	 * Create the WSDL
	 *
	 * @param bool      $reset If TRUE clears the cache and re-parse sources
	 * @param bool|null $optimize If TRUE, override the Generator->optimize property and force optimizing (default: FALSE)
	 * @return string The UTF-8 encoded WSDL as string
	 * @throws \Exception
	 */
	public function generateWsdl(bool $reset = FALSE,?bool $optimize = NULL): string {
		// // Ask the cache
		if(!$reset && $this->cacheWsdl && !$optimize) {
			throw new \Exception('Not implemented yet!');
		}//if(!$reset && $this->cacheWsdl && !$optimize)
		$this->parseSource($reset);
		if(!count($this->methods) || !count($this->types)) {
			Debugger::addMessage('generateWsdl: No methods and types found!');
			throw new \Exception('No methods and no complex types are available');
		}//if(!count($this->methods) || !count($this->types))
		if(!strlen($this->serviceName)) {
			Debugger::addMessage('generateWsdl: No service name!');
			throw new \Exception('Could not determine webservice handler class name');
		}//if(!strlen($this->serviceName))
		$wsdl = $this->getWsdlHeader();
		$wsdl .= $this->getWsdlTypeSchema();
		$wsdl .= $this->getWsdlMessages();
		$wsdl .= $this->getWsdlPorts();
		$wsdl .= $this->getWsdlBindings();
		$wsdl .= $this->getWsdlService();
		$wsdl .= $this->getWsdlFooter();
		$wsdl = self::optimizeWsdl($wsdl,(isset($optimize) ? $optimize : $this->optimize));
		// // Fill the cache
		if($this->cacheWsdl && !$optimize) {
			Debugger::addMessage('WSDL cache not implemented yet!');
		}//if($this->cacheWsdl && !$optimize)
		return $wsdl;
	}//END public function generateWsdl
	/**
	 * Output the WSDL to the client
	 *
	 * @param boolean $withHeaders Output XML headers (default: TRUE)
	 * @return void
	 * @throws \Exception
	 */
	public function outputWsdl(bool $withHeaders = FALSE): void {
		if($withHeaders) { header('Content-Type: text/xml; charset=UTF-8',TRUE); }
		echo $this->generateWsdl();
	}//END public function outputWsdl
	/**
	 * Get not nillable types array
	 *
	 * @return array Non-nillable types
	 * @access public
	 * @static
	 */
	public static function getNonNillable(): array {
		return self::$nonNillable;
	}//END public static function getNonNillable
	/**
	 * Get basic types array
	 *
	 * @return array Basic types
	 * @access public
	 * @static
	 */
	public static function getBasicTypes(): array {
		return self::$basicTypes;
	}//END public static function getBasicTypes
	/**
	 * Format XML human readable
	 *
	 * @param string $xml The XML
	 * @return string Human readable XML
	 * @access public
	 * @static
	 * @throws \Exception
	 */
	public static function formatXml(string $xml): string {
		$input = fopen('data://text/plain,'.$xml,'r');
		$output = fopen('php://temp','w');
		$xf = new Formatter($input,$output);
		$xf->format();
		rewind($output);
		$xml = stream_get_contents($output);
		fclose($input);
		fclose($output);
		return $xml;
	}//END public static function formatXml
	/**
	 * Remove tabs and newline from XML
	 *
	 * @param string $xml The unoptimized XML
	 * @return string The optimized XML
	 * @access public
	 * @static
	 */
	public static function optimizeXml(string $xml): string {
		return preg_replace('/[\n|\t]/','',$xml);
	}//END public static function optimizeXml
	/**
	 * Optimize WSDL XML
	 *
	 * @param string $data WSDL string
	 * @param bool   $readable Get the XML in human readable format
	 * @return string Optimized WSDL XML
	 * @throws \Exception
	 * @acces public
	 * @static
	 */
	public static function optimizeWsdl(string $data,bool $readable = TRUE): string {
		if($readable) { return self::formatXml($data); }
		return self::optimizeXml($data);
	}//END public static function optimizeWsdl
}//END class Generator