<?
namespace Common;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Initializer\InitializerInterface;
use Common\Traits\ServiceManagerAware;
use Common\Traits\LoggerAware;
use Common\Traits\ConfigAware;
use Common\Db\Historical;
use Common\Traits\Initializable;
use Common\Traits\ViewAware;
use Common\Db\Discussion;
use Admin\Model\CommentsDb;
use Common\Traits\IdentityAware;


class TraitInitializer implements InitializerInterface{
	

	public function __invoke(ContainerInterface $container, $instance) {

		if($instance instanceof ServiceManagerAware ){
			$instance->setServiceManager($container);
		}
		if($instance instanceof LoggerAware){
			$service = $container->get('DefaultLogger');
			$instance->setLogger($service);
		}
		if($instance instanceof ConfigAware){
			$service = $container->get('Config');
			$instance->setConfig($service);
		}
		
		if($instance instanceof ViewAware){
			$service = $container->get('View');
			$instance->view($service);
		}
		
		if($instance instanceof Historical){
			$historyReader = $container->get('HistoryReader');
			$historyWriter = $container->get('HistoryWriter');
			$instance->setHistoryReader($historyReader);
			$instance->setHistoryWriter($historyWriter);
		}
		
		if($instance instanceof Discussion){
			$commentsDb = $container->get(CommentsDb::class);
			$instance->setCommentsDb($commentsDb);
			$userId = $container->get('identity')->id;
			$instance->setUserId($userId);
		}
		
		if($instance instanceof IdentityAware){
			$identity = $container->get('identity');
			$instance->setIdentity($identity);
		}
		
		
		if($instance instanceof Initializable){
			$instance->init();
		}
		
	}

}