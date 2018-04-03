<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Direct API Authorize Request
 */
class DirectVoidRequest extends DirectApiAbstractRequest {

	protected function getTransactionType(){ return self::TT_VOID; }

}
