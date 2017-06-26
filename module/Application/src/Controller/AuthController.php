<?php
namespace Application\Controller;

use Application\Model\UserDb;
use Common\FormErrors;
use Common\Mailer;
use Common\ViewHelper\Flash;
use Zend\Http\Client;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\ViewHelper\Phone;
use Common\Traits\LoggerTrait;
use Common\Traits\LoggerAware;
use Common\ImageService;

/**
 * @property Zend\Log\Logger 	    		$log
 * @property Application\Model\UserDb 	    $userDb
 * @Controller
 */ 
class AuthController extends \Common\SiteController implements LoggerAware{
		
	use LoggerTrait;
	
	const HASH_SALT = "s23b";
	const MIN_PASSWORD_LENGTH = 5;
		
	/** @var UserDb */ 
	var $userDb = null;
	
	public function init() {		
		$this->userDb = $this->serv(UserDb::class);	}
	
	/**
     * @Route(name="login", route="/login")
     * */
		
		
		
    public function loginAction(){
    	
    	if($this->shouldRedirect()){
    		return $this->redirect()->toRoute("home");
    	}	    	
    	
    	$errors = new FormErrors();
    	if($this->request->isPost()){    			    	
	    	$login = $this->params()->fromPost('login');	    	
	    	$password = $this->params()->fromPost('password');
	    	$user = $this->userDb->getIdentity($login, $password);
	    	    	
	    	if($user == null){
	    		$errors->_login = "<strong>Упс!</strong> Неверный логин или пароль. Попробуйте еще";
	    	} else {
	    		return $this->authorizeUser($user);
	    	}
	    	
	    	$vm = new ViewModel([
	    			'login' => $login,
	    			'errors' => $errors
	    	]);
	    	
	    	$vm->setTemplate('application/auth/register');
	    	return $vm;
	    	
    	} else {
    		return $this->redirect()->toRoute("register");
    	}
    	    	
    }
    
    /**
     * @Route(name="test", route="/test")
     * */
    public function testAction(){    	
    	/* @var $mailSender MailSender */
    	$mailSender = $this->serv('MailSender');
    	    	
    	// $mailSender->send('application/auth/mail-forget', ['code' => '123456']);
    	
    	die;
    	
    }
    
    
    /**
     * @Route(name="logout", route="/logout")
     * */
    public function logoutAction(){
    	$this->identity()->clear();
    	return $this->redirect()->toRoute("home");
    }
        
    
    /**
     * @Route(name="register", route="/register")
     * */    
    public function registerAction(){

    	if($this->shouldRedirect()){
    		return $this->redirect()->toRoute("home");
    	}
    	
    	$request = $this->getRequest();    	
    	$errors = new FormErrors();
    	
    	if ($request->isPost()){
    		$data = $request->getPost()->toArray();
    		 
    		$data = filter_var_array($data, [
    			'login' 		=> FILTER_SANITIZE_STRING,
    			'displayname' 	=> FILTER_SANITIZE_STRING,
    			'password' 		=> FILTER_SANITIZE_STRING,    			 
    			'password_c' 	=> FILTER_SANITIZE_STRING,
    			'phone' 		=> FILTER_SANITIZE_STRING,
    			'agree' 		=> FILTER_SANITIZE_STRING,
    		]);
    		
    		if(empty($data['login'])){
    			$errors->login = "Введите Ваш электронный адрес";
    		} else if(!filter_var($data['login'], FILTER_VALIDATE_EMAIL)){
    			$errors->login = "Некорректный электронный адрес";
			}
    		
			if(empty($data['displayname'])){
				$errors->displayname = "Введите ваше ФИО"; 
			} else if(strlen($data['displayname']) < 2){
				$errors->displayname = "ФИО - мимнимум 2 символа"; 
			}
			
			if(empty($data['password'])){
				$errors->password = "Введите пароль";
				$data['password'] = $data['password_c'] = '';
			} else if(strlen($data['password']) < self::MIN_PASSWORD_LENGTH){
				$errors->password = "Мимнимум ".self::MIN_PASSWORD_LENGTH." символов";
				$data['password'] = $data['password_c'] = '';
			}
			
			if($data['password_c'] != $data['password']){
				$errors->password_c = "Пароли не совпадают";
				$data['password'] = $data['password_c'] = '';
			}
			
			if(empty($data['agree'])){
				$errors->agree = "Необходимо Ваше согласие";
			}
			
			$data['phone'] = Phone::normalize($data['phone']);
			
    		if (!$errors->hasErrors()){    			
    			/* @var $mailer Mailer */
    			$mailer = $this->serv(Mailer::class);
    			    			
    			$u = $this->userDb->findByLogin($data['login']);
    			if($u != null){
    				$errors->login = "Этот email уже зарегестрирован";
    			} else {
    				  
    				$data['status'] = UserDb::STATUS_NEW;
    				unset($data['password_c']);    				
    				unset($data['agree']);
    				
					$code = md5($data['login'].time().self::HASH_SALT);
					$data['vir_code'] = $code;
					
					$id = $this->userDb->registerCustomer($data);
					
					$user = $this->userDb->getIdentityById($id);
					
					$mailer->send($user['email'], 'Регистрация на сайте Olvia', 'auth/mail-register.phtml', ['user' => $user]);

					return $this->authorizeUser($user);
					
    			}
    		}
    		
    	} else {
    		$data = [
    				'displayname' => $this->identity()->displayname,
    				'password' => '',
    				'password_c' => '',
    				'login' => $this->identity()->email,
    				'phone' => $this->identity()->phone,    				
    		];
    		
    	}
    	
    	$sess = new Container('register');
    	
    	return new ViewModel(['data' => $data, 'errors' => $errors, 
    			'info_template' => 	$sess['info_template'],
    			'info_vars' => 		$sess['info_vars'],
    	]);    	
    }

    
    private function shouldRedirect(){
    	// редиректим всех, залогиненных юзеров, кроме админов. 
    	return $this->identity()->isLogged() && (!$this->identity()->isAdmin() && !$this->request->isPost());
    }
    
