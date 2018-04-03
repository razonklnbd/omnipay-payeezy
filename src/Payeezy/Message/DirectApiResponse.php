<?php

namespace Omnipay\Payeezy\Message;

use Omnipay\Common\Message\AbstractResponse;
use Omnipay\Common\Message\RequestInterface;
use Omnipay\Common\Exception\InvalidResponseException;
use Omnipay\Common\Message\ResponseInterface;

/**
 * Payeezy Direct API Response
 */
class DirectApiResponse extends AbstractResponse {

	#private $response;
	private $objData;

	public function __construct(RequestInterface $request, $pResponse){
        $this->request = $request;
        #$this->response=$pResponse;
        $this->objData=$pResponse;
        $this->data=json_decode(strval($this->objData), true);
        #if($this->data['transaction_type']=='void') die('$data<pre>'.print_r($this->data, true).'<br />@'.__LINE__.': '.__FILE__);
        #else echo('$data<pre>'.print_r($this->data, true).'<br />@'.__LINE__.': '.__FILE__);
        #$this->data = explode('|,|', substr($data, 1, -1));
        if (count($this->data) < 1) {
        	#die('$this->data<pre>'.print_r($this->data, true));
            throw new InvalidResponseException();
        }
    }
    private $badReqErrMsg;
    public function setResponseErrorMessage($pErrMsg){
    	$this->badReqErrMsg=$pErrMsg;
    	return $this;
    }
    public function getResponseErrorMessage(){ return $this->badReqErrMsg; }
    public function isResponseErrorExists(){ return !empty($this->badReqErrMsg); }
    public function getResponseErrorArray(){
    	$rtrn=array();
    	if($this->isResponseErrorExists() && isset($this->data) && is_array($this->data) && array_key_exists('Error', $this->data)){
    		if(array_key_exists('messages', $this->data['Error'])){
	    		foreach($this->data['Error']['messages'] as $msg) $rtrn[$msg['code']]=(array_key_exists($msg['code'], $rtrn)?$rtrn[$msg['code']].', ':'').$msg['description'];
    		}
    	}
    	return $rtrn;
    }
    public function getResponseErrorData(){
    	if($this->isResponseErrorExists()) return $this->data;
    	return array();
    }
    public function getData(){
    	if($this->isResponseErrorExists()) return array();
    	return parent::getData();
    }
    /**
     * https://support.payeezy.com/hc/en-us/articles/203730509-First-Data-Payeezy-Gateway-Bank-Response-Codes
     * posisble error handling
     * card not accepted/invalid card
     * card has not sufficent balance for transaction
     * card expired
     * 
     * 201 - invalid card data
     * 202 - invalid input of amount
     * 
     * 302 - Credit Floor, Insufficient funds
     * 509 - Over the limit, Exceeds withdrawal or activity amount limit
     * 510 - Over Limit Frequency, Exceeds withdrawal or activity count limit
     * 521 - Insufficient funds, Insufficient funds/over credit limit
     * 
     * 522 - Card is expired, Card has expired
     * 903 - Invalid Expiration, Invalid or expired expiration date
     * 
     * 304 - card Not On File, No card record, or invalid/nonexistent to account specified
     * 
	 */
	public function isCardInvalid(){
		if($this->isSuccessful()) return false;
		$code=$this->getCode();
		return ('201'==$code || '304'==$code);
	}
	public function isAmountInvalid(){
		if($this->isSuccessful()) return false;
		$code=$this->getCode();
		return ('202'==$code);
	}
	public function isAmountDeclined(){
		if($this->isSuccessful()) return false;
		$code=$this->getCode();
		return ('302'==$code || '209'==$code || '510'==$code || '521'==$code);
	}
	public function isCardExpired(){
		if($this->isSuccessful()) return false;
		$code=$this->getCode();
		return ('card_expired'==$code || '522'==$code || '903'==$code);
	}

    public function isSuccessful(){
    	if($this->isResponseErrorExists()) return false;
    	$errCode=$this->getStatusCode();
    	return ('success' === $errCode || 'approved' === $errCode);
    }
    private function getStatusCode(){
    	$rtrn=(array_key_exists('transaction_status', $this->data)?$this->data['transaction_status']:(array_key_exists('validation_status', $this->data)?$this->data['validation_status']:(array_key_exists('status', $this->data)?$this->data['status']:'')));
    	if(empty($rtrn)){
    		$errMsg=$this->getResponseErrorArray();
    		if(!empty($errMsg)){
    			reset($errMsg);
    			return key($errMsg);
    		}
    		return 'unknown';
    	}
    	return $rtrn;
    }
    public function getCode(){ return (array_key_exists('bank_resp_code', $this->data)?$this->data['bank_resp_code']:(array_key_exists('gateway_resp_code', $this->data)?$this->data['gateway_resp_code']:$this->getStatusCode())); }
    public function getMessage(){
    	if(!$this->isSuccessful()){
    		$errMsg=$this->getResponseErrorArray();
    		if(!empty($errMsg)) return implode(', ', $errMsg);
    	}
    	#die('$this->data @'.__LINE__.': '.__FILE__.'<pre>'.print_r($this->data, true));
    	return (array_key_exists('bank_message', $this->data)?$this->data['bank_message']:(array_key_exists('gateway_message', $this->data)?$this->data['gateway_message']:(array_key_exists('status', $this->data)?$this->data['status']:'UNDEFINED')));
    }
    public function getTransactionReference(){ return (array_key_exists('transaction_tag', $this->data)?$this->data['transaction_tag']:''); }
    public function getTransactionId(){ return (array_key_exists('transaction_id', $this->data)?$this->data['transaction_id']:''); }

}
