<?php 

namespace Application\Model;

use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Select;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;


class MasterPricesDb extends Table implements Multilingual{

	use MultilingualTrait;
	
	protected $table = 'users_master_prices';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    	$this->langFields(['name', 'price_desc']);    
    }
    
    public function getMasterPrices($masterId){
    	$select = new Select($this->table);
    	$select->where
    		->expression('master_id = ?',$masterId)
    		->and
    		->equalTo('status = ?',1);
    	$select
    		->order('priority DESC')
    		->order('id ASC');
    	return $this->fetchAll($select);
    }
    
}