    /**
     * @Route(name="register-confirm", route="/register-confirm")
     * */
    public function registerConfirmAction(){
    	$sess = new Container('register');   	
    	$request = $this->getRequest();
    	
    	$errors = new FormErrors();
    	
    	if(!$sess->offsetExists('time') || $sess->time < time() - 30*60){ 
    		// Время жизни кода - 30 минут
    		
    		$sess->offsetUnset('time');
    		$sess->offsetUnset('data');
    		$sess->offsetUnset('hash');    		
    		return $this->redirect()->toRoute('home');    		
    	} else {
    		// Проверка кода
    		$sData = $sess['data'];
    		if ($request->isPost()){    			
    			$sHash = $sess['hash'];
    		
    			$code = $this->params()->fromPost('code');
    			$hash = md5($code.$sData['phone'].self::HASH_SALT);
    		
    			if($hash!=$sHash){
    				$errors->code = "Неверный код";    				
    			} else {
    				$phoneNumber = $sData['phone'];
    				
    				$u = $this->userDb->findByLogin($phoneNumber);
    				if($u!=null){
    					return $this->redirect()->toRoute('register');
    				} else {
    					$id = $this->userDb->createCarrier($sData);
    					$this->identity()->set(['id' => $id, 'name'=>$sData['name'], 'roles' => ['carrier']]);
    					$this->redirect()->toRoute('carrier-profile');
    				}    				
    			}
    		}
    	}
    	return new ViewModel(['errors' => $errors]);
    }
    
    
    /**
     * @Route(name="forget", route="/forget[/:step]", type="segment")
     * */
    public function forgetAction(){
    	
    	if($this->shouldRedirect()){
    		return $this->redirect()->toRoute("home");
    	}
    	
    	$errors = new FormErrors();	
    	$request = $this->getRequest();
    	    	
    	$view = new ViewModel();
    	
    	$view->step = $step = $this->params('step', '');
    	$sess = new Container('forget');
    	
    	if($step == 'pass'){
    		// Форма ввода кода
			if($sess->verified !== "Да!"){
				return $this->redirect()->toRoute('forget');
			}
			
			if ($request->isPost()){
				$password1 = $request->getPost('password1');
				$password2 = $request->getPost('password2');
				if(empty($password1)){
					$errors->form = "Введите пароль";
				} else if(strlen($password1) < self::MIN_PASSWORD_LENGTH){
					$errors->form = "Длина пароля - мимнимум ".self::MIN_PASSWORD_LENGTH." символов";
				} else if($password2 != $password1) {
					$errors->form = "Пароли не совпадают";
				} else {
					$user = $this->userDb->findByLogin($sess['login']);
					if(empty($user)){
						$this->sendFlashMessage("Пользователь не найден", Flash::ERROR);
						return $this->redirect()->toRoute('forget');
					} else {
						$this->userDb->setPassword($password1, $user['id']);
						$identiy = $this->userDb->getIdentity($sess['login'], $password1);						
						$this->identity()->set($identiy);
						
						$sess->offsetUnset('time');
						$sess->offsetUnset('login');
						$sess->offsetUnset('verified');
						if ($this->identity()->isAdmin()){
							return $this->redirect()->toRoute("private");
						} else {
							$this->sendFlashMessage("Пароль восстановлен", Flash::SUCCESS);
							return $this->redirect()->toRoute("home");
						}
							
					}
				}
			}
    		
    	} else if($step == 'code'){
    		if ($request->isPost()){
    			$code = $request->getPost('code', null);
    		} else {
    			$code = $request->getQuery('code', null);
    		}
    		if(!empty($code)){
    			if($sess->offsetExists('time') && $sess->time > time() - 30*60 ){
    				$hash = md5($code.$sess['login'].self::HASH_SALT);
    				if($hash != $sess['hash']){
    					$errors->form = "Невеверный код";
    				} else {
    					$sess->verified = "Да!";
    					return $this->redirect()->toRoute('forget', ['step' => 'pass']);
    				}
    			} else {
    				$sess->offsetUnset('login');
    				$sess->offsetUnset('hash');
    				$sess->offsetUnset('time');    				
    				return $this->redirect()->toRoute('forget');
    			}
    		}    		
    	} else {    		
    		// Форма ввода логина (первый шаг)
    		if ($request->isPost()){
    			$login = $request->getPost('login');    	
    			$view->login = $login;

    			if(empty($login)){
    				$errors->form = "Укажите Ваш email";    			
    			} else {
    				$user = $this->userDb->findByLogin($login);
    				if(empty($user)){
    					$errors->form = "Пользователь не зарегистрирован";
    				} else {
   
    					$code = md5($login.time().self::HASH_SALT);    						
    					
    					$mailer = $this->serv(Mailer::class);    					
    					$hash = md5($code.$login.self::HASH_SALT);    					
    					$sess['login'] = $login;
    					$sess['hash'] = $hash;
    					$sess['time'] = time();
    					
    					$mailer->send($login, 'Восстановление доступа на сайт Olvia', 'auth/mail-forget.phtml', ['user' => $user, 'code' => $code]);
    					
    					return $this->redirect()->toRoute('forget', ['step' => 'code']);
    				}
    			}
    		}
    	}
    	
    	$view->errors = $errors	;
    	return $view;
    	
    }
    
    

