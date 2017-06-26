<?
namespace Common\ControllerPlugin;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\ControllerPlugin;

/**
 * @ControllerPlugin(name = "sendFlashMessage")
 */
class SendFlashMessage extends AbstractPlugin{
	
	public function __invoke($msg = null, $class=Flash::INFO){
		if($msg === null){
			return $this;
		} else {
			$_SESSION[Flash::SESSION_KEY][] = [
					'message' => $msg,
					'class' => $class
			];
		}   	
    }
    
    public function clear(){
    	$container = new Container('flash_message');
    	unset($container->items);
    } 
    
}
    