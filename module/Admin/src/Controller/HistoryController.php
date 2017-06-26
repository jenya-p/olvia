<?php

namespace Admin\Controller;

use Admin\Model\Content\ContentDb;
use Admin\Model\Content\DivisionDb;
use Admin\Model\Users\UserDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\Db\Historical;
use Common\SiteController;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Admin\Model\Content\PhotoalbumDb;
use Admin\Model\Content\VideoalbumDb;
use Admin\Model\Content\VideoDb;
use Admin\Model\Content\PhotoDb;
use Admin\Model\Content\ReviewDb;
use Admin\Model\Users\MasterDb;
use Admin\Model\Users\CustomerDb;
use Admin\Model\Content\DiplomDb;
use Admin\Model\Users\MasterPricesDb;
use Admin\Model\Orders\ConsultationDb;
use Admin\Model\Orders\OrdersDb;
use Admin\Model\Orders\CallDb;
use Admin\Model\Courses\CourseDb;
use Admin\Model\Courses\EventDb;
use Admin\Model\Courses\TarifsDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 */
class HistoryController extends SiteController {
	
	/**
	 * @Route(name="history",route="/history/:entity/:id", extends="private")
	 */
	public function historyAction() {
		$entity = $this->params('entity');
		$id = $this->params('id');
		
		$service = $this->getHistoricalService($entity);
		if($service !== null && $service instanceof Historical){
			return new ViewModel([
					'history' => $service->readHistory($id)
				]);
		} else {
			throw new \Exception("entity $entity not found");
		}
	}
	
	public function getHistoricalService($entity){
		$map = [
				'content' => ContentDb::class,
				'content_division' => DivisionDb::class,
				'user' => UserDb::class,
				'customer' => CustomerDb::class,
				'master' => MasterDb::class,
				'videoalbum' => VideoalbumDb::class,
				'video' => VideoDb::class,
				'photoalbum' => PhotoalbumDb::class,
				// 'photo' => PhotoDb::class,
				'review' => ReviewDb::class,
				'diplom' => DiplomDb::class,
				'master-prices' => MasterPricesDb::class,
				'order_call' => CallDb::class,
				'order_consultation' => ConsultationDb::class,
				'order_orders' => OrdersDb::class,
				'course' => CourseDb::class,
				'event' => EventDb::class,
				'tarifs' => TarifsDb::class,
				
		];
		
		if(!in_array($entity, $map)){
			return $this->serv($map[$entity]);
		} else {
			return null;
		}
		
		
	}
	
	
}

