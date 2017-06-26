<?
namespace Admin\Model\Orders;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;
use Common\ViewHelper\Phone;
use Common\Db\Discussion;
use Common\Db\DiscussionTrait;
use Admin\Model\Courses\EventDb;
use Zend\Db\Sql\Sql;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Courses\TarifsDb;

class OrdersDb extends Table implements CRUDListModel, Historical, Discussion, ServiceManagerAware {
	
	use HistoricalTrait, DiscussionTrait, ServiceManagerTrait;

	const STATUS_PREORDER = 'preorder';
	const STATUS_NEW = 'new';
	const STATUS_DONE = 'done';
	const STATUS_DECLINE = 'decline';
	const STATUS_ARCHIVE = 'archive';
		
	protected $table = 'order_orders';
	protected $table2shedule = 'order_order2shedule';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->history('order_orders');		
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['o' => 'order_orders']);
		
		$select->join(['ua' => 'users_accounts'], 'ua.id = o.user_id', ['user_displayname' => 'displayname'], Join::JOIN_LEFT);
		$select->join(['uc' => 'users_customers'], 'uc.id = o.user_id', ['customer_id' => 'id', 'customer_name' => 'name'], Join::JOIN_LEFT);

		$select->join(['e' => 'course_events'], 'e.id = o.event_id', [				
												'event_title' => 'title_ru'				
										], Join::JOIN_LEFT);
		$select->join(['c' => 'courses'], 'c.id = e.course_id', [
												'course_title' => 'title_ru',
												'course_id' => 'id'
										], Join::JOIN_LEFT);
		$select->join(['o2s' => 'order_order2shedule'], 	'o2s.order_id = o.id', [], Join::JOIN_LEFT);
		$select->join(['shed' => 'course_event_shedule'], 	'o2s.shedule_id = shed.id', ['shedule_date' => 'date'], Join::JOIN_LEFT);
		
		if(!empty($filter['query'])){
			$nest = $select->where->nest();
			$nest->expression('concat(" ", LOWER(oc.name)) like ?', "% ".mb_strtolower($filter['query']."%"))
				->or->expression('LOWER(oc.skype) like ?', mb_strtolower($filter['query']."%"))
				->or->equalTo('oc.phone', Phone::normalize($filter['query']))
				->or->expression('LOWER(ua.email) like ?', mb_strtolower($filter['query']."%"));
				
		}
			
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(o.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			$select->order($sort[0]. ' '.$sort[1]);
		} else {
			$select->order('o.id desc');
		}
		
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		$item['status_name'] = $this->statusOption($item['status']);
		
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
	
	
	public function updateOne($update, $id){
		if(!empty($update['discounts']) && !is_string($update['discounts'])){
			$update['discounts'] = json_encode($update['discounts']);
		}
		return parent::updateOne($update, $id);
	}
	
	
	public function insert($insert){
		if(!empty($insert['discounts']) && !is_string($insert['discounts'])){
			$insert['discounts'] = json_encode($insert['discounts']);
		}
    }	
	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
	
		$historyWriter = $this->getHistoryWriter();
		$historyWriter->reset('order_orders', $newValues, $oldValues, $id);
					
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader();
		$historyReader->reset('order_orders', $id);
		
		$dir1 = [$historyReader, 'getUserDescription'];
		$historyReader->addDictionary('user_id', $dir1);
		 
		$tarifsDb = $this->serv(TarifsDb::class);
		$dir2 = [$tarifsDb, 'getDescription'];
		$historyReader->addDictionary('tarif_id', $dir2);
		
		$eventsDb = $this->serv(EventDb::class);
		$dir2 = [$eventsDb, 'getDescription'];
		$historyReader->addDictionary('event_id', $dir2);
				
		$dir3 = [$historyReader, 'dateDescription'];
		$historyReader->addDictionary('add_shedule', $dir3);
		$historyReader->addDictionary('remove_shedule', $dir3);
		 
		$historyReader->addDictionary('status', $this->statusOptionsNames);
		 
		
		
		return $historyReader->getRecordsByDate();
	}

	// Misc	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		return $historyReader->getStat();		
	}
		
	
	
	var $statusOptionsNames = [
			self::STATUS_PREORDER => 'Предзаказ',
			self::STATUS_NEW => 	 'Новая',
			self::STATUS_DONE =>  	 'Обработана',
			self::STATUS_DECLINE =>  'Отмена',
			self::STATUS_ARCHIVE =>  'Архив',
	];
	
	public function statusOption($key){
		if( array_key_exists($key, $this->statusOptionsNames)){
			return $this->statusOptionsNames[$key];
		} else {
			return $key;
		}
	}
	
	public function statusOptions(){
		return $this->statusOptionsNames;
	}
	
	
	public function getEventOrderCount($eventId, $status = null){
		$select = new Select(['o' => $this->table]);
		$select->reset(Select::COLUMNS)
			->columns([
					'count' => new Expression('count(o.id)')])
			->where->equalTo('o.event_id', $eventId);
		if($status != null){
			$select->where->equalTo('o.status', $status);
		} else {
			$select->where->notIn('o.status', [self::STATUS_ARCHIVE, self::STATUS_DECLINE]);
		}
			
	}
	
	
	public function getSheduledOrderCount($sheduleId, $status = null){
		$select = new Select(['o' 	=> $this->table]);
		$select->join(['o2s' 		=> $this->table2shedule], ['o2s.order_id = o.id'], []);
		$select->reset(Select::COLUMNS);
		
		$select
			->columns(['count' => new Expression('count(o.id)')])
			->where->equalTo('o2s.shedule_id', $sheduleId);		
			
		if($status != null){
			$select->where->equalTo('o.status', $status);
		} else {
			$select->where->notIn('o.status', [self::STATUS_ARCHIVE, self::STATUS_DECLINE]);
		}	
	}
	
	
	var $cacheOrderDates = [];
	public function getOrderShedule($orderId){
		$select = new Select(['sh' 	=> 'course_event_shedule']);
		$select->join(['o2s' => $this->table2shedule], 'o2s.shedule_id = sh.id', [], Join::JOIN_INNER);
		$select->where->equalTo('o2s.order_id', $orderId);
		$select->order('sh.date asc');
		return $select->fetchAll();
	}
	
	
	public function saveOrderShedule($orderId, $sheduleIds){
		
		$this->saveSheduleHistory($orderId, $sheduleIds);
		
		// Удаляем...  
		$sql = new Sql( $this->getAdapter() );
		$delete = $sql->delete('order_order2shedule');
		$delete->where->expression('order_id = ?', $orderId);
		$sql->prepareStatementForSqlObject($delete)->execute();
		
		// ... и вставляем
		$sheduleIds = array_unique($sheduleIds);
		foreach ($sheduleIds as $sheduleId){
			$insert = [
				'order_id' => $orderId,
				'shedule_id' => $sheduleId
			];
			$this->getAdapter()->insert('order_order2shedule', $insert);
		}
		
	}
	
	
	public function saveSheduleHistory($orderId, $newDateIds, $oldDateIds = null){

		if($oldDateIds === null){
			// Получаем старые ID привязанных дат
			$dateIdsSelect = new Select(['o2s' 	=> 'order_order2shedule']);
			$dateIdsSelect->reset(Select::COLUMNS)->columns(['shedule_id']);
			$dateIdsSelect->where->equalTo('o2s.order_id', $orderId);
			$oldDateIds = $dateIdsSelect->fetchColumn();
		}
		
		// Записываем историю
		$historyWriter = $this->getHistoryWriter(null, null, $orderId);
		foreach ($newDateIds as $nDate){
			if(!in_array($nDate, $oldDateIds)){
				$date = $this->getSheduleDate($nDate);
				$historyWriter->write('add_shedule', 	$date, null, $nDate);
			}
		}
		foreach ($oldDateIds as $oDate){
			if(!in_array($oDate, $newDateIds)){
				$date = $this->getSheduleDate($oDate);
				$historyWriter->write('remove_shedule', null, $date, $oDate);
			}
		}
	}
	
	public function getSheduleDate($sheduleId){
		return $this->getAdapter()->fetchOne('select date from course_event_shedule where id = :id', ['id' => $sheduleId]);
	}
	
	
}
