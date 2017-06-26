<?php

namespace Common;

use Common\Db\AdapterFactory;
use Common\Db\TableFactory;
use Common\ViewHelper\Html;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Interop\Container\ContainerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ControllerPluginProviderInterface;
use Zend\ModuleManager\Feature\ControllerProviderInterface;
use Zend\ModuleManager\Feature\InitProviderInterface;
use Zend\ModuleManager\Feature\ServiceProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;
use Zend\ModuleManager\ModuleManagerInterface;
use Zend\Mail\Transport\Sendmail;
use Common\ViewHelper\Config;
use Common\ViewHelper\Image;
use Common\ViewHelper\Minfiers\HeadLink;
use Common\ViewHelper\Minfiers\InlineScript;

class Module implements ConfigProviderInterface, AutoloaderProviderInterface,
	InitProviderInterface, ControllerProviderInterface, ServiceProviderInterface,
	ViewHelperProviderInterface, ControllerPluginProviderInterface
	{
	var $identity;
		
	public function init(ModuleManagerInterface $manager) {
		AnnotationRegistry::registerLoader('class_exists');
		$this->identity = new Identity();
	}
	
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
	public function getControllerConfig() {
		return [
			'initializers' => [
					TraitInitializer::class
			]
		];
	}
	
	public function getServiceConfig() {
		return [
			'initializers' => [
				TraitInitializer::class
			],
			'factories' => [
				'DbAdapter' => AdapterFactory::class,
				ImageService::class => function (ContainerInterface $cnt) {
					$config = $cnt->get('Config');
					$service = new ImageService($config['path']['images'], $config['imagesUrl']);
					return $service;
				},
				Mailer::class => function($sm){
					$transport = new Sendmail();
					$renderer = $sm->get('ViewRenderer');
					$service = new Mailer($transport, $renderer, $sm->get('Config'));
					return $service;
				},
			],
			'abstract_factories' => [
				TableFactory::class
			],
			'services' => [
				'identity' => $this->identity
			]			
		];
	}

	
	
	public function getViewHelperConfig() {
		return [
			'initializers' => [
				TraitInitializer::class
			],
			'services' => [
				'identity' => $this->identity
			],
			'factories' => [
				'html' => function (ContainerInterface $cnt){
					$config = $cnt->get('Config');
					return new Html($config);
				},
				'config' => function(ContainerInterface $cnt){
					$config = $cnt->get('Config');
					return new Config($config);
				},
				'image' => function(ContainerInterface $cnt){
					$imageService = $cnt->get(ImageService::class);
					return new Image($imageService);
				}
				,
				\Zend\View\Helper\HeadLink::class => function(ContainerInterface $cnt){
					$config = $cnt->get('Config');					
					if($config['environment'] == 'dev'){
						return new \Zend\View\Helper\HeadLink();
					} else {
						return new HeadLink($config['path']['public']);
					}
				}
				,
				\Zend\View\Helper\InlineScript::class => function(ContainerInterface $cnt){					
					$config = $cnt->get('Config');
					if($config['environment'] == 'dev'){
						return new \Zend\View\Helper\InlineScript();
					} else {
						return new InlineScript($config['path']['public']);
					}					
				}
			]
		];
	}
	
	public function getControllerPluginConfig() {
		return [
			'services' => [
				'identity' => $this->identity
			],
			'initializers' => [
					TraitInitializer::class
			]
		];
	}
	
}
