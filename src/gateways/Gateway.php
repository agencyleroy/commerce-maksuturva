<?php

namespace craft\commerce\maksuturva\gateways;

use craft\commerce\maksuturva\models\forms\Payment;
use craft\commerce\maksuturva\models\responses\Maksuturva;
use craft\commerce\maksuturva\models\PaymentRequest;
use craft\commerce\base\Gateway as BaseGateway;
use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\models\PaymentSource;
use craft\helpers\UrlHelper;
use craft\web\Response;
use craft\web\View;
use Craft;

/**
 * Gateway represents Maksuturva gateway
 */
class Gateway extends BaseGateway
{

    /**
     * @var string
     */
    public $sellerid;

    /**
     * @var string
     */
    public $secretkey;

    /**
     * @var bool
     */
    public $testmode;

    /**
     * @var string
     */
    protected $productionPaymentPath = 'https://www.maksuturva.fi/NewPaymentExtended.pmt';

    /**
     * @var string
     */
    protected $testPaymentPath = 'https://test1.maksuturva.fi/NewPaymentExtended.pmt';

    /**
     * @var string
     */
    protected $productionPaymentMethodsPath = 'https://test1.maksuturva.fi/GetPaymentMethods.pmt';

    /**
     * @var string
     */
    protected $testPaymentMethodsPath = 'https://test1.maksuturva.fi/GetPaymentMethods.pmt';

    /**
     * @inheritdoc
     */
    public function getPaymentFormHtml(array $params)
    {
        $defaults = [
            'gateway' => $this,
            'paymentForm' => $this->getPaymentFormModel()
        ];

        $params = array_merge($defaults, $params);

        $view = Craft::$app->getView();

        $previousMode = $view->getTemplateMode();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        $html = $view->renderTemplate('commerce-maksuturva/paymentForm', $params);
        $view->setTemplateMode($previousMode);

        return $html;
    }

    /**
     * @inheritdoc
     */
    public function getPaymentFormModel(): BasePaymentForm
    {
        return new Payment();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('commerce-maksuturva/gatewaySettings', ['gateway' => $this]);
    }

    /**
     * @inheritdoc
     */
    public function getPaymentTypeOptions(): array
    {
        return [
            'purchase' => Craft::t('commerce-maksuturva', 'Purchase (Authorize and Capture Immediately)'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function authorize(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {

    }

    /**
     * @inheritdoc
     */
    public function capture(Transaction $transaction, string $reference): RequestResponseInterface
    {

    }

    /**
     * @inheritdoc
     */
    public function completeAuthorize(Transaction $transaction): RequestResponseInterface
    {

    }

    /**
     * @inheritdoc
     */
    public function completePurchase(Transaction $transaction): RequestResponseInterface
    {
        $request = Craft::$app->getRequest();

        // Hash to compare calculated hash with
        $hash = $request->getQueryParam('pmt_hash');

        // Maksuturva does not return query parameters in the
        // documented / required order so hash string needs to be
        // built manually
        $hashString = $request->getQueryParam('pmt_action') . '&';
        $hashString .= $request->getQueryParam('pmt_version') . '&';
        $hashString .= $request->getQueryParam('pmt_id') . '&';
        $hashString .= $request->getQueryParam('pmt_reference') . '&';
        $hashString .= $request->getQueryParam('pmt_amount') . '&';
        $hashString .= $request->getQueryParam('pmt_currency') . '&';
        $hashString .= $request->getQueryParam('pmt_sellercosts') . '&';
        if (!empty($request->getQueryParam('pmt_paymentmethod'))) {
            $hashString .= $request->getQueryParam('pmt_paymentmethod') . '&';
        }
        $hashString .= $request->getQueryParam('pmt_escrow') . '&';
        // Add secret key to hash string
        $hashString .= $this->secretkey . '&';

        $hashString = strtoupper(hash('MD5', $hashString));

        // Create response where the hash string
        // comparsion determines the success
        $response = new Maksuturva([
            'success' => $hashString === $hash,
            'pmt_reference' => $transaction->reference,
        ]);

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function createPaymentSource(BasePaymentForm $sourceData, int $userId): PaymentSource
    {

    }

    /**
     * @inheritdoc
     */
    public function deletePaymentSource($token): bool
    {

    }

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return 'Maksuturva';
    }

    /**
     * @inheritdoc
     */
    public function purchase(Transaction $transaction, BasePaymentForm $form): RequestResponseInterface
    {
        $paymentRequest = new PaymentRequest($transaction, $form);
        $paymentRequest->validate();

        $url = $this->testmode ? $this->testPaymentPath : $this->productionPaymentPath;

        $response = new Maksuturva($paymentRequest->attributes);

        if ($paymentRequest->hasErrors()) {
            $transaction->getOrder()->addModelErrors($paymentRequest);
        }
        else {
            $response->setRedirectUrl($url);
        }

        return $response;
    }

    /**
     * @inheritdoc
     */
    public function refund(Transaction $transaction): RequestResponseInterface
    {

    }

    /**
     * @inheritdoc
     */
    public function processWebHook(): Response
    {

    }

    /**
     * @inheritdoc
     */
    public function supportsAuthorize(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCapture(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompleteAuthorize(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsCompletePurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsPaymentSources(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPurchase(): bool
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function supportsRefund(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsPartialRefund(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function supportsWebhooks(): bool
    {
        return false;
    }

    /**
     *
     */
    public function retrieveAvailablePaymentMethods()
    {
        $url = $this->testmode ? $this->testPaymentMethodsPath : $this->productionPaymentMethodsPath;

        $post_data['sellerid'] = $this->sellerid;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        $response = curl_exec($ch);

        $xml = simplexml_load_string($response);
        $json = json_encode($xml);

        return json_decode($json, true);
    }

}
