<?
namespace Application\ViewHelper;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;
use Zend\Session\Container;
use Zend\View\Renderer\RendererInterface;
use Application\ControllerPlugin\UserFlow;

class UserFlowFlash extends AbstractPlugin{
	
	var $session;
	/** @var RendererInterface */
	var $renderer = null;
	
	public function __construct(RendererInterface $renderer){
		$this->renderer = $renderer;
		$this->session = new Container(UserFlow::class);
	}
	
	public function __invoke(){
		if($this->session->offsetExists('flash')){
			$html = '';			
			foreach ($this->session['flash'] as $flashMessage) {
				$html .= $this->renderer->render($flashMessage['template'], $flashMessage['vars']);
			}			
		}
		$this->session->offsetUnset('flash');
		return $html;
	}
	
}