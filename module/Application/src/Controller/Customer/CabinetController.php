<?

namespace Application\Controller\Customer;

use Application\Model\CustomerDb;
use Common\Annotations\Roles;
use Common\ImageService;
use Common\SiteController;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Application\Model\UserDb;
use Common\FormErrors;
use Common\ViewHelper\Phone;
use Application\Controller\AuthController;
/**
 * @Controller
 * @Roles(value="customer")
 */
class CabinetController extends SiteController{

	/** @var CustomerDb */
	var $customerDb;
	
	/** @var UserDb */
	var $userDb; 
	
	public function init(){
		$this->customerDb = $this->serv(CustomerDb::class);
		$this->userDb = $this->serv(UserDb::class);
	}
	
	
	/**
	 * @Route(name="customer-cabinet", route="/customer/cabinet", type="segment")
	 */
	public function customerCabinetAction(){
		$id = $this->identity()->id;
		$item = $this->customerDb->get($id);
		
		$request = $this->getRequest();
		$errors = new FormErrors();
		
		
		if($request->isPost()){
			$data = $this->params()->fromPost('item', []);
						
			$data = filter_var_array($data, [
				'email' => 			FILTER_SANITIZE_STRING,
				'displayname' => 	FILTER_SANITIZE_STRING,
				'name' => 			FILTER_SANITIZE_STRING,
				'city' => 			FILTER_SANITIZE_STRING,
				'skype' => 			FILTER_SANITIZE_STRING,
				'birthday' => 		FILTER_SANITIZE_STRING,
				'phone' => 			FILTER_SANITIZE_STRING,
				'sex' => 			FILTER_SANITIZE_STRING,
				'description'	=> 	FILTER_SANITIZE_STRING,
			]);
			
			if(empty($data['email'])){
				$errors->email = "Введите Ваш электронный адрес";
			} else if(!filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
				$errors->email = "Некорректный электронный адрес";
			} else if($item['email'] != $data['email'] && 
					!$this->userDb->checkEmail($data['email'], $id) ){
				$errors->email = "Пользователь с таким электронным адресом уже зарегестрирован";
			} 
			
			if(empty($data['displayname'])){
				$errors->displayname = "Введите ваше ФИО";
			} else if(strlen($data['displayname']) < 2){
				$errors->displayname = "ФИО - мимнимум 2 символа";
			}

			if($data['sex'] != 'f' && $data['sex'] != 'm'){
				$data['sex'] = null;
			}
			
			if(preg_match('/\d{1,2}\.\d{1,2}\.\d{2,4}/', $data['birthday'])){
				$dt = \DateTime::createFromFormat('d.m.Y', $data['birthday']);
				$dt = $dt->getTimestamp();
				
				if($dt < time() - 60*60*24*80*365 || $dt > time() - 60*60*24*8*365){
					$errors->birthday = "Введите реальные данные";
				} else {
					$data['birthday'] = $dt;
				}				
			} else {
				$data['birthday'] = null;
			}
			
			if(!$errors->hasErrors()){

				$this->userDb->updateOne([
						'email' => $data['email'] ,
						'skype' => $data['skype'] , 
						'phone' => Phone::normalize( $data['phone'] ) ,
						'displayname' =>  $data['displayname'] ,
				], $this->identity()->id, true);				
				
				$this->customerDb->updateOne([
						'name' => $data['name'] ,
						'city' => $data['city'] ,
						'sex' => $data['sex'] ,
						'birthday' => $data['birthday'] ,
						'description' => $data['description'] ,
				], $this->identity()->id);
				
				$this->userDb->refreshIdentity($this->identity());
				
				return $this->redirect()->toRoute('customer-cabinet');
				
			} else {
				$item = $data;
			}
			
		}
		$this->userDb->buildItem($item);
		return [
			'item' => $item,
			'errors' => $errors
		];
	}
	
	const MAX_FILE_SIZE = 4; // Mb
	
	/**
	 * @Route(name="customer-image-upload", route="/customer/image-upload", type="segment")
	 */
	public function uploadAction(){
		
		
		$request = $this->getRequest();
		if(! $request->isPost() && !$request->isXmlHttpRequest()){
			return $this->redirect()->toRoute("customer-cabinet");
		}
		
		$file = $this->params()->fromFiles('image');
		
		if(!is_array($file)){
			return $this->redirect()->toRoute("customer-cabinet");			
		}
		

		$name 	= $file['name'];
		$source = $file['tmp_name'];
		
		$ext = pathinfo($name, PATHINFO_EXTENSION);
		
		if($ext != 'jpg' && $ext != 'jpeg' && $ext != 'png' && $ext != 'bmp'){			
			$errorMsg = 'Сюда можно загружать только картинки (jpeg, png, bmp)';
		}
		
		if($file['size'] > self::MAX_FILE_SIZE * 1024 * 1024){
			$errorMsg = 'Загружаемый файл должен быть размером не более '.self::MAX_FILE_SIZE.' MB';
		}
		
		
		if (!is_file($source)) {
			$errorMsg = 'Что то пошло не так. Попробуйте позже.';			
		}
		
		if(!empty($errorMsg)){
			return new JsonModel([
					'result' => 'error',
					'message' => $errorMsg
				]);
		}
		
		
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$id = $this->identity()->id;
		
		$image = $imageService->import($file, 'users/'.$id);
		
		$this->customerDb->updateOne([
				'image' => $image
		], $this->identity()->id);
		
		return new JsonModel([
				'result' => 'ok',
				'image' => $imageService->resize($image,  ImageService::SIZE_USERPICK_LARGE)
		]);
		
		
	}
	
	
	
	/**
	 * @Route(name="customer-change-password", route="/customer/change-password", type="segment")
	 */
	public function changePasswordAction(){
	
		$request = $this->getRequest();
		if(! $request->isPost() && !$request->isXmlHttpRequest()){
			return $this->redirect()->toRoute("customer-cabinet");
		}
	
		$password1 = $this->params()->fromPost('password1');
		$password2 = $this->params()->fromPost('password2');
		
		$errorMsg = null;
		
		if(empty($password1)){
			$errorMsg = "Введите пароль";
		} else if( strlen($password1) < AuthController::MIN_PASSWORD_LENGTH ){
			$errorMsg = "Мимнимум ".AuthController::MIN_PASSWORD_LENGTH." символов";
		}
		
		if($password1 != $password2){
			$errorMsg = "Пароли не совпадают";
		}
	
		if(!empty($errorMsg)){
			return new JsonModel([
					'result' => 'error',
					'message' => $errorMsg
			]);
		}
		
		$id = $this->identity()->id;
	
		$this->userDb->setPassword($password1, $id);
		
		return new JsonModel([
				'result' => 'ok',
		]);	
	
	}
	
}
