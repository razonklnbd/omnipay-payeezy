<?php
/**
 * OmnipayWrapper class
 */

namespace Omnipay;

use Omnipay\Common\GatewayFactory;
use Omnipay\Common\Helper;
use Omnipay\Common\AbstractGateway;
use Omnipay\Common\CreditCard;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Omnipay\OmnipayBridge\AbstractBridge;
use Omnipay\Common\Exception\InvalidCreditCardException;

/**
 *
 * @author razon
 *
 */
class OmnipayWrapper{
	const CMD_DEFAULT='authorize';
	const CMD_TOKENIZE='tokenize';
	const CMD_AUTHORIZE='authorize';
	const CMD_PURCHASE='purchase';
	const CMD_CAPTURE='capture';
	const CMD_VOID='void';
	const CMD_REFUND='refund';
	const CMD_TAGGED_REFUND='taggedRefund';
	private $lastExecutedCommand;
	/**
	 * @var AbstractBridge
	 */
	private $bridge;
	/**
	 * @var AbstractGateway
	 */
	private $gateway;
	/**
	 * @var string
	 */
	private $gwName;
	/**
	 * @var boolean
	 */
	private $gwTestMode;
	/**
	 * @var array
	 */
	private $gwOptions;
	/**
	 * @var string
	 */
	private $defaultPaymentCommand;
	/**
	 *
	 * @param string $pGateway
	 * @param string|array|JSON Object $pOptions [JSON string support]
	 */
	function __construct($pGateway, $pOptions, $pTestMode=false){
		$this->currencyIso3Code='USD';
		$fOptions=array();
		if(!empty($pOptions)){
			if(is_array($pOptions)) $fOptions=$pOptions;
			elseif(is_string($pOptions)) $fOptions=json_decode($pOptions, true);
			else{
				$x = (array)$pOptions;
				if(!empty($x)) $fOptions=$x;
			}
		}
		$this->gwName=str_replace(array('/', '\\'), '\\', $pGateway);
		$this->gwOptions=$fOptions;
		$this->setDefaultPaymentCommand();
		#if(array_key_exists('developerMode', $this->gwOptions) && true==$this->gwOptions['developerMode']) $pTestMode=true;
		#die('@'.__LINE__.': '.__FILE__.'<pre>'.print_r($this->gwOptions, true));
		$this->gwTestMode=$pTestMode;
		$this->bridge=AbstractBridge::factory($this->getGwName());
		$this->bridge->setGwOptions($this->getGwOptions());
		$this->gw();
	}
	private function setDefaultPaymentCommand(){
		$fGwOptions=$this->getGwOptions();
		#die('$this->paymentAuthorizeOnly(): '.var_export($this->paymentAuthorizeOnly, true).' @'.__LINE__.': '.__FILE__);
		if(array_key_exists('authorizeOnly', $fGwOptions)) $pPaymentAuthorizeOnly=$fGwOptions['authorizeOnly'];
		if(isset($pPaymentAuthorizeOnly) && !is_bool($pPaymentAuthorizeOnly)) unset($pPaymentAuthorizeOnly);
		if(!isset($pPaymentAuthorizeOnly)) $pPaymentAuthorizeOnly=(self::CMD_AUTHORIZE==self::CMD_DEFAULT);
		if(true==$pPaymentAuthorizeOnly && !$this->gw()->supportsAuthorize()) throw new \Exception('authorization is not supported by "'.$this->getGwName().'"');
		if(false==$pPaymentAuthorizeOnly && !$this->gw()->supportsPurchase()) throw new \Exception('purchase is not supported by "'.$this->getGwName().'"');
		$this->defaultPaymentCommand=(true==$pPaymentAuthorizeOnly ? self::CMD_AUTHORIZE : self::CMD_PURCHASE);
		return $this;
	}
	/**
	 * @return string
	 */
	public function getPaymentMethod(){ return $this->bridge->getPaymentMethod(); }
	/**
	 * @return string
	 */
	public function getUsingCardOrAccountName(){ return $this->bridge->getUsingCardOrAccountName(); }
	public function getSuccessfulTransactionAmount(){ return $this->bridge->getSuccessfulTransactionAmount(); }
	public function isAuthorizeDefault(){ return (self::CMD_AUTHORIZE==$this->defaultPaymentCommand); }
	public function isPurchaseDefault(){ return (self::CMD_PURCHASE==$this->defaultPaymentCommand); }
	public function setCurrencyIso3Code($pIso3Code){
		$this->bridge->setCurrencyIso3Code($pIso3Code);
		return $this;
	}
	public function setCard($pFirstName, $pLastName=NULL, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV=NULL){
        #die('@'.__LINE__.': '.__FILE__);
        #if(!self::isCreditCardValid($pFirstName, $pLastName, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV)) die('card not valid! @'.__LINE__.': '.__FILE__);
		$this->bridge->setCard($pFirstName, $pLastName, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV);
		return $this;
	}
	public function unsetCard(){ return $this->bridge->unsetCard(); }
	public static function isCreditCardValid($pFirstName, $pLastName=NULL, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV){
		$crdtCardObj=AbstractBridge::getCreditCardObject($pFirstName, $pLastName, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV);
		try{
			$crdtCardObj->validate();
			return true;
		}catch (InvalidCreditCardException $icce){
			return false;
		}
	}
	public function getAmountFromTransactionData(){ return $this->bridge->getAmountFromTransactionData(); }
	public function isTransactionOk($pReturnAnySuccessStatus=false){ return $this->bridge->isTransactionOk($pReturnAnySuccessStatus); }
	public function isTransactionFail(){ return !($this->bridge->isTransactionOk()); }
	/**
	 * @return ResponseInterface
	 */
	public function getLastResponseObject(){ return $this->bridge->getLastResponseObject(); }
	/**
	 * @return boolean
	 */
	public function isCardExistAtResponse(){ return $this->bridge->isCardExistAtResponse(); }
	/**
	 * @param string $pData [optional, base64 encoded serialized value]
	 * @return \Omnipay\OmnipayBridge\RespondedCreditCard
	 */
	public function getCardFromResponse($pData=NULL){ return $this->bridge->getCardFromResponse($pData); }
	public function getGwName(){ return $this->gwName; }
	public function getGwOptions(){
		if(!isset($this->gwOptions)) return array();
		return $this->gwOptions;
	}
	/**
	 * @param boolean $pForceLoad
	 * @return AbstractGateway
	 */
	public function gw($pForceLoad=false){
		if(!isset($this->gateway) || true==$pForceLoad){
			$factory=new GatewayFactory();
			$this->gateway = $factory->create($this->gwName);
			if(!empty($this->gwOptions)) Helper::initialize($this->gateway, $this->gwOptions);
			if(true==$this->gwTestMode) $this->gateway->setTestMode(true);
		}
		return $this->gateway;
	}
	/**
	 * Supports Authorize
	 *
	 * @return boolean True if this gateway supports the authorize() method
	 */
	public function supportsAuthorize(){ return $this->gw()->supportsAuthorize(); }