    /**
     * @Route(name="vkauth", route="/vk-auth")
     * */
    public function vkauthAction(){
    	
    	$code = $this->getRequest()->getQuery('code');
    	if(empty($code)){
    		throw new \Exception("no code");
    	}
    	    	
    	$response = $this->vkStep1($code);
    	
    	$vkId = $response['user_id'];
		if(empty($vkId)){
			$this->sendFlashMessage("Во время авторизации произошла ошибка. Попробуйте еще раз", Flash::ERROR);
			return $this->redirect()->toRoute('register');
		}
		$response = $this->vkStep2($vkId, $response['access_token']);
		$vkId = $response['domain'];
		$user = $this->userDb->getIdentityByVk($vkId);
		if(empty($user)){			
			/* @var $images ImageService */
			$images = $this->serv(ImageService::class);
			
			
			$userData = [
				'vk_id' => 			$vkId,
				'email' => 			$response['email'],
				'displayname' => 	$response['name'],
				'customer_name' =>  $response['first_name'].' '.$response['last_name'],				
				// 'city' => $response['city'],
				'birthday' => strtotime($response['bdate']),
			];
			
			
			if(!empty($response['photo'])){
				$newId = $this->userDb->getNextId();				
				$userData['image'] = $images->importUrl($response['photo'], 'users/'.$newId);
			}
			
			$userData['sex'] = ($response['sex'] == 1)? 'f' : ($response['sex'] == 2) ? 'm' : null;
			
			$userData['birthday'] = strtotime($response['bdate']);
			if(empty($userData['birthday'])){
				$userData['birthday'] = null;
			}
			
			if($this->identity()->isLogged()){				
				$this->userDb->updateIdentity($userData, $this->identity());
			} else {
				$this->userDb->registerCustomer($userData);
				// $this->sendFlashMessage("Здравствуйте, ".$user['name'].". Что бы начать принимать заказы, заполните профайл", Flash::INFO);
			}			
			
			$user = $this->userDb->getIdentityByVk($vkId);
		}
		$this->userDb->logged($user);
		return $this->authorizeSocialUser($user);    	
    }
    
    /**
     * @param string $code - код пришедший от VK
     * @return array
     */
    private function vkStep1($code) {
    	$params = array(
    			'client_id' => $this->config['vk_client_id'],
    			'redirect_uri'  => $this->config['base_url'].$this->url()->fromRoute('vkauth'),
    			'client_secret' => $this->config['vk_secret_key'],
    			'code' => $code,
    	);
    	
    	$url = 'https://oauth.vk.com/access_token' . '?' . urldecode(http_build_query($params));
    	$client = new Client($url, array(
    			'sslverifypeer' => false,
    	));    	
    	$response = $client->send();
    	$response = json_decode($response->getBody(), true);
    	return $response;
    }
    
