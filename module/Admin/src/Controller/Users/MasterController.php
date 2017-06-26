<?php

namespace Admin\Controller\Users;

use Admin\Forms\Users\MasterForm;
use Admin\Model\Users\MasterPricesDb;
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
use Admin\Model\Users\MasterDb;
use Admin\Model\Content\DiplomDb;
use Common\Utils;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property UserDb $db 
 */
class MasterController extends CRUDController implements CRUDEditModel{
	
	/** @var $userDb UserDb */
	var $userDb = null;
	
	public function init(){
		$this->db = $this->serv(MasterDb::class);
		$this->userDb = $this->serv(UserDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="master-index",route="/master-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function masterIndexAction(){
		return $this->crudList($this->db);
	}
	
	/**
	 * @Route(name="master-edit", route="/master-edit/:id",extends="private",type="segment")
	 */
	public function masterEditAction(){
		return parent::processEditForm(MasterForm::class, $this);
	}
		
	protected function index(){		
		
	}
	
	/**
	 * @Route(name="master-image-upload", route="/master-upload/[:id]",extends="private",type="segment")
	 */
	public function uploadAction(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
	
		$id = $this->params('id', 'new');
	
		if($id == 'new'){
			$id = $this->db->getNextId();
		}
	
		$image = $imageService->import($this->params()->fromFiles('image'), 'masters/'.$id);
	
		return new JsonModel([
				'result' => 'ok',
				'original' => $image,
				'preview' => $imageService->resize($image,  400, 400)
		]);
	
	}

	// CRUD Model 
	public function load($id) {
		$item = $this->db->get($id);		
		return $item;
	}

	public function create() {
		return ['name' => '', 'active' => 1, 'status' => 1];
	}

	public function validate(array $data){
		
    }
    
    public function save(array $data){
    	if(empty($data['alias'])){
    		$data['alias'] = $data['name'];
    	}
    	$data['alias'] = Utils::urlify($data['alias']);    	
    	$data['alias'] = $this->db->uniqueAlias($data['alias'], $this->id);
    	     	
    	if($this->isNew){
    		/* @var $userDb UserDb */
    		$account = [
    			'displayname' => $data['name']
    		];
    		$data['id'] = $this->id = $this->userDb->insert($account);
    		$this->db->insert($data);    	
    		$this->userDb->saveHistory(['roles' => [UserDb::ROLE_MASTER]], ['roles' => []], $this->id);
    	} else {    	
    		$this->db->updateOne($data, $this->id);
    	}
    	
    	return $this->id;
    }
    
    public function afterSave(){
    	if($this->isNew){
    		$this->sendFlashMessage("Профайл специалиста создан", Flash::SUCCESS);
    	} else {
    		$this->sendFlashMessage("Профайл специалиста сохранен", Flash::SUCCESS);
    	}

    	return $this->afterSaveRedirect();
    }
    
    public function edit(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
   	    	
    	$uploadUrl = $this->url()->fromRoute('private/master-image-upload', ['id' => $this->id]);
    		 
    	$this->form->field('image')
    		->url($uploadUrl)
    		->preview($imageService->resize($this->item['image'], 400, 400))
    		->full($imageService->resize($this->item['image']));
    	
    	/** @var $masterPricesDb MasterPricesDb */
    	$masterPricesDb = $this->serv(MasterPricesDb::class); 
    	
    	/** @var $diplomDb DiplomDb */
    	$diplomDb = $this->serv(DiplomDb::class);
    	
    	if(!$this->isNew && $this->item['status'] != 0){
    		$this->layout()->site_url = $this->url()->fromRoute('master-view', ['alias' => $this->item['alias']]);
    	}
    	
    	return [
    		'prices' => $masterPricesDb->getMasterPrices($this->item['id']),
    		'diplomas' => $diplomDb->getItems(['master' => $this->item['id']], 1, 10),
    		'diplomasTotals' => $diplomDb->getTotals(['master' => $this->item['id']]),
    		'stat' => $this->db->getStat($this->item['id']),
    		'profiles' => $this->userDb->getProfiles($this->item['id'])
    	];
    	
    }

    /**
     * @Route(name="master-status", route="/master-status/:id",extends="private",type="segment")
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
    
    
    
    
}

