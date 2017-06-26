<?php 

namespace Application\Model;

use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;

class CustomerDb extends Table implements Historical{
	
	use HistoricalTrait;
	
    protected $table = 'users_customers';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->history('customer');
    }
    
    public function get($id){
    	$id = $this->id($id);
    	if(isset($this->cache[$id])){
    		return $this->cache[$id];
    	}
    
    	$select = new Select(['c' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'c.id = a.id',  [    			
    		'displayname',
    		'email',
    		'skype',
    		'phone',
    		'vk_id', 
    		'fb_id',
    	], Select::JOIN_LEFT);
    	 
    	$select->where->equalTo('c.id', $id);
    	$item = $this->getAdapter()->fetchRow($select);
    
    	$this->buildItem($item);
    
    	$this->cache[$id] = $item;
    
    	return $item;
    }
    
    /*
     * Возвращает тройки (
     * 	type: (preorder|order|consult), 
     *  id - ID документа, 
     *  date - дата выподлнения, по ней и сортируется список 
     * )
     */
    public function getActualOrderIds($uderId){
    	// ЫЫыыы!! 
    	$sql = "select * from (
    			select
    			'preorder' as type,
    			o.id as id,
    			e.expiration_date as meet_date
    			from order_orders o
    			inner join course_events e on e.id = o.event_id
    			where o.user_id = ".$uderId." AND e.type = 'announce' and e.expiration_date > UNIX_TIMESTAMP() and o.`status` != 'decline' and o.`status` != 'archive'
    	
    		union
    			select
    			'order' as type,
    			o.id as id,
    			max(shed.date) as meet_date
    			from order_orders o
    			inner join course_events e on e.id = o.event_id
    			inner join order_order2shedule o2s on o2s.order_id = o.id
    			inner join course_event_shedule shed on shed.id = o2s.shedule_id
    			where o.user_id = ".$uderId." AND e.type != 'announce' and shed.date > UNIX_TIMESTAMP() and o.`status` != 'decline' and o.`status` != 'archive'
    			group by o.id
    	
    		union
    	
    			select
    			'consult' as type,
    			c.id as id,
    			c.meet_date as meet_date
    			from order_consultations c
    			where c.user_id = ".$uderId." AND (c.meet_date > UNIX_TIMESTAMP() OR c.meet_date is NULL) and c.status != 'decline' and c.status != 'archive'
    	) t order by t.meet_date asc";  
    	
    	
    	return $this->getAdapter()->fetchAll($sql);		 
    }
    
    public function getHistoryOrderIds($uderId){
    	// ЫЫыыы!!
    	$sql = "
    		select * from (
    			
    			select
    			'order' as type,
    			o.id as id,
    			max(shed.date) as meet_date
    			from order_orders o
    			inner join course_events e on e.id = o.event_id
    			inner join order_order2shedule o2s on o2s.order_id = o.id
    			inner join course_event_shedule shed on shed.id = o2s.shedule_id
    			where o.user_id = ".$uderId." AND e.type != 'announce' and shed.date <= UNIX_TIMESTAMP()  and o.`status` != 'decline' and o.`status` != 'archive'
    			group by o.id
   
    		union
   
    			select
    			'consult' as type,
    			c.id as id,
    			c.meet_date as meet_date
    			from order_consultations c
    			where c.user_id = ".$uderId." AND (c.meet_date <= UNIX_TIMESTAMP()) and c.status != 'decline' and c.status != 'archive'
    	) t order by t.meet_date asc";
    	return $this->getAdapter()->fetchAll($sql);
    }
    
    
    
    public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
    	$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
    	$historyWriter->setSkipDataFor('description');
    	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	return null; 
    }
    
}
