<?php 

namespace Admin\Model\Users;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Historical;
use Common\CRUDListModel;
use Zend\Db\Sql\Expression;
use Common\Db\HistoricalTrait;


class MasterPricesDb extends Table implements CRUDListModel, Historical, Multilingual {

	use HistoricalTrait, MultilingualTrait;
	
	protected $table = 'users_master_prices';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    	$this->langFields(['name', 'price_desc']);
    	$this->history('master-prices');
    }
    
    public function getMasterPrices($masterId){
    	$select = new Select($this->table);
    	$select->where->expression('master_id = ?',$masterId);
    	$select
    		->order('priority DESC')
    		->order('id ASC');
    	return $this->fetchAll($select);
    }
    
    /**
     * @param array $filter
     * @return Select
     */
    public function getSelect($filter){
    
    	$select = new Select(['mp' => 'users_master_prices']);
    	 
    	$select->join(['m' => 'users_masters'], 'm.id = mp.master_id',  ['master_name' => 'name_'.$this->lang]);
    	$select->join(['a' => 'users_accounts'], 'a.id = mp.master_id',  ['master_email' => 'email']);
    	 
    	if(!empty($filter['query'])){
    		$nest = $select->where->nest();
	    		$nest
	    				->expression('concat(" ", LOWER(mp.name_'.$this->lang.')) like ?', "% ".mb_strtolower($filter['query']."%"))
	    			->or->expression('concat(" ", LOWER(m.name_'.$this->lang.')) like ?', "% ".mb_strtolower($filter['query']."%"))
		    		->or->expression('LOWER(a.login) = ?', mb_strtolower($filter['query']));
    	}
    	 
    	if(!empty($filter['master'])){
    		$select->where->equalTo('mp.master_id', $filter['master']);
    	}
    	 
    	return $select;
    }
    
    public function getTotals($filter){
    	 
    	$select = $this->getSelect($filter);
    	$select->reset(Select::COLUMNS)
    		->columns(['count' => new Expression('count(mp.id)')]);
    	return $select->fetchRow();
    }
    
    public function getItems($filter, $p = 1, $ipp = 100){
    	$select = $this->getSelect($filter);
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    	
    	$sort = $filter['sort'];
    	if(!empty($sort) && is_array($sort) && count($sort) == 2){
    		
    		if(in_array($sort[0], ['price_desc', 'name'])){
    			$sort[0] = 'mp.'.$sort[0].'_'.$this->lang;
    		} else if($sort[0] == 'master_name'){
    			$sort[0] = 'm.name_'.$this->lang;
    		} else {
    			$sort[0] = 'mp.'.$sort[0];
    		}

    		$select->order($sort[0]. ' '.$sort[1]);
    	} else {
    		$select
	    		->order('m.priority DESC')
	    		->order('m.id ASC')
    			->order('mp.priority desc')
    			->order('mp.id asc');
    	}
    	
    	return $this->fetchAll($select);    	
    }
    
    
    
    public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
    	$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
    	$historyWriter->setSkipDataFor(['body', 'consultation', 'education', 'seo_description', 'seo_keywords']);
    	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	$historyReader = $this->getHistoryReader($id);
    	return $historyReader->getRecordsByDate();
    }
    

    public function getStat($id){
    	$historyReader = $this->getHistoryReader($id);
    	$stat = $historyReader->getStat();
    	return $stat;
    }

    public function getDescription($id){
    	$price = $this->get($id);
    	if(!empty($price)){
    		return $price['name'].' (id = '.$id.')';
    	} else if(!empty($id)){
    		return '<i>не найдено</i> (id = '.$id.')';
    	} else {
    		return null;
    	}
    }
    
}
