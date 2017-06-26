<?
namespace Admin\Model;

use Common\Db\Adapter;

class HistoryWriter {
	
	const TABLE_NAME = 'history';
	
	/* @var Adapter */
    var $db;
    /* @var array */
    var $config;    
    var $entity;
    var $itemId;
    var $old;
    var $new;
    var $attr2key;
    var $attrs;   
    var $skipDataFor = [];
    
    public function __construct(Adapter $db, $config){
    	$this->db = $db;
    	$this->config = $config;
    }
    
    /** 
     * Записывальщик истории
     * @param string $entity имя таблицы
     * @param array|object $newValues - то, что будем писать в БД 
     * @param array|object $oldValues - что сейчас есть в БД
     * @param string $itemId		  - ID строки, если не указано, берется из $oldValues['id']
     */
    public function reset($entity, $newValues = null, $oldValues = null, $itemId = null) {
		$this->new = (array)$newValues;
		$this->old = (array)$oldValues;		
		if(!empty($itemId)) $this->itemId = $itemId;
		else $this->itemId = $this->old['id'];
		
		$this->entity = $entity;
		
		if(!array_key_exists($entity, $this->config) || !is_array($this->config[$entity])){
			throw new \Exception('Для сущности "'.$entity.'" отсутствуют настройки истории');
		}
		
		
		$this->attrs = array_keys($this->config[$entity]);
		
    }
    
    public function getAttrs(){
    	return $this->attrs;
    }
    
    public function setAttrs($attrs){
    	return $this->attrs = $attrs;
    }
    
    public function removeAttr($attr){    	
    	if(isset($this->attrs[$attr])){
    		unset($this->attrs[array_search($attr,$this->attrs)]);
    	}
    	return $this;
    }
    
    public function setSkipDataFor($skippedAttrs){
    	$this->skipDataFor = $skippedAttrs;
    }
    
    public function hasUpdated($attr){
    	return (array_key_exists($attr, $this->new)) && $this->new[$attr] != $this->old[$attr];
    }
        
    public function write($attr, $newVal=null, $oldVal=null, $extra=null, $extra2=null){
    	$ind = array_search($attr,$this->attrs); 
    	if($ind !== null){
    		if(strlen($newVal) > 255){
    			$newVal = null;
    		}
    		if(strlen($oldVal) > 255){
    			$oldVal = null;
    		}
    		unset($this->attrs[$ind]);
    		if(is_array($extra)) $extra = serialize($extra);
    		$insert_array = array(
    				'entity'		=> $this->entity,
    				'attribute' 	=> $attr,
    				'item_id' => $this->itemId,
    				'date' => $this->getTime(),
    				'user' => $this->getUserId(),
    				'value' => $newVal,
    				'from_value' => $oldVal,
    				'extra' => $extra,
    				'extra2' => $extra2
    		);
    		$this->db->insert(self::TABLE_NAME, $insert_array);    		
    	}    	
    }

    public function writeArrayDiff($attr, $newVal = [], $oldVal = [], $extra=null, $extra2=null){
    	if(in_array('add_'.$attr, $this->attrs)){    		
    		foreach ($newVal as $nId){
    			if(!in_array($nId, $oldVal)){
    				$this->write('add_'.$attr, $nId, null, $extra, $extra2);
    			}
    		}
    	}
    	if(in_array('remove_'.$attr, $this->attrs)){
    		foreach ($oldVal as $oId){
    			if(!in_array($oId, $newVal)){
    				$this->write('remove_'.$attr, null, $oId, $extra, $extra2);
    			}
    		}
    	}
    }
    
    
    
    /**
     * Записываем все
     * @param string $extraValues - аналитика для каждого отдельного атрибута
     */
    public function writeAll($extraValues=null){
    	foreach ($this->attrs as $attr){    		
    		if($this->hasUpdated($attr)){    			 
    			if(isset($extraValues[$attr])) {
    				$extra = $extraValues[$attr];    			
    			} else {
    				$extra = null;
    			}
    			if(in_array($attr, $this->skipDataFor)){
    				$this->write($attr, null, null, $extra);
    			} else {
    				$this->write($attr, $this->new[$attr], $this->old[$attr], $extra);
    			}
    			
    		}
    	}
    }
    
    /**
     * Есть ли изменения? 
     * @return boolean
     */
    public function hasUpdates(){
    	foreach ($this->attrs as $attr){
    		if($this->hasUpdated($attr)){
    			return true;
    		}		
    	}
    }
    /**
     * Записываем только какие то конкретные атрибуты
     * @param array[string] $attrs - имена атрибутов
     * @param string $extraValues  - аналитика для каждого атрибута
     */
    public function writeAttributes($attrs, $extraValues=null){
    	foreach ($attrs as $attr){
    		if($this->hasUpdated($attr)){
    			if(isset($extraValues[$attr])) {
    				$extra = $extraValues[$attr];
    			} else {
    				$extra = null;
    			}
    			$this->write($attr, $this->new[$attr], $this->old[$attr], $extra);
    		}
    	}
    }
    
    
    // Хелперы для получения текущего времени и пользователя
    var $time = null;
    private function getTime(){
    	if($this->time==null){
    		$this->time = time();
    	}
    	return $this->time;
    }
    
    var $userId = null;

	public function getEntity() {
		return $this->entity;
	}

	public function setEntity($entity) {
		$this->entity = $entity;
		return $this;
	}

	public function getItemId() {
		return $this->itemId;
	}

	public function setItemId($itemId) {
		$this->itemId = $itemId;
		return $this;
	}

	public function getOld() {
		return $this->old;
	}

	public function setOld($old) {
		$this->old = $old;
		return $this;
	}

	public function getNew() {
		return $this->new;
	}

	public function setNew($new) {
		$this->new = $new;
		return $this;
	}

	public function getUserId() {
		return $this->userId;
	}

	public function setUserId($userId) {
		$this->userId = $userId;
		return $this;
	}
	
    
    
}

