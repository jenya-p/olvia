<?
namespace Common;

use Zend\Mvc\Controller\Plugin\PluginInterface;
use Zend\Session\Container;
use Zend\Stdlib\DispatchableInterface;
use Zend\View\Helper\HelperInterface;
use Zend\View\Renderer\RendererInterface;

class Identity implements PluginInterface, HelperInterface{
	
	var $iden;
	private $serviceLocator;
	
	public function __construct($serviceLocator){
		$this->iden = new Container('CurrentUser');
		if(!isset($this->iden['id'])){
			$this->clear();
		}
	}
	
	public function setServiceLocator($serviceLocator) {
		$this->serviceLocator = $serviceLocator;
	}
	
	public function __get($key){
		return $this->iden[$key];
	}
	
	public function __set($key, $val){
		$this->iden[$key] = $val;
	}
	
	public function set($values){
		foreach ($values as $key => $value){
			$this->iden[$key] = $value;
		}
	}
	
	public function clear(){
		$this->iden['id'] = 0;
		$this->iden['name'] = '';
		$this->iden['roles'] = ['guest'];
	}
	
	public function hasRole($roleName){
		return in_array($roleName, $this->iden['roles']);		
	}
	
	public function isAdmin(){
		return in_array('admin', $this->iden['roles']);
	}
	
	public function isLogged(){
		return !empty($this->iden['id']);
	}
	
	public function	__invoke(){
		return $this;
	}
	
	
	var $admin = false;
	public function getAdmin(){
		/* @var $adminDb \Application\Model\AdminDb */
		$id = $this->id;
		if($this->admin === false){
			$adminDb = $this->serviceLocator->get('Application\Model\AdminDb');
			$this->admin = $adminDb->get($id);
			if(empty($this->admin)){
				$this->clear();
				throw new \Exception("Не найден профайл администратора id=".$id);
			}
		}
		return $this->admin;
	}
	
	
	
	protected $view = null;
	public function setView(RendererInterface $view){
		$this->view = $view;
		return $this;
	}
	public function getView(){
		return $this->view;
	}
	
	protected $controller;
	public function setController(DispatchableInterface $controller){
		$this->controller = $controller;
	}
	public function getController(){
		return $this->controller;
	}
	

}