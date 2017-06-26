<?php

namespace Admin\Controller\Users;

use Admin\Model\Users\MasterPricesDb;
use Admin\Model\Users\UserDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\ImageService;
use Common\ViewHelper\Flash;
use Zend\View\Model\JsonModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Admin\Model\Users\MasterDb;
use Admin\Model\Content\DiplomDb;
use Common\Utils;
use Common\CRUDEditModel;
use Admin\Forms\Users\MasterPricesForm;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property MasterPricesDb $db 
 */
class MasterPricesController extends CRUDController implements CRUDEditModel{
	
	/** @var $masterDb MastrerDb */
	var $masterDb = null;
	
	public function init(){
		$this->db = $this->serv(MasterPricesDb::class);
		$this->masterDb = $this->serv(MasterDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="master-prices-index",route="/master-prices-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function masterPricesIndexAction(){
		return $this->crudList($this->db);
	}
	
	/**
	 * @Route(name="master-prices-edit", route="/master-prices-edit/:id",extends="private",type="segment")
	 */
	public function masterPricesEditAction(){
		return parent::processEditForm(MasterPricesForm::class, $this);
	}
		
	protected function index($return){		
		if(!empty($return['filter']['master'])){
			/* @var $masterDb masterDb */
			$masterDb = $this->serv(MasterDb::class);
			$master = $masterDb->get($return['filter']['master']);
			return ['masterName' => $master['displayname']];
		}
	}
	
	
	// CRUD Model 
	public function load($id) {
		$item = $this->db->get($id);		
		return $item;
	}

	public function create() {
		return ['name' => '', 'active' => 1, 'status' => 1, 'master_id' => $this->params()->fromQuery('master_id', null)];
	}

	public function validate(array $data){
		
    }
    
    public function save(array $data){
    	
    	if(empty($data['price_desc'])){
    		if(empty($data['price'])){
    			$data['price_desc'] = 'бесплатно';
    		} else {
    			$data['price_desc'] = number_format($data['price'], 0, '.', ' ').' руб.';
    		}
    	}
    	     	
    	if($this->isNew){
    		$this->id = $this->db->insert($data);
    	} else {    	
    		$this->db->updateOne($data, $this->id);
    	}
    	
    	return $this->id;
    }
    
    public function afterSave(){
    	if($this->isNew){
    		$this->sendFlashMessage("Тариф создан", Flash::SUCCESS);
    	} else {
    		$this->sendFlashMessage("Тариф сохранен", Flash::SUCCESS);
    	}

    	return $this->afterSaveRedirect();
    }
    
    public function edit(){
    	/* @var $masterDb MasterDb */
    	$masterDb = $this->serv(MasterDb::class);

		if(!$this->isNew){
			$this->form->field('master_id')->disable();
		}		
		return [
			'stat' => $this->db->getStat($this->item['id']),
			'master' =>	$masterDb->get($this->item['id'])
		];
    	
    }
    
    /**
     * @Route(name="master-prices-status", route="/master-prices-status/:id",extends="private",type="segment")
     */
    public function statusAction(){
    	$id = $this->params('id', 'new');
    	$item = $this->db->get($id);
    	if(empty($item)){
    		return new JsonModel(['result' => 'error', 'message' => 'Объект не найден']);
    	}
    	$update = ['status' => 0];
    	if($item['status'] == 0){
    		$update['status'] = 1;
    	}
    	$this->db->updateOne($update, $id);
    	return new JsonModel(['result' => 'ok', 'status' => $update['status']]);    	 
    }
    
    
}

