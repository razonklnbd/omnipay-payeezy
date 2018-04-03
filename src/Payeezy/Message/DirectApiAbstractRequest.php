<?php

namespace Omnipay\Payeezy\Message;

use Omnipay\Common\CreditCard;
use Omnipay\Payeezy\DirectGateway;

/**
 * Payeezy Abstract Request
 */


abstract class DirectApiAbstractRequest extends AbstractRequest {

	#TODO: method implementation for refund, void | like (credit_card, token)


	private function getPayeezyTransactionId(){
		$tt=$this->getTransactionType();
		if(!empty($tt)){
			$fWithCC=($tt==self::TT_AUTH || $tt==self::TT_PURCHASE);
			if(false==$fWithCC){
				$this->validate('transactionId');
				return $this->getTransactionId();
			}
		}
		return 0;
	}


	/*
multi-use token, capture payload
{
  "merchant_ref": "Astonishing-Sale",
  "transaction_type": "capture",
  "method": "token",
  "amount": "100",
  "currency_code": "USD",
  "token": {
    "token_type": "transarmor",
    "token_data": {
      "type": "Enter card type",
      "value": "Enter Transarmor Token Value",
      "cardholder_name": "Enter card holder name",
      "exp_date": "Enter card expiration - MMYY"
    }
  }
}
trtoken required to create Payeezy / Transarmor tokens
	 */
	public function getData(){
		$tt=$this->getTransactionType();
		if(empty($tt)){
			$this->validate('card');
			$this->getCard()->validate();
			$data = array(
					'type'=> 'FDToken', # 'Transarmor', # 'FDToken',
					'auth'=> 'false',
					'ta_token'=> DirectGateway::TRANSARMOR_DEV_TOKEN,
					'credit_card'=> $this->getCreditCardArray(),
				);
			if($this->isTransarmorEnabled()){
                if($this->isLiveMode() && strlen($this->getTransarmorToken())>0) $data['ta_token']=$this->getTransarmorToken();
                #$data['type']='Transarmor';
                #throw new \Exception('need details!!!');
                #die('$this->getTransarmorToken(): '.$this->getTransarmorToken().' | $this->isLiveMode(): '.var_export($this->isLiveMode(), true).'<pre>'.print_r($data, true));
            }
		}else{ #
			$this->validate('amount');
			$fWithCC=($tt==self::TT_AUTH || $tt==self::TT_PURCHASE);
			if(false==$fWithCC){
				$this->validate('transactionReference');
				$fTrnsctnTag=$this->getTransactionReference();
				$trnsctnId=$this->getPayeezyTransactionId();
				if($tt==self::TT_SPLIT){
/*
Value: x/y, where x = current shipment & y = total number of shipments.
If y is not known, y = 99.
Ex: shipment1 - 01/99,
shipment2 - 02/99 
...
When sending final shipment, x = y
Ex: shipment3 - 03/03. 
*/
					$this->validate('splitX', 'splitY');
					$splitX=$this->getSplitX();
					$splitY=$this->getSplitY();
					if(intval($splitY)<=0 || intval($splitX)<=0) throw new \Exception('splitX = '.$splitX.' | splitY = '.$splitY.' | unable to split...');
					if(intval($splitX)>intval($splitY)) throw new \Exception('splitX ('.$splitX.') > splitY ('.$splitY.')');
					$fSplitShipment=$splitX.'/'.$splitY;
				}
			}
			$data = array(
					'transaction_type'=> $tt,
					'method'=> 'credit_card', # 'credit_card', 'token'
					'amount'=> str_replace('.', '', $this->getAmount()),
					'currency_code'=> $this->getCurrency(),
				);
			#if($tt==self::TT_VOID) die('$this->getAmount(): '.$this->getAmount().'<pre>'.var_export($data, true).' @'.__LINE__.': '.__FILE__);
			$dscrptn=$this->getDescription();
			#die('<pre>'.var_export($dscrptn, true).' @'.__LINE__.': '.__FILE__);
			if(!empty($dscrptn)) $data['merchant_ref']=$dscrptn;
			if(true==$fWithCC){
				if($this->isTransarmorEnabled()){
					$card=$this->getCard();
					if(is_null($card)){
						$data['method']='token';
						$data['token']=array(
								'token_type'=>'transarmor',
								'token_data'=>$this->getTransarmorTokenArray(),
						);
						#die('<pre>'.var_export($data, true).' @'.__LINE__.': '.__FILE__);
					}else $data['credit_card']=$this->getCreditCardArray();
				}else $data['credit_card']=$this->getCreditCardArray();
			}
			#die('<pre>'.var_export($data, true).' @'.__LINE__.': '.__FILE__);
			if(!empty($trnsctnId)) $data['transaction_id']=$trnsctnId;
			if(!empty($fTrnsctnTag)) $data['transaction_tag']=$fTrnsctnTag;
			if(!empty($fSplitShipment)) $data['split_shipment']=$fSplitShipment;
			#echo('payload:::::<br /><pre>'.var_export($data, true).' @'.__LINE__.': '.__FILE__);
		}
		#echo('payload:::::<br /><pre>'.var_export($data, true).' @'.__LINE__.': '.__FILE__);
		return $data;
	}
    const BRAND_VISA = 'Visa';
    const BRAND_MASTERCARD = 'Mastercard';
    const BRAND_DISCOVER = 'Discover';
    const BRAND_AMEX = 'American Express';
    const BRAND_DINERS_CLUB = 'Diners Club';
    const BRAND_JCB = 'JCB';
    const BRAND_SWITCH = 'switch';
    const BRAND_SOLO = 'solo';
    const BRAND_DANKORT = 'dankort';
    const BRAND_MAESTRO = 'maestro';
    const BRAND_FORBRUGSFORENINGEN = 'forbrugsforeningen';
    const BRAND_LASER = 'laser';
    private static function getPayeezyCardType($pOrgiType){
    	$map=array(
    			CreditCard::BRAND_VISA=>self::BRAND_VISA,
    			CreditCard::BRAND_MASTERCARD=>self::BRAND_MASTERCARD,
    			CreditCard::BRAND_DISCOVER=>self::BRAND_DISCOVER,
    			CreditCard::BRAND_AMEX=>self::BRAND_AMEX,
    			CreditCard::BRAND_DINERS_CLUB=>self::BRAND_DINERS_CLUB,
    			CreditCard::BRAND_JCB=>self::BRAND_JCB,
    			CreditCard::BRAND_SWITCH=>self::BRAND_SWITCH,
    			CreditCard::BRAND_SOLO=>self::BRAND_SOLO,
    			CreditCard::BRAND_DANKORT=>self::BRAND_DANKORT,
    			CreditCard::BRAND_MAESTRO=>self::BRAND_MAESTRO,
    			CreditCard::BRAND_FORBRUGSFORENINGEN=>self::BRAND_FORBRUGSFORENINGEN,
    			CreditCard::BRAND_LASER=>self::BRAND_LASER,
    			#CreditCard::BRAND=>self::BRAND,
	    	);
    	if(!array_key_exists($pOrgiType, $map)) throw new \Exception('Card not supported at WMS Level. Please consult with WMS developer. @'.__LINE__.': '.__FILE__);
    	return $map[$pOrgiType];
    }
    public static function getOmniCardType($pOrgiType){
    	$map=array(
    			self::BRAND_VISA=>CreditCard::BRAND_VISA,
    			self::BRAND_MASTERCARD=>CreditCard::BRAND_MASTERCARD,
    			self::BRAND_DISCOVER=>CreditCard::BRAND_DISCOVER,
    			self::BRAND_AMEX=>CreditCard::BRAND_AMEX,
    			self::BRAND_DINERS_CLUB=>CreditCard::BRAND_DINERS_CLUB,
    			self::BRAND_JCB=>CreditCard::BRAND_JCB,
    			self::BRAND_SWITCH=>CreditCard::BRAND_SWITCH,
    			self::BRAND_SOLO=>CreditCard::BRAND_SOLO,
    			self::BRAND_DANKORT=>CreditCard::BRAND_DANKORT,
    			self::BRAND_MAESTRO=>CreditCard::BRAND_MAESTRO,
    			self::BRAND_FORBRUGSFORENINGEN=>CreditCard::BRAND_FORBRUGSFORENINGEN,
    			self::BRAND_LASER=>CreditCard::BRAND_LASER,
    			#self::BRAND=>CreditCard::BRAND,
	    	);
    	if(!array_key_exists($pOrgiType, $map)) throw new \Exception('Card not supported at WMS Level. Please consult with WMS developer. @'.__LINE__.': '.__FILE__);
    	return $map[$pOrgiType];
    }
    protected function getCreditCardArray(){
		$this->validate('card');
		$card=$this->getCard();
		$card->validate();
		$expYr=$card->getExpiryYear();
		if(strlen($expYr)>2) $expYr=substr($expYr, 2);
		$expMn=$card->getExpiryMonth();
		if(strlen($expMn)<2) $expMn='0'.$expMn;
		$a2r=array(
				'type'=> self::getPayeezyCardType($card->getBrand()),
				'cardholder_name'=> $card->getFirstName().' '.$this->getCard()->getLastName(),
				'card_number'=> $card->getNumber(),
				'exp_date'=> $expMn.$expYr,
				#'cvv'=> $this->getCard()->getCvv(),
			);
		$cvv=$card->getCvv();
		if(!empty($cvv)) $a2r['cvv']=$cvv;
		#die('$card payload: @'.__LINE__.': '.__FILE__.'<pre>'.print_r($a2r, true));
		return $a2r;
	}
	private function getTransArmorTokenArrayX(){
		$this->validate('transarmor');
		$card=$this->getCard();
		$card->validate();
		$expYr=$card->getExpiryYear();
		if(strlen($expYr)>2) $expYr=substr($expYr, 2);
		$a2r=array(
				'type'=> $card->getBrand(),
				'cardholder_name'=> $card->getFirstName().' '.$this->getCard()->getLastName(),
				'card_number'=> $card->getNumber(),
				'exp_date'=> $card->getExpiryMonth().$expYr,
				#'cvv'=> $this->getCard()->getCvv(),
		);
		$cvv=$card->getCvv();
		if(!empty($cvv)) $a2r['cvv']=$cvv;
		return $a2r;
	}
	private function getDataX(){
		$this->validate('amount', 'card');
		$this->getCard()->validate();
		$data = $this->getBaseArray();
		#$data['x_customer_ip'] = $this->getClientIp();
		#if ($this->getTestMode()) $data['x_test_request'] = 'TRUE';
		#return array_merge($data, $this->getBillingData());
		return $data;
	}


	protected function getEndpoint(){
		$tid=$this->getPayeezyTransactionId();
		$url2ld=$this->getParameter('liveBaseEndpoint');
		if ($this->isDeveloperMode()) $url2ld=$this->getParameter('devBaseEndpoint');
		if(!empty($tid)) $url2ld.='/'.$tid;
		#echo '<div>'.$url2ld.' @'.__LINE__.': '.__FILE__.'</div>';
		return $url2ld;
	}



}
