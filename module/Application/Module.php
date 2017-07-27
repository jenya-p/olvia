<?php
namespace Application;

use Application\Model\Content\ContentDb;
use Application\ViewHelper\Content;
use Application\ViewHelper\Popup;
use Application\ViewHelper\Seo;
use Application\ViewHelper\UserFlowFlash;
use Interop\Container\ContainerInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\ModuleManager\Feature\ViewHelperProviderInterface;
use Application\ViewHelper\UserFlowCartInfo;
use Application\ViewHelper\SitePaginator;
use Application\ViewHelper\MonthPaginator;
use Application\ViewHelper\WeekPaginator;
use Zend\Http\Header\SetCookie;
use Zend\Http\Request;
use Zend\Mvc\MvcEvent;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Common\ContextSwitcher;
use Zend\ModuleManager\Feature\ControllerPluginProviderInterface;
use Application\ViewHelper\WeekPaginatorOptions;

class Module implements ConfigProviderInterface, AutoloaderProviderInterface, ViewHelperProviderInterface,
	BootstrapListenerInterface {

	public function getConfig() {
		return include __DIR__ . '/config/module.config.php';
	}
	
	
	public function onBootstrap(\Zend\EventManager\EventInterface $e) {
		/* @var $app \Zend\Mvc\Application */
		$app = $e->getApplication();
		$context = new ContextSwitcher();
		$context->setMobileViewPath(__DIR__.DS.'view_mobile'.DS);
		$context->attach($app->getEventManager());
		
	}

	public function getViewHelperConfig() {
		return [ 
				'factories' => [ 
						'content' => function (ContainerInterface $cnt) {
							$contentDb = $cnt->get(ContentDb::class);
							return new Content($contentDb);
						}, 
						'seo' => function(ContainerInterface $cnt){
							$vhm = $cnt->get('ViewHelperManager');
		                	$service = new Seo($vhm);
			                return $service;
		                },
		                'popup' => function(ContainerInterface $cnt){
			                $renderer = $cnt->get('ViewRenderer');
			                $service = new Popup($renderer);
			                return $service;
		                },
		                'userFlowFlash' => function(ContainerInterface $cnt){
			                $renderer = $cnt->get('ViewRenderer');
			                $service = new UserFlowFlash($renderer);
			                return $service;
		                },
		                'userFlowCartInfo' => function(ContainerInterface $cnt){
			                $renderer = $cnt->get('ViewRenderer');
			                $service = new UserFlowCartInfo($renderer);
			                return $service;
		                },
		                'sitePaginator' => function(ContainerInterface $cnt){
			                $service = new SitePaginator();
			                return $service;
		                }, 
						'monthPaginator' => function(ContainerInterface $cnt){
							$vhm = $cnt->get('ViewHelperManager');
		                	$service = new MonthPaginator($vhm);
			                return $service;
		                }, 
						'weekPaginator' => function(ContainerInterface $cnt){
							$vhm = $cnt->get('ViewHelperManager');
		                	$service = new WeekPaginator($vhm);
			                return $service;
		                },
		                'weekPaginatorOptions' => function(ContainerInterface $cnt){
			                $vhm = $cnt->get('ViewHelperManager');
			                $service = new WeekPaginatorOptions($vhm);
			                return $service;
		                }
		                
				] 
		];
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
	

}
