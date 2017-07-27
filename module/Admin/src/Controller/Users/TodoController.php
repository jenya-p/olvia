<?
namespace Admin\Controller\Users;

use Admin\Forms\Users\TodoForm;
use Admin\Model\Users\TodoDb;
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
 * @property TodoDb $db 
 */
class TodoController extends CRUDController implements CRUDEditModel{
	
	/** @var TodoDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(TodoDb::class);		 
		$this->crudInit('todos');
	}
	
	/**
	 * @Route(name="todos-index",route="/todos-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function todoIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="todos-edit", route="/todos-edit/:id",extends="private",type="segment")
	 */
	public function todoEditAction(){
		return parent::processEditForm(TodoForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		// return [];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		
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
			$this->sendFlashMessage("Задача сохранена", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Задача сохранена", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('todos', ['id' => $this->id]);
		}
		return [
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="todos-delete", route="/todos-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
        	
    /**
     * @Route(name="todos-status", route="/todos-status/:id",extends="private",type="segment")
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

