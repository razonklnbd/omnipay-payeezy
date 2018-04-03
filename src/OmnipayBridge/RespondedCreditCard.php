<?php
namespace Omnipay\OmnipayBridge;

use Omnipay\Common\CreditCard;
use Omnipay\OmnipayWrapper;
class RespondedCreditCard extends CreditCard {

	const FRMIDX__FN='firstName';
	const FRMIDX__LN='lastName';
	const FRMIDX__CN='number';
	const FRMIDX__EM='expiryMonth';
	const FRMIDX__EY='expiryYear';
	const FRMIDX__SN='cvv';
	const FRMIDX__CT='cardType';
	const FRMIDX__TD='cardTokenData';

	public function getArrayCopy(){ return $this->getParameters(); }
	public function getDataCopy(){ return base64_encode(serialize($this->getArrayCopy())); }
	public function exchangeArray(array $pArray){
		foreach($pArray as $key=>$value) $this->setParameter($key, $value);
		if(array_key_exists(self::FRMIDX__FN, $pArray)) $this->setFirstName($pArray[self::FRMIDX__FN]);
		if(array_key_exists(self::FRMIDX__LN, $pArray)) $this->setLastName($pArray[self::FRMIDX__LN]);
		if(array_key_exists(self::FRMIDX__CN, $pArray)) $this->setNumber($pArray[self::FRMIDX__CN]);
		if(array_key_exists(self::FRMIDX__EM, $pArray)) $this->setExpiryMonth($pArray[self::FRMIDX__EM]);
		if(array_key_exists(self::FRMIDX__EY, $pArray)) $this->setExpiryYear($pArray[self::FRMIDX__EY]);
		if(array_key_exists(self::FRMIDX__SN, $pArray)) $this->setCvv($pArray[self::FRMIDX__SN]);
		return $this;
	}
	public function exchangeData($pData){
		$this->exchangeArray(unserialize(base64_decode($pData)));
		return $this;
	}
	public static function get($pData=NULL){
		$c2r=new static();
		if(!empty($pData)){
			if(is_array($pData)) $c2r->exchangeArray($pData);
			elseif(is_string($pData)) $c2r->exchangeData($pData);
		}
		return $c2r;
	}

	/**
	 * @var \Omnipay\Common\CreditCard
	 
	private $fullCardInstance;
	/**
	 * @return boolean
	 
	public function isFullCardExists(){ return (array_key_exists('fullCardInstance', $this->data) && $this->data['fullCardInstance'] instanceof CreditCard); }
	public function setFullCard(CreditCard $pCC){
		$this->fullCardInstance=$pCC;
		$this->data['fullCardInstance']=base64_encode(serialize($this->fullCardInstance));
		return $this;
	}
	/**
	 * @throws \Exception
	 * @return \Omnipay\Common\CreditCard
	 
	public function getFullCard(){
		if($this->isFullCardExists()){
			if(empty($this->fullCardInstance)) $this->fullCardInstance=unserialize(base64_decode($this->data['fullCardInstance']));
			return $this->fullCardInstance;
		}
		throw new \Exception('no full card found, please set full card first!');
	}
	*/
	public function setCardResponseData($pCardTokenData){
		$this->setParameter(self::FRMIDX__TD, $pCardTokenData);
		return $this;
	}
	public function getCardResponseData(){ return $this->getParameter(self::FRMIDX__TD); }
	#  [type] => visa [cardholder_name] => Jhon Smith [card_number] => 8291 [exp_date] => 1030
	public function setCardType($pCardType){
		$this->setParameter(self::FRMIDX__CT, $pCardType);
		return $this;
	}
	public function getCardType(){ return $this->getParameter(self::FRMIDX__CT); }
	private $supportedBrands;
	private function getSupportedBrandsArray(){
		if(!isset($this->supportedBrands)) $this->supportedBrands=OmnipayWrapper::getSupportedBrands();
		return $this->supportedBrands;
	}
	public function getCartTitle(){
		$crdType=$this->getCardType();
		$supportedBrands=$this->getSupportedBrandsArray();
		return (array_key_exists($crdType, $supportedBrands)?$supportedBrands[$crdType]:'UNKNOWN');
	}
	public function setAdditionalData($pDataIdx, $pValue){
		$this->setParameter(self::getAdditionalDataIdx($pDataIdx), $pValue);
		return $this;
	}
	public function getAdditionalData($pDataIdx){ return $this->getParameter(self::getAdditionalDataIdx($pDataIdx)); }
	private static function getAdditionalDataIdx($pDataIdx){
		$rtrn='additional_'.strval($pDataIdx);
		if(strlen($rtrn)>32) $rtrn=md5($rtrn);
		return '_'.$rtrn;
	}
/*	public function setCardHolderName($pName){
		$this->$this->data['cardHolderName']=$pName;
		return $this;
	}
	public function getCardHolderName(){ return $this->data['cardHolderName']; }
	public function setCardNumber($pNumber){
		$this->$this->data['cardNumber']=$pNumber;
		return $this;
	}
	public function getCardNumber(){ return $this->data['cardNumber']; }
	public function setCardLastFourDigit($pNumber){
		$this->$this->data['cardLastFourDigit']=$pNumber;
		return $this;
	}
	public function getCardLastFourDigit(){ return $this->data['cardLastFourDigit']; }
	public function setExpMonth($pExpMonth){
		$this->$this->data['cardExpMonth']=$pExpMonth;
		return $this;
	}
	public function getExpMonth(){ return $this->data['cardExpMonth']; }
	public function setExpYear($pExpYear){
		$this->$this->data['cardExpYear']=$pExpYear;
		return $this;
	}
	public function getExpYear(){ return $this->data['cardExpYear']; }*/


	public function isNotValid(){
		try{
			parent::validate();
			return false;
		}catch (\Exception $e){
			return true;
		}
	}
	public function isValid(){ return !$this->isNotValid(); }
	public function setInformationAsOk(){
		$this->setParameter('informationFl', 'yes');
		return $this;
	}
	public function setInformationAsNotOk(){
		$this->setParameter('informationFl', '0');
		return $this;
	}
	public function isInformationSetDone(){
		$infoFl=$this->getParameter('informationFl');
		return ($infoFl=='yes');
	}

}

