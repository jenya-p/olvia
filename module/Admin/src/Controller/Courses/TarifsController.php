<?
namespace Admin\Controller\Courses;

use Admin\Forms\Courses\TarifsForm;
use Admin\Model\Courses\TarifsDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Admin\Model\Courses\CourseDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property TarifsDb $db 
 */
class TarifsController extends CRUDController implements CRUDEditModel{
	
	/** @var TarifsDb */
	var $db;
	
	/** @var CourseDb */
	var $courseDb;
	
	public function init(){
		$this->db = $this->serv(TarifsDb::class);
		$this->courseDb = $this->serv(CourseDb::class);
		$this->crudInit('tarifs');
	}
	
	/**
	 * @Route(name="tarifs-index",route="/tarifs-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function tarifsIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(&$return){
		if(!empty($return['filter']) && !empty($return['filter']['course_id'])){
			$courseId = $return['filter']['course_id'];
			$filterCourse = $this->courseDb->get($courseId);
			$return['filter_course'] = $filterCourse;
		} 
	}
	
	/**
	 * @Route(name="tarifs-edit", route="/tarifs-edit/:id",extends="private",type="segment")
	 */
	public function tarifsEditAction(){
		return parent::processEditForm(TarifsForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		$ret = ['subscription' => 1];
		$course_id = $this->params()->fromQuery('course_id', null);
		if(!empty($course_id)){
			$course = $this->courseDb->get($course_id);
			if(!empty($course)){
				$ret['course_id'] = $course['id'];
			}
		}
		return $ret;
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		
		$data['type'] = 0;
		
		if(empty($data['price_desc'])){
			if(empty($data['price'])){
				$data['price_desc'] = 'бесплатно';
			} else {
				$data['price_desc'] = number_format($data['price'], 0, '.', ' ').' руб.';
			}
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
			$this->sendFlashMessage("Тариф сохранен", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Тариф сохранен", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect('tarifs');
	}
	
	public function edit(){
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('event', ['id' => $this->id]);
		}
		
		if(!$this->isNew){
			$this->form->field('course_id')->disable();
		}
		
		return [
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="tarifs-delete", route="/tarifs-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
        	
    /**
     * @Route(name="tarifs-status", route="/tarifs-status/:id",extends="private",type="segment")
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

