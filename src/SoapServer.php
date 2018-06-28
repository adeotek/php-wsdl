<?php
/**
 * SoapWsdlServer class file
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
 * Class SoapWsdlServer
 *
 * @package PhpWsdl
 */
class SoapServer {
	/**
	 * Target class (service name)
	 *
	 * @var string
	 */
	protected $className = NULL;
	/**
	 * SOAP service URI
	 *
	 * @var string
	 */
	protected $serviceUri = NULL;
	/**
	 * The namespace
	 *
	 * @var string
	 */
	protected $namespace = NULL;
	/**
	 * The SOAP endpoint URI
	 *
	 * @var string
	 */
	protected $endPoint = NULL;
	/**
	 * PHP login username
	 *
	 * @var string
	 */
	protected $user = NULL;
	/**
	 * PHP login password
	 *
	 * @var string
	 */
	protected $password = NULL;
	/**
	 * The WSDL generator config
	 *
	 * @var array
	 */
	protected $wsdlConfig = [];
	/**
	 * The options for the PHP SoapServer
	 *
	 * @var array
	 */
	protected $soapServerOptions = NULL;
	/**
	 * SoapWsdlServer constructor
	 *
	 * @param array $params Configuration array
	 * @return void
	 * @access public
	 */
	public function __construct(array $params = []) {
		$this->namespace = isset($params['namespace']) && strlen($params['namespace']) ? $params['namespace'] : $this->computeNamespace();
		$this->endPoint = isset($params['endPoint']) && strlen($params['endPoint']) ? $params['endPoint'] : $this->computeEndPoint();
		if(isset($params['className']) && strlen($params['className'])) { $this->className = $params['className']; }
		if(isset($params['serviceUri']) && strlen($params['serviceUri'])) { $this->serviceUri = $params['serviceUri']; }
		if(isset($params['wsdlConfig']) && is_array($params['wsdlConfig'])) { $this->wsdlConfig = $params['wsdlConfig']; }
		// SOAP server options
		$this->soapServerOptions = [
			'soap_version'=>(isset($params['soapVersion']) && is_integer($params['soapVersion']) ? $params['soapVersion'] : SOAP_1_2),
			'encoding'=>'UTF-8',
			'compression'=>SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP | 9
		];
	}//END public function __construct
	/**
	 * Get login required
	 *
	 * @return bool Login required
	 * @access public
	 */
	public function requireLogin(): bool {
		return (is_string($this->user) && strlen($this->user));
	}//END public function requireLogin
	/**
	 * Run SOAP server (with or without WSDL)
	 *
	 * @return void
	 * @access public
	 * @throws \Exception
	 */
	public function runServer(): void {
		// Login
		if($this->requireLogin()) {
			$user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : NULL;
			$password = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : NULL;
			if(strtolower($this->user)!=strtolower($user) || (is_string($this->password) && strlen($this->password) && $this->password!=$password)) {
				// self::Debug('Login required');
				header('WWW-Authenticate: Basic realm="SOAP webservice login required"');
			    header('HTTP/1.0 401 Unauthorized');
			    return;
			}//if(strtolower($this->user)!=strtolower($user) || (is_string($this->password) && strlen($this->password) && $this->password!=$password))
		}//if($this->requireLogin())
		if(isset($_GET['WSDL']) || isset($_GET['wsdl'])) {
			$this->wsdlConfig['namespace'] = $this->namespace;
			$this->wsdlConfig['endPoint'] = $this->endPoint;
			$this->wsdlConfig['optimize'] = !isset($_GET['readable']); // Call with "?WSDL&readable" to get human readable WSDL
			$generator = new Generator($this->wsdlConfig);
			$generator->outputWsdl(TRUE);
		} else {
			if(!strlen($this->className) || !strlen($this->serviceUri)) {
				header('HTTP/1.0 500 Invalid SoapServer configuration');
				return;
			}//if(!strlen($this->className) || !strlen($this->serviceUri))
			// When in non-wsdl mode the uri option must be specified
			$options = $this->soapServerOptions;
			$options['actor'] = $this->endPoint;
			$options['uri'] = $this->serviceUri;
			// Create a new SOAP server
		    $server = new \SoapServer(NULL,$options);
		    // Attach the API class to the SOAP Server
		    $server->setClass($this->className);
		    // Start the SOAP requests handler
		    $server->handle();
		}//if(isset($_GET['WSDL']) || isset($_GET['wsdl']))
	}//END public function runServer
	/**
	 * Compute endpoint URI
	 *
	 * @return string Computed endpoint
	 * @access public
	 * @static
	 */
	public static function computeEndPoint(): string {
		return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];
	}//END public static function computeEndPoint
	/**
	 * Compute namespace
	 *
	 * @return string Computed namespace
	 * @access public
	 * @static
	 */
	public static function computeNamespace(): string {
		return ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=='on') ? 'https' : 'http').'://'.$_SERVER['SERVER_NAME'].str_replace(basename($_SERVER['SCRIPT_NAME']),'',$_SERVER['SCRIPT_NAME']);
	}//END public static function computeNamespace
}//END class SoapServer