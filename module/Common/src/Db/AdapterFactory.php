<?php
namespace Common\Db;

use Common\Db\Adapter;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AdapterFactory implements FactoryInterface{
    
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null){
    	$config = $container->get('Config');

    	$adapter = new Adapter($config['db']);
    	Adapter::setDefaultDbAdapter($adapter);    	
    	return $adapter;
    }
    
}
