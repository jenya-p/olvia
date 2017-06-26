<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

class DiplomDb extends Table implements CRUDListModel, Multilingual {
	
	use MultilingualTrait;
	
	protected $table = 'content_diplomas';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title']);
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['cd' => 'content_diplomas']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(cd.title_ru) like ?', mb_strtolower($filter['query']."%"));
		}
				
		if(!empty($filter['master'])){
			$select->where->equalTo('master_id', $filter['master']);
		}
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(cd.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$select->join(['u' => 'users_accounts'], 'u.id = cd.master_id', ['master_name' => 'displayname'], Join::JOIN_LEFT);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			if($sort[0] == 'master_name'){
				$select->order(new Expression('u.displayname '.$sort[1]));
			} else {
				$select->order('cd.'.$sort[0]. ' '.$sort[1]);
			}
		} else {
			$select->order('cd.id desc');
		}
		
		
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		return parent::buildItem($item);
	}

	public function insert($insert){
    	return parent::insert($insert);    	
    }	


}
