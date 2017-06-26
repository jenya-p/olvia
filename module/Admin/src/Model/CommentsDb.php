<?
namespace Admin\Model;

use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

class CommentsDb extends Table{
	
	protected $table = 'comments';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;		
	}
	
	public function get($id){
		$id = $this->id($id);
		if(!isset($this->cache[$id])){
			$select = new Select(['c' => 'comments']);
			$select->where->equalTo('c.id', $id);
			$select->join(['u' => 'users_accounts'], 'u.id = c.user_id', ['user_displayname' => 'displayname'], Join::JOIN_LEFT);
			$this->cache[$id] = $this->getAdapter()->fetchRow($select);
			$this->buildItem($this->cache[$id]);
		}
		return $this->cache[$id];
	}
	
	
	public function getInfo($entity, $id){
		$select = new Select(['c' => 'comments']);
		$select
			->columns(['user_id', 'date', 'body'])
			->where->equalTo('entity', $entity)
			->and->equalTo('item_id', $id);
		$select
			->order('c.date DESC')
			->order('c.id ASC');
		
		$select
			->join(['u' => 'users_accounts'], 'u.id = c.user_id', ['user_displayname' => 'displayname'], Join::JOIN_LEFT)
			->limit(1);		
		
		$info = $this->fetchRow($select);

		$select = new Select(['c' => 'comments']);
		$select->columns(['count' => new Expression('count(*)')]);
		$select->where->equalTo('entity', $entity)->and->equalTo('item_id', $id);
		$info['count'] = $select->fetchOne();

		return $info;		
	}
	
	public function getCommments($entity, $id, $limit = null){
		$select = new Select(['c' => 'comments']);
		
		$select->where->equalTo('entity', $entity)
			->and->equalTo('item_id', $id);
		
		$select
			->order('c.date DESC')
			->order('c.id ASC');
		
		if(!empty($limit)){
			$select->limit($limit);
		}
			
		$select->join(['u' => 'users_accounts'], 'u.id = c.user_id', ['user_displayname' => 'displayname'], Join::JOIN_LEFT);
		
		return $this->fetchAll($select);
	}
	
	public function deleteComments($entity, $id){
		return $this->delete(['entity' => $entity, 'item_id' => $id]);
	}
		
}
