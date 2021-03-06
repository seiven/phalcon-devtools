<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2015 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Builder;

use Phalcon\Utils;
use SplFileObject;

/**
 * Controller Class
 *
 * Builder to generate controller
 *
 * @package     Phalcon\Builder
 * @copyright   Copyright (c) 2011-2015 Phalcon Team (team@phalconphp.com)
 * @license     New BSD License
 */
class Controller extends Component
{
    /**
     * Create Builder object
     *
     * @param array $options Builder options
     * @throws BuilderException
     */
    public function __construct(array $options = array())
    {
        if (!isset($options['name'])) {
            throw new BuilderException('Please specify the controller name.');
        }

        if (!isset($options['force'])) {
            $options['force'] = false;
        }

        parent::__construct($options);
    }

    /**
     * @return string
     * @throws \Phalcon\Builder\BuilderException
     */
    public function build()
    {
    	// load root path
        if ($this->options->contains('directory')) {
            $this->path->setRootPath($this->options->get('directory'));
        }
        // load module path
        if ($this->options->contains('module')) {
        	// get module dir
        	$config = $this->getConfig();
        	if (!isset($config->application->modulesDir)) {
        		if (!file_exists($config->application->modulesDir)) mkdir($config->application->modulesDir);
        		throw new BuilderException('Please specify a modules directory.');
        	}
        	$_rootPath = rtrim($config->application->modulesDir, '\\/') . DIRECTORY_SEPARATOR;
        	$module = $this->options->get('module');
        	if (!file_exists($_rootPath.$module)){
        		throw new BuilderException('module not frond.');
        	}
            $this->path->setRootPath($_rootPath.$module. DIRECTORY_SEPARATOR);
        }
        $namespace = 'Application\\'.$this->options->get('module').'\Controllers';
        if (!$this->options->contains('namespace') && $this->options->contains('module') && $this->checkNamespace($namespace)) {
        	// if namespace is empty and has module
        	$namespace = 'namespace '.$namespace.';'.PHP_EOL.PHP_EOL;
        }elseif ($this->options->contains('namespace') && $this->checkNamespace($this->options->get('namespace'))) {
            $namespace = 'namespace '.$this->options->get('namespace').';'.PHP_EOL.PHP_EOL;
        }else{
        	$namespace = '';
        }

        $baseClass = $this->options->get('baseClass', '\Phalcon\Mvc\Controller');
 
        $config = $this->getConfig();
		if (!isset($config->application->controllersDir)) {
        	throw new BuilderException('Please specify a controller directory.');
		}

        $controllersDir = $config->application->controllersDir; 
            
        if (!$this->options->contains('name')) {
            throw new BuilderException('The controller name is required.');
        }

        $name = str_replace(' ', '_', $this->options->get('name'));

        $className = Utils::camelize($name);

        // Oops! We are in APP_PATH and try to get controllersDir from outside from project dir
        if ($this->isConsole() && substr($controllersDir, 0, 3) === '../') {
            $controllersDir = ltrim($controllersDir, './');
        }

        $controllerPath = rtrim($controllersDir, '\\/') . DIRECTORY_SEPARATOR . $className . "Controller.php";
        $code = "<?php\n\n".$namespace."class ".$className."Controller extends ".$baseClass."\n{\n\n\tpublic function indexAction()\n\t{\n\n\t}\n\n}\n\n";
        $code = str_replace("\t", "    ", $code);

        if (file_exists($controllerPath) && !$this->options->contains('force')) {
            throw new BuilderException(sprintf('The Controller %s already exists.', $name));
        }

        $controller = new SplFileObject($controllerPath, 'w');

        if (!$controller->fwrite($code)) {
            throw new BuilderException(
                sprintf('Unable to write to %s. Check write-access of a file.', $controller->getRealPath())
            );
        }

        if ($this->isConsole()) {
            $this->_notifySuccess(
                sprintf('Controller "%s" was successfully created.', $name)
            );
            echo $controller->getRealPath(), PHP_EOL;
        }

        return $className . 'Controller.php';
    }
}
