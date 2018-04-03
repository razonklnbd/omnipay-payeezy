<?php
namespace Omnipay\OmnipayBridge;

#use Omnipay\Payeezy\Message\DirectApiResponse;
use Omnipay\Common\Message\ResponseInterface as DirectApiResponse;
use Omnipay\Payeezy\DirectGateway;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Omnipay\Payeezy\Message\DirectApiAbstractRequest;

class PayeezyDirectBridge extends AbstractBridge {

	private $defaultParams;
	private function getDefaultParameters($pIdxParam=NULL, $pDefaultVal=NULL){
		if(!isset($this->defaultParams)){
			$dGateway=new DirectGateway();
			$this->defaultParams=$dGateway->getDefaultParameters();
		}
		if(empty($pIdxParam)) return $this->defaultParams;
		if(array_key_exists($pIdxParam, $this->defaultParams)) return $this->defaultParams[$pIdxParam];
		return $pDefaultVal;
	}
	private function getOptions($pIdxParam=NULL, $pDefaultVal=NULL){
		$fGwOptions=$this->getGwOptions();
		if(empty($pIdxParam)) return $fGwOptions;
		if(array_key_exists($pIdxParam, $fGwOptions)) return $fGwOptions[$pIdxParam];
		return $this->getDefaultParameters($pIdxParam, $pDefaultVal);
	}
	private function isTransarmorEnabled(){ return $this->getOptions('transarmorEnabled', false); }

