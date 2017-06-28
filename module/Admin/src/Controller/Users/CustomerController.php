<?php

namespace Admin\Controller\Users;

use Admin\Forms\Users\CustomerForm;
use Admin\Model\Users\CustomerDb;
use Admin\Model\Users\UserDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ImageService;
use Common\ViewHelper\Flash;
use Zend\View\Model\JsonModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\Utils;
use Common\ViewHelper\Phone;
use Common\Db\Select;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property UserDb $db 
 */
class CustomerController extends CRUDController implements CRUDEditModel{
	
	
	/** @var $userDb UserDb */
	var $userDb = null;
	
	
	public function init(){
		$this->db = $this->serv(CustomerDb::class);
		$this->userDb = $this->serv(UserDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="customer-index",route="/customer-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function customerIndexAction(){
		return $this->crudList($this->db);
	}
	
	/**
	 * @Route(name="customer-edit", route="/customer-edit/:id",extends="private",type="segment")
	 */
	public function customerEditAction(){
		return parent::processEditForm(CustomerForm::class, $this);
	}
		
	protected function index(){		
	}
	
	
	// CRUD Model 
	public function load($id) {
		$item = $this->db->get($id);
		if(!empty($item)){
			$account = $this->userDb->get($id);
			Utils::arrayMergePrefixed($account, 'account_', $item);
		}		
		return $item;
	}

	public function create() {
		return ['name' => ''];
	}

	public function validate(array $data){
		if(!empty($data['account_email']) && !$this->userDb->checkEmailUniqueness($data['account_email'], $this->id)){
			$this->form->error('account_email', "Этот email-адресс уже зарегестрирован");
		}	
		
		if(empty($data['account_displayname']) && empty($data['name'])){
			$this->form->error('account_displayname', "Заполните это поле");
		}
    }
    
    public function save(array $data){
    	
    	$account = Utils::arrayDetachPrefixed($data, 'account_');
    	
    	if(empty($account['displayname']) && !empty($data['name'])){
    		$account['displayname'] = $data['name'];
    	} else if(!empty($account['displayname']) && empty($data['name'])){
    		$data['name'] = $account['displayname'];
    	}
    	
    	if($this->isNew){
    		$data['id'] = $this->id = $this->userDb->insert($account);
    		$this->db->insert($data);
    		$this->userDb->saveHistory(['roles' => [UserDb::ROLE_CUSTOMER]], ['roles' => []], $this->id);
    	} else {
    		$this->userDb->updateOne($account, $this->id);
    		$this->db->updateOne($data, $this->id);
    	}
    	
    	return $this->id;
    }
    
    public function afterSave(){
    	if($this->isNew){
    		$this->sendFlashMessage("Профиль клиента создан", Flash::SUCCESS);
    	} else {
    		$this->sendFlashMessage("Профиль клиента сохранен", Flash::SUCCESS);
    	}

    	return $this->afterSaveRedirect();
    }
    
    public function edit(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    	 
    	return [    		
    		'stat' => $this->db->getStat($this->item['id']),
    		'profiles' => $this->userDb->getProfiles($this->item['id'])
    	];
    	
    }

    /**
     * @Route(name="customer-status", route="/customer-status/:id",extends="private",type="segment")
     */
    public function statusAction(){
    	$id = $this->params('id', 'new');
    	$item = $this->db->get($id);
    	if(empty($item)){
    		return new JsonModel(['result' => 'error', 'message' => 'Объект не найден']);
    	}
    	$update = ['active' => 0];
    	if($item['active'] == 0){
    		$update['active'] = 1;
    	}
    	$this->db->updateOne($update, $id);
    	return new JsonModel(['result' => 'ok', 'active' => $update['active']]);
    	 
    }

    
    /**
     * @Route(name="customer-details-ajax", route="/customer-details-ajax",extends="private",type="Literal")
     */
    public function customerDetailsAction(){
    	$id = $this->params()->fromQuery('id', null);
    	$phone = $this->params()->fromQuery('phone', null);
    	$skype = $this->params()->fromQuery('skype', null);
    	$email = $this->params()->fromQuery('email', null);
    	
    	
    	$select = new Select(['c' => 'users_customers']);
    	$select
	    	->reset(Select::COLUMNS)
	    	->columns(['id','name']);
    	$select->join(['a' => 'users_accounts'], 'c.id = a.id',  ['skype', 'phone', 'email'], Select::JOIN_LEFT);
    	
    	if(!empty($id)){
    		$select->where->equalTo('a.id', $id);
    	} else if(!empty($phone)){
    		$phone = Phone::normalize($phone);
    		$select->where->equalTo('a.phone', $phone);
    	} else if(!empty($email)){
    		$select->where->expression('LOWER(u.email) = ?', mb_strtolower($email));
    	} else if(!empty($skype)){
    		$select->where->expression('LOWER(a.skype) = ?', mb_strtolower($skype));
    	} else {
    		return new JsonModel([
    			'result' => 'not-found'
    		]);
    	}
    	
    	$select->limit(1);
    	$customer = $select->fetchRow();
    	if(!empty($customer)){
	    	return new JsonModel([
	    			'result' => 'ok',
	    			'customer' => [
	    				'id' => $customer['id'],
				    	'name' => $customer['name'],
				    	'displayname' => $customer['displayname'],
				    	'phone' => $customer['phone'],
				    	'phone_formated' => Phone::format($customer['phone']),
				    	'email' => $customer['email'],
				    	'skype' => $customer['skype'],
	    				'url' => $this->url()->fromRoute('private/customer-edit', ['id' => $customer['id']])
	    			]
	    			
	    	]);
    	} else {
    		return new JsonModel([
    			'result' => 'not-found'
    		]);
    	}
    	
    }
    
}

