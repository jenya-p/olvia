<?

namespace Application\Controller;


use Application\Model\Orders\CallDb;
use Application\Model\UserDb;
use Common\SiteController;
use Common\ViewHelper\Phone;
use Zend\View\Model\JsonModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Application\Model\Courses\EventDb;
use Application\Model\Courses\TarifsDb;
use Zend\View\Model\ViewModel;
use Application\Model\Courses\CourseDb;

/**
 * @Controller
 */
class OrderController extends SiteController{
	
	/**
	 * @Route(name="order-call", route="/order-call", type="segment")
	 */
	public function orderCallAction(){
		/* @var $callDb CallDb */
		$callDb = $this->serv(CallDb::class);
	
		if (!$this->getRequest()->isPost()){
			return $this->redirect()->toRoute('home');
		}
		
		$data = filter_var_array($this->getRequest()->getPost()->toArray(), [
			'phone' 		=> FILTER_SANITIZE_STRING,
			'name'		 	=> FILTER_SANITIZE_STRING,
			'message' 		=> FILTER_SANITIZE_STRING,				
		]);
				
		$data['phone'] = Phone::normalize($data['phone']);
		
		if(empty($data['phone'])){
			throw new \Exception('Не заполнено поле "телефон"');
		}

		$data['date'] = time();
		
		if($this->identity()->isLogged()){
			$data['user_id'] = $this->identity()->id;
		}
		/* @var $userDb UserDb */
		$userDb = $this->serv(UserDb::class);		
		$userDb->updateIdentity($data, $this->identity());
		$callDb->insert($data);
		return new JsonModel(['result' => 'ok']);
		
	}
	
	
	/**
	 * @Route(name="order-consultation", route="/order-consultation", type="segment")
	 */
	public function orderConsultationAction(){
	
		if (!$this->getRequest()->isPost()){
			return $this->redirect()->toRoute('home');
		}
		
		$data = $this->filterConsultationDataFromRequest();
				
		if ($this->getOrderMethod() == 'register') {
			$this->userFlow()->createConsultationAfterLogin($data);
			return $this->redirect()->toRoute('register');				
		}
		
		/* @var $userDb UserDb */
		$userDb = $this->serv(UserDb::class);
		$userDb->updateIdentity($data, $this->identity());
		
		return $this->userFlow()->createConsultation($data);		
		
	}
		
	private function filterConsultationDataFromRequest(){
		$data = filter_var_array($this->getRequest()->getPost()->toArray(), [
				'phone' 		=> FILTER_SANITIZE_STRING,
				'skype' 		=> FILTER_SANITIZE_STRING,
				'name'		 	=> FILTER_SANITIZE_STRING,
				'master_id'		=> FILTER_SANITIZE_NUMBER_INT,
				'tarif_id'		=> FILTER_SANITIZE_NUMBER_INT,
				'message' 		=> FILTER_SANITIZE_STRING,
		]);
		
		$data['phone'] = Phone::normalize($data['phone']);
		
		$type = $this->getRequest()->getPost('type', null);
		if($type == 'skype'){
			$data['message'] = "Консультация по скайпу\n\n".$data['message'];
		} else if($type == 'pers'){
			$data['message'] = "Личная консультация\n\n".$data['message'];
		}
		
		if(empty($data['master_id'])){
			$data['master_id'] = null;
			$data['message'] = "Специалист не указан\n".$data['message'];
		}
		
		if(empty($data['tarif_id'])){
			$data['tarif_id'] = null;
			$data['message'] = "Тариф не указан\n".$data['message'];
		}
		

		$method = $this->getOrderMethod();
		if($method == "popup"){
			$data['message'] = "Заказ из всплывающего окна\n".$data['message'];
		} else if($method == "fast"){
			$data['message'] = "Быстрый заказ\n".$data['message'];
		} else if($method == "register"){
			$data['message'] = "Заказ с регистрацией\n".$data['message'];
		}
		
		return $data;
	}

	
	private function getOrderMethod(){
		return $this->getRequest()->getPost('method', null);
	}

	
	
	/**
	 * @Route(name="order-event", route="/order-event", type="segment")
	 */
	public function orderEventAction(){
		
		$data = $this->filterEventOrderDataFromRequest();
				
		if($this->identity()->isLogged()){
			return $this->userFlow()->createOrder($data);
		} else {
			$this->userFlow()->addOrderAfterLogin($data);
			return $this->redirect()->toRoute('register');
		}
		
	}
	
	private function filterEventOrderDataFromRequest(){
		if ($this->getRequest()->isPost()){
			$data = $this->getRequest()->getPost()->toArray();
		} else {
			$data = $this->getRequest()->getQuery()->toArray();
		}
		$data = filter_var_array($data, [
			'date'		=> FILTER_SANITIZE_NUMBER_INT,
			'tarif'		=> FILTER_SANITIZE_NUMBER_INT,
			'event'		=> FILTER_SANITIZE_NUMBER_INT,
			'message' 	=> FILTER_SANITIZE_STRING,
		]);
			
		return $data;
	}
	
	
	
	/**
	 * @Route(name="order-event-popup", route="/order-event-popup", type="segment")
	 */
	public function orderEventPopupAction(){
		/* @var $eventDb EventDb */
		$eventDb = $this->serv(EventDb::class);
		
		$dateId = $this->params()->fromQuery('date');
		$tarifId = $this->params()->fromQuery('tarif');
		
		$date = $eventDb->getDate($dateId);
		if (empty($date)) {
			throw new \Exception('Cобытие не найдено');
		}
		$date = $date[0];		
		$eventDb->buildSheduleRecord($date, $this->identity()->id);
		
		if(empty($tarifId) && !empty($date['tarifs'])){
			$tarifId = $date['tarifs'][0]['id'];
		} 
		
		$vm = new ViewModel([
			'date' => $date,
			'tarif_id' => $tarifId,
		]);
		if($this->getRequest()->isXmlHttpRequest()){
			$vm->setTerminal(true);
		}		
		return $vm;
		 
	}
	
	
	/**
	 * @Route(name="order-announce-popup", route="/order-announce-popup", type="segment")
	 */
	public function orderAnnouncePopupAction(){
		/* @var $eventDb EventDb */
		$eventDb = $this->serv(EventDb::class);
		
		/* @var $courseDb CourseDb */
		$courseDb = $this->serv(CourseDb::class);
		
		$eventId = $this->params()->fromQuery('event');
		
		$event = $eventDb->get($eventId);

		if(empty($event) || $event['type'] != \Admin\Model\Courses\EventDb::TYPE_ANNOUNCE ){
			throw new \Exception('Cобытие не найдено');
		}
		
		$course = $courseDb->get($event['course_id']);
		
		$vm = new ViewModel([
				'event' => $event,
				'course' => $course,
		]);
		if($this->getRequest()->isXmlHttpRequest()){
			$vm->setTerminal(true);
		}
		return $vm;			
	}
	
			
}
