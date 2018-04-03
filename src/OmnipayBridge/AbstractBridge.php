<?php
namespace Omnipay\OmnipayBridge;

use Omnipay\Common\CreditCard;
use Omnipay\Common\Message\ResponseInterface;

abstract class AbstractBridge {
	final function __construct(){ /* we want to keep it simple, so no other contructor */ }
	/**
	 * @var array
	 */
	private $gwOptions;
	/**
	 * @var CreditCard
	 */
	protected $card;
	/**
	 * @var string
	 */
	protected $currencyIso3Code;


	public abstract function tokenize();
	public abstract function authorize($pAmount);
	/**
	 * one kind of purchase, it may called as taggedPurchase. basically it occur after authorize. it capture (/purchase) only authorized amount through authorized token
	 * @param string $pAmount
	 */
	public abstract function capture($pAmount=NULL);
	public abstract function void($pAmount=NULL);
	public abstract function purchase($pAmount);
	/**
	 * @see refund()
	 * @param string $pAmount
	 */
	public abstract function taggedRefund($pAmount=NULL);
	/**
	 * always on captured or purchased or authorized amount.
	 * it is an alias of taggedRefund
	 * @param string $pAmount
	 */
	public abstract function refund($pAmount=NULL);


	public abstract function setTokenizeResponse(ResponseInterface $pResponse);
	public abstract function setAuthorizeResponse(ResponseInterface $pResponse);
	public abstract function setCaptureResponse(ResponseInterface $pResponse);
	public abstract function setVoidResponse(ResponseInterface $pResponse);
	public abstract function setPurchaseResponse(ResponseInterface $pResponse);
	public abstract function setRefundResponse(ResponseInterface $pResponse);
	public abstract function setTaggedRefundResponse(ResponseInterface $pResponse);

	/**
	 * @param string $pGwName - name of gateway
	 */
	public static function factory($pGwName){
		$bridge['_'.md5('Payeezy\Direct')]='PayeezyDirectBridge';
		$fGwName='_'.md5($pGwName);
		if(!array_key_exists($fGwName, $bridge)) throw new \Exception('gateway not supported by bridge!');
		$clsName=__NAMESPACE__.'\\'.$bridge[$fGwName];
		return new $clsName();
	}
	public function setGwOptions(array $pOptions){
		$this->gwOptions=$pOptions;
		return $this;
	}
	protected function getGwOptions(){
		if(!isset($this->gwOptions)) return array();
		return $this->gwOptions;
	}
	public function setCurrencyIso3Code($pIso3Code){
		$this->currencyIso3Code=$pIso3Code;
		return $this;
	}
	/**
	 * @param string $pFirstName
	 * @param string $pLastName
	 * @param string|int $pCardNumber
	 * @param string|int $pExpireMonth - two digit
	 * @param string|int $pExpireYear - four digit
	 * @param string|int $pCVV
	 * @return \Omnipay\OmnipayBridge\AbstractBridge
	 */
	public function setCard($pFirstName, $pLastName=NULL, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV=NULL){
		$this->card=self::getCreditCardObject($pFirstName, $pLastName, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV);
		$this->card->validate();
		return $this;
	}
	public function unsetCard(){
		if(isset($this->card)) unset($this->card);
		return true;
	}
	/**
	 * @param string $pFirstName
	 * @param string $pLastName
	 * @param string|int $pCardNumber
	 * @param string|int $pExpireMonth - two digit
	 * @param string|int $pExpireYear - four digit
	 * @param string|int $pCVV
	 * @return \Omnipay\Common\CreditCard
	 */
	public static function getCreditCardObject($pFirstName, $pLastName=NULL, $pCardNumber, $pExpireMonth, $pExpireYear, $pCVV){
		$cc=new CreditCard();
		#$cc->setTitle($pCardName);
		$cardHolderName=trim($pFirstName.' '.$pLastName);
		$cc->setFirstName($pFirstName);
		if(!empty($pLastName)) $cc->setLastName($pLastName);
		$cc->setName($cardHolderName);
		$cc->setNumber($pCardNumber);
		$cc->setExpiryMonth($pExpireMonth);
		$cc->setExpiryYear($pExpireYear);
		if(!empty($pCVV)) $cc->setCvv($pCVV);
		return $cc;
	}
	/**
	 * @return ResponseInterface
	 */
	abstract public function getLastResponseObject();
	private $dataCache;
	private $response2save;
	private $data2use;
	protected final function getCacheData(){
		if(!isset($this->dataCache)) return array();
		return $this->dataCache;
	}
	protected final function setResponseToSave(array $pResponse){
		#echo '<div>saved response - @'.__LINE__.': '.__FILE__.'<pre>'.print_r($pResponse, true);
		$this->dataCache=$this->response2save=$pResponse;
		return $this;
	}
	protected final function isResponseFound(){ return (isset($this->response2save) && is_array($this->response2save) && !empty($this->response2save)); }
	abstract public function isTransactionOk($pReturnAnySuccessStatus=false);
	/**
	 * @return string
	 */
	abstract public function getPaymentMethod();
	/**
	 * @return string
	 */
	abstract public function getUsingCardOrAccountName();
	abstract public function getSuccessfulTransactionAmount();
	/**
	 * @return string
	 */
	public final function getResponseToSave(){ return base64_encode(serialize($this->response2save)); }
	/**
	 * @param string $pData
	 * @return \Omnipay\OmnipayBridge\AbstractBridge
	 */
	public final function setDataToUse($pData){
		#echo '<div>data reseted strlen($pData): '.strlen($pData).' @'.__LINE__.': '.__FILE__.'</div>';
		$this->dataCache=$this->data2use=unserialize(base64_decode($pData));
		return $this;
	}
	abstract public function getAmountFromTransactionData();
	abstract public function isCardExistAtResponse();
	/**
	 * @param string $pResponseData [optional, base64 encoded serialized value]
	 * @return \Omnipay\OmnipayBridge\RespondedCreditCard
	 */
	abstract public function getCardFromResponse($pResponseData=NULL);
	protected final function getDataToUse(){
		if(!isset($this->data2use)) return array();
		return $this->data2use;
	}
}

