<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Direct API Authorize Request
 */
class DirectTaggedRefundRequest extends DirectApiAbstractRequest {

	protected function getTransactionType(){ return self::TT_TAGGED_REFUND; }

}
