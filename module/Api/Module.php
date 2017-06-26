<?php

namespace Api;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;

class Module implements ConfigProviderInterface, AutoloaderProviderInterface, ServiceProviderInterface {

	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}

	public function getAutoloaderConfig() {
		return [ 
				'Zend\Loader\StandardAutoloader' => [ 
						'namespaces' => [ 
								__NAMESPACE__ => __DIR__ . '/src/' 
						] 
				] 
		];
	}

	public function getServiceConfig() {
		
	}
}
