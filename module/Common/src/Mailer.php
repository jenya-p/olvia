<?php
namespace  Common;


use Zend\Mail\Transport\TransportInterface;
use Zend\View\Renderer\RendererInterface;

class Mailer{
	
	/* @var TransportInterface */
	var $transport = null;
	
	/* @var RendererInterface */
	var $renderer = null;
	
	var $robotEmail = null;
	
	var $robotName = null;
	
	var $layout = '';
	
	var $debugMailTo = false;
		
	public function __construct(TransportInterface $transport, RendererInterface $renderer, $config){
		$this->transport = $transport;
		$this->renderer = $renderer;
		$this->robotEmail = $config['robotEmail'];
		$this->robotName = $config['robotName'];
		$this->layout = $config['mailLayout'];	
		
		if(!empty($config['debugMailTo'])){
			$this->debugMailTo = $config['debugMailTo'];
		}
	}
			
	public function send($to, $subject, $template, $vars){
		
		$body = $this->renderer->render('/mail/'.$template, $vars);
		
		if(!empty($this->layout)){
			$body = $this->renderer->render($this->layout, array_merge($vars, ['_content' => $body]));
		}

		$textPart = new \Zend\Mime\Part($body);
		$textPart->type = "text/html";
		$textPart->encoding = \Zend\Mime\Mime::ENCODING_BASE64;
		$mime = new \Zend\Mime\Message();
		$mime->setParts(array($textPart));
		
		$message = new \Zend\Mail\Message();
		$message->setFrom($this->robotEmail, $this->robotName);
		if($this->debugMailTo === true){
			$message->addTo($this->debugMailTo);			
		} else {
			$message->addTo($to);
		}		
		$message->setSubject($subject);
		$message->setEncoding('UTF-8');
		$message->setBody($mime);
		
		$headers = $message->getHeaders();
		$headers->removeHeader('Content-Type');
		$headers->addHeaderLine('Content-Type', 'text/html; charset=UTF-8');
				
		try{
			$this->transport->send($message);
		} catch (\Exception $e){
			// TODO Logging
		}
		
	}

	public function setDebugMailTo($debugMailTo) {
		$this->debugMailTo = $debugMailTo;
		return $this;
	}
	
		
}
