<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Abstract Request
 */

use Omnipay\Common\Message\AbstractRequest as CommonAbstractRequest;
use Guzzle\Http\Curl\CurlHandle;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Exception\BadResponseException;

abstract class AbstractRequest extends CommonAbstractRequest {
	/**
	 * Custom field name to send the transaction ID to the notify handler.
	 */
	const TRANSACTION_ID_PARAM = 'omnipay_transaction_id';

	# transaction type
	const TT_AUTH='authorize';
	const TT_PURCHASE='purchase';
	const TT_SPLIT='split';
	const TT_CAPTURE='capture';
	const TT_REFUND='refund';
	const TT_TAGGED_REFUND='refund';
	const TT_VOID='void';

	protected abstract function getTransactionType();

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

    public function isDeveloperMode(){ return true===boolval($this->getDeveloperMode()); }
	public function getDeveloperMode(){ return $this->getParameter('developerMode'); }
	public function setDeveloperMode($value){ return $this->setParameter('developerMode', $value); }
    public function isLiveMode(){ return false===$this->isDeveloperMode(); }
	public function getLiveBaseEndpoint(){ return $this->getParameter('liveBaseEndpoint'); }
	public function setLiveBaseEndpoint($value){ return $this->setParameter('liveBaseEndpoint', $value); }
	public function getLiveTokenEndpoint(){ return $this->getParameter('liveTokenEndpoint'); }
	public function setLiveTokenEndpoint($value){ return $this->setParameter('liveTokenEndpoint', $value); }
	public function getDevBaseEndpoint(){ return $this->getParameter('devBaseEndpoint'); }
	public function setDevBaseEndpoint($value){ return $this->setParameter('devBaseEndpoint', $value); }
	public function getDevTokenEndpoint(){ return $this->getParameter('devTokenEndpoint'); }
	public function setDevTokenEndpoint($value){ return $this->setParameter('devTokenEndpoint', $value); }
	
public function getCustomerId(){ return $this->getParameter('customerId'); }
public function setCustomerId($value){ return $this->setParameter('customerId', $value); }
public function getHashSecret(){ return $this->getParameter('hashSecret'); }
public function setHashSecret($value){ return $this->setParameter('hashSecret', $value); }


	/**
	 * Payeezy
	 * HMAC Authentication
	 * 
	 * @param string $payload - JSON string
	 * @return array
	 */
	private function hmacAuthorizationToken($payload){
		$nonce = strval(hexdec(bin2hex(openssl_random_pseudo_bytes(4, $cstore))));
		$timestamp = strval(time()*1000); //time stamp in milli seconds
		$data = $this->getApiKey() . $nonce . $timestamp . $this->getMerchantToken() . $payload;
		$hmac = hash_hmac ( 'sha256' , $data , $this->getApiSecret(), false );    // HMAC Hash in hex
		$authorization = base64_encode($hmac);
		#die('$json_encode($payload, JSON_FORCE_OBJECT): '.json_encode($payload, JSON_FORCE_OBJECT).' @'.__LINE__.': '.__FILE__);
		return array(
				#'Content-Type'=>'application/json',
				#'apikey'=>$this->getApiKey(),
				#'token'=>$this->getMerchantToken(),
				'Authorization' => $authorization,
				'nonce' => $nonce,
				'timestamp' => $timestamp,
			);
	}


/*
	protected function getBillingData(){
        $data = array();
        $data['x_amount'] = $this->getAmount();

        // This is deprecated. The invoice number field is reserved for the invoice number.
        $data['x_invoice_num'] = $this->getTransactionId();

        // A custom field can be used to pass over the merchant site transaction ID.
        $data[static::TRANSACTION_ID_PARAM] = $this->getTransactionId();

        $data['x_description'] = $this->getDescription();

        if ($card = $this->getCard()) {
            // customer billing details
            $data['x_first_name'] = $card->getBillingFirstName();
            $data['x_last_name'] = $card->getBillingLastName();
            $data['x_company'] = $card->getBillingCompany();
            $data['x_address'] = trim(
                $card->getBillingAddress1()." \n".
                $card->getBillingAddress2()
            );
            $data['x_city'] = $card->getBillingCity();
            $data['x_state'] = $card->getBillingState();
            $data['x_zip'] = $card->getBillingPostcode();
            $data['x_country'] = $card->getBillingCountry();
            $data['x_phone'] = $card->getBillingPhone();
            $data['x_email'] = $card->getEmail();

            // customer shipping details
            $data['x_ship_to_first_name'] = $card->getShippingFirstName();
            $data['x_ship_to_last_name'] = $card->getShippingLastName();
            $data['x_ship_to_company'] = $card->getShippingCompany();
            $data['x_ship_to_address'] = trim(
                $card->getShippingAddress1()." \n".
                $card->getShippingAddress2()
            );
            $data['x_ship_to_city'] = $card->getShippingCity();
            $data['x_ship_to_state'] = $card->getShippingState();
            $data['x_ship_to_zip'] = $card->getShippingPostcode();
            $data['x_ship_to_country'] = $card->getShippingCountry();
        }

        return $data;
    }
*/


