<?php 

namespace Application\Model;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Application\Model\Users\MasterPricesDb;
use Zend\Db\Sql\Join;

class MasterDb extends Table implements Multilingual{
	
	use MultilingualTrait;
	
	protected $table = 'users_masters';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['name', 'summary', 'body', 'consultation', 'education', 'seo_title', 'seo_description', 'seo_keywords']);
    }
    
    
    public function getByAlias($alias){
    	    	    	 
    	$select = new Select(['m' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['displayname', 'email'], Select::JOIN_LEFT);
    	$select->where->equalTo('m.alias', $alias)->
    		and->equalTo('m.status', 1);
    	
    	$item = $this->fetchRow($select);
    	
    	$this->buildItem($item);
    	 
    	return $item;
    }
        
    
    public function getSelect($filter){
    
    	$select = new Select(['m' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['displayname', 'email'], Select::JOIN_LEFT);
    	$select->where->equalTo('m.status', 1);
    	
    	if(!empty($filter['query'])){
    		
    		$select->where->and->expression('concat(" ", LOWER(m.name_'.$this->lang.')) like ?', "% ".mb_strtolower($filter['query']."%"));
    		
    	}
    		
    	return $select;
    }
    
    public function getTotals($filter){    
    	$select = $this->getSelect($filter);
    	$select->reset(Select::COLUMNS)
    		->columns(['count' => new Expression('count(m.id)')]);
    	return $select->fetchRow();    
    }
    
    public function getItems($filter, $p = 1, $ipp = 100){
    	$select = $this->getSelect($filter);
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    
    	$select
    		->order('m.priority desc')
    		->order('m.name_'.$this->lang.' asc');
    
    	$items = $this->fetchAll($select);

    	return $items;
    }
    
    
    public function getPersonalConsultants(){
    	$select = new Select(['m' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['displayname', 'email'], Select::JOIN_LEFT);
    	$select->where->equalTo('m.status', 1)
    		->and->equalTo('m.personal', 1);
    		
    	$select
	    	->order('m.priority desc')
	    	->order('m.name_'.$this->lang.' asc');
    	    	
    	$masters = $this->fetchAll($select);
    	
    	return $masters;
    }
    
    
    
    public function getCourseMasters($courseId){
    	$select = new Select(['m' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['displayname', 'email'], Select::JOIN_LEFT);
    	
    	$select->join(['e2m' => 'course_event2master'], 'e2m.master_id = m.id', [], Join::JOIN_INNER);
    	$select->join(['e' => 'course_events'], 'e.id = e2m.event_id', [], Join::JOIN_INNER);
    	
    	$select
    		->where->equalTo('e.status', 1)
    		->and->expression('(e.expiration_date is null OR e.expiration_date > ?)', ['now' => time()])
    		->and->equalTo('e.course_id', $courseId)
    		->and->equalTo('m.status', 1);
    	
    	$select
	    	->order('m.priority desc')
	    	->order('m.id asc')
    		->group('m.id');
    	    		
    	return $this->fetchAll($select);
    }

    
    var $mastersByEventCache = [];
    
    public function getEventMasters($eventId){

    	if(!isset($this->mastersByEventCache[$eventId])){
    		
    		$select = new Select(['m' => $this->table]);
    		$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['displayname', 'email'], Select::JOIN_LEFT);
    		 
    		$select->join(['e2m' => 'course_event2master'], 'e2m.master_id = m.id', [], Join::JOIN_INNER);
    		
    		$select->where
	    		->equalTo('e2m.event_id', $eventId)
	    		->and->equalTo('m.status', 1);
    		
    		$select
	    		->order('m.priority desc')
	    		->order('m.id asc');
    		
    		$this->mastersByEventCache[$eventId] = $this->fetchAll($select);
    		
    	}
    	return $this->mastersByEventCache[$eventId];    	
    	
    }
    
    
}
