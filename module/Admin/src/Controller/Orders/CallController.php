<?
namespace Admin\Controller\Orders;

use Admin\Forms\Orders\CallForm;
use Admin\Model\Orders\CallDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property CallDb $db 
 */
class CallController extends CRUDController implements CRUDEditModel{
	
	/** @var CallDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(CallDb::class);		 
		$this->crudInit('order-call');
	}
	
	/**
	 * @Route(name="order-call-index",route="/order-call-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function callIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="order-call-edit", route="/order-call-edit/:id",extends="private",type="segment")
	 */
	public function callEditAction(){
		return parent::processEditForm(CallForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		return ['status' => CallDb::STATUS_NEW];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){		
		if(empty($data['date'])){
			$data['date'] = time();
		}
		if($this->isNew){					
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
		return $this->id;
	}
	
	public function afterSave(){
		if($this->isNew){
			$this->sendFlashMessage("Заявка сохранена", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Заявка сохранена", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('order-call', ['id' => $this->id]);
		}
		return [
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="order-call-delete", route="/order-call-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');
    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
    }
    
    
        	
    /**
     * @Route(name="order-call-status", route="/order-call-status/:id",extends="private",type="segment")
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

