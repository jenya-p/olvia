<?

namespace Common;

use Zend\Stdlib\ArrayUtils;
use ZfAnnotation\Event\ParseEvent;
use Common\Annotations\Roles;
use Common\Annotations\Layout;
use Doctrine\Common\Annotations\Annotation;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\EventManager\EventManagerInterface;
use ZfAnnotation\Parser\ClassAnnotationHolder;

class ControllerAnnotationListener implements ListenerAggregateInterface{

	use ListenerAggregateTrait;
	
	var $roles = [];
	var $layout = [];
	
	public function onClassParsed(ParseEvent $e) {

		$cah = $e->getTarget();
		
		/* @var $cah \ZfAnnotation\Parser\ClassAnnotationHolder */
		if ($cah instanceof ClassAnnotationHolder) {
			if (! $cah->getClass()->isSubclassOf(SiteController::class) || $cah->getClass()->isAbstract()) {
				return ;
			}
			
			foreach ( $cah->getAnnotations() as $classAnnotation ) {
				if($classAnnotation instanceof Roles){
					$this->processRolesAnnotation($classAnnotation, $cah->getClass());
				} else if($classAnnotation instanceof Layout){
					$this->processLayoutAnnotation($classAnnotation, $cah->getClass());
				}				
			}
			
			/* @var $mah \ZfAnnotation\Parser\MethodAnnotationHolder  */
			foreach ( $cah->getMethods() as $mah ) {
				foreach ( $mah->getAnnotations() as $methodAnnotation ) {
					if($methodAnnotation instanceof Roles){
						$this->processRolesAnnotation($methodAnnotation, $cah->getClass(), $mah->getMethod());
					} else if($methodAnnotation instanceof Layout){
						$this->processLayoutAnnotation($methodAnnotation, $cah->getClass(), $mah->getMethod());
					}
				}
			}
		}
	}
	

	public function onFinalize(ParseEvent $event) {
		$config = $event->getTarget();
		
		$config = ArrayUtils::merge($config, [
				'actionNessesaryRolesMap' => $this->roles, 
				'actionLayoutMap' => $this->layout
				
		]);
		
		$event->setTarget($config);
	}
	

	private function processRolesAnnotation($annotation, $class, $method = null){
		$value = [
			'roles' => $annotation->value
		];
		if($method == null){
			$this->roles[$class->getName()]['*'] = $value;
		} else {
			$this->roles[$class->getName()][$method->getName()] = $value;
		}		
	}
	
	private function processLayoutAnnotation($annotation, $class, $method = null){
		$value = $annotation->value;
		if($method == null){
			$this->layout[$class->getName()]['*'] = $value;
		} else {
			$this->layout[$class->getName()][$method->getName()] = $value;
		}
	}

	public function attach(EventManagerInterface $events, $priority = 1) {
	
		$events->attach(ParseEvent::EVENT_CLASS_PARSED, [$this, 'onClassParsed'], 100);
	
		$events->attach(ParseEvent::EVENT_FINALIZE, [$this, 'onFinalize'], 100);
	
	}
}