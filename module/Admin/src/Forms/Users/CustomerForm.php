<?php

namespace Admin\Forms\Users;

use Common\Form\Form;
use ZfAnnotation\Annotation\Service;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements;

/**
 * @Service
 */
class CustomerForm extends Form implements Initializable{
	
	use ImageElements;
	
	var $defaultElementDecorator = 'default';
	
	
	public function init(){
		
		$this->addClass('g-form');
			
		$this->field('name', 'text', null, ['label' => 'Имя клиента']);
		
		$this->field('city', 'text', null, ['label' => 'Город']);
				
		$this->field('account_displayname', 'text', 'Имя пользователя')
			// ->validator('required', 'Заполните это поле')
			->description('Видно на сайте другим пользователям');
			
		
		$this->field('account_email', 'text', null, ['label' => 'Электронная почта'])
			// ->validator('required', 'Заполните это поле')
			->validator('email', 'Некорректный адрес электронной почты')
			->description('Используется в качестве логина и для рассылки уведомлений');
		
		$this->field('account_skype', 'text', null, ['label' => 'Skype']);
		
		$this->field('account_phone', 'text', null, ['label' => 'Телефон']);
				  			
  		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 			  		
	}
} 
