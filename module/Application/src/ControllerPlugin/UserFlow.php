<?
namespace Application\ControllerPlugin;
use Application\Model\MasterDb;
use Application\Model\MasterPricesDb;
use Application\Model\Orders\ConsultationDb;
use Application\Model\UserDb;
use Common\Identity;
use Common\SiteController;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use ZfAnnotation\Annotation\ControllerPlugin;
use Application\Model\Courses\EventDb;
use Admin\Model\Courses\EventDb as AdminEventDb;
use Application\Model\Courses\TarifsDb;
use Application\Model\Orders\OrdersDb;
use Admin\Model\Orders\OrdersDb as AdminOrderDb;

/**
 * @ControllerPlugin(name = "userFlow")
 */
class UserFlow extends AbstractPlugin implements ServiceManagerAware{
	
	const ORDERTYPE_CONSULTATION = 'consultation';
	const ORDERTYPE_ORDER = 'order';
	
	
	use ServiceManagerTrait; 
	
	var $session;
	
	public function __construct(){
		$this->session = new Container(UserFlow::class);
	}
	
	
	public function createConsultationAfterLogin($data){
		
		$orderDefinition = $this->buildConsultationVars($data);
		
		if(!$this->session->offsetExists('cart')){
			$this->session['cart'] = [];
		}
		$this->session['cart'][] = $orderDefinition;
		
	}
	
	
	public function createConsultation($data){
		$controller = $this->getController();
		if(!$controller instanceof SiteController){
			return null;
		}
		
		/* @var $identity Identity */
		$identity = $controller->identity();
		if($identity->isLogged()){
			$data['user_id'] = $identity()->id;
		}
	
		$data['date'] = time();
		
		$data['status'] = \Admin\Model\Orders\ConsultationDb::STATUS_NEW;
		
		if(empty($data['name'])){
			$data['name'] = $identity()->displayname;
		}
		if(empty($data['phone'])){
			$data['phone'] = $identity()->phone;
		}
		if(empty($data['skype'])){
			$data['skype'] = $identity()->skype;
		}
	
		if(!empty($data['master_id'])){
			$masterDb = $this->serv(MasterDb::class);
			$master = $masterDb->get($data['master_id']);
			if(empty($master)){
				ubset($data['master_id']);
			}
		}
		
		if(!empty($data['tarif_id'])){
			/* @var $masterPricesDb MasterPricesDb */
			$masterPricesDb = $this->serv(MasterPricesDb::class);
			$price = $masterPricesDb->get($data['tarif_id']);
			if(!empty($master) && $price['master_id'] != $master['id']){
				throw new \Exception('Что то пошло не так');
			}
			if(!empty($price)){
				$data['price'] = $price['price'];
			}
		}
		
		/* @var $consultationDb ConsultationDb */
		$consultationDb = $this->serv(ConsultationDb::class);
		$consultationDb->insert($data);
	
		
		// TODO Отправка почтовых сообщений сообщений
		
		if($controller->getRequest()->isXmlHttpRequest()){
			return new JsonModel(['result' => 'ok']);
		} else {
			$vars = $this->buildConsultationVars($data);
			$this->sendFlash('application/customer/info/thanks.consultation.phtml', $vars);
			return $controller->redirect()->toRoute('customer-calendar');
		}
	}
	
	
	public function addOrderAfterLogin($data){
	
		$orderDefinition = $this->buildOrderVars($data);
	
		if(!$this->session->offsetExists('cart')){
			$this->session['cart'] = [];
		}
		$this->session['cart'][] = $orderDefinition;
		
	}
	
	
	public function createOrder($data){
		
		$controller = $this->getController();
		if(!$controller instanceof SiteController){
			return null;
		}
		
		/* @var $identity Identity */
		$identity = $controller->identity();
		if(!$identity->isLogged()){
			return null;
		}

		/* @var $eventDb EventDb */
		$eventDb = $this->serv(EventDb::class);
		/* @var $orderDb OrdersDb */
		$orderDb = $this->serv(OrdersDb::class);
		/* @var $tarifsDb TarifsDb */
		$tarifsDb = $this->serv(TarifsDb::class);
		
		/* @var $adminEventDb AdminEventDb */
		$adminEventDb = $this->serv(AdminEventDb::class);
		/* @var $adminOrderDb AdminOrderDb */
		$adminOrderDb = $this->serv(AdminOrderDb::class);
		
		$order = [];
		$order['date'] = time();	
		
		$order['name'] = $identity()->displayname;
		$order['phone'] = $identity()->phone;
		$order['skype'] = $identity()->skype;
		$order['user_id'] = $identity()->id;
		
		$order['payed'] = 0;
		$order['message'] = $data['message'];
				
		if(!empty($data['date'])){ 
			// Make event order
			
			$shedule = $eventDb->getDate($data['date']);
			if(empty($shedule)){
				throw new \Exception('Событие не найдено');
			}
			$shedule = $shedule[0];
			$eventDb->buildSheduleRecord($shedule, $identity->id);
				
			$order['event_id'] = $shedule['event']['id'];
			
			$tarif = $this->getTarifFromArray($shedule['tarifs'], $data['tarif']);
			
			if(!empty($tarif)){
				$order['tarif_id'] = 	$tarif['id'];
				$order['price'] = 		$tarif['price'];
				$order['discounts'] = 	$tarif['discounts'];
				
			} else {
				$order['price'] = 0;
			}
			
			$order['status'] = \Admin\Model\Orders\OrdersDb::STATUS_NEW;
			$orderId = $orderDb->insert($order);
			
			// Добавляем даты
			$dates = $adminEventDb->getDefaultOrderShedule($orderId, $shedule['date']);
			
			foreach ($dates as $date){
				$adminOrderDb->addOrderShedule($orderId, $date['id']);
			}
			
		} else {
			
			// Make pre order
			$eventId = $data['event'];
			$tarifId = $data['tarif'];
			
			$event = $eventDb->get($eventId);
			if(empty($event) || $event['type'] != \Admin\Model\Courses\EventDb::TYPE_ANNOUNCE ){
				throw new \Exception('Событие не найдено');
			}
						
			if(!empty($tarifId)){
				$tarifs = $tarifsDb->getEventTarifs($eventId);
				$tarif = $this->getTarifFromArray($tarifs, $tarifId);				
			}
			
			if(!empty($tarif)){
				$order['tarif_id'] = $tarif['id'];
				$order['price'] = $tarif['price'];
				$order['discounts'] = $tarif['discounts'];
			} else {
				$order['price'] = 0;
			}
			$order['event_id'] = $eventId;
			$order['status'] = \Admin\Model\Orders\OrdersDb::STATUS_PREORDER;
			
			$orderId = $orderDb->insert($order);
		}
		
		
		// TODO Отправка почтовых сообщений сообщений
		
		if($controller->getRequest()->isXmlHttpRequest()){
			return new JsonModel(['result' => 'ok']);
		} else {
			$vars = $this->buildOrderVars($data);
			$this->sendFlash('application/customer/info/thanks.order.phtml', $vars);
			return $controller->redirect()->toRoute('customer-calendar');
		}		
	}	
	