	/**
	 * Получение данных пользователя из VK
	 * @param string $vkId - ID пользователя VK
	 * @param string $accessToken - ключь сессии
	 * @return array
	 */
    private function vkStep2($vkId, $accessToken) {
    	echo $url;
	    $url = 'https://api.vk.com/method/users.get?uids='.$vkId.'&fields=first_name,last_name,nickname,photo_400_orig,sex,bdate,city,domain&access_token='.$accessToken;
	    $client = new Client($url, array(
	    		'sslverifypeer' => false,
	    ));
	    $response = $client->send();
	    $response = json_decode($response->getBody(), true);	    
	    $response = $response['response'][0];
	    $nameArr = [$response['first_name'],$response['last_name']];
	    $response['name'] = trim(implode(" ", $nameArr));
	    if(empty($response['name'])){
	    	$response['name'] = $response['nickname'];
	    }	    
	    $response['photo'] = $response['photo_400_orig'];
	    return $response;
    }
    

    
    /**
     * Авторизация из Facebook 
     * @Route(name="fbauth", route="/fb-auth")
     * */
    public function fbauthAction(){   	 
    	
    	$code = $this->getRequest()->getQuery('code');
    	if(empty($code)){
    		throw new \Exception("no code");
    	}
    	    	
    	$response = $this->fbStep1($code);
    	$response = $this->fbStep2($response['access_token']);
		$fbId = $response['id'];
		if(empty($fbId)){
			$this->sendFlashMessage("Во время авторизации произошла ошибка. Попробуйте еще раз", Flash::ERROR);
			return $this->redirect()->toRoute('register');
		}
			
		$user = $this->userDb->getIdentityByFb($fbId);
		if(empty($user)){
			$userData = [
					'fb_id' => $fbId,
					'displayname' => $response['name'],
			];
			
			if($this->identity()->isLogged()){
				$this->userDb->updateIdentity($userData, $this->identity());
			} else {
				$this->userDb->registerCustomer($userData);
				// $this->sendFlashMessage("Здравствуйте, ".$user['displayname'].". Что бы начать принимать заказы, заполните профайл");
			}	
			
			$user = $this->userDb->getIdentityByFb($fbId);
			
		}
		$this->userDb->logged($user);
		return $this->authorizeSocialUser($user);
		    	
    	die;
    }
    
    
    /**
     * @param string $code - код пришедший от FB
     * @return array
     */
    private function fbStep1($code) {
    	$params = array(
    			'client_id' => $this->config['fb_client_id'],
    			'redirect_uri'  => $this->config['base_url'].$this->url()->fromRoute('fbauth'),
    			'client_secret' => $this->config['fb_secret_key'],
    			'code' => $code,
    	);
    	
    	$url = 'https://graph.facebook.com/oauth/access_token' . '?' . urldecode(http_build_query($params));
    	$client = new Client($url, array(
    			'sslverifypeer' => false,
    	));
    	$rBody = $client->send();
    	$response = json_decode($rBody->getBody(), true);    	
    	return $response;
    }
    
    /**
     * Получение данных пользователя из FB
     * @param string $accessToken - ключь сессии
     * @return array
     */
    private function fbStep2($accessToken) {
    	$url = 'https://graph.facebook.com/me?access_token='.$accessToken;
    	$client = new Client($url, array(
    			'sslverifypeer' => false,
    	));
//     	$url = 'https://graph.facebook.com/me/photo?access_token='.$accessToken;
//     	$client = new Client($url, array(
//     			'sslverifypeer' => false,
//     	));
    	$response = $client->send();
    	$response = json_decode($response->getBody(), true);
    	return $response;
    }
    
    

    
    /**
     * Авторизация через социалки
     * @param array $user - Identity
     * @return \Zend\Http\Response - куда редиректить пользователя
     */
    function authorizeSocialUser($user) {
    	$sess = new Container('login');
    	$this->userDb->logged($user);    	
    	$this->identity()->set($user);
    	
    	return $this->userFlow()->afterAuthorization();
    }
    
    /**
     * Авторизация по логину/паролю
     * @param array $user - Identity
     * @return \Zend\Http\Response - куда редиректить пользователя
     */
    private function authorizeUser($user){
    	
    	$sess = new Container('register');
    	$sess->offsetUnset('info_template');
    	$sess->offsetUnset('info_vars');
    	
    	$sess = new Container('login');
    	
    	$this->userDb->logged($user);
    	
    	$this->identity()->set($user);
    	
    	return $this->userFlow()->afterAuthorization();
    }
}
