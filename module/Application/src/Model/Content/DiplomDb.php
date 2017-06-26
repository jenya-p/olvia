<?php 

namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Select;
use Zend\Db\Sql\Expression;


class DiplomDb extends Table implements Multilingual {

	use MultilingualTrait;
	
	protected $table = 'content_diplomas';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title']);
    }
    
    public function getHomeDiplomas(){
    	$select = new Select($this->table);
    	$select->where
	    	->expression('status = ?', 1)->
	    	and->expression('home = ?', 1);
   	
    	$select
    		->order('priority desc')
    		->order('id asc');
    		
    	return $this->fetchAll($select);
    	 
    }
    
    public function getMasterDiplomas($masterId){
    	$select = new Select($this->table);
    	$select->where
    	->expression('status = ?', 1)->
    	and->expression('master_id = ?', $masterId);
    
    	$select
	    	->order('priority desc')
	    	->order('id asc');
    
    	return $this->fetchAll($select);
    
    }
    
}
