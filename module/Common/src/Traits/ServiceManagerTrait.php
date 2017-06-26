<?php
namespace Common\Traits;
 
use Interop\Container\ContainerInterface as ZendServiceManager;
 
trait ServiceManagerTrait {
	
  protected $serviceManager;
 
  public function setServiceManager(ZendServiceManager $serviceManager){
  	$this->serviceManager = $serviceManager;
  	return $this;
  }
  
  
  public function getServiceManager(){
      return $this->serviceManager;
  }
 
  /**
   * @param String $name
   * @return object|array
   */
  public function serv($name){
  	return $this->serviceManager->get($name);
  }
  
}