<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Direct API Authorize Request
 */
class DirectTokenRequest extends DirectApiAbstractRequest {
	#protected $action = 'AUTH_ONLY';

	protected function getTransactionType(){ return ''; }

	protected function getEndpoint(){
		if ($this->getDeveloperMode()) {
			return $this->getParameter('devTokenEndpoint');
		} else {
			return $this->getParameter('liveTokenEndpoint');
		}
	}

}
