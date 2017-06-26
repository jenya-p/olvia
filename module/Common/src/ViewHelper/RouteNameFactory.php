<?php
namespace Common\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;

/**
 * @ViewHelper(name="routeName", type="factory")
 * */
class RouteNameFactory implements FactoryInterface{
	
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
		$routeMatch = $container->get('Application')->getMvcEvent()->getRouteMatch();
		if(!empty($routeMatch)){
			$routeName = $routeMatch->getMatchedRouteName();
		} else {
			$routeName = '';
		}
		return new RouteName($routeName);
	}

}