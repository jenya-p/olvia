<?
namespace Admin\Forms\Orders;

use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Admin\Forms\UsersElements;
use Admin\Model\Orders\CallDb;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\UserDb;

/**
 * @Service(name="Admin\Forms\Orders\CallForm")
 */
class CallForm extends Form implements Initializable, ServiceManagerAware{
		
	use UsersElements, ServiceManagerTrait;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		/* @var $callDb CallDb */
		$callDb = $this->serv(CallDb::class);
		
		/* @var $userDb UserDb */
		$userDb = $this->serv(UserDb::class);
				
		$this->field('name', 'text', 'Имя в заявке');
 		$this->field('phone', 'phone', 'Телефон для связи');
 		$this->field('user_id', 'user', 'Клиент') 			
 			->options($userDb)
 			->addExtra('attributes', 'data-type = "c"');
 		
 		$this->field('status', 'select', 'Статус')
 			->options([$callDb, 'statusOptions']);
 		
 		$this->field('message', 'textarea', 'Сообщение клиента');
 		 		 		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
