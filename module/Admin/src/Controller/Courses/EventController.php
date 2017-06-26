<?
namespace Admin\Controller\Courses;

use Admin\Forms\Courses\EventForm;
use Admin\Model\Courses\EventDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Admin\Model\Courses\CourseDb;
use Admin\Model\Courses\TarifsDb;
use Common\Db\Select;
use Zend\Db\Sql\Expression;
use Common\Db\Adapter;
use Zend\Db\Adapter\Exception\InvalidQueryException;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property EventDb $db 
 */
class EventController extends CRUDController implements CRUDEditModel{
	
	/** @var EventDb */
	var $db;

	/** @var CourseDb */
	var $courseDb;
		
	public function init(){
		$this->db = $this->serv(EventDb::class);	
		$this->courseDb = $this->serv(CourseDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="event-index",route="/event-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function eventIndexAction(){		
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
	 * @Route(name="event-edit", route="/event-edit/:id",extends="private",type="segment")
	 */
	public function eventEditAction(){
		return parent::processEditForm(EventForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		$item['masters'] = $this->db->getMasterIds($id);
		$item['tarifs'] = $this->db->getTarifIds($id);		
		return $item;
	}

	public function create() {
		$ret = [];
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
		
		if(!empty($data['add_dates'])){
			$dateFrom = time() 	- 365*24*60*60;
			$dateTo = 	time() 	+ 365*24*60*60;
			$dates = explode("\n", $data['add_dates']);
			foreach ($dates as $date){
				$date = trim($date);
				if(empty($date)) continue;				
				$date = strtotime($date);
				if(!is_numeric($date) || $date < $dateFrom || $date > $dateTo){
					$this->form->field('add_dates')->error('Ошибка в формате введенных данных');
					break;
				}
			}
		}
	}
	
	public function save(array $data){
		
		$masters = $data['masters'];
		unset($data['masters']);
		
		if(empty($data['place'])){
			$data['place'] = null;
		}
		
		$addDates = $data['add_dates'];
		unset($data['add_dates']);
		
		if($this->isNew){						
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}

		$this->db->saveMasters($this->id, $masters);
		
		$tarifs = $this->params()->fromPost('tarifs');
		$this->db->saveTarifs($this->id, $tarifs);
		
		if(!empty($addDates)){
			$dateFrom = time() - 365*24*60*60;
			$dateTo = time() + 365*24*60*60;
			$dates = explode("\n", $addDates);
			foreach ($dates as $date){
				$date = trim($date);
				if(empty($date)) continue;
				$date = strtotime($date);
				
				if(!is_numeric($date) || $date < $dateFrom || $date > $dateTo){
					continue;
				}
				try{
					$this->db->getAdapter()->insert('course_event_shedule', ['event_id' => $this->id, 'date' => $date]);
				} catch(InvalidQueryException $e){}
								
			}
		}
		
		
		
		return $this->id;
	}
	
	public function afterSave(){
		if($this->isNew){
			$this->sendFlashMessage("Мероприятие сохранено", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Мероприятие сохранено", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('event', ['id' => $this->id]);
		}
		$ret = [];
		
		if(!$this->isNew){
			$this->form->field('course_id')->disable();
			if(!$this->db->canChangeType($this->item)){
				$this->form->field('type')->disable();
			}
		}
		
		$ret['stat'] = $this->db->getStat($this->item['id']);
		
		$courseId = $this->form->field('course_id')->value();
		if(!empty($courseId)){
			/* @var $tarifDb TarifsDb */
			$tarifDb = $this->serv(TarifsDb::class);
			$ret['course_tarifs'] = $tarifDb->getItems(['course_id' => $courseId], null);
		}
		
		$ret['event_shedule'] = $this->db->getShedule($this->item['id']);
				
		return $ret; 
	}
	
	
	/**
     * @Route(name="event-delete", route="/event-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');
    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
        	
    /**
     * @Route(name="event-status", route="/event-status/:id",extends="private",type="segment")
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
    	
    /**
     * @Route(name="event-edit-tarifs", route="/event-edit/:id/tarifs",extends="private",type="segment")
     */
    public function tarifsAction(){
    	$courseId = $this->params()->fromQuery('course_id', null);
    	if(!empty($courseId)){
    		/* @var $tarifDb TarifsDb */
			$tarifDb = $this->serv(TarifsDb::class);
			$tarifs = $tarifDb->getItems(['course_id' => $courseId], null);
    	} else {
    		return new JsonModel(['result' => 'error', 'message' => 'Не указан ID курса']);
    	}
    	 
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	$html = $renderer->render('admin/courses/event/event-edit.tarifs.phtml',[
    			'course_tarifs' => $tarifs,
    			'value' => $this->db->getMasterIds($courseId)
    	]);
    	 
    	return new JsonModel([
    			'result' => 'ok',
    			'html' => $html
    	]);
    }
    
    /**
     * @Route(name="event-delete-shedule", route="/event-delete-shedule/:id",extends="private",type="segment")
     */
    public function deleteSheduleAction(){
    	$sheduleId = $this->params('id', null);
    	
    	if(!empty($sheduleId)){
    		$select = new Select(['esh' => 'order_order2shedule']);
    		$select->columns(['count' => new Expression('count(*)')]);
    		$select->where->expression('esh.shedule_id = ?', $sheduleId);
    		$orderCount = $select->fetchOne();
    		if($orderCount != 0){
    			return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно. Есть заказы на эту дату']);
    		} else {
    			$this->db->getAdapter()->query('delete from course_event_shedule where id = '.$sheduleId, Adapter::QUERY_MODE_EXECUTE);
    		}
    	} else {
    		return new JsonModel(['result' => 'error', 'message' => 'Не указан ID вcтречи']);
    	}
    	
    	
    	return new JsonModel([
    			'result' => 'ok'
    	]);
    }
    
    
}

