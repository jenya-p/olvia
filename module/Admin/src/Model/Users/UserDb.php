<?php 

namespace Admin\Model\Users;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\OptionsModel;
use Common\Db\Select;
use Common\Db\Table;
use Common\Form\Element;
use Common\Form\Option;
use Common\Utils;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;

class UserDb extends Table implements CRUDListModel, OptionsModel, Multilingual, Historical, ServiceManagerAware {

	use MultilingualTrait, HistoricalTrait, UserHistoryTrait, ServiceManagerTrait;
	
	const ROLE_GUEST = 		'guest';
	const ROLE_ADMIN = 		'admin';
	const ROLE_CUSTOMER = 	'customer';
	const ROLE_MASTER = 	'master';
	
	var $roleTables = [ 
			self::ROLE_ADMIN => 	'users_admins',
			self::ROLE_CUSTOMER => 	'users_customers',
			self::ROLE_MASTER => 	'users_masters' 
	];
	
    protected $table = 'users_accounts';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;        
        $this->langFields('master_name');
        $this->history('user');
    }
    
    
    public function get($id){
    	$id = $this->id($id);
    	if(isset($this->cache[$id])){
    		return $this->cache[$id];    		
    	}
    	
    	$select = new Select(['a' => $this->table]);
    	$select->where->equalTo('a.id', $id);
    	
    	$item = $this->getAdapter()->fetchRow($select);
    	
    	if(empty($item)) {
    		return null;
    	}
    	 
    	$this->buildItem($item);
    	
    	$this->cache[$id] = $item;
    	 
    	return $item;
    }
    
    public function getRoles($id){
    	$id = $this->id($id);
    	$profiles = $this->getProfiles($id);
    	$roles = [];
    	foreach (array_keys($this->roleTables) as $role){
    		if(array_key_exists($role, $profiles) && $profiles[$role]['active'] == 1){
    			$roles[] = $role;
    		}
    	}
    	return $roles;
    }
    
    var $profilesCache = [];
    
    public function getProfiles($id){
    	$id = $this->id($id);
    	if(isset($this->profilesCache[$id])){
    		return $this->profilesCache[$id];
    	}
    	 
    	$profiles = [];
    	
    	foreach ($this->roleTables as $role => $table){    		
    		$select = new Select([$role => $table]);
    		$select->columns(['active']);
    		$select->where->expression('id = ?', $id);
    		$profile = $this->getAdapter()->fetchRow($select);
    		if(!empty($profile)){
    			$profiles[$role] = $profile;
    		}    		 
    	}
    	 
    	$this->profilesCache[$id] = $profiles;
    	return $profiles;
    	 
    }
    
    public function insert($data){
    	
    	$this->abstractLanguage($data);
    	if(empty($data['email'])){
    		$data['email'] = null;
    	}
    	
    	$data['login'] = $data['email'];
    	
    	if(!empty($data['password'])){
    		$data['password'] = md5($data['password'].\Application\Model\UserDb::HASH_SALT);
    	}
    	
    	$insert = $data; 
    	unset($insert['roles']);
    	
    	$id = parent::insert($insert);
    	 
    	return $id;    	
    }

    public function updateOne($data, $id){
    	
    	$oldValues = $this->get($id);
    	
    	$this->abstractLanguage($data);
    	
    	if(empty($data['email'])){
    		$data['email'] = null;
    	}
    	
    	if(empty($oldValues['login']) || $oldValues['login'] == $oldValues['email']){
    		$data['login'] = $data['email'];
    	}
    	    	
    	if(!empty($data['password']))[
    		$data['password'] = md5($data['password'].\Application\Model\UserDb::HASH_SALT)
    	];
    	
    	$result = parent::update($data, "id=$id");
    	
    	$this->saveHistory($data, $oldValues, $id);
    	
    	$this->clearCache($id);
    	
    	return $result;
    }
     
    public function updateRoles($roles, $data, $id){
    	$oldRoles = $this->getRoles($id);
    	    	
    	$this->updateRole($id, UserDb::ROLE_ADMIN, 		in_array(UserDb::ROLE_ADMIN, $roles));
    	$this->updateRole($id, UserDb::ROLE_CUSTOMER, 	in_array(UserDb::ROLE_CUSTOMER, $roles), 	['name' => $data['displayname']]	);
    	$this->updateRole($id, UserDb::ROLE_MASTER, 	in_array(UserDb::ROLE_MASTER, $roles), 		['name_ru' => $data['displayname']]	);
    	
    	$this->saveHistory(['roles' => $roles],['roles' => $oldRoles], $id);
    	
    	unset($this->profilesCache[$id]);
    	 
    }
    
    public function updateRole($userId, $role, $active, $defaultData = null){
    	$table = $this->getRoleTable($role);
    	$profiles = $this->getProfiles($userId, $role);
    	$profile = $profiles[$role];
    	
    	$db = $this->getAdapter();
    	$active = boolval($active);
    	
    	if($active === false){
    		if(!empty($profile) && $profile['active'] == 1){
    			$db->updateOne($table, $userId, ['active' => 0]);
    		}
    	} else {
    		$data = ['active' => 1];
    		if(empty($profile)){    			
    			$data['id'] = $userId;
    			if(!empty($defaultData)){
    				$data = array_merge($data, $defaultData);
    			}
    			$db->insert($table, $data);
    		} else {
    			$db->updateOne($table, $userId, $data);
    		}
    	}
    }
    
    
    /**
     * @param array $filter
     * @return Select
     */
    public function getSelect($filter){
    
    	$select = new Select(['u' => 'users_accounts']);
    	
    	foreach ($this->roleTables as $role => $table){
    		$select->join([$role => $table], 'u.id = '.$role.'.id',  [$role => new Expression(''.$role.'.active = 1')], Select::JOIN_LEFT);
    	}
    	
    	if(!empty($filter['query'])){
    		$nest = $select->where->nest();
    		$nest->expression('LOWER(u.displayname) like ?', mb_strtolower($filter['query']."%"))
    			->or->expression('LOWER(u.login) = ?', mb_strtolower($filter['query']));
    	}
    	
    	
    	if(!empty($filter['role'])){    		
    		$select->where->expression($filter['role'].'.active = 1', []);
    	}
    	
    	return $select;
    }
    
    public function getTotals($filter){
    	
    	$select = $this->getSelect($filter);
    	$select->reset(Select::COLUMNS)
    		->columns(['count' => new Expression('count(u.id)')]);
    	return $select->fetchRow();
    }
    
    public function getItems($filter, $p = 1, $ipp = 100){
    	$select = $this->getSelect($filter);
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    	$select->order('u.id asc');
    	$items = $select->fetchAll();
    	foreach ($items as &$item){
    		$this->buildItem($item);
    	}
    	return $items;
    }
    
    
    public function buildItem(&$item){
    	return parent::buildItem($item);
    }
    
    
    public function checkEmailUniqueness($email, $id = null){
    	$select = new Select(['u' => 'users_accounts']);
    	$select->where->expression('u.email = ?', $email);
    	if(!empty($id)){
    		$select->where->expression('u.id != ?', $id);
    	}
    	$select->columns([new Expression('count(*)')]);
    	return $select->fetchOne() == 0;
    }
    
  
    public function getRoleTable($role){
    	if(!array_key_exists($role, $this->roleTables)){
    		throw new \Exception('Роль пользователя "'.$role.'" не существет');
    	}
    	return  $this->roleTables[$role];
    }
    
	public function options() {
		return null;
	}

	public function option($key) {
		$user = $this->get($key);
		$profiles = $this->getProfiles($key);
		
		if($user != null ){
			return [
				'label' => $user['displayname'],
				'roles' => array_keys($profiles)
			];
		} else {
			null;
		}
	}
	
	
	public function getRoleOptions(){
		return [
				self::ROLE_ADMIN => 'Администратор',
				self::ROLE_MASTER => 'Специалист',
				self::ROLE_CUSTOMER => 'Клиент',
		];
	}
	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();
		$item = $this->get($id);
		$stat['logged'] = $item['logged'];
		$stat['accessed'] = $item['accessed'];
		return $stat;
	}
	
	
}