	/**
	 * Supports Complete Authorize
	 *
	 * @return boolean True if this gateway supports the completeAuthorize() method
	 */
	public function supportsCompleteAuthorize(){ return $this->gw()->supportsCompleteAuthorize(); }

	/**
	 * Supports Capture
	 *
	 * @return boolean True if this gateway supports the capture() method
	 */
	public function supportsCapture(){ return $this->gw()->supportsCapture(); }

	/**
	 * Supports Purchase
	 *
	 * @return boolean True if this gateway supports the purchase() method
	 */
	public function supportsPurchase(){ return $this->gw()->supportsPurchase(); }

	/**
	 * Supports Complete Purchase
	 *
	 * @return boolean True if this gateway supports the completePurchase() method
	 */
	public function supportsCompletePurchase(){ $this->gw()->supportsCompletePurchase(); }

	/**
	 * Supports Refund
	 *
	 * @return boolean True if this gateway supports the refund() method
	 */
	public function supportsRefund(){ return $this->gw()->supportsRefund(); }

	/**
	 * Supports Void
	 *
	 * @return boolean True if this gateway supports the void() method
	 */
	public function supportsVoid(){ return $this->gw()->supportsVoid(); }

	/**
	 * Supports CreateCard
	 *
	 * @return boolean True if this gateway supports the create() method
	 */
	public function supportsCreateCard(){ return $this->gw()->supportsCreateCard(); }

