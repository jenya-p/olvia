<?php

namespace Admin\Forms\Users;

use Common\Form\Form;
use ZfAnnotation\Annotation\Service;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements;

/**
 * @Service
 */
class UserForm extends Form implements Initializable{
	
	use ImageElements;
	
	var $defaultElementDecorator = 'default';
	
	
	public function init(){
		
		$this->addClass('g-form');
		
 		$this->field('displayname', 'text', 'Имя пользователя')
 			->validator('required', 'Заполните это поле')
 			->description('Видно на сайте другим пользователям');
 		
 			
  		$this->field('email', 'text', null, ['label' => 'Электронная почта'])
  			// ->validator('required', 'Заполните это поле')
  			->validator('email', 'Некорректный адрес электронной почты')
  			->description('Используется в качестве логина и для рассылки уведомлений');
  		
  		$this->field('skype', 'text', null, ['label' => 'Skype']);
  		
  		$this->field('phone', 'phone', null, ['label' => 'Телефон']);
  		
  		$this->field('roles', 'checkboxgroup', null, ['label' => 'Роли'])
  			->validator('required', 'Роли должны быть указаны, хотя бы одна');
  			
  		$this->field('password_1', 'password', null, ['label' => 'Новый пароль']);
  		$this->field('password_2', 'password', null, ['label' => 'Повторите ввод пароля']);

  		
  		$this->field('vk_id', 'text', null, ['label' => 'VK']);
  		$this->field('fb_id', 'text', null, ['label' => 'FB']);
  		
  		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 			  		
	}
} 
