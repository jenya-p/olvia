<?
namespace Application\Model\Orders;

use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Select;
use Zend\Db\Sql\Join;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Application\Model\Courses\TarifsDb;
use Admin\Model\Orders\OrdersDb as AdminOrderDb;

class OrdersDb extends Table implements  Historical, ServiceManagerAware {
	
	use HistoricalTrait, ServiceManagerTrait;
	
	protected $table = 'order_orders';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->history('order_orders');
	}
		
		
	
	public function getOrders($userId, $eventId = null, $tarifId = null, $sheduleId = null){
		$select = new Select(['o' => $this->table]);
		$select->where->equalTo('user_id', $userId)
			->and->in('status', [AdminOrderDb::STATUS_PREORDER, AdminOrderDb::STATUS_NEW, AdminOrderDb::STATUS_DONE]);
		
		if(!empty($eventId)){
			$select->where->equalTo('event_id', $eventId);
		}
		if(!empty($tarifId)){
			$select->where->equalTo('tarif_id', $tarifId);
		}
		if(!empty($sheduleId)){
			$select->join(['o2s' => 'order_order2shedule'], 'o2s.order_id = o.id', [], Join::JOIN_INNER);
			$select->group('o.id');
			$select->where->equalTo('o2s.shedule_id', $sheduleId);
		}
		$select->order('id desc');
		return $this->fetchAll($select);
	}
	
	public function buildItem(&$item){
	
		if(!empty($item['discounts'])){
			$item['discounts'] = json_decode($item['discounts'], true);
			uasort ( $item['discounts'] , function ($a, $b) {
				if ($a['days'] == $b['days']) {
					return 0;
				}
				return ($a['days'] < $b['days']) ? -1 : 1;
			});
		}
	
		return parent::buildItem($item);
	}
	
	public function insert($insert){
		if(!empty($insert['discounts']) && !is_string($insert['discounts'])){
			$insert['discounts'] = json_encode($insert['discounts']);
		}
		return parent::insert($insert);
	}
	
	public function updateOne($update, $id){
		if(!empty($update['discounts']) && !is_string($update['discounts'])){
			$update['discounts'] = json_encode($update['discounts']);
		}
		return parent::updateOne($update, $id);
	}
	
	
	public function calculateActualPrice(&$item, $eventDate, $now = null){
		/* @var $tarifsDb TarifsDb */
		$tarifsDb = $this->serv(TarifsDb::class);
		return $tarifsDb->calculateActualPrice($item, $eventDate, $now);		
	}
	
	
	public function addShedule($orderId, $sheduleId){
		
		$hw = $this->getHistoryWriter(null, null, $orderId);
		$date = $this->getAdapter()->fetchOne('select date from course_event_shedule where id = :id', ['id' => $sheduleId]);		
		$hw->write('add_shedule', $date, null, $sheduleId);
		
		$this->getAdapter()->insert('order_order2shedule', [
				'order_id' => $orderId,
				'shedule_id' => $sheduleId
		]);
		
	}
	
	public function getShedule($courseId, $strict = true){
		$sql = 'select shed.*
			from course_event_shedule shed
			inner join order_order2shedule o2s on shed.id = o2s.shedule_id
			where o2s.order_id = :courseId';
		if($strict){
			$sql .= ' and shed.date >= '.time();
		}
		$sql .= ' order by shed.date asc';
		
		return $this->getAdapter()->fetchAll($sql, ['courseId' => $courseId]);
		
	}
	
	
		// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		return $historyReader->getRecordsByDate();
	}
		
		
}
