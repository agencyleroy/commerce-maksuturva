<?php

namespace craft\commerce\maksuturva\models\responses;

use craft\commerce\base\RequestResponseInterface;
use craft\commerce\models\payments\BasePaymentForm;
use Craft;

/**
 * Maksuturva represents Maksuturva request response
 */
class Maksuturva implements RequestResponseInterface
{

    /**
     * @var string
     */
    private $_redirect = '';

    /**
     * @var array
     */
    private $_data = [];

    /**
     * Response constructor
     */
    public function __construct(array $data = [])
    {
        $this->_data = $data;
    }

    /**
     * Set off-site payment url
     *
     * @param string $url
     */
    public function setRedirectUrl(string $url)
    {
        $this->_redirect = $url;
    }

    /**
     * @inheritdoc
     */
    public function isSuccessful(): bool
    {
        return array_key_exists('success', $this->_data) && $this->_data['success'];
    }

    /**
     * @inheritdoc
     */
    public function isRedirect(): bool
    {
        return !empty($this->_redirect);
    }

    /**
     * @inheritdoc
     */
    public function getRedirectMethod(): string
    {
        return 'POST';
    }

    /**
     * @inheritdoc
     */
    public function getRedirectData(): array
    {
        return $this->_data;
    }

    /**
     * @inheritdoc
     */
    public function getRedirectUrl(): string
    {
        return $this->_redirect;
    }

    /**
     * @inheritdoc
     */
    public function getTransactionReference(): string
    {
        return $this->_data['pmt_reference'];
    }

    /**
     * @inheritdoc
     */
    public function getCode(): string
    {
        return $this->isSuccessful() ? '' : 'payment.failed';
    }

    /**
     * @inheritdoc
     */
    public function getMessage(): string
    {
        return $this->isSuccessful() ? '' : Craft::t('commerce-maksuturva', 'Payment failed.');
    }

    /**
     * @inheritdoc
     */
    public function redirect()
    {
        $variables = [];
        $hiddenFields = '';

        // Gather all post hidden data inputs.
        foreach ($this->getRedirectData() as $key => $value) {
            $hiddenFields .= sprintf('<input type="hidden" name="%1$s" value="%2$s" />', htmlentities($key, ENT_QUOTES, 'UTF-8', false), htmlentities($value, ENT_QUOTES, 'UTF-8', false)) . "\n";
        }

        $variables['inputs'] = $hiddenFields;

        // Set the action url to the responses redirect url
        $variables['actionUrl'] = $this->getRedirectUrl();

        // Set Craft to the site template mode
        $templatesService = Craft::$app->getView();
        $oldTemplateMode = $templatesService->getTemplateMode();
        $templatesService->setTemplateMode($templatesService::TEMPLATE_MODE_CP);

        $template = $templatesService->renderPageTemplate('commerce-maksuturva/postRedirectTemplate', $variables);

        // Restore the original template mode
        $templatesService->setTemplateMode($oldTemplateMode);

        // Send the template back to the user.
        ob_start();
        echo $template;
        Craft::$app->end();
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public function isProcessing(): bool
    {
        return false;
    }
}
