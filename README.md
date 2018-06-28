# PHP SOAP WSDL Generator and server
Generate WSDL from PHP comments or programmatically and optionally create SOAP WSDL server.

### This project is based on [PHP WSDL generator](http://wan24.xpress-blog.de/software/php-wsdl-generator/) by Andreas Zimmermann.


## Description
This library provides support for WSDL XML generation and running a PHP SOAP server (with or without WSDL support).
Features:
- Extract WSDL definitions from PHP comments
- Optimize WSDL XML (remove line breaks and tabs)
- Create SOAP server with or without WSDL support (PHP SoapServer)
- Support for "simple" complex types and one-dimensional arrays
- The SOAP endpoint URI is determined automatically if not provided
- Generate WSDL definition for one or more PHP source files
- http Auth Login to use Web services


## System Requirements
- PHP 7.1+
- PHP SOAP extension


## Installation

### Composer
Add PHP-WSDL repository to your `composer.json` file
``` json
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/adeotek/php-wsdl.git"
    }
],
```
Add library
``` bash
$ composer require adeotek/php-wsdl
```
or add it to your `composer.json` file
``` json
"require": {
    "adeotek/php-wsdl": "~1.0.0"
}
```

### Manual
To install manually install the library, is only necessary to copy the content of the `src` directory into your project.


## How to use PhpWsdl

###Classes description
- AdeoTEK\PhpWsdl\SoapServer
    - SOAP server class (uses PHP SoapServer class)
- AdeoTEK\PhpWsdl\Generator
    - Class responsible for WSDL generation 
- AdeoTEK\PhpWsdl\BaseObject
    - Abstract class implemented by all parsing resulted objects
- AdeoTEK\PhpWsdl\Element
    - Complex types elements (properties) object 
- AdeoTEK\PhpWsdl\Parameter
    - Methods parameters and return types objects resulted from parsing
- AdeoTEK\PhpWsdl\ComplexType
    - Complex types objects resulted from parsing (arrays or classes)
- AdeoTEK\PhpWsdl\Method
    - Methods objects resulted from parsing
- AdeoTEK\PhpWsdl\Parser
    - Class responsible with parsing PHP source code
- AdeoTEK\PhpWsdl\Formatter
    - Helper class used for XML formatting
- AdeoTEK\PhpWsdl\Debugger
    - Class used for debugging

###Keywords processed by the parser in order to generate WSDL definitions:
- @pw_complex
    - A complex type (array/class)
- @pw_element
    - A complex type property/element
- @param
    - A method parameter
- @return
    - A method return type

Key-value options can be provided for complex types and/or their properties.
The options are defined using `@pw_set` keyword and must be placed right before the target element.

###Usage
- Simple usage example (SoapServer)
``` php
$soapServer = new AdeoTEK\PhpWsdl\SoapServer([
    'className'=>'\Some\Class',
    'serviceUri'=>'service_uri',
    'wsdlConfig'=>[
        'optimize'=>TRUE,
        'includeDesc'=>FALSE,
        'srcFiles'=>[
            'path/OtherClass.php',
            'path/sourceFile.php'
        ],
    ],
]);
$soapServer->runServer();
		
$debugMessages = AdeoTEK\PhpWsdl\Debugger::getMessages();
```


### License
PhpWsdl is GPL (v3 or later) licensed per default. See `LICENSE` file for the full GPLv3 license text.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version. 

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details. 

You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
