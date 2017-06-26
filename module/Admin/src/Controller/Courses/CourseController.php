<?
namespace Admin\Controller\Courses;

use Admin\Forms\Courses\CourseForm;
use Admin\Model\Courses\CourseDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Common\ImageService;
use Admin\Model\Content\TagDb;
use Common\Utils;
use Common\Db\Select;
use Admin\Model\Courses\EventDb;
use Admin\Model\Courses\TarifsDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property CourseDb $db 
 */
class CourseController extends CRUDController implements CRUDEditModel{
	
	/** @var CourseDb */
	var $db;
	
	/** @var TagDb */
	var $tagDb;
		
	public function init(){
		$this->db = $this->serv(CourseDb::class);
		$this->tagDb = $this->serv(TagDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="course-index",route="/course-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function courseIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="course-edit", route="/course-edit/:id",extends="private",type="segment")
	 */
	public function courseEditAction(){
		return parent::processEditForm(CourseForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		$item['tags'] = $this->tagDb->getItemTags('course', $id);
		return $item;
	}

	public function create() {
		// return [];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		if(empty($data['alias'])){
			$data['alias'] = $data['title'];
		}
		$data['alias'] = Utils::urlify($data['alias']);

		$tags = $data['tags'];
		unset($data['tags']);
		
		if($this->isNew){						
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
		
		$this->db->saveTagHistory($tags, $this->item['tags'], $this->id);
		
		$this->tagDb->saveItemTags('course', $this->id, $tags);
		
		
		return $this->id;
	}
	
	public function afterSave(){
		if($this->isNew){
			$this->sendFlashMessage("Курс сохранен", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Курс сохранен", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('course', ['id' => $this->id]);
		}
		
		$uploadUrl = $this->url()->fromRoute('private/course-image-upload', ['id' => $this->id]);
		 
		$this->form->field('image')
			->url($uploadUrl);
		
		$this->form->field('image')
			->preview($imageService->resize($this->item['image'], 400, 100))
			->full($imageService->resize($this->item['image']));
		
		$ret = ['stat' => $this->db->getStat($this->item['id']) ];
			
		if(!$this->isNew){
			/* @var $tarifDb TarifDb */
			$tarifDb = $this->serv(TarifsDb::class);
			/* @var $eventsDb EventDb */
			$eventsDb = $this->serv(EventDb::class);
				
			$filter = ['course_id' => $this->item['id']];
			
			$ret['tarif_items'] = $tarifDb->getItems($filter, 1, 10);
			$ret['tarif_totals'] = $tarifDb->getTotals($filter);
			
			$ret['event_items'] = $eventsDb->getCourseEvents($this->item['id']);
			$ret['event_totals'] = $eventsDb->getTotals($filter);		
		}
		
		
		return $ret;
	}
	
	
	/**
     * @Route(name="course-delete", route="/course-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
        	
    /**
     * @Route(name="course-status", route="/course-status/:id",extends="private",type="segment")
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
     * @Route(name="course-image-upload", route="/course-upload/[:id]",extends="private",type="segment")
     */
    public function uploadAction(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    
    	$id = $this->params('id', 'new');
    
    	if($id == 'new'){
    		$id = $this->db->getNextId();
    	} else {
    		$item = $this->db->get($id);
    	}
    
    	try{
    		$image = $imageService->import($this->params()->fromFiles('image'), 'courses/'.$id);
    	} catch (\Exception $e){
    		return new JsonModel([
    			'result' => 'error',
    			'message' => $e->getMessage()
    		]);
    	}
    
    	if(!empty($item)){
    		$this->db->updateOne(['image' => $image], $item['id']);
    	}
    
    	return new JsonModel([
    			'result' => 'ok',
    			'original' => $image,
    			'preview' => $imageService->resize($image, 400, 100)
    	]);
    
    }
    
    
    /**
     * @Route(name="course-ajax-select",route="/course-ajax-select",extends="private",type="segment")
     */
    public function ajaxSelectAction(){
    	$query = $this->params()->fromQuery('q');
    	$query = mb_strtolower($query);
    	if(empty($query)){
    		return new JsonModel([]);
    	}
    	 
    	$select = new Select(['c' => 'courses']);
    	$select->columns(['id', 'value' => 'title_'.$this->db->lang()]);
    	
    	$nest = $select->where->nest;
    	$nest -> expression('concat(" ", LOWER(c.title_'.$this->db->lang().')) like ?', "% ".$query."%")
    		->or->expression('c.alias like ?', $query."%");
    	 
    	$select->limit(20);
    	
    	$suggestions = $select->fetchAll();
    	 
    	return new JsonModel([
    		"query" => $query,
    		"suggestions" => $suggestions]);    	 
    
    }
    
}

