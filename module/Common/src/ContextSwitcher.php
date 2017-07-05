<?php

namespace Common;

use Zend\EventManager\AbstractListenerAggregate;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\MvcEvent;
use Zend\Http\PhpEnvironment\Request;
use Zend\Http\Header\SetCookie;
use Zend\Mvc\Service\ViewTemplatePathStackFactory;
use Zend\View\Resolver\TemplatePathStack;

class ContextSwitcher extends AbstractListenerAggregate{
	
	const MODE_DESKTOP = "desktop";
	const MODE_MOBILE = "mobile";
	
	var $mobileViewPath = null;
	
	var $mode = self::MODE_DESKTOP;

	public function getMode() {return $this->mode;}

	public function setMode($mode) {
		$this->mode = $mode;
	}

	public function setMobileViewPath($mobileViewPath) {
		$this->mobileViewPath = $mobileViewPath;
	}

	public function attach(EventManagerInterface $events, $priority = 1) {
		$events->attach(MvcEvent::EVENT_DISPATCH, [$this, 'dispatch']);		
		$events->attach(MvcEvent::EVENT_RENDER, [$this, 'render']);		
	}
	

	public function dispatch(MvcEvent $e){
		/* @var $request Request */
		$request = $e->getRequest();
		
		if(! $request instanceof Request ){ return ;}
		
		$routeName = $e->getRouteMatch()->getMatchedRouteName();
		
		if(substr($routeName, 0, 8) == 'private/'){ return ;}
		
		if(empty($request->getCookie()->mobile_mode)){
			/* @var $response Response */
			$response = $e->getResponse();
			
			/* var $mobileDetect Mobile_Detect */
			$mobileDetect = $e->getApplication()->getServiceManager()->get('mobileDetect');
			
			if($mobileDetect->isMobile() || $mobileDetect->isTablet()){
				$cookieValue = self::MODE_MOBILE;
			} else {
				$cookieValue = self::MODE_DESKTOP;
			}
			
			$cookie = new SetCookie('mobile_mode', $cookieValue, null, '/');
			$response->getHeaders()->addHeader($cookie);
		} else if ($request->getCookie()->mobile_mode == self::MODE_MOBILE){
			$this->mode = self::MODE_MOBILE;
		}
		
	}
	
	
	public function render(MvcEvent $e){
		/* @var $request Request */
		$request = $e->getRequest();
		
		if(! $request instanceof Request  ){ return ;}
		
		$routeName = $e->getRouteMatch()->getMatchedRouteName();
		
		if(substr($routeName, 0, 8) == 'private/'){ return ;}
		
		if($this->mode == self::MODE_MOBILE){
			$sm = $e->getApplication()->getServiceManager();
			
			/* @var $viewResolver TemplatePathStack */
			$viewResolver = $sm->get('ViewTemplatePathStack');
			$config = $sm->get('Config');
			$viewResolver->addPath($this->mobileViewPath);
		}
				
	}
	
	
	
}