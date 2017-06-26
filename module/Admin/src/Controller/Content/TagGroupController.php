<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\TagGroupForm;
use Admin\Model\Content\TagGroupDb;
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
 * @property TagGroupDb $db 
 */
class TagGroupController extends CRUDController implements CRUDEditModel{
	
	/** @var TagGroupDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(TagGroupDb::class);		 
		$this->crudInit('tag-groups');
	}
	
	/**
	 * @Route(name="tag-groups-index",route="/tag-groups-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function tagGroupIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="tag-groups-edit", route="/tag-groups-edit/:id",extends="private",type="segment")
	 */
	public function tagGroupEditAction(){
		return parent::processEditForm(TagGroupForm::class, $this);
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
			$this->sendFlashMessage("Группа создана", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Группа сохранена", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('tag-groups', ['id' => $this->id]);
		}
		return [
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="tag-groups-delete", route="/tag-groups-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$childCount = $this->db->getChildCount($id);
    	if($childCount == 0){
    		$this->db->deleteOne($id);
    		return new JsonModel(['result' => 'ok']);
    	} else {
    		return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно. В группе есть теги']);
    	}
    }
    
    
    	
}

