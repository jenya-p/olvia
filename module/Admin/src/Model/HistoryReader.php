<?
namespace Admin\Model;

use Common\Db\Adapter;
use Common\Db\Select;
use Admin\Model\Users\UserDb;

class HistoryReader {
	
	const TABLE_NAME = 'history';
	
	const EMPTY_VALUE = '<i>пусто</i>';
	
	/* @var Adapter */
	var $db;	
	/* @var array */
	var $config;
	
    var $entity;
    var $itemId;  
    var $attrs;   
	
    
    public function __construct(Adapter $db, $config){
    	$this->db = $db;
    	$this->config = $config;
    	$this->dictionaries['status'] = [0 => 'Выкл', '1' => 'Вкл'];
    	$this->dictionaries['user'] = [$this, 'getUserDescription'];
    	$this->dictionaries['author'] = [$this, 'getUserDescription'];
    	$this->dictionaries['date'] = [$this, 'dateDescription'];
    	$this->dictionaries['created'] = [$this, 'dateDescription'];
    }
    
    
    public function reset($entity, $itemId) {
    
    	$this->entity = $entity;
    
    	$this->itemId = $itemId;
    	
    	if(!array_key_exists($entity, $this->config) || !is_array($this->config[$entity])){
    		throw new \Exception('Для сущности "'.$entity.'" отсутствуют настройки истории');
    	}
    
    	$this->attrs = $this->config[$entity];
    
    }
    
    var $records = null;
    var $byDate = null;
    var $byStatus = null;
    
    var $dictionaries = array();
    public function addDictionary($name, &$dic){
    	$this->dictionaries[$name] = $dic;
    }
        
    public function getRecords($status = null){
    	if ($this->records === null){

    		$this->records = $this->db->fetchAll('SELECT * FROM history h
				WHERE h.item_id = :itemId AND h.entity in (:entity) 
				ORDER BY h.date desc', ['itemId' => $this->itemId, 'entity' => $this->entity]);
    		
    		$this->createIndexes();
    		
    	}
    	if($status == null){
    		return $this->records;
    	} else if(isset($this->byName[$status])) {
    		return $this->byName[$status];
    	} else {
    		return null;
    	}    		
    }
    
    
    public function getRecordsByDate(){
		$this->getRecords(false);		
    	return $this->byDate;
    }
    

    private function createIndexes(){
    	$this->byDate = array();
    	$this->byName = array();
    	foreach ($this->records as $rec){
    		
    		$attribute = $rec['attribute'];
    		if(array_key_exists($attribute, $this->dictionaries)){
    			if(is_callable($this->dictionaries[$attribute])){
    				$rec['value'] = call_user_func_array($this->dictionaries[$attribute], [$rec['value'], $rec]) ;
    				$rec['from_value'] = call_user_func_array($this->dictionaries[$attribute], [$rec['from_value'], $rec]) ;
    			} else {
    				if(array_key_exists($rec['value'], $this->dictionaries[$attribute])){    			
    					$rec['value'] = $this->dictionaries[$attribute][$rec['value']];
    				}
    				if(array_key_exists($rec['from_value'], $this->dictionaries[$attribute])){
    					$rec['from_value'] = $this->dictionaries[$attribute][$rec['from_value']];
    				}
    			} 
    			
    		} 
    		
    		if($rec['from_value'] == "" ) $rec['from_value'] = self::EMPTY_VALUE;
    		if($rec['value'] 	  == "" ) $rec['value'] = self::EMPTY_VALUE;
    		$rec['text'] = $this->attrs[$attribute];
    		
    		if(!isset($this->byDate[$rec['date']])){
    			$user = $this->getUser($rec['user']);
    			
    			$this->byDate[$rec['date']] = array(
    					'date' => $rec['date'],
    					'user' =>  $user,
    					'rows' => array()
    			);
    		}
    		
    		$this->byDate[$rec['date']]['rows'][] = $rec;
    	
    		if(!isset($this->byName[$attribute])){
    			$this->byName[$attribute] = array();
    		}
    		$this->byName[$attribute][] = $rec;    		
    	}
    }
    
    
    
    public function formatDates($rec){
    	if(empty($rec['from_value'])){
    		$rec['from_value'] = '-';
    	} else {
    		$rec['from_value'] = date("d.m.Y", $rec['from_value']);
    	}
    	if(empty($rec['value'])){
    		$rec['value'] = '-';
    	} else {
    		$rec['value'] = date("d.m.Y", $rec['value'] );
    	}
    }
   
    public function getLastUpdate(){
    	$record = $this->db->fetchRow('SELECT date as updated, user as user_id FROM history h
				WHERE h.item_id = :itemId AND h.entity in (:entity)
				ORDER BY h.date desc', ['itemId' => $this->itemId, 'entity' => $this->entity]);
    	if(!empty($record)){
    		$record['updated_by'] = $this->getUser($record['user_id']);
    	}
    	return $record;
    }
    
    public function getStat(){
    	$stat = [
	    	'entity' => $this->entity,
	    	'item_id' => $this->itemId,
	    	'has_history' => false];
    	
    	$record = $this->db->fetchRow("SELECT date, user FROM history h 
    		WHERE h.item_id = :itemId AND h.entity = :entity
	    	ORDER BY attribute = '_create' DESC, DATE ASC
	    	LIMIT 1", ['itemId' => $this->itemId, 'entity' => $this->entity]);
    	
    	if(!empty($record)){
    		$stat['created'] 	= $record['date'];    		
    		$stat['created_by'] = $this->getUser($record['user']);
    		$stat['has_history'] = true;
    	}
    	
    	$record = $this->db->fetchRow("SELECT date, user, attribute = '_delete' as is_delete FROM history h
	    	WHERE h.item_id = :itemId AND h.entity = :entity
	    	ORDER BY date DESC, attribute = '_delete' DESC
	    	LIMIT 1", ['itemId' => $this->itemId, 'entity' => $this->entity]);
    	
    	if(!empty($record)){    		
    		if($record['is_delete']){
    			$stat['deleted'] 	= $record['date'];
    			$stat['deleted_by'] = $this->getUser($record['user']);
    		} else {
    			$stat['updated'] 	= $record['date'];
    			$stat['updated_by'] = $this->getUser($record['user']);
    		}
    		$stat['has_history'] = true;
    	}	
    	
    	return $stat;
    }

    var $userCache = []; 
    protected function getUser($id){
    	if(!array_key_exists($id, $this->userCache)){
    		$this->userCache[$id] = $this->db->fetchRow('SELECT id, displayname from users_accounts where id = :id', ['id' => $id]);
    	}
    	return $this->userCache[$id];
    }
    
    protected function dateDescription($time, $rec){
    	if(empty($time)){
    		return self::EMPTY_VALUE;
    	}
    	return date('d.m.Y H:i', $time);
    }
    
    protected function getUserDescription($id, $rec){
    	if(empty($id)){
    		return self::EMPTY_VALUE;
    	}
    	$user = $this->getUser($id);
    	if(empty($user)) {
    		return 'error';
    	} else {
    		return $user['displayname'].' (id = '.$user['id'].')';
    	}
    }
    

}
