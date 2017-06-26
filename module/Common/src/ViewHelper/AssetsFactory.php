<? 
namespace Common\ViewHelper;
use ZfAnnotation\Annotation\ViewHelper;
use Zend\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Zend\Router\Http\RouteMatch;

/**
 * @ViewHelper(name="assets", type="factory")
 * */
class AssetsFactory implements FactoryInterface{
	
	public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
		$config = $container->get('Config');

		$routeMatch = $container->get('Application')->getMvcEvent()->getRouteMatch();
		
		if(!empty($routeMatch) && $routeMatch instanceof RouteMatch){
			$routeMatch->getMatchedRouteName();
			
			$assetPath = $routeMatch->getParam('controller');
			$assetPath = str_replace(['\\Controller', 'Controller', '\\'], ['', '', DIRECTORY_SEPARATOR], $assetPath); 
			
			$assetPath .= DIRECTORY_SEPARATOR.$routeMatch->getParam('action');
			$assetPath = mb_strtolower($assetPath);

		} else {
			$assetPath = null;
		}
		return new Assets($config['path']['public'], $assetPath);
	}
	
}