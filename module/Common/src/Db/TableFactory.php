<?php 
namespace Common\Db;
 
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\AbstractFactoryInterface;
use Zend\Db\TableGateway\TableGateway;

class TableFactory implements AbstractFactoryInterface{
	
	public function __invoke(ContainerInterface $container, $requestedName, array $options = NULL) {
		
		$dbAdapter = $container->get('DbAdapter');

		/* @var $table Table */		
		$table = new $requestedName($dbAdapter);
		
		if($table instanceof Multilingual){
			$table->lang('ru');
		}
		
		return $table;
		
	}
	
	public function canCreate(ContainerInterface $container, $requestedName){
		return preg_match('/(Application|Admin)\\\\Model\\\\(\\w|\\\\)+Db/', $requestedName);
	}
	

}
