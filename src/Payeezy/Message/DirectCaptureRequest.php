<?php

namespace Omnipay\Payeezy\Message;

/**
 * Payeezy Direct API Authorize Request
 */
class DirectCaptureRequest extends DirectApiAbstractRequest {

	protected function getTransactionType(){ return self::TT_CAPTURE; }

}
