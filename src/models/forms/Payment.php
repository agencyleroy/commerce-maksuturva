<?php

namespace craft\commerce\maksuturva\models\forms;

use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\PaymentSource;

/**
 * Maksuturva Payment form model.
 */
class Payment extends BasePaymentForm
{

    public $paymentMethod;

    /**
     * @inheritdoc
     */
    public function populateFromPaymentSource(PaymentSource $paymentSource)
    {
    }

}
