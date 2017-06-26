<?
namespace Application\Model\Courses;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Join;
use Common\Db\Select;

class TarifsDb extends Table implements Multilingual{
	
	use MultilingualTrait;
	
	protected $table = 'course_tarifs';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title']);
	}

	
	var $tarifsByEventCache = [];
	
	public function getEventTarifs($eventId, $date = null){
		
		if(!isset($this->tarifsByEventCache[$eventId])){
			
			$select = new Select(['t' => 'course_tarifs']);
			$select->join(['e2t' => 'course_event2tarif'], 'e2t.tarif_id = t.id', [], Join::JOIN_INNER);
			$select->where->equalTo('t.status', 1)->
				and->equalTo('e2t.event_id', $eventId);
			$select->order('t.priority desc')->order('t.id asc');
			
			$this->tarifsByEventCache[$eventId] = $this->fetchAll($select);
			
		}
		
		if($date != null){
			foreach ($this->tarifsByEventCache[$eventId] as &$tarif){
				$this->calculateActualPrice($tarif, $date);
			}
		}
		return $this->tarifsByEventCache[$eventId];
		
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
	
	public function calculateActualPrice(&$item, $eventDate, $now = null){
		$item['actual_price'] = $item['price'];
		if($item['price'] == 0 || empty($item['discounts'])) return;		
		if($now == null){
			$now = strtotime('today');
		} else {
			$now = strtotime("midnight", time());
		}
		
		$eventDate = strtotime("midnight", $eventDate);
		$deltaDays = ($eventDate - $now) / (60 * 60 * 24);
		 
		$item['actual_discounts'] = [];
		foreach ($item['discounts'] as &$discountRec){
			$discountRec['price'] = $item['price'] - $discountRec['discount'];
			$discountRec['till_date'] = $eventDate - 60 * 60 * 24 * $discountRec['days'];
			if($discountRec['days'] <= $deltaDays){				
				$item['actual_price'] = $discountRec['price'];
				$item['actual_discounts'][] = $discountRec;
			}
		}
	}
	
	
}