	/**
	 * Supports DeleteCard
	 *
	 * @return boolean True if this gateway supports the delete() method
	 */
	public function supportsDeleteCard(){ return $this->gw()->supportsDeleteCard(); }

	/**
	 * Supports UpdateCard
	 *
	 * @return boolean True if this gateway supports the update() method
	 */
	public function supportsUpdateCard(){ return $this->gw()->supportsUpdateCard(); }
	/**
	 * check if token method possible to call for this gateway
	 * @return boolean
	 */
	public function supportsTokenize(){ return method_exists($this->gw(), 'tokenize'); }
	/**
	 * check if tagged refund method possible to call for this gateway
	 * @return boolean
	 */
	public function supportsTaggedRefund(){ return method_exists($this->gw(), 'taggedRefund'); }
	/**
	 * @param string $pData
	 * @return \Omnipay\OmnipayWrapper
	 */
	public function setDataToUse($pData){
		$this->bridge->setDataToUse($pData);
		return $this;
	}
	/**
	 * @return string
	 */
	public function getResponseToSave(){ return $this->bridge->getResponseToSave(); }
	/**
	 * call real function
	 */
	public function tokenize(){
		$this->lastExecutedCommand=self::CMD_TOKENIZE;
        #die('$this->gw(true): '.get_class($this->gw(true)).'<pre>'.print_r($this->gw(true)->tokenize($this->bridge->tokenize())->send(), true));
		return $this->bridge->setTokenizeResponse($this->gw(true)->tokenize($this->bridge->tokenize())->send());
	}
	public function authorize($pAmount){
		$this->lastExecutedCommand=self::CMD_AUTHORIZE;
		return $this->bridge->setAuthorizeResponse($this->gw(true)->authorize($this->bridge->authorize($pAmount))->send());
	}
	public function capture($pAmount=NULL){
		$this->lastExecutedCommand=self::CMD_CAPTURE;
		return $this->bridge->setCaptureResponse($this->gw(true)->capture($this->bridge->capture($pAmount))->send());
	}
	public function void($pAmount=NULL){
		$this->lastExecutedCommand=self::CMD_VOID;
		return $this->bridge->setVoidResponse($this->gw(true)->void($this->bridge->void($pAmount))->send());
	}
	public function purchase($pAmount){
		$this->lastExecutedCommand=self::CMD_PURCHASE;
		return $this->bridge->setPurchaseResponse($this->gw(true)->purchase($this->bridge->purchase($pAmount))->send());
	}
	public function refund($pAmount=NULL){
		$this->lastExecutedCommand=self::CMD_REFUND;
		return $this->bridge->setRefundResponse($this->gw(true)->refund($this->bridge->refund($pAmount))->send());
	}
	public function taggedRefund($pAmount=NULL){
		$this->lastExecutedCommand=self::CMD_TAGGED_REFUND;
		return $this->bridge->setTaggedRefundResponse($this->gw(true)->taggedRefund($this->bridge->taggedRefund($pAmount))->send());
	}

	public function getLastExecutedCommand(){ return $this->lastExecutedCommand; }


	public static function getSupportedBrands(){
		$cBrands[CreditCard::BRAND_AMEX]='American Express'; # 'Amarican Express';
		$cBrands[CreditCard::BRAND_DANKORT]='Dankort';
		$cBrands[CreditCard::BRAND_DINERS_CLUB]='Diners Club';
		$cBrands[CreditCard::BRAND_DISCOVER]='Discover';
		$cBrands[CreditCard::BRAND_FORBRUGSFORENINGEN]='BRAND_FORBRUGSFORENINGEN';
		$cBrands[CreditCard::BRAND_JCB]='JCB';
		$cBrands[CreditCard::BRAND_LASER]='Laser';
		$cBrands[CreditCard::BRAND_MAESTRO]='Mestro';
		$cBrands[CreditCard::BRAND_MASTERCARD]='Master Card';
		$cBrands[CreditCard::BRAND_SOLO]='Solo';
		$cBrands[CreditCard::BRAND_SWITCH]='Switch';
		$cBrands[CreditCard::BRAND_VISA]='Visa';
		#$cBrands[CreditCard::BRAND]='';
		return $cBrands;
	}

}


