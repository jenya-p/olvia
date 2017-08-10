<?
namespace Admin\Model\Courses;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\OptionsModel;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\MasterDb;
use Zend\Db\Sql\Join;
use Zend\Db\Sql\Sql;
use Admin\Model\Orders\OrdersDb;
use Zend\Db\Adapter\Exception\InvalidQueryException;
use Common\CRUDCalendarModel;

class EventDb extends Table implements CRUDListModel, CRUDCalendarModel, Multilingual, Historical, OptionsModel, ServiceManagerAware {
						   
	const SOFT_DAYS = 5; // Кол-во дней в течении которых можно редактировать всякое
	
	const TYPE_ANNOUNCE = 'announce';
	const TYPE_PERM = 'perm';
	const TYPE_SINGLE= 'single';
	const TYPE_COURSE= 'course';

	var $typeNames = [		
		self::TYPE_ANNOUNCE => 	'Анонс',
		self::TYPE_SINGLE => 	'Разовое',
		self::TYPE_PERM => 		'Постоянное',		
		self::TYPE_COURSE => 	'Курс',
	];
	
	use MultilingualTrait, HistoricalTrait, ServiceManagerTrait;
	
	protected $table = 'course_events';
	protected $tableShedule = 'course_event_shedule';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'date_text', 'time_text']);
		$this->history('event');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['ev' => 'course_events']);		

		$select->join(['sh' => $this->tableShedule], 'sh.event_id = ev.id', [
						'date' => new Expression('ifnull(sh.date, ev.expiration_date)'), 
						'shedule_date' => 'date',
						'shedule_id' => 'id'
				], Join::JOIN_LEFT);
		
		$select->join(['c' => 'courses'], 'ev.course_id = c.id', [
				'course_title' => 'title_'.$this->lang()]
				, Join::JOIN_LEFT);
		
		if(!empty($filter['query'])){
			$select->where->expression('concat(" ", LOWER(ev.title_'.$this->lang().')) like ?', "% ".mb_strtolower($filter['query']."%"));
		}
		
		if(!empty($filter['course_id'])){
			$select->where->equalTo('course_id', $filter['course_id']);
		}
		$select->group('sh.id');
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::GROUP);
		$select->reset(Select::COLUMNS)
			->columns([
					'count' => new Expression('count(ev.id)'),
			]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = null){
		$select = $this->getSelect($filter);
		
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order(new Expression('IFNULL(sh.date, ev.expiration_date) ASC'));
		$select->order('ev.id asc');
		
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function getCalendarBounds($filter) {
		$select = $this->getSelect($filter);
		$select->reset(Select::GROUP);
		$select->reset(Select::COLUMNS)
			->columns([
					'count' => new Expression('count(ev.id)'),
					'start' => new Expression('min(sh.date)'),
					'end' => new Expression('max(sh.date)')
				]);
		return $select->fetchRow();
	}
	
	public function getCalendarItems($filter, $from, $to) {
		$select = $this->getSelect($filter);

		$select->where->greaterThanOrEqualTo('sh.date', $from);
		$select->where->lessThan('sh.date', $to);
		$select->columns(['*', 'formated_date' => new Expression('DATE_FORMAT(FROM_UNIXTIME(IFNULL(sh.date, ev.expiration_date)),"%y-%m-%d")')]);
		
		$select->order(new Expression('IFNULL(sh.date, ev.expiration_date) ASC'));
		$select->order('ev.id asc');
		
		$itemsByDate = $select->fetchGroups('formated_date');
		foreach ($itemsByDate as &$itemsByDateItem){
			foreach ($itemsByDateItem['items'] as &$item){
				$this->buildItem($item);
			}			
		}
		return $itemsByDate;
	}
	
	
	
	
	
	public function buildItem(&$item){
		$item['type_name'] = $this->typeNames[$item['type']];
		/* @var $orderDb OrdersDb */
		$orderDb = $this->serv(OrdersDb::class);
		if($item['type'] == self::TYPE_ANNOUNCE || empty($item['shedule_id'])){
			$item['order_count'] = $orderDb->getEventOrderCount($item['id']);
		} else {
			$item['order_count'] = $orderDb->getSheduledOrderCount($item['shedule_id']);
		}
		
		parent::buildItem($item);
		$this->buildEventTime($item);
		return $item;
	}

	
	public function buildEventTime(&$item){
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
		return $item;
	}
	
	public function insert($insert){
    	return parent::insert($insert);    	
    }	
	 
	// Options Model implementation
	public function options() {
		return null;
	}

	public function option($key) {
		$item = $this->get($key);
		if($item != null ){
			return $item[''];
		} else {
			null;
		}
	}
	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {	
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);	
		// $historyWriter->setSkipDataFor(['']);							
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		$courseDb = $this->serv(CourseDb::class);
		$historyReader->addDictionary('course_id', $courseDb);
		$dic = [$historyReader,'getUserDescription'];
		$historyReader->addDictionary('add_master', $dic);
		$historyReader->addDictionary('remove_master', $dic);
		$historyReader->addDictionary('type', $this->getTypeOptions());
		
		
		$tarifDb = $this->serv(TarifsDb::class);
		$dic = [$tarifDb, 'option'];
		$historyReader->addDictionary('add_tarif', $dic);
		$historyReader->addDictionary('remove_tarif', $dic);
		
		$dic = [$historyReader,'dateDescription'];
		$historyReader->addDictionary('expiration_date', $dic);
		
		return $historyReader->getRecordsByDate();
	}

	// Misc	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);		
		$stat = $historyReader->getStat();		
		return $stat;
	}
	
	
	public function getDescription($id){
		$price = $this->get($id);
		if(!empty($price)){
			return $price['title'].' (id = '.$id.')';
		} else if(!empty($id)){
			return '<i>не найдено</i> (id = '.$id.')';
		} else {
			return null;
		}
	}
	
	
	public function getTypeOptions(){
		return $this->typeNames;
	}
	
	
	
	public function getMasterIds($eventId){
		$eventId = $this->id($eventId);
		/* @var $masterDb MasterDb */
		$masterDb = $this->serv(MasterDb::class);
		$select = new Select(['e2m' => 'course_event2master']);
		$select->columns(['id' => 'master_id']);
		$select->join(['m' => $masterDb->getTable()], 'e2m.master_id = m.id', [], Join::JOIN_INNER);
		$select->where->equalTo('e2m.event_id', $eventId);
		return $select->fetchColumn();
	}
		
	public function saveMasters($eventId, $masterIds){
		$masterIds = array_unique($masterIds);
		$oldValues = $this->getMasterIds($eventId);
		$historyWriter = $this->getHistoryWriter(null, null, $eventId);
		
		$historyWriter->writeArrayDiff('master', $masterIds, $oldValues);
		
		$sql = new Sql( $this->getAdapter() );
		$delete = $sql->delete('course_event2master');
		$delete->where->expression('event_id = ?', $eventId);
		$sql->prepareStatementForSqlObject($delete)->execute();
		
		foreach ($masterIds as $mId){
			$insert = [
					'event_id' => $eventId,
					'master_id' => $mId,
			];
			$this->getAdapter()->insert('course_event2master', $insert);
		}		
	}
	
	public function getTarifIds($eventId){
		$eventId = $this->id($eventId);		
		/* @var $tarifDb TarifsDb */
		$tarifDb = $this->serv(TarifsDb::class);		
		$select = new Select(['e2t' => 'course_event2tarif']);
		$select->columns(['id' => 'tarif_id']);
		$select->join(['t' => $tarifDb->getTable()], 'e2t.tarif_id = t.id', [], Join::JOIN_INNER);		
		$select->where->equalTo('e2t.event_id', $eventId);
		
		return $select->fetchColumn();
	}

	public function saveTarifs($eventId, $tarifIds){
		$tarifIds = array_unique($tarifIds);
		$oldValues = $this->getTarifIds($eventId);
		$historyWriter = $this->getHistoryWriter(null, null, $eventId);
		$historyWriter->writeArrayDiff('tarif', $tarifIds, $oldValues);
	
		$sql = new Sql( $this->getAdapter() );
		$delete = $sql->delete('course_event2tarif');
		$delete->where->expression('event_id = ?', $eventId);
		$sql->prepareStatementForSqlObject($delete)->execute();
	
		foreach ($tarifIds as $mId){
			$insert = [
					'event_id' => $eventId,
					'tarif_id' => $mId,
			];
			$this->getAdapter()->insert('course_event2tarif', $insert);
		}
	}
	
	public function getTarifs($eventId){
		$eventId = $this->id($eventId);
		/* @var $tarifDb TarifsDb */
		$tarifDb = $this->serv(TarifsDb::class);
		$select = new Select(['t' => $tarifDb->getTable()]);		
		$select->join(['e2t' => 'course_event2tarif'], 'e2t.tarif_id = t.id', []);
		$select->where->equalTo('e2t.event_id', $eventId);
		$select->order('t.id asc');
		return $tarifDb->fetchAll($select);
	}
	
	
	public function addShedule($eventId,$dates){		
		if(empty($dates)) return;
		sort($dates, SORT_ASC & SORT_NUMERIC);
		
		$event = $this->get($eventId);
		foreach ($dates as $date){
			try{
				$newSheduleId = $this->getAdapter()->insert('course_event_shedule', ['event_id' => $eventId, 'date' => $date]);
				
				if($date > time()){
					
					$sqlParams = ['eventId' => $eventId, 'date' => $date];
					$sql = null;
					
					if($event['type'] == self::TYPE_SINGLE){
						// Добавляем дату в заказы без даты;						
						$sql = 'select o.id as order_id
							from order_orders o
							left join order_order2shedule os on os.order_id = o.id
							where o.event_id = :eventId and o.status in ("preorder", "new", "done") and o.date < :date os.shedule_id is null';
						

					} else if($event['type'] == self::TYPE_COURSE){
						// Добавляем дату во все заказы;						
						$sql = 'select o.id as order_id
							from order_orders o
							left join order_order2shedule os on os.order_id = o.id
							where o.event_id = :eventId and o.status in ("preorder", "new", "done") and o.date < :date';
						
						
					} else if($event['type'] == self::TYPE_PERM){
						// Добавляем в заказы, для которых кол-во дат меньше указанных в тарифе (поле tarifs.subscripton)
						$sql = 'select o.id as order_id, t.subscription
							from order_orders o
							left join order_order2shedule os on os.order_id = o.id
							left join course_tarifs t on t.id = o.tarif_id
							where o.event_id = :eventId and o.status in ("preorder", "new", "done") and o.date < :date
							group by o.id
							having count(os.shedule_id) < t.subscription';
						
					}
					
					if($sql !== null){
						$orderIds = $this->getAdapter()->fetchColumn($sql, $sqlParams);
						
						if(!empty($orderIds)){
							$orderDb = $this->serv(OrdersDb::class);
							foreach ($orderIds as $orderId){
								
								$insertRes = $orderDb->addOrderShedule($orderId, $newSheduleId);
								if($insertRes){
									$orderDb->addComment($orderId, 'Даты проставлены автоматически, при редактировании мероприятия');
								}
							}
						}
						
					}
					
				}
			} catch(InvalidQueryException $e){}			
		}
	}
	
	
	public function getShedule($eventId){
		$select = new Select(['esh' => 'course_event_shedule']);
		$select->join(['o2s' => 'order_order2shedule'], 'o2s.shedule_id = esh.id',
				['order_count' => new Expression('count(o2s.order_id)')], Join::JOIN_LEFT);
		$select->order('esh.date asc');
		$select->group('esh.id');
		$select->where->equalTo('esh.event_id', $eventId);
		return $select->fetchAll();
	}
	
	public function updateShedule($sheduleId, $date){
		
		$select = new Select(['sh' => 'course_event_shedule']);
		$select->columns(['event_id']);
		$select->where->equalTo('sh.id', $sheduleId);
		$eventId = $select->fetchOne();
		
		if(!empty($eventId)){
			$select = new Select(['sh' => 'course_event_shedule']);
			$select->columns(['count' => new Expression('count(*)')]);
			$select->where->equalTo('sh.event_id', $eventId)
				->and->notEqualTo('sh.id', $sheduleId)
				->and->between('sh.date', $date-1*60*60, $date+1*60*60);
			$sheduleCount = $select->fetchOne();
			if($sheduleCount != 0){				
				throw new \Exception('На эту дату уже запланировано мероприятие');
			} else {
				$this->getAdapter()->updateOne('course_event_shedule', $sheduleId, ['date' => $date]);
			}			
		} else {
			throw new \Exception('Мероприятие не найдено');
		}
		
	}
	
	public function canChangeType($event){
		$event = $this->get($event);
		if($event['type'] == self::TYPE_ANNOUNCE) return true;
		/* @var $ordersDb OrdersDb */ 
		$ordersDb = $this->serv(OrdersDb::class);
		$orderCount = $ordersDb->getEventOrderCount($event['id']);
		return $orderCount == 0;
	}
	
	
	public function getCourseEvents($courseId){
		 
		$select = new Select(['e' => 'course_events']);
		$select->join(['sh' => 'course_event_shedule'] , 'sh.event_id = e.id',
				[		'shedule_date_min'  => new Expression('min(sh.date)'),
						'shedule_date_max'  => new Expression('max(sh.date)'),
						'shedule_count' 	=> new Expression('count(sh.id)')
				], Join::JOIN_LEFT);
		$select->where->equalTo('e.course_id', $courseId);
		$select
				->group('e.id')
				->order('e.status desc')
				->order('e.expiration_date asc')
				->order('e.id asc');
	
		$events = $this->fetchAll($select);
	
		if(!empty($events)){
			$eventIds = array_column($events, 'id');
	
			$select = new Select(['o' => 'order_orders']);
			$select->reset(Select::COLUMNS)
					->columns(['event_id', 'count' => new Expression('count(id)')]);
	
			$select->group('o.event_id');
			$select->where->in('o.event_id', $eventIds);
	
			$orderCounts = $select->fetchPairs();
	
			foreach ($events as &$event){
				$event['order_count'] = $orderCounts[$event['id']];
			}
		}
		 
		return $events;
		 
	}
	
	
	/**
	 * Дефолтное назначение дат для заказа в зависимости от настроек события и тарифа
	 * 
	 * @param unknown $orderId
	 * @param unknown $startDate
	 * @return array|array
	 */
	public function getDefaultOrderShedule($order, $startDate = null){
		if(is_numeric($order)){
			$orderId = $order;
			/* @var $orderDb TarifsDb */
			$orderDb = $this->serv(OrdersDb::class);
			$order = $orderDb->get($orderId);
			if(empty($order)){ return []; }
		} else {
			$orderId = $order['id'];
		}
		
		$event = $this->get($order['event_id']);
		if(empty($event)){ return []; }
		
		if($event['type'] == EventDb::TYPE_ANNOUNCE){
			return [];
		}
		
		$select = new Select (['sh' => 'course_event_shedule']);
		$select->join(['e' => 'course_events'], 'e.id = sh.event_id', [], Join::JOIN_INNER);
		$select->where->equalTo('sh.event_id', $event['id']);
		$select->order('sh.date asc');
		
		if($startDate == null){
			$startDate = time();
		}
		$startDate = strtotime('midnight', $startDate);
		$select->where->greaterThanOrEqualTo('date', $startDate);
		
		if($event['type'] == EventDb::TYPE_COURSE){
			// все даты больше текущей
		} else if($event['type'] == EventDb::TYPE_PERM){
			// даты больше текущей, но ровно столько, сколько указано в тарифе, минус текущая подписка
			
			$subscriptionLeft = $this->getOrderSubscriptionLeft($orderId, $order['tarif_id']);
			
			if($subscriptionLeft <= 0){
				return [];
			} else {
				$select->limit($subscriptionLeft);
			}
			
		} else if($event['type'] == EventDb::TYPE_SINGLE){
			// Выбирем одну, ближайшую дату			
			$select->limit(1);			
		}		
		
		return $this->fetchAll($select);
	}
	
	
	public function getOrderSubscriptionLeft($orderId, $tarifId){
		$db = $this->getAdapter();
		if(empty($orderId)){
			$currentSubscription = 0;
		} else {
			$currentSubscription =
			$db->fetchOne('select count(*) from order_order2shedule where order_id = :orderId', ['orderId' => $orderId]);
		}
		
		if(!empty($order['tarif_id'])){
			$tarifSubscription =
			$db->fetchOne('select subscription from course_tarifs where id = :tarifId', ['tarifId' => $tarifId]);
		}
		
		if(empty($tarifSubscription)){
			$tarifSubscription = 1;
		}
		return $tarifSubscription - $currentSubscription;
	}

	
	/*
 
	 
	 
	Анонсы
		- Дата указанная в мероприятии фиктивная, может меняться, можно поменять на другой тип.

		- Мероприятие.				Можно менять тип.
									Дата задается одним полем ввода, по умолчанию - дата завершения записи.
									Можно менять тип на другой, во всех остальных - нельзя, выводится соответствующий месадж.
		- Список мероприятий. 		Одна дата

		- Список заказов. 			Выводиться тектовая дата из события
		- Заказ.					Выводиться тектовая дата из события
		                            
		- Календарь пользователя. 	На дату события, выводим текстовое описание из события
		- Архив пользователя. 		нет
		- Заказ на сайте.			Скидка отсчитыватся от даты на которую делается заказ.
									Заказ со статусом предзаказ
		
		
	Разовые
		- Одна дата на мероприятие
			
		- Мероприятие.				Дата задаеться одним полем ввода, по умолчанию - дата завершения записи
		- Список мероприятий. 		Одна дата
	
		- Список заказов. 			Выводится одна точная дата
		- Заказ.					Выводится одна точная дата
		                            
		- Календарь пользователя. 	Если дата события в будущем
		- Архив пользователя. 		Если дата события в прошлом
		- Заказ на сайте.			Скидка отсчитыватся от даты на которую делается заказ.
									
							
							
	Постоянные
		- Для мероприятия указан список дат.
		- Завершение записи.		не совпадает.
		
		- Мероприятие.				Есть возможность задать даты вручную или выбрать диапазон / дни недели
		- Список мероприятий. 		Все привязанные даты
			
		- Список заказов. 			Выводится минимальная дата из привязанных и кол-во занятий, если больше одного
		- Заказ.					Список привязанных дат.
		
		- Календарь пользователя. 	Показывается ближайшая дата, на которую есть заказ
		- Архив пользователя. 		Показывать, если хоть одна заказанная дата в прошлом 
		- Заказ на сайте.			Скидка отсчитыватся от даты на которую делается заказ.
									Абонементные тарифы на кол-во занятий и срочные, заказ привязывается к нескольким датам.
	 
		
	Курсы
		- Список дат, заказ на дату и автоматом на все последующие.
		- Завершение записи.		не совпадает, по умолчанию - день первого занятия.
		- Мероприятие.				Указываеться, как в постоянных списком дат, диапазоном + дни
									При сохранении новых дат, происходит автопривязка к заказам.
									
		- Список мероприятий. 		Все привязанные даты		
			
		- Список заказов. 			Выводится минимальная дата из привязанных и пометка если она не совпадает с минимальной по событию
		- Заказ.					Список привязанных дат.
		
		- Календарь пользователя. 	Показывается ближайшая дата, на которую есть заказ
		- Архив пользователя.		Показывать, если все заказанные даты в прошлом 
		- Заказ. 					Скидка отсчитывается от даты первого занятия по курсу
									Заказ привязыватся ко всем датам курса в будущем.
	
		*/
}
