<?php
namespace Common\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;

class RouteName extends \Zend\View\Helper\AbstractHelper {
		
	var $routeName;
	public function __construct($routeName){
		$this->routeName = $routeName;
	}
	
	
	public function __invoke($template = null, $vars = null){
		return $this->routeName;
	}	
	
	
}