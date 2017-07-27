<?php 
namespace Common;

use Zend\Mvc\MvcEvent;
use Zend\Http\PhpEnvironment\Request;
use Zend\Session\Container;
use \Common\Identity;
use Application\Controller\IndexController;
use Common\ControllerPlugin\SendFlashMessage;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Common\Traits\ConfigTrait;
use Common\Traits\ConfigAware;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\InitializableInterface;
use Application\Model\UserDb;
use Admin\Forms\Users\UserForm;
use Common\ControllerPlugin\Platform;

/**
 * @method \Common\Identity identity()
 * @method \Zend\Http\PhpEnvironment\Request getRequest()
 * @method \Common\ControllerPlugin\SendFlashMessage sendFlashMessage($msg, $class)
 * @method \Application\ControllerPlugin\UserFlow userFlow()
 * @method \Common\ControllerPlugin\Platform platform()
 */

class SiteController extends \Zend\Mvc\Controller\AbstractActionController implements ServiceManagerAware, ConfigAware{
	
	use ServiceManagerTrait, ConfigTrait;
                
    const GUEST_ROLE = 'guest';
    const DEFAULT_LAYOUT = 'layout/public';
    
    private function isAccessible($className, $methodName){
    	$conf = $this->getConfig('actionNessesaryRolesMap');
    	
    	$roles = null;
    	if(isset($conf[$className])){
    		if(isset($conf[$className][$methodName])){
    			$roles = $conf[$className][$methodName];
    		} else if(isset($conf[$className]['*'])){
    			$roles = $conf[$className]['*'];    			
    		}
    	}
    	if(empty($roles)) {
    		return true;
    	}
    	if(!is_array($roles)){
    		$roles = array($roles);
    	}
    	$identity = $this->identity();
    	
    	foreach ($roles as $nesRole){
    		if($identity->hasRole($nesRole)) return true;
    	}    	
    	return false;
    }
    
    private function getLayout($className, $methodName){
    	$conf = $this->getConfig('actionLayoutMap');
    	$layout = null;
 
    	if(isset($conf[$className])){
    		if(isset($conf[$className][$methodName])){
    			$layout = $conf[$className][$methodName];
    		} else if(isset($conf[$className]['*'])){
    			$layout = $conf[$className]['*'];
    		}
    	}
    	 
    	if(empty($layout)) {
    		self::DEFAULT_LAYOUT;
    	} else {
    		return 'layout/'.$layout;
    	}
    }
    
    
    public function init(){}
    
    public function onDispatch(MvcEvent $e){
    	
    	$routeMatch = $e->getRouteMatch();
    	if (!$routeMatch) {    		
    		throw new \DomainException('Missing route matches; unsure how to retrieve action');
    	}
    	
    	$action = $routeMatch->getParam('action', 'not-found');
    	$method = static::getMethodFromAction($action);
    	$class = get_class($this);    	

    	if ($this->identity()->isLogged()){
    		/* @var $userDb UserDb */
    		$userDb = $this->serv(UserDb::class);
    		$userDb->accessed($this->identity()->id);
    	}
    			
    	
	   	if($this->isAccessible($class, $method)){
    		$this->layout($this->getLayout($class, $method));
    		$this->init();
    		
    		if (!method_exists($this, $method)) {
    			$method = 'notFoundAction';
    		}
    		
    		$actionResponse = $this->$method();
    		
    		$e->setResult($actionResponse);
    		
    		return $actionResponse;
    		
    	} else if ($this->identity()->isLogged()){
    		
    		$event      = $this->getEvent();
    		$routeMatch = $event->getRouteMatch();
    		$routeMatch->setParam('action', 'error403');
    		$this->getResponse()->setStatusCode(403);
    		
    		$vm = new ViewModel();
    		$vm->setTemplate('error/403');
    		$event->setResult($vm);
    		return $vm;
    		
    		
    	} else {
    		$this->userFlow()->redirectAfterLogin($this->getRequest()->getRequestUri());
    		return $this->redirect()->toRoute('login');
    	}
    }
  
    
    public function getCurrentRoute(){
    	$r = $this->serv('Application')->getMvcEvent()->getRouteMatch();
    	return $r;
    }
    
}