	public function isCardExistAtResponse(){
		$data=$this->getDataToUse();
		if(!empty($data)) return array_key_exists('card', $data);
		return false;
	}
	/**
	 * @see \Omnipay\OmnipayBridge\AbstractBridge::getCardFromResponse()
	 */
	public function getCardFromResponse($pResponseData=NULL){
		$fResponseData=array();
		if(!empty($pResponseData)) $fResponseData=base64_decode(serialize($pResponseData));
		if(empty($fResponseData)) $fResponseData=$this->getCacheData();
		if(empty($fResponseData) && $this->isCardExistAtResponse()) $fResponseData=$this->getDataToUse();
		if(!empty($fResponseData)){
			if(array_key_exists('card', $fResponseData)) return $this->getCardFromArray($fResponseData['card'], $fResponseData);
			if(array_key_exists('token', $fResponseData)) return $this->getCardFromArray($fResponseData['token'], $fResponseData);
		}
		return new RespondedCreditCard();
	}
	public function isTransactionOk($pReturnAnySuccessStatus=false){
		if($this->isResponseFound()){
			$data=$this->getCacheData();
			#echo('$data<pre>'.print_r($data, true).' @'.__LINE__.': '.__FILE__);
			if(array_key_exists('transaction_status', $data)) return ($data['transaction_status']=='approved');
			if(true==$pReturnAnySuccessStatus){
				if(array_key_exists('status', $data)) return ($data['status']=='success');
			}
		} #else die('respond not found! @'.__LINE__.': '.__FILE__);
		return false;
	}
	public function getSuccessfulTransactionAmount(){
		if($this->isResponseFound()){
			$data=$this->getCacheData();
			#die('$data! @'.__LINE__.': '.__FILE__.'<pre>'.print_r($data, true));
			if(array_key_exists('amount', $data) && floatval($data['amount'])!=0) return self::amnt2rtrn($data['amount']);
		}
		return '0.00';
	}
	private static function amnt2rtrn($pAmnt){ return substr($pAmnt, 0, (strlen($pAmnt)-2)).'.'.substr($pAmnt, (strlen($pAmnt)-2)); }
	private function getCardFromArray(array $pCardInfoArray=array(), array $pResponseArray=array()){
		$c2r=new RespondedCreditCard();
		if(!empty($pResponseArray)) $c2r->setCardResponseData(base64_encode(serialize($pResponseArray)));
		if(!empty($pCardInfoArray)){
			#  [type] => visa [cardholder_name] => Jhon Smith [card_number] => 8291 [exp_date] => 1030
			$c2r->setName($pCardInfoArray['cardholder_name']);
			if(array_key_exists('card_number', $pCardInfoArray)) $c2r->setNumber($pCardInfoArray['card_number']);
			$crdType=DirectApiAbstractRequest::getOmniCardType($pCardInfoArray['type']);
			#$c2r->setCardTypeIdx($crdType);
			$c2r->setCardType($crdType);
			$c2r->setExpiryMonth(substr($pCardInfoArray['exp_date'], 0, 2));
			$c2r->setExpiryYear(substr($pCardInfoArray['exp_date'], 2));
		}
		return $c2r;
	}
	/**
	 * @return string
	 * {@inheritDoc}
	 * @see \Omnipay\OmnipayBridge\AbstractBridge::getUsingCardOrAccountName()
	 */
	public function getUsingCardOrAccountName(){
		$data=$this->getCacheData();
		if(empty($data) && $this->isCardExistAtResponse()) $data=$this->getDataToUse();
		if(!empty($data) && array_key_exists('card', $data)){
			$card=$this->getCardFromArray($data['card']);
			return $card->getCardType();
		}
		if(!empty($data) && array_key_exists('token', $data) && array_key_exists('token_type', $data['token']) && 'transarmor'==$data['token']['token_type'] && array_key_exists('token_data', $data['token']) && array_key_exists('type', $data['token']['token_data'])) return $data['token']['token_data']['type'];
		return 'n/a';
	}
	public function getLastResponseObject(){
		if(!isset($this->lastResponse)) return new \Exception('No Response from Gateway!');
		return $this->lastResponse;
	}
	private $lastTransactionData;
	private $lastResponse;
	private function setPayeezyBaseResponse(DirectApiResponse $pResponse, $pHandleAmount=false){
		$this->lastResponse=$pResponse;
		if($this->lastResponse->isSuccessful()){
			$d2s=$pResponse->getData();
			if(true==$pHandleAmount){
				if(!isset($this->tmpAmount2Save)) $this->tmpAmount2Save='0.00';
				$d2s['wemproData']['amount']=$this->tmpAmount2Save;
			}
			$this->lastTransactionData=$d2s;
			#die('$d2s<pre>'.print_r($d2s, true));
			$this->setResponseToSave($d2s);
		}else $this->setResponseToSave(array());
		return $this->lastResponse;
	}
	/**
	 * @return string
	 * {@inheritDoc}
	 * @see \Omnipay\OmnipayBridge\AbstractBridge::getPaymentMethod()
	 */
	public function getPaymentMethod(){
		$data=$this->getCacheData();
		if(array_key_exists('method', $data)) return $data['method'];
		return 'N/A';
	}
	private function authorizeOrPurchaseArray($pAmount){
		$rtrn['amount']=$this->getPayeezyAmount($pAmount);
		if($this->isTransarmorEnabled()){
			if(!isset($this->card)){
				$data=$this->getDataToUse();
				if(empty($data)) throw new \Exception('you must set token data to proceed otherwise disable transarmor from config!');
				if(!array_key_exists('token', $data)) throw new \Exception('"token" not found from data you provided to use!');
				$rtrn['transarmorTokenArray']=$data['token'];
			}else $rtrn['card']=$this->card;
		}else{
			if(!isset($this->card)) throw new \Exception('you must set card before getting token from gateway!');
			$rtrn['card']=$this->card;
		}
		#die('$rtrn<pre>'.print_r($rtrn, true));
		return $rtrn;
	}
	private $tmpAmount2Save;
	private function getPayeezyAmount($pAmount=NULL){
		$data=$this->getDataToUse();
		if(empty($pAmount) && !empty($data)){
			$pAmount=$data['amount'];
			if(array_key_exists('wemproData', $data) && array_key_exists('amount', $data['wemproData'])) $pAmount=$data['wemproData']['amount'];
			#die('$pAmount: '.$pAmount.' @'.__LINE__.': '.__FILE__);
		}
		if(isset($pAmount)) $this->tmpAmount2Save=$pAmount;
		#die('$pAmount: '.$pAmount.' @'.__LINE__.': '.__FILE__);
		return $pAmount;
	}
	public function getAmountFromTransactionData(){
		$pAmount=0;
		$data=$this->getDataToUse();
		if(!empty($data)){
			if(array_key_exists('amount', $data) && floatval($data['amount'])!=0) $pAmount=self::amnt2rtrn($data['amount']);
			if(array_key_exists('wemproData', $data) && array_key_exists('amount', $data['wemproData'])) $pAmount=$data['wemproData']['amount'];
		}
		#die('$pAmount: '.$this->tmpAmount2Save.' @'.__LINE__.': '.__FILE__.'<pre>'.print_r($data, true));
		return $pAmount;
	}
	public function setTokenizeResponse(DirectApiResponse $pResponse){ return $this->setPayeezyBaseResponse($pResponse); }
	public function tokenize(){
		if(!isset($this->card)) throw new \Exception('you must set card before getting token from gateway!');
		return array('card' => $this->card);
	}
	public function setAuthorizeResponse(DirectApiResponse $pResponse){ return $this->setPayeezyBaseResponse($pResponse, true); }
	public function authorize($pAmount){ return $this->authorizeOrPurchaseArray($pAmount); }
	public function setCaptureResponse(DirectApiResponse $pResponse){ return $this->setPayeezyBaseResponse($pResponse, true); }
	public function capture($pAmount=NULL){
		#die('$pAmount: '.$pAmount.' | $this->getPayeezyAmount($pAmount): '.$this->getPayeezyAmount($pAmount).' @'.__LINE__.': '.__FILE__);
		$data=$this->getDataToUse();
		return array(
				'maxTxAmount'=>$this->getAmountFromTransactionData(),
				'amount'=>$this->getPayeezyAmount($pAmount),
				'transactionId' => $data['transaction_id'],
				'transactionReference' => $data['transaction_tag'],
		);
	}
	public function setVoidResponse(DirectApiResponse $pResponse){ return $this->setPayeezyBaseResponse($pResponse, true); }
	public function void($pAmount=NULL){
		$data=$this->getDataToUse();
		if(!array_key_exists('transaction_id', $data) || !array_key_exists('transaction_tag', $data)) throw new \Exception('not vaoidable transaction!');
		#die('$this->getPayeezyAmount($pAmount): '.$this->getPayeezyAmount($pAmount).' | $pAmount: '.$pAmount.' @'.__LINE__.': '.__FILE__);
		return array(
				'maxTxAmount'=>$this->getAmountFromTransactionData(),
				'amount'=>$this->getPayeezyAmount($pAmount),
				'transactionId' => $data['transaction_id'],
				'transactionReference' => $data['transaction_tag'],
			);
	}
	public function setPurchaseResponse(DirectApiResponse $pResponse){ return $this->setPayeezyBaseResponse($pResponse, true); }
	public function purchase($pAmount){ return $this->authorizeOrPurchaseArray($pAmount); }
	public function setRefundResponse(DirectApiResponse $pResponse){ return $this->setPayeezyBaseResponse($pResponse, true); }
	public function refund($pAmount=NULL){
		$data=$this->getDataToUse();
		return array(
				'maxTxAmount'=>$this->getAmountFromTransactionData(),
				'amount'=>$this->getPayeezyAmount($pAmount),
				'transactionId' => $data['transaction_id'],
				'transactionReference' => $data['transaction_tag'],
		);
	}
	public function setTaggedRefundResponse(DirectApiResponse $pResponse){ return $this->setRefundResponse($pResponse); }
	public function taggedRefund($pAmount=NULL){ return $this->refund($pAmount); }

}