	public function clear(){
		if($this->session->offsetExists('cart')){
			$this->session->offsetUnset('cart');
		}
		if($this->session->offsetExists('redirect_after_login')){
			$this->session->offsetUnset('redirect_after_login');
		}

		if($this->session->offsetExists('flash')){
			$this->session->offsetUnset('flash');
		}
	}
	
	public function redirectAfterLogin($url){
		$this->session['redirect_after_login'] = $url;
	}
	
	
	public function afterAuthorization(){
		$controller = $this->getController();
		if(!$controller instanceof SiteController){
			return null;
		}
		
		/* @var $identity Identity */
		$identity = $controller->identity();
		if(!$identity->isLogged()){
			return null;
		}
		
		if($this->session->offsetExists('cart')){
				
			foreach ($this->session['cart'] as $orderDefinition){
				
				$data = $orderDefinition['data'];
				if(empty($data)){
					continue;
				}
				
				if($orderDefinition['type'] == self::ORDERTYPE_CONSULTATION){
					
					$this->createConsultation($data);
					
				} else if($orderDefinition['type'] == self::ORDERTYPE_ORDER){
			
					$this->createOrder($data);						
					
				}
			}			
			$this->session->offsetUnset('cart');
			$this->session->offsetUnset('redirect_after_login');
			
			return $controller->redirect()->toRoute('customer-calendar');
			
		}
		
		
		if(!empty($this->session['redirect_after_login'])){
			
			$redirectUrl = $this->session['redirect_after_login'];
			$this->session->offsetUnset('redirect_after_login');
			return $controller->redirect()->toUrl($redirectUrl);
			
		} else {
			
			if($this->getController()->identity()->hasRole(UserDb::ROLE_ADMIN)){
				
				return $controller->redirect()->toRoute("private");
				
			} else if($identity->hasRole(UserDb::ROLE_CUSTOMER)){
				
				return $controller->redirect()->toRoute("customer-calendar");
				
			}
			return $controller->redirect()->toRoute("home");
		}
		
		return $controller->redirect()->toRoute('customer-calendar');
		
	}
	
	
	private function buildConsultationVars($data){
		$vars = [
			'type' => self::ORDERTYPE_CONSULTATION,
			'data' => $data,
		];
		
		
		if(!empty($data['master_id'])){
			/* @var $masterDb MasterDb */
			$masterDb = $this->serv(MasterDb::class);
			$master = $masterDb->get($data['master_id']);
			if(!empty($master)){
				$vars['master_name'] = $master['name'];
			}
		}
		if(!empty($data['tarif_id'])){
			/* @var $masterPricesDb MasterPricesDb */
			$masterPricesDb = $this->serv(MasterPricesDb::class);
			$price = $masterPricesDb->get($data['tarif_id']);
			if(!empty($price)){
				$vars['tarif_name'] = $price['name'];
			}
		}
		
		return $vars;
	}
	
	
	private function buildOrderVars($data){
		$vars = [
			'type' => self::ORDERTYPE_ORDER,
			'data' => $data,
		];
	
		/* @var $eventDb EventDb */
		$eventDb = $this->serv(EventDb::class);
		/* @var $tarifsDb TarifsDb */
		$tarifsDb = $this->serv(TarifsDb::class);
		/* @var $masterDb MasterDb */
		$masterDb = $this->serv(MasterDb::class);
		
		
		if(!empty($data['date'])){
			$shedule = $eventDb->getDate($data['date']);
			if(empty($shedule)){
				throw new \Exception('Событие не найдено');
			}
			$shedule = $shedule[0];
			$eventDb->buildSheduleRecord($shedule, $identity->id);
				
			$vars['shedule'] = $shedule;
			$vars['event'] = $shedule['event'];
			$vars['masters'] = $shedule['masters'];
			$vars['tarif'] = $this->getTarifFromArray($shedule['tarifs'], $data['tarif']);			
		} else {
			// Make pre order
			$eventId = $data['event'];
			$tarifId = $data['tarif'];
				
			$vars['event'] = $eventDb->get($eventId);
			$vars['masters'] = $masterDb->getEventMasters($eventId);
			
			if(!empty($tarifId)){
				$tarifs = $tarifsDb->getEventTarifs($eventId);
				$vars['tarif'] = $this->getTarifFromArray($tarifs, $tarifId);
			}
		}		
		
		return $vars;
	}
	
	
	public function sendFlash($template, $vars){
		if(!$this->session->offsetExists('flash')){
			$this->session['flash'] = [];
		}
		$this->session['flash'][] = [
				'template' => $template,
				'vars' => $vars,
			];
	}
	
	
	
	public function getTarifFromArray($tarifs, $tarifId){
		
		if(empty($tarifId) && !empty($tarifs)){
			
			return $tarifs[0];
			
		} else if(!empty($tarifId)){
			foreach ($tarifs as $tarif){
				if($tarif['id'] == $tarifId){
					return $tarif;
				}
			}
			return null;
		}
		
	}
		
}