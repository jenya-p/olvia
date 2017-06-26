<?php
namespace Common\Traits;
 
use Interop\Container\ContainerInterface as ZendServiceManager;
 
interface ServiceManagerAware{
	
 
  public function setServiceManager(ZendServiceManager $serviceManager);
  
  public function getServiceManager();
 
  public function serv($name);
  
}