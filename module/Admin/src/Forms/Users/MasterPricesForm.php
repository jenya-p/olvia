<?
namespace Admin\Forms\Users;

use Admin\Forms\ImageElements;
use Admin\Forms\UsersElements;
use Common\Form\Form;
use Common\Traits\Initializable;
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Model\Users\UserDb;
use Admin\Model\Users\MasterDb;

/**
 * @Service(name="Admin\Forms\Users\MasterPricesForm")
 */
class MasterPricesForm extends Form implements ServiceManagerAware, Initializable{
	
	use ServiceManagerTrait, ImageElements, UsersElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		$masterDb = $this->serv(MasterDb::class);
		
		$this->field('name', 'text', 'Название тарифа')->validator('required', 'Заполните это поле');
		
		$this->field('master_id', 'user', 'Специалист')
			->validator('required', 'Заполните это поле')
			->options($masterDb);
		
		$this->field('status', 'checkbox', 'Опубликовано');
		
		$this->field('need_skype', 'checkbox', 'Требуется Skype');
		
		$this->field('need_phone', 'checkbox', 'Требуется телефон');
		
		$this->field('priority', 'number', 'Приоритет')
			->description('Число. Чем больше, тем раньше показывается раздел');
		
		$this->field('price', 'number', 'Цена');
		
		$this->field('price_desc', 'text', 'Расшифровка цены');
		 				
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}
		
} 
