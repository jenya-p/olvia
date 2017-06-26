<?php

namespace Admin;

use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Interop\Container\ContainerInterface;
use Admin\Model\HistoryReader;
use Admin\Model\HistoryWriter;
use Admin\Model\Users\UserDb;

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
		return [ 
				'factories' => [ 
						'HistoryReader' => function (ContainerInterface $cnt) {
							$dbAdapter = $cnt->get('DbAdapter');
							$config = $cnt->get('Config');
							$service = new HistoryReader($dbAdapter, $config ['history_db_settings']);
							return $service;
						},
						'HistoryWriter' => function ($cnt) {
							$dbAdapter = $cnt->get('DbAdapter');							
							$config = $cnt->get('Config');
							$identity = $cnt->get('identity');
							$service = new HistoryWriter($dbAdapter, $config ['history_db_settings']);
							$service->setUserId($identity->id);
							return $service;
						} 
				] 
		];
	}
}
