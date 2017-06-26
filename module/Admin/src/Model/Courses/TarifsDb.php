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
use Zend\Db\Sql\Join;
use Common\Traits\ServiceManagerTrait;
use Common\Traits\ServiceManagerAware;

class TarifsDb extends Table implements CRUDListModel, Multilingual, Historical, OptionsModel, ServiceManagerAware{
	
	use MultilingualTrait, HistoricalTrait, ServiceManagerTrait;
	
	protected $table = 'course_tarifs';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'price_desc']);	
		$this->history('tarifs');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['ct' => 'course_tarifs']);		

		$select->join(['c' => 'courses'],'c.id = ct.course_id', ['course_title' => 'title_'.$this->lang()], Join::JOIN_LEFT);
		
		if(!empty($filter['query'])){
			$select->where->expression('concat(" ", LOWER(ct.title_'.$this->lang() .')) like ?', "% ".mb_strtolower($filter['query']."%"))
				->or->expression('concat(" ", LOWER(c.title_'.$this->lang() .')) like ?', "% ".mb_strtolower($filter['query']."%"));
		}
			
		if(!empty($filter['course_id'])){
			$select->where->equalTo('course_id', $filter['course_id']);
		}
		
		if(!empty($filter['event_id'])){
			$select->where->equalTo('ct.event_id', $filter['event_id']);
		}
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(ct.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		if($p !== null){
			$select->limit($ipp)->offset(($p-1)*$ipp);
		}
		$select
			->order('ct.priority desc')
			->order('ct.id asc');
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		if(!empty($item['discounts'])){
			$item['discounts'] = json_decode($item['discounts']);
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
    
    
	// Options Model implementation
	public function options() {
		return null;
	}

	public function option($key) {
		$item = $this->get($key);
		if($item != null ){
			return $item['title'];
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
	
	public function getEventTarifs($eventId){
		$select = new Select(['t' => 'course_tarifs']);
		 
		$select->join(['et' => 'course_event2tarif'] , 't.id = et.tarif_id', [], Join::JOIN_INNER);
		$select->where->equalTo('et.event_id', $eventId);
		 
		$select->order('t.priority desc')
				->order('t.id asc');
	
		$tarifs = $this->fetchAll($select);
		 
		if(!empty($tarifs)){
			$select = new Select(['o' => 'order_orders']);
			$select->reset(Select::COLUMNS)
					->columns(['tarif_id', 'count' => new Expression('count(id)')]);
	
			$select->group('o.tarif_id');
			$select->where->equalTo('o.event_id', $eventId);
			 
			$orderCounts = $select->fetchPairs();
	
			foreach ($tarifs as &$tarif){
				$tarif['order_count'] = $orderCounts[$tarif['id']];
			}
		}
	
		return $tarifs;
	
	}


		
}
