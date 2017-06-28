<?php

namespace Admin\Controller\Users;


use Admin\Forms\Users\UserForm;
use Admin\Model\Users\UserDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\Db\Select;
use Common\ImageService;
use Common\ViewHelper\Flash;
use Doctrine\Common\Collections\Expr\Expression;
use Zend\Db\Sql\Join;
use Zend\Form\Element\Email;
use Zend\View\Model\JsonModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Admin\Model\Users\CustomerDb;
use Common\ViewHelper\Phone;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property UserDb $db 
 */
class UsersController extends CRUDController implements CRUDEditModel{
	
	public function init(){
		$this->db = $this->serv(UserDb::class);
		$this->crudInit('user');
	}
	
	/**
	 * @Route(name="admin-index",route="/admin-index",extends="private",type="segment")
	 */
	public function adminIndexAction(){
		return $this->redirectToFilteredList('private/user-index', ['role' => 'admin']);
	}
		
	/**
	 * @Route(name="user-index",route="/user-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function usersIndexAction(){
		return $this->crudList($this->db);
	}
	
	/**
	 * @Route(name="user-edit", route="/user-edit/:id",extends="private",type="segment")
	 */
	public function usersEditAction(){
		return parent::processEditForm(UserForm::class, $this);
	}
		
	protected function index(){		
		return [
			'roleOptions' => $this->db->getRoleOptions()
		];		
	}
	
	// CRUD Model 
	public function load($id) {
		$item = $this->db->get($id);
		if(!empty($item)){
			$item['roles'] = $this->db->getRoles($id);
		}		
		return $item;
	}

	public function create() {
		return ['name' => '', 'login' => ''];
	}

	public function validate(array $data){

		if(!empty($data['email']) && !$this->db->checkEmailUniqueness($data['email'], $this->id)){
			$this->form->error('email', "Этот email-адресс уже зарегестрирован");	
		}
		
		if(!empty($data['password_1'])){
			if($data['password_1'] != $data['password_2']){
				$this->form->error('password_2', "Пароли не совпадают");
			}
		}
		
    }
    
    public function save(array $data){
    	
    	if(!empty($data['password_1'])){
    		$data['password'] = $data['password_1'];
    	}
    	
    	$roles = $data['roles'];
    	unset($data['password_1']);
    	unset($data['password_2']);    	
    	unset($data['roles']);
    	
    	if($this->isNew){    		
    		$this->id = $this->db->insert($data);    	
    	} else {    	
    		$this->db->updateOne($data, $this->id);    		
    	}
    	$this->db->updateRoles($roles, $data, $this->id);
    	
    	return $this->id;
    }
    
    public function afterSave(){
    	if($this->isNew){
    		$this->sendFlashMessage("Профайл пользователя создан", Flash::SUCCESS);
    	} else {
    		$this->sendFlashMessage("Профайл пользователя сохранен", Flash::SUCCESS);
    	}

    	return $this->afterSaveRedirect();
    }
    
    public function edit(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    	
    	$this->form
    		->field('roles')
    		->options($this->db->getRoleOptions());
    	
    	if(empty($this->item['password'])) {
    		$this->form->field('password_1')->description('Пароль для пользователя не задан');
    	}
    		
    	
    	if($this->item['login'] != $this->item['email']) {
    		$this->form->field('email')->description('Логин для входа на сайт - "'.$this->item['login'].'"');
    	}
    	
    	return [
    		'stat' => $this->db->getStat($this->item['id']),
    		'profiles' => $this->db->getProfiles($this->item['id'])
    	];
    	
    }


    /**
     * @Route(name="user-ajax-select",route="/user-ajax-select",extends="private",type="segment")
     */
    public function ajaxSelectAction(){
    	$query = $this->params()->fromQuery('q');
    	if(empty($query)){
    		return new JsonModel([]);
    	}
    	
    	$type = $this->params()->fromQuery('t', 'am');
    	
    	$select = new Select(['u' => 'users_accounts']);
    	$select->columns(['id', 'value' => 'displayname']);
    	
    	$where = $select->where->nest; 
    	if(strpos($type, 'a') !== false){
    		$select->join(['a' => 'users_admins'], 'a.id = u.id', [], Join::JOIN_LEFT);
    		$where->or->isNotNull('a.id', null);
    	}
    	
    	if(strpos($type, 'm') !== false){
    		$select->join(['m' => 'users_masters'], 'm.id = u.id', [], Join::JOIN_LEFT);
    		$where->or->isNotNull('m.id');
    	}
    	
    	if(strpos($type, 'c') !== false){
    		$select->join(['c' => 'users_customers'], 'c.id = u.id', [], Join::JOIN_LEFT);
    		$where->or->isNotNull('c.id');
    	}
    	
    	if (filter_var($query, FILTER_VALIDATE_EMAIL)) {
    		$select->where->expression('LOWER(u.email) = ?', mb_strtolower($query));
    		
    	} else if (is_numeric($query)) {
    		$select->where->equalTo('u.id', $query);
    		
    	} else {
    		$select->where->nest
	    		->expression('u.displayname like ?', mb_strtolower($query).'%')->or
	    		->expression('u.displayname like ?', '% '.mb_strtolower($query).'%');
    	}
    	
    	
    	$select->group('u.id')
	    	->order('u.displayname asc')
	    	->limit(20);
    	 
    	$suggestions = $select->fetchAll();
    	
    	return new JsonModel([
    		"query" => $query,
	   		"suggestions" => $suggestions]);
    	    	
    }

    
}

