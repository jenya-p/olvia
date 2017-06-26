<?

namespace Application\Controller\Customer;


use Common\SiteController;
use ZfAnnotation\Annotation\Controller;
use Common\Annotations\Roles;
use ZfAnnotation\Annotation\Route;
use Application\Model\Orders\OrdersDb;
use Application\Model\CustomerDb;
use Application\Model\Orders\ConsultationDb;
use Application\Model\Courses\EventDb;
use Application\Model\Courses\CourseDb;
use Application\Model\MasterDb;
use Application\Model\Courses\TarifsDb;
use Application\Model\MasterPricesDb;

/**
 * @Controller
 * @Roles(value="customer")
 */
class CalendarController extends SiteController{
	
	/** @var OrdersDb */
	var $orderDb;	
	/** @var CustomerDb */
	var $customerDb;		
	/** @var ConsultationDb */
	var $consultationDb;	
	/** @var EventDb */
	var $eventDb;
	/** @var CourseDb */
	var $courseDb;
	/** @var MasterDb */
	var $masterDb;
	/** @var TarifsDb */
	var $tarifsDb;
	/** @var MasterPricesDb */
	var $masterPriceDb;
	
	public function init(){
		$this->orderDb = $this->serv(OrdersDb::class);
		$this->customerDb = $this->serv(CustomerDb::class);
		$this->consultationDb = $this->serv(ConsultationDb::class);
		$this->eventDb = $this->serv(EventDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
		$this->masterDb = $this->serv(MasterDb::class);
		$this->tarifsDb = $this->serv(TarifsDb::class);
		$this->masterPriceDb = $this->serv(MasterPricesDb::class);
	}
	
	
	/**
	 * @Route(name="customer-calendar", route="/customer/calendar", type="segment")
	 */
	public function customerCalendarAction(){

		$orders = $this->customerDb->getActualOrderIds($this->identity()->id);
		$total = 0;
		$totalDiscount = 0;
		foreach ($orders as &$order){
			
			if($order['type'] == 'consult'){
				$item = $this->consultationDb->get($order['id']);
				$order['order'] = &$item;
				$order['master'] = $this->masterDb->get($item['master_id']);
				$order['tarif'] = $this->masterPriceDb->get($item['tarif_id']);
				$total +=  max(0, $item['price'] - $item['payed']);
				
			} else if ($order['type'] == 'order') {
				$item =  $this->orderDb->get($order['id']);
				$order['order'] = &$item;				
				$order['shedule'] = $this->orderDb->getShedule($order['id']);				
								
			} else if ($order['type'] == 'preorder') {
				$item =  $this->orderDb->get($order['id']);
				$order['order'] = &$item;							
			}
			
			if ($order['type'] == 'order' || $order['type'] == 'preorder') {
				
				$order['event'] 	= $this->eventDb->get($item['event_id']);
				$order['tarif'] 	= $this->tarifsDb->get($item['tarif_id']);
				$order['masters'] 	= $this->masterDb->getEventMasters($item['event_id']);
				
				$this->orderDb->calculateActualPrice($item, $order['meet_date']);
				
				if(empty($item['payed'])){					
					$total += $item['actual_price'];
					$totalDiscount += max(0, $item['price'] - $item['actual_price']);
				}
			}
			unset($item);
		}

		return [
			'orders' => $orders,
			'total' => $total,
			'totalDiscount' => $totalDiscount
		];
	}
	
	
	
	
	/**
	 * @Route(name="customer-history", route="/customer/history", type="segment")
	 */
	public function customerHistoryAction(){
		$orders = $this->customerDb->getHistoryOrderIds($this->identity()->id);

		foreach ($orders as &$order){
				
			if($order['type'] == 'consult'){
				$item = $this->consultationDb->get($order['id']);
				$order['order'] = &$item;
				$order['master'] = $this->masterDb->get($item['master_id']);
				$order['tarif'] = $this->masterPriceDb->get($item['tarif_id']);

			} else if ($order['type'] == 'order' || $order['type'] == 'preorder') {
				$item =  $this->orderDb->get($order['id']);
				$order['order'] = &$item;				
				$order['event'] 	= $this->eventDb->get($item['event_id']);
				$order['tarif'] 	= $this->tarifsDb->get($item['tarif_id']);
				$order['masters'] 	= $this->masterDb->getEventMasters($item['event_id']);
				$order['course'] 	= $this->courseDb->get($order['event']['course_id']);
			}
			unset($item);
		}
		
		return [
				'orders' => $orders,				
		];
	}
	
}
