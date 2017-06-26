<?
namespace Application\ViewHelper;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;
use Zend\View\Renderer\RendererInterface;
use Application\ControllerPlugin\UserFlow;

class UserFlowCartInfo extends AbstractPlugin{
	
	var $session;
	/** @var RendererInterface */
	var $renderer = null;
	
	public function __construct(RendererInterface $renderer){
		$this->renderer = $renderer;
		$this->session = new Container(UserFlow::class);
	}
	
	public function __invoke(){
		if(!empty($this->session['cart'])){
			foreach ($this->session['cart'] as $vars) {
				if($vars['type'] == UserFlow::ORDERTYPE_CONSULTATION){
					$template = '/application/auth/cart.consultation.phtml';
				} else if ($vars['type'] == UserFlow::ORDERTYPE_ORDER){
					$template = '/application/auth/cart.order.phtml';
				}
				$html .= $this->renderer->render($template, $vars);
			}			
		}
		return $html;
	}
	
}