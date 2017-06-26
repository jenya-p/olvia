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
use Zend\Db\Sql\Join;
use Zend\Db\Sql\Where;
use Zend\Db\Sql\Sql;
use Common\Utils;

class TagDb extends Table implements CRUDListModel, Multilingual, OptionsModel {
	
	use MultilingualTrait;
	
	protected $table = 'content_tags';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['name', 'seo_title', 'seo_description', 'seo_keywords']);	
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['ct' => 'content_tags']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(ct.name_ru) like ?', mb_strtolower($filter['query']."%"));
		}
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(ct.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->join(['tg' => 'content_tag_groups'], 'tg.id = ct.group_id', ['group_name' => new Expression('tg.name_'.$this->lang())], Join::JOIN_LEFT);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order('ct.id asc');
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		$select = new Select(['tr' => 'content_tag_refs']);
		$select->where->equalTo('tag_id', $item['id']);
		$select->columns(['entity', 'count' => new Expression('count(item_id)')]);
		$select->group('entity');
		$counts = $select->fetchPairs();
		Utils::arrayMergePrefixed($counts, 'ref_count_', $item);
		return parent::buildItem($item);
	}

	public function insert($insert){
    	if(empty($insert['created'])){
    		$insert['created'] = time();
    	}
    	return parent::insert($insert);    	
    }	

    public function deleteOne($id){
    	$sql = new Sql( $this->getAdapter() );
    	$delete = $sql->delete('content_tag_refs');
    	$delete->where->equalTo('tag_id', $id);
    	$sql->prepareStatementForSqlObject($delete)->execute();
    	
    	return parent::deleteOne($id);
    }
    
	 
    var $options = null; 
    
	// Options Model implementation
	public function options() {
		// TODO Caching
		if($this->options == null){
			$select = new Select(['t' => 'content_tags']);
			$select->columns(['id', 'name' => 'name_'.$this->lang(), 'alias' => 'alias','status']);
			$select->join(['tg' => 'content_tag_groups'], 'tg.id = t.group_id', ['group_name' => new Expression('tg.name_'.$this->lang())], Join::JOIN_LEFT);
			$select->order('tg.id asc');
			$select->order('t.id asc');
			$this->options = [];
			foreach ($select->fetchAll() as &$row){
				$this->options[$row['id']] = $row;
			}			
		} 
		
		return $this->options;
	}

	public function option($key) {
		if($this->options == null){
			$this->options();
		}
		return $this->options[$key];		
	}
	
	public function optionName($key){
		$tag = $this->option($key);
		if(!empty($tag)){
			return $tag['name'];
		} else {
			return null;
		}
	}
	
	// Misc
	public function getStat($id){
		$item = $this->get($id);
		$stat = ['created' => $item['created']];
		
		return $stat;
	}	
		
	public function getItemTags($entity, $id = null){
		if(is_array($entity) && $id == null){
			$id = $entity[1];
			$entity = $entity[0];
		}
		$select = new Select(['tr' => 'content_tag_refs']);
		$select->where->expression('entity = ?', $entity)->and->equalTo('item_id', $id);
		$select->columns(['tag_id']);
		$select->order('tr.priority desc');
		return $select->fetchColumn();
	}
	
	
	public function saveItemTags($entity, $id, $tagIds = []){
		
		$sql = new Sql( $this->getAdapter() );
		$delete = $sql->delete('content_tag_refs');
		$delete->where->expression('entity = ?', $entity)->and->equalTo('item_id', $id);
		$sql->prepareStatementForSqlObject($delete)->execute();
		$tagIds = array_unique($tagIds);
		$priority = 0;
		foreach ($tagIds as $tagId){
			$insert = [
					'entity' => $entity,
					'item_id' => $id,
					'tag_id' => $tagId,
					'priority' => $priority
				];
			$this->getAdapter()->insert('content_tag_refs', $insert);
		}
	}
	
	
}
