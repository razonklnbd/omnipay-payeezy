<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Direct API Purchase Request
 */
class DirectPurchaseRequest extends DirectApiAbstractRequest {

	protected function getTransactionType(){ return self::TT_PURCHASE; }

}
