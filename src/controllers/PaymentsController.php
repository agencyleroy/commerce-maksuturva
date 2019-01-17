<?php

namespace craft\commerce\maksuturva\controllers;

use craft\commerce\controllers\BaseFrontEndController;
use craft\commerce\Plugin;
use Craft;

/**
 *
 */
class PaymentsController extends BaseFrontEndController
{

    /**
     * Processes return from off-site payment error
     *
     * @throws Exception
     * @throws HttpException
     */
    public function actionErrorReturn()
    {
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        $hash = $request->getParam('commerceTransactionHash');

        $transaction = Plugin::getInstance()->getTransactions()->getTransactionByHash($hash);

        if (!$transaction) {
            throw new HttpException(400, Craft::t('commerce-maksuturva', 'Can not complete payment for missing transaction.'));
        }

        $error = '';
        $error .= $request->getQueryParam('pmt_errortexttouser', '');

        if ($transaction->getGateway()->testmode) {
            $error .= $request->getQueryParam('error_fields', '');
        }

        if (!empty($error)) {
            $session->setError($error);
        }

        $order = $transaction->getOrder();

        return $this->redirect($order->cancelUrl);
    }
}
