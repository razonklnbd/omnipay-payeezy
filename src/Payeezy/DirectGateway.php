<?php

namespace Omnipay\Payeezy;

use Omnipay\Common\AbstractGateway;

/**
 * Payeezy Direct API Class
 *
 * @link https://developer.payeezy.com/docs-sandbox
 */
class DirectGateway extends AbstractGateway {

	const UNKNOWN_NUM_FOR_SHIPMENT = 99;
    const TRANSARMOR_DEV_TOKEN='NOIW';

	public function getName(){ return 'Payeezy Direct API'; }

	public function getDefaultParameters(){
		return array(
				'apiKey'        	=> '',
				'apiSecret'    		=> '',
				'merchantToken'		=> '',
				'testMode'          => false,
				'developerMode'     => false,
				'maxTxAmount'		=> 0, # must bigger than ZERO
				/* split shipment settings */
				'splitX'	=> '', # start value of a shipment
				'splitY'	=> strval(self::UNKNOWN_NUM_FOR_SHIPMENT), # unknown number of shipment may occur
				/* transarmor enable or not */
				'transarmorEnabled' => false,
                'transarmorToken' => '',
				'transarmorTokenArray' => array(),
				/* end point url */
				'liveBaseEndpoint'      => 'https://api.payeezy.com/v1/transactions', #'https://api.globalgatewaye4.firstdata.com/transaction/v19',
				'liveTokenEndpoint'      => 'https://api.payeezy.com/v1/transactions/tokens',
				'devBaseEndpoint'      => 'https://api-cert.payeezy.com/v1/transactions',
				'devTokenEndpoint'      => 'https://api-cert.payeezy.com/v1/transactions/tokens',
			);
	}

	public function getApiKey(){ return $this->getParameter('apiKey'); }
	public function setApiKey($value){ return $this->setParameter('apiKey', $value); }
	public function getApiSecret(){ return $this->getParameter('apiSecret'); }
	public function setApiSecret($value){ return $this->setParameter('apiSecret', $value); }
	public function getMerchantToken(){ return $this->getParameter('merchantToken'); }
	public function setMerchantToken($value){ return $this->setParameter('merchantToken', $value); }
	public function getSplitX(){ return $this->getParameter('splitX'); }
	public function setSplitX($value){ return $this->setParameter('splitX', $value); }
	public function getSplitY(){ return $this->getParameter('splitY'); }
	public function setSplitY($value){ return $this->setParameter('splitY', $value); }

	public function getMaxTxAmount(){ return $this->getParameter('maxTxAmount'); }
	public function setMaxTxAmount($value){ return $this->setParameter('maxTxAmount', $value); }

	public function getTransarmorEnabled(){ return $this->getParameter('transarmorEnabled'); }
	public function isTransarmorEnabled(){ return $this->getTransarmorEnabled(); }
	public function setTransarmorEnabled($value){ return $this->setParameter('transarmorEnabled', $value); }
    public function getTransarmorToken(){ return $this->getParameter('transarmorToken'); }
    public function setTransarmorToken($value){ return $this->setParameter('transarmorToken', $value); }
	public function getTransarmorTokenArray(){ return $this->getParameter('transarmorTokenArray'); }
	public function setTransarmorTokenArray($value){ return $this->setParameter('transarmorTokenArray', $value); }

	public function getDeveloperMode(){ return $this->getParameter('developerMode'); }
	public function setDeveloperMode($value){ return $this->setParameter('developerMode', $value); }

	public function setEndpoints($endpoints){
		$this->setParameter('liveBaseEndpoint', $endpoints['live']['base']);
		$this->setParameter('liveTokenEndpoint', $endpoints['live']['token']);
		$this->setParameter('devBaseEndpoint', $endpoints['dev']['base']);
		return $this->setParameter('devTokenEndpoint', $endpoints['dev']['token']);
	}
	public function getLiveBaseEndpoint(){ return $this->getParameter('liveBaseEndpoint'); }
	public function setLiveBaseEndpoint($value){ return $this->setParameter('liveBaseEndpoint', $value); }
	public function getLiveTokenEndpoint(){ return $this->getParameter('liveTokenEndpoint'); }
	public function setLiveTokenEndpoint($value){ return $this->setParameter('liveTokenEndpoint', $value); }
	public function getDevBaseEndpoint(){ return $this->getParameter('devBaseEndpoint'); }
	public function setDevBaseEndpoint($value){ return $this->setParameter('devBaseEndpoint', $value); }
	public function getDevTokenEndpoint(){ return $this->getParameter('devTokenEndpoint'); }
	public function setDevTokenEndpoint($value){ return $this->setParameter('devTokenEndpoint', $value); }

	public function tokenize(array $parameters = array()){ return $this->createRequest('\Omnipay\Payeezy\Message\DirectTokenRequest', $parameters); }
    public function authorize(array $parameters = array()){ return $this->createRequest('\Omnipay\Payeezy\Message\DirectAuthorizeRequest', $parameters); }
    public function capture(array $parameters = array()){ return $this->createRequest('\Omnipay\Payeezy\Message\DirectCaptureRequest', $parameters); }
    public function purchase(array $parameters = array()){ return $this->createRequest('\Omnipay\Payeezy\Message\DirectPurchaseRequest', $parameters); }
    public function void(array $parameters = array()){ return $this->createRequest('\Omnipay\Payeezy\Message\DirectVoidRequest', $parameters); }
    public function refund(array $parameters = array()){ return $this->createRequest('\Omnipay\Payeezy\Message\DirectTaggedRefundRequest', $parameters); }
    public function taggedRefund(array $parameters = array()){ return $this->refund($parameters); }


}
