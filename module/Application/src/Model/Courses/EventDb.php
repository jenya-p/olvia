<?
namespace Application\Model\Courses;

use Admin\Model\Courses\EventDb as AdminEventDb;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Join;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Application\Model\MasterDb;
use Application\Model\Orders\OrdersDb;
use Common\Traits\Initializable;
use Application\Controller\CoursesController;
use Zend\Db\Sql\Expression;

class EventDb extends Table implements Multilingual, ServiceManagerAware, Initializable{
		
	use MultilingualTrait, ServiceManagerTrait;
	
	protected $table = 'course_events';

	public function __construct(Adapter $adapter) {
		AdminEventDb::class;
		$this->adapter = $adapter;
		$this->langFields(['title', 'date_text', 'time_text']);
	}
	

	/** @var MasterDb */
	var $masterDb = null;
	
	/** @var TarifsDb */
	var $tarifsDb = null;
	
	/** @var OrdersDb */
	var $ordersDb = null;
	
	/** @var CourseDb */
	var $courseDb = null;
	
	public function init(){
		$this->masterDb = $this->serv(MasterDb::class);
		$this->tarifsDb = $this->serv(TarifsDb::class);
		$this->ordersDb = $this->serv(OrdersDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
	}
	
	
	public function buildItem(&$item){
		parent::buildItem($item);	
		if(!empty($item['time_text'])){
			$re = '/(\d\d:\d\d).*(\d\d:\d\d)\s?(\S.*)?/';
			$mastces = [];
			if(preg_match($re, $item['time_text'], $matches)){
				$item['time_text_from'] = $matches[1];
				$item['time_text_till'] = $matches[2];
				$item['time_text_duration'] = $matches[3];
			}	
			else {
				$item['time_text_duration'] = $item['time_text'];
			}
		}
	}

	/* filter['date_range'] - месяц в формате date('y-m-d').'_'.date('y-m-d')
	 */
	public function getCourseDates($courseId, $filter){
		$select = new Select();
		
		$select->from(['e' =>    'course_events']);
		$select->columns([
					'event_title' => 'title_'.$this->lang(),
					'event_id' 	=> 'id'
				]);
		$select->join(['shed' => 'course_event_shedule'], 'shed.event_id = e.id', ['date', 'id'], Join::JOIN_INNER);
		
		$dateRangeStr = $filter['date_range'];
		
		if(!empty($dateRangeStr)){
			list($dateFrom, $dateTo) = explode('_', $dateRangeStr);
			$select->where->between('shed.date', strtotime($dateFrom), strtotime($dateTo));			
		} else {
			$select->where->greaterThan('shed.date', time());
			$select->limit(5);
		}
				
		$select->where->equalTo('e.course_id', $courseId)
			->and->notEqualTo('e.type', AdminEventDb::TYPE_ANNOUNCE);		
		$select->order('shed.date asc');
		return $this->fetchAll($select);
	}
			
	public function getDate($id){
		$select = new Select();
	
		$select->from(['e' =>    'course_events']);
		$select->columns([
				'event_date_text' => 'date_text_'.$this->lang(),
				'event_time_text' => 'time_text_'.$this->lang(),
				'event_count' => 'count',
				'event_title' => 'title_'.$this->lang(),
				'event_id' 	=> 'id'
		]);
		$select->join(['shed' => 'course_event_shedule'], 'shed.event_id = e.id', ['date', 'id'], Join::JOIN_INNER);
		
		$select->where->equalTo('shed.id', $id);
		
		return $this->fetchAll($select);
	}
	
	
	public function getCourseAnnouncements($courseId, $filter){
		$select = new Select();
		
		$select->from(['e' =>    'course_events']);		
		$select->where->equalTo('e.course_id', $courseId)
			->and->equalTo('e.type', AdminEventDb::TYPE_ANNOUNCE);
		$select->order('e.expiration_date asc');
		return $this->fetchAll($select);
	}
	
	

	/**
	 * @param array $filter
	 */
	public function getSheduleSelect($filter){
		$select = new Select(['shed' => 'course_event_shedule']);
		$select->group('shed.id')
		->order('shed.date asc');
		
		$select->join(['e' => 'course_events'], 'e.id = shed.event_id', [], Join::JOIN_INNER);
		$select->where->equalTo('e.status', '1');
				
		if(!empty($filter['course_id'])){
			$select->where->equalTo('e.course_id', $filter['course_id']);
		}
		
		if(!empty($filter['tag_ids'])){
			$select->join(['tr' => 'content_tag_refs'], 'tr.item_id = e.course_id', [], Join::JOIN_INNER);
			$select->where
			->in('tr.tag_id', $filter['tag_ids'])
			->and->equalTo('tr.entity', "course");
		}
		
		if(!empty($filter['master'])){
			$select->join(['e2m' => 'course_event2master'], 'e2m.event_id = e.id', [], Join::JOIN_INNER);
			$select->where
			->equalTo('e2m.master_id', $filter['master']);
		}
		
		return $select;
	}
	
	
	
	
	public function getSheduleBounds($filter, $strict = true){
		$select = $this->getSheduleSelect($filter);
		
		if($strict){
			$select->where->greaterThan('date', time());
		}
		
		$select->reset('columns')->reset('group');
		$select->columns([
				'start' => new Expression('min(shed.date)'),
				'end' => new Expression('max(shed.date)')
			]);

		return $select->fetchRow();
	}
	
	/*
	 * from, to - date range timstamp's
	 * tag_ids - course refferenced tag ids, array
	 * master - event refferenced master id, numeric
	 * userId - current user id? to calculate order statuses
	 */
	public function getShedule($filter, $bounds, $userId = null){
		$select = $this->getSheduleSelect($filter);
		
		if(!empty($bounds['from']) && !empty($bounds['to'])){
			$select->where->between('shed.date', $bounds['from'], $bounds['to']);
		}
		
		$dates = $select->fetchAll();
		
		foreach ($dates as &$date){
			$this->buildSheduleRecord($date, $userId);
		}
		
		return $dates;
	}
	
	public function buildSheduleRecord(&$date, $userId = null){
		$eventId = $date['event_id'];

		$date['event'] 	 =  $this->get($eventId);
	
		$date['course']  =  $this->courseDb->get($date['event']['course_id']);
					
		$date['masters'] =  $this->masterDb->getEventMasters($eventId);
			
		$date['tarifs']  =  $this->tarifsDb->getEventTarifs($eventId, $date['date']);
		
		if($date['date'] < time() || (!empty($date['event']['expiration_date']) && $date['event']['expiration_date'] < time())){
			$date ['order_status'] = CoursesController::ORDER_STATUS_EXPIRED;
		} else {
			$date ['order_status'] = CoursesController::ORDER_STATUS_AVAILABLE;			
		}
		
		foreach ($date['tarifs'] as &$tarif){
			
			$tarif ['order_status'] = $date ['order_status'];
			
			if(!empty($userId)){
				$orders = $this->ordersDb->getOrders($userId, $eventId, $tarif['id'], $date['id']);
	
				if(!empty($orders)){
					$order = $orders[0];
					$tarif['order'] = $order;
					if($order['payed'] != 0) {
						$tarif['order_status'] = CoursesController::ORDER_STATUS_PAIED;
						$date['order_status'] = CoursesController::ORDER_STATUS_PAIED;
					} else if ($order['price'] != 0){
						$tarif['order_status'] = CoursesController::ORDER_STATUS_NEED_PAY;
						$date['order_status'] = CoursesController::ORDER_STATUS_NEED_PAY;
					} else {
						$tarif['order_status'] = CoursesController::ORDER_STATUS_FREE;
						$date['order_status'] = CoursesController::ORDER_STATUS_FREE;
					}
				}
			}
		}
	}
	
}
