<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\OptionsModel;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;

class TagGroupDb extends Table implements CRUDListModel, Multilingual, OptionsModel {
	
	use MultilingualTrait;
	
	protected $table = 'content_tag_groups';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['name']);	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['ctg' => 'content_tag_groups']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(ctg.name_ru) like ?', mb_strtolower($filter['query']."%"));
		}

		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(ctg.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order('ctg.id asc');
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

    
    public function getChildCount($id){
    	$select = new Select(['t' => 'content_tags']);
    	$select->columns(['count' => new Expression('count(*)')]);
    	$select->where->expression('group_id = ?', $id);
    	return $select->fetchOne();
    }
    
    
    // Options Model implementation
    var $options = null;	
	public function options() {
		if($this->options === null){
			$select = new Select(['ctg' => 'content_tag_groups']);
			$select->order('id');
			$select->columns(['id', 'name_'.$this->lang()]);
			$this->options = $select->fetchPairs();
		}		
		return $this->options; 
	}

	public function option($key) {
		if($this->options === null){
			$this->options = $this->options();
		}
		return $this->options[$key];		
	}
	
	// Misc
	public function getStat($id){
		$item = $this->get($id);
		$stat = ['created' => $item['created']];
		return $stat;
	}	
		
}
