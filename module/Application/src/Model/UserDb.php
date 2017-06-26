<?php 

namespace Application\Model;

use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Expression;
use Common\ViewHelper\Phone;
use Common\Db\Multilingual;
use Common\Db\Historical;
use Common\Db\MultilingualTrait;
use Common\Db\HistoricalTrait;
use Admin\Model\Users\UserHistoryTrait;
use Common\Identity;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Common\Utils;


class UserDb extends Table implements Multilingual, Historical, ServiceManagerAware {

	use MultilingualTrait, HistoricalTrait, UserHistoryTrait, ServiceManagerTrait;

	const ROLE_GUEST = 		'guest';
	const ROLE_ADMIN = 		'admin';
	const ROLE_CUSTOMER = 	'customer';
	const ROLE_MASTER = 	'master';
	
	const STATUS_NEW = 'new';
	const STATUS_VERIFIED = 'verified';
	const STATUS_BANNED = 'banned';
	//const STATUS_STUB = 'stub';
	
	
	
    protected $table = 'users_accounts';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields('master_name');
        $this->history('user');
    }
    
    const HASH_SALT = "6as@a3e#2";
    
    public function findByLogin($login){
    	$adapter = $this->getAdapter();
    	$select = new Select(['u' => 'users_accounts']);    	 
    	$select->where->expression("u.login = ?", $login);
    	$sql = new Sql($adapter);
    	return $sql->prepareStatementForSqlObject($select)->execute()->next();    	
    }
    
    public function checkEmail($newEmail, $id = null){
    	$select = new Select(['a' => 'users_accounts']);
    	if($id !== null){
    		$select->where->notEqualTo('id',$id);
    	}
    	 
    	$nest = $select->where->nest;
    	$nest->equalTo('email', $newEmail)->
    		or->equalTo('login', $newEmail);
    	$select->reset(Select::COLUMNS)->columns(['count' => new Expression('count(*)')]);
    	$rowsCount = $select->fetchOne();
    	return $rowsCount == 0;
    }
    
    
    public function defaultIdentity(){
	    return [
	    	'id' => 0,
	    	'name' => '',
	    	'roles' => [self::ROLE_GUEST]
	    ];
    }
       
    public function getIdentity($login, $password){

    	if(empty($login) || empty($password)){    		
    		return null;
    	}
    	
    	$select = $this->getIdentitySelect(true);

    	$select->where->expression("u.login = ?", $login);
    	$select->where->expression("u.password = ?", md5($password.self::HASH_SALT));
    	
    	$data = $this->fetchRow($select);
    	
    	if(empty($data)){
    		return null;
    	} else {
    		return $this->buildIdentity($data);    		
    	}    	    	
    }
   

    public function getIdentityByVk($id){
    	if(empty($id)){
    		throw new \Exception("Пустое VK ID");
    	}
    	
    	$select = $this->getIdentitySelect(false);
    	
    	$select->where->expression("u.vk_id = ?", $id);
    	
    	$data = $this->fetchRow($select);
    	if(empty($data)){
    		return null;
    	} else {
    		return $this->buildIdentity($data);
    	}
    }
    
    public function getIdentityByFb($id){
    	if(empty($id)){
    		throw new \Exception("Пустое FB ID");
    	}
    	
    	$select = $this->getIdentitySelect(false);
    	
    	$select->where->expression("u.fb_id = ?", $id);
    	
    	$data = $this->fetchRow($select);
    	
    	if(empty($data)){
    		return null;
    	} else {
    		return $this->buildIdentity($data);
    	}
    }
    
    public function getIdentityById($id){
    	if(empty($id)){
    		throw new \Exception("Пустое ID");
    	}
    	 
    	$select = $this->getIdentitySelect(false);
    	 
    	$select->where->expression("u.id = ?", $id);
    	 
    	$data = $this->fetchRow($select);
    	 
    	if(empty($data)){
    		return null;
    	} else {
    		return $this->buildIdentity($data);
    	}
    }
    
    
    /*
    public function insertStubIdentity($data){
    
    	$insert = [
    			'displayname' => $data['displayname'] ?: $data['name'],
    	];
    
    	foreach (['phone','skype','email','vk_id','fb_id'] as $fieldname){
    		if(!empty($data[$fieldname]) && empty($this->identity()->__get($fieldname))){
    			$insert[$fieldname] = $data[$fieldname];
    			$this->identity()->$fieldname = $data[$fieldname];
    		}
    	}
    
    	if(!empty($insert)){
    		$insert['status'] = self::STATUS_STUB;
    		$this->insert($insert);    		
    	}
    	return $this->buildIdentity($data);    
    }
    */
    
    
    
    /**
     * @return Select
     */
    private function getIdentitySelect($joinAdmins = false){
    	$select = new Select(['u' => 'users_accounts']);
    	if($joinAdmins){
    		$select->join(['a' => 'users_admins'], 'u.id = a.id', ['admin' => 	new Expression('a.active = 1')], Select::JOIN_LEFT);
    	}
    	$select->join(['c' => 'users_customers'], 'u.id = c.id', ['customer' => new Expression('c.active = 1')], Select::JOIN_LEFT);
    	$select->join(['m' => 'users_masters'], 'u.id = m.id', ['master' => 	new Expression('m.active = 1')], Select::JOIN_LEFT);
    	return $select;
    }
    
    
    private function buildIdentity($data) {
    	$identity = [
    			'id' => $data['id'],
    			'displayname' => $data['displayname'],
    			'skype' => $data['skype'],
    			'phone' => $data['phone'],
    			'email' => $data['email'],
    			'status' => $data['status'],
    			'roles' => []
    	];
    	
    	if(!empty($data['vk_id'])){
    		$identity['social'] = 'https://vk.com/'.$data['vk_id'];
    	} else if(!empty($data['fb_id'])){
    		$identity['social'] = 'https://www.facebook.com/'.$data['fb_id'];
    	}  
    	
    	if(!empty($data['admin']) && $data['admin'] == true){
    		$identity['roles'][] = self::ROLE_ADMIN;
    	}
    	if(!empty($data['customer']) && $data['customer'] == true){
    		$identity['roles'][] = self::ROLE_CUSTOMER;
    	}
    	if(!empty($data['master']) && $data['master'] == true){
    		$identity['roles'][] = self::ROLE_MASTER;
    	}
    	
    	return $identity;
    }
    
    
    public function setPassword($newPassword, $id) {
    	$update = [
    		'password' => $newPassword
    	];

    	return $this->updateOne($update, $id);
    }
    
    public function getCustomer($id){
    	$select = new Select(['c' => 'users_customers']);
    	$select->where->equalTo('id',$id);
    	return $this->getAdapter()->fetchRow($select);
    }
    
    public function registerCustomer($data){
    	
    	$account = [];
    	$account['displayname'] = $data['displayname'];
    	$account['phone'] 		= $data['phone'];
    	$account['skype'] 		= $data['skype'];
    	$account['email'] 		= $data['login'];
    	$account['login'] 		= $data['login'];
    	$account['vk_id'] 		= $data['vk_id'];
    	$account['fb_id'] = $data['fb_id'];
    	if(!empty($data['password'])){
    		$account['password'] = md5($data['password'].self::HASH_SALT);
    	}
    	
    	$id = $this->insert($account);    	
    	$customer = [
    			'id' => $id,
    			'name' => $data['displayname'],
    			'city' => $data['city'],
    			'image' => $data['image'],
    			'sex' => $data['sex'],
    			'birthday' => $data['birthday'],
    	];
    	if(!empty($data['customer_name'])){
    		$customer['name'] = $data['customer_name'];
    	}
    	
    	$this->getAdapter()->insert('users_customers', $customer);
    	return $id;
    }
    
    
    public function updateIdentity($data, Identity &$identity, $forse = false){
    	$data['displayname'] = $data['displayname'] ?: $data['name'];
    	$update = [];
    
    	$identityFields = ['displayname', 'phone','skype','email','vk_id','fb_id'];
    	
    	
    	foreach ($identityFields as $fieldname){
    		if(!empty($data[$fieldname]) && ($forse || empty($identity->__get($fieldname)))){
    			$identity->$fieldname = $data[$fieldname];
    		}
    	}
    	
    	if($identity->isLogged()){
    		/* @var $customerDb CustomerDb */
    		$customerDb = $this->serv(CustomerDb::class);
    		$id = $identity->id;
    		
    		$customerFields = ['name', 'city', 'birthday', 'sex', 'image', 'description'];
    		$customer = $customerDb->get($id);
    		$customerUpdate = Utils::arrayFilterAddition($data, $customerFields, $customer);
    		
    		if(!empty($customerUpdate)){
    			$customerDb->updateOne($customerUpdate, $id);
    		}
    		
    		$accountFields = ['password', 'displayname', 'login', 'email', 'phone', 'skype', 'vk_id', 'fb_id'];
    		$account = $this->get($id);    		
    		$accountUpdate = Utils::arrayFilterAddition($data, $accountFields, $account);
    		
    		if(!empty($accountUpdate)){
    			$this->updateOne($accountUpdate, $id);
    		}
    			
    	}
    }
    
    
    public function updateOne($data, $id){
    	    	 
    	$oldValues = $this->get($id);
    	
    	if(!empty($data['email'])){
    		if(empty($oldValues['login']) || $oldValues['login'] == $oldValues['email']){
    			$data['login'] = $data['email'];
    		}
    	}
    	    
    	if(!empty($data['password']))[
    		$data['password'] = md5($data['password'].\Application\Model\UserDb::HASH_SALT)
    	];
    	
    	$result = parent::update($data, "id=".$id);
    	
    	$this->saveHistory($data, $oldValues, $id);
    	
    	$this->clearCache($id);
    	    	
    	 
    	return $result;
    }
    
    public function updateCustomer($data, $id, $forse = true){
    	$customer = $this->getCustomer($id);
    	$data['name'] = $data['customer_name'] ?: $data['name'] ?: $data['displayname'];
    	
    	if(empty($customer)){
    		$insert = [
    			'id' => $id, 
    			'active' => 1,
    		];    	
   			$ret = $this->getAdapter()->insert('users_customers', $insert);
   			
    	} else {
    		$ret = $this->getAdapter()->updateOne('users_customers', $id, $data);    		
    	}
    	    	    	
    	return $ret;
    }
    
    
    public function logged($user){
    	$id = $this->id($user);
    	$this->getAdapter()->sql('UPDATE '.$this->table.' set logged = UNIX_TIMESTAMP() WHERE id = :id', ['id' => $id]);
    }
    
    public function accessed($user){
    	$id = $this->id($user);
    	$this->getAdapter()->sql('UPDATE '.$this->table.' set accessed = UNIX_TIMESTAMP() WHERE id = :id', ['id' => $id]);
    }
    
    public function refreshIdentity(Identity $identity){
    	if($identity->isLogged()){
    		$data = $this->getIdentityById($identity->id);
    		$identity->clear();    		    		
    		if(!empty($data)){    			
    			$identity->set($data);
    		}
    	}
    	
    }
    
}