	private $fixOptionFl;
	private function fixCurlOptions($pForceFix=false){
		if(!isset($this->fixOptionFl) || true==$pForceFix){
			$config=$this->httpClient->getConfig();
			$curlOptions=$this->httpClient->getConfig(Client::CURL_OPTIONS);
			#die('$curlOptions: '.var_export($curlOptions, true).' @'.__LINE__.': '.__FILE__);
			$curlOptions[CurlHandle::BODY_AS_STRING]=true;
			$curlOptions[CURLOPT_POST]=true;
			$curlOptions[CURLOPT_SSL_VERIFYPEER]=false;
			$fHdrs=array(
					'Content-Type:application/json',
					'apikey:'.$this->getApiKey(),
					'token:'.$this->getMerchantToken(),
				);
			#foreach($thsHdrs as $k=>$hdr) $fHdrs[]=$k.':'.$hdr;
			$crlHttpHeadrs=(array_key_exists(CURLOPT_HTTPHEADER, $curlOptions)?array_merge_recursive($curlOptions[CURLOPT_HTTPHEADER], $fHdrs):$fHdrs);
			#die('$crlHttpHeadrs -<pre>'.print_r($crlHttpHeadrs, true).'<br />@'.__LINE__.': '.__FILE__);
			$curlOptions[CURLOPT_HTTPHEADER]=$crlHttpHeadrs;
			$config->set(Client::CURL_OPTIONS, $curlOptions);
			$this->httpClient->setConfig($config);
			#die('config done! @'.__LINE__.': '.__FILE__);
			$this->fixOptionFl=true;
		}
		return $this;
	}

	private function getDirectApiResponseInstance($pRspns){
		try{
			return new DirectApiResponse($this, $pRspns);
		}catch(\Omnipay\Common\Exception\InvalidResponseException $ire){
			die('No response detected! @'.__LINE__.': '.__FILE__.'<pre>'.print_r($pRspns));
		}
	}
	private $sentData;
	public function sendData($data){
		$this->sentData=$fData=json_encode($data, JSON_FORCE_OBJECT);
		#die('$$fData: <pre>'.var_export(array($fData, $data), true).' @'.__LINE__.': '.__FILE__);
		$this->fixCurlOptions();
		try{
			$httpResponse = $this->httpClient->post($this->getEndpoint(), $this->hmacAuthorizationToken($fData), $fData)->send();
            #die('@'.__LINE__.': '.__FILE__.' | $httpResponse->getBody()<pre>'.print_r($httpResponse->getBody(), true));
			$this->response = $this->getDirectApiResponseInstance($httpResponse->getBody()); # new DirectApiResponse($this, $httpResponse->getBody());
		}catch (ClientErrorResponseException $cere){
			$rspns=$cere->getResponse();
			#die('response cautgh here! @'.__LINE__.': '.__FILE__);
			$this->response = $this->getDirectApiResponseInstance($rspns->getBody(true)); # new DirectApiResponse($this, $rspns->getBody(true));
			$this->response->setResponseErrorMessage($cere->getMessage());
		}catch (BadResponseException $bre){
			$rspns=$bre->getResponse();
			#die('response cautgh here! @'.__LINE__.': '.__FILE__);
			$this->response = $this->getDirectApiResponseInstance($rspns->getBody(true)); # new DirectApiResponse($this, $rspns->getBody(true));
			$this->response->setResponseErrorMessage($bre->getMessage());
		}
		#echo 'var_export($httpResponse, true): '.$fData.'<pre>'.var_export($this->response, true); die('@'.__LINE__.': '.__FILE__);
		return $this->response;
	}

	abstract protected function getEndpoint();

	public function send() {
		if($this->getTransactionType()==self::TT_VOID || $this->getTransactionType()==self::TT_REFUND || $this->getTransactionType()==self::TT_TAGGED_REFUND){
			if($this->getMaxTxAmount()<=0) throw new \Exception('maximum tx amount must be positive value! current max tx amount: '.$this->getMaxTxAmount());
			if($this->getAmount()>$this->getMaxTxAmount()) throw new \Exception('elligible tx amount - "'.$this->getMaxTxAmount().'", but required tx amount - '.$this->getAmount());
			if($this->getTransactionType()==self::TT_VOID && floatval($this->getAmount())!=floatval($this->getMaxTxAmount())) throw new \Exception('only full amount void supported, please');
		}
		return parent::send();
	}

}
