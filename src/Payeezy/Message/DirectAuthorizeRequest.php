<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Direct API Authorize Request
 */
class DirectAuthorizeRequest extends DirectApiAbstractRequest {

	protected function getTransactionType(){ return self::TT_AUTH; }

}
