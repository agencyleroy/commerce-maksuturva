<?php

namespace craft\commerce\maksuturva\models;

use craft\commerce\maksuturva\base\MaksuturvaRequestTrait;
use craft\commerce\models\payments\BasePaymentForm;
use craft\commerce\models\Transaction;
use craft\commerce\models\Address;
use craft\commerce\models\LineItem;
use craft\commerce\elements\Order;
use craft\commerce\base\Gateway;
use craft\helpers\UrlHelper;
use craft\db\Query;
use craft\base\Model;
use Craft;

/**
 *
 */
class PaymentRequest extends Model
{
    use MaksuturvaRequestTrait;

    /**
     * Lineitems belonging to the transaction order
     *
     * @var array
     */
    private $_lineItems = [];

    /**
     * Create a populated PaymentRequest object based on
     * the transaction and submitted data.
     *
     * @var Transaction $transaction
     * @var BasePaymentForm $form
     */
    public function __construct(Transaction $transaction, BasePaymentForm $form)
    {
        $order = $transaction->getOrder();
        $gateway = $transaction->getGateway();
        $secretkey = $gateway->secretkey;

        $this->populateFromForm($form);
        $this->populateFromGateway($gateway);
        $this->populateFromOrder($order);
        $this->populateFromTransaction($transaction);
        $this->generateId();
        $this->generateReference();
        $this->populateHash($secretkey);
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['pmt_buyeremail', 'pmt_buyername', 'pmt_buyeraddress', 'pmt_buyerpostalcode', 'pmt_buyercity'], 'required'];
        $rules[] = [['pmt_deliveryname', 'pmt_deliveryaddress', 'pmt_deliverypostalcode', 'pmt_deliverycity'], 'required'];
        $rules[] = ['pmt_userlocale', 'filter', 'filter' => [$this, 'filterUserLocale']];

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes($names = null, $except = [])
    {
        $values = parent::getAttributes($names, $except);

        $count = 1;
        foreach ($this->_lineItems as $key => $lineItem) {
            foreach ($lineItem as $key => $value) {
                if (!empty($value)) {
                    $values[$key . $count] = $value;
                }
            }
            $count++;
        }

        return $values;
    }

    /**
     * Filter pmt_userlocale
     * Return a default launguage string if value is not among allowed languages
     *
     * @param string $value
     * @return string $value
     */
    public function filterUserLocale($value)
    {
        $allowed = ['fi_FI', 'sv_FI', 'en_FI'];

        if (in_array($value, $allowed)) {
            return $value;
        }

        return 'en_FI';
    }

    /**
     * Set attributes from PaymentForm
     *
     * @param craft\commerce\models\payments\BasePaymentForm $form
     */
    private function populateFromForm(BasePaymentForm $form)
    {
        $this->pmt_paymentmethod = $form->paymentMethod;
    }

    /**
     * Set attributes from Gateway
     *
     * @param craft\commerce\base\Gateway $gateway
     */
    private function populateFromGateway(Gateway $gateway)
    {
        $this->pmt_sellerid = $gateway->sellerid;
    }

    /**
     * Set attributes from Order
     *
     * @param craft\commerce\elements\Order $order
     */
    private function populateFromOrder(Order $order)
    {
        $this->pmt_orderid = $order->id;
        $this->pmt_buyeremail = $order->email;
        $this->pmt_currency = $order->currency;
        $this->pmt_amount = $this->getOrderAmount($order);
        $this->generateId();
        $this->generateReference();
        $this->populateBuyer($order->getBillingAddress());
        $this->populateDelivery($order->getShippingAddress());
        $this->populateMisc();
        $this->populatelineItems($order->lineItems);
        $this->populateDiscount($order->getAdjustmentsTotalByType('discount')); // This should be a discount code applied
        $this->populateShipping($order->getAdjustmentsTotalByType('shipping'));
    }

    /**
     * Set attributes from Transaction
     *
     * @param craft\commerce\models\Transaction $transaction
     */
    private function populateFromTransaction(Transaction $transaction)
    {
        $this->pmt_okreturn = UrlHelper::url('actions/commerce/payments/complete-payment', ['commerceTransactionHash' => $transaction->hash]);
        $this->pmt_errorreturn = UrlHelper::url('actions/commerce-maksuturva/payments/error-return', ['commerceTransactionHash' => $transaction->hash]);
    }

    /**
     * Set misc attributes
     */
    private function populateMisc()
    {
        $this->pmt_userlocale = str_replace('-', '_', Craft::$app->language);
        $this->pmt_duedate = date('d.m.Y');
        $this->pmt_cancelreturn = UrlHelper::url('shop/checkout');
        $this->pmt_delayedpayreturn = UrlHelper::url('shop/checkout');
    }

    /**
     * Set buyer attributes from Address
     *
     * @param craft\commerce\models\Address $address
     */
    private function populateBuyer(Address $address)
    {
        $this->pmt_buyername = $address->fullName;
        $this->pmt_buyeraddress = $address->address1;
        $this->pmt_buyerpostalcode = $address->zipCode;
        $this->pmt_buyercity = $address->city;
    }

    /**
     * Set delivery attributes from Address
     *
     * @param craft\commerce\models\Address $address
     */
    private function populateDelivery(Address $address)
    {
        $this->pmt_deliveryname = $address->fullName;
        $this->pmt_deliveryaddress = $address->address1;
        $this->pmt_deliverypostalcode = $address->zipCode;
        $this->pmt_deliverycity = $address->city;
    }

    /**
     * Add multiple lineitems
     *
     * @param array A list of LineItems.
     */
    private function populatelineItems(array $lineItems)
    {
        foreach ($lineItems as $lineItem) {
            $this->populatelineItem($lineItem);
        }
    }

    /**
     * Add single lineitem
     *
     * @param craft\commerce\models\LineItem $lineItem
     */
    private function populatelineItem(LineItem $lineItem)
    {
        $row = [
            'pmt_row_name' => $lineItem->description,
            'pmt_row_desc' => $lineItem->description,
            'pmt_row_quantity' => $lineItem->qty,
            'pmt_row_deliverydate' => date('d.m.Y'),
            'pmt_row_price_net' => number_format($lineItem->price, 2, ',', ''),
            'pmt_row_vat' => $this->getLineItemTax($lineItem),
            'pmt_row_discountpercentage' => $this->getLineItemSalePercentage($lineItem),
            'pmt_row_type' => 1,
        ];
        // Increase row count
        $this->pmt_rows++;
        // Add row to lineitems
        $this->_lineItems[] = $row;
    }

    /**
     * Add discount to lineitems
     *
     * @param int $int The discount to be added
     */
    private function populateDiscount(int $int)
    {
        if ($int !== 0) {
            $this->_lineItems[] = [
                'pmt_row_name' => Craft::t('commerce-maksuturva', 'Discount'),
                'pmt_row_desc' => Craft::t('commerce-maksuturva', 'Discount'),
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date('d.m.Y'),
                'pmt_row_price_net' => number_format($int, 2, ',', ''),
                // Need to figure out how to calculate tax on shipping
                'pmt_row_vat' => '0,00',
                'pmt_row_discountpercentage' => '0,00',
                'pmt_row_type' => 6,
            ];
            // Increase row count
            $this->pmt_rows++;
        }
    }

    /**
     * Add shipping costs to lineitems
     *
     * @param int $int The shipping cost to be added
     */
    private function populateShipping(int $int)
    {
        if ($int !== 0) {
            $this->_lineItems[] = [
                'pmt_row_name' => Craft::t('commerce-maksuturva', 'Shipping costs'),
                'pmt_row_desc' => Craft::t('commerce-maksuturva', 'Shipping costs'),
                'pmt_row_quantity' => 1,
                'pmt_row_deliverydate' => date('d.m.Y'),
                'pmt_row_price_net' => number_format($int, 2, ',', ''),
                // Need to figure out how to calculate tax on shipping
                'pmt_row_vat' => '0,00',
                'pmt_row_discountpercentage' => '0,00',
                'pmt_row_type' => 2,
            ];
            // Increase row count
            $this->pmt_rows++;
            // Set seller costs
            $this->pmt_sellercosts = number_format($int, 2, ',', '');
        }
    }

    /**
     * Set hash based on other attributes
     *
     * @param string $secretkey The secret key obtained from Maksuturva
     */
    private function populateHash(string $secretkey)
    {
        $hashString = '';

        $except = [
            'pmt_sellerid',
            'pmt_userlocale',
            'pmt_rows',
            'pmt_buyeremail',
            'pmt_charset',
            'pmt_charsethttp',
            'pmt_hashversion',
            'pmt_keygeneration',
        ];

        $values = $this->getAttributes(null, $except);

        foreach ($values as $key => $value) {
            if (!empty($value)) {
                $hashString .= $value . '&';
            }
        }

        $hashString .= $secretkey . '&';

        $this->pmt_hash = hash($this->pmt_hashversion, $hashString);
    }

    /**
     * Generate unique payment ID based on the order ID.
     */
    private function generateId()
    {
        if (empty($this->pmt_id)) {
            $query = new Query();
            $query
                ->select('orderid')
                ->from('{{%commerce_transactions}} transactions')
                ->where(['orderid' => $this->pmt_orderid]);

            $count = $query->count();

            $this->pmt_id = $this->pmt_orderid . '_' . $count;
        }
    }

    /**
     * Generate reference number with check digit
     * based on the order ID, using 7-3-1 method.
     */
    private function generateReference()
    {
        $weights = [7, 3, 1];
        $sum = 0;
        foreach (array_reverse(str_split($this->pmt_orderid)) as $key => $value) {
            $sum += intval($value) * intval($weights[$key % 3]);
        }
        $check = 9 - (($sum - 1) % 10);

        $reference = $this->pmt_orderid . $check;

        $this->pmt_reference = $reference;
    }

    /**
     * Helper function for getting a LineItems tax.
     *
     * @param craft\commerce\models\LineItem $lineItem
     */
    private function getLineItemTax(LineItem $lineItem)
    {
        // Calculate tax, can't use real tax when discounts are applied
        $percent = ($lineItem->getAdjustmentsTotalByType('tax') / $lineItem->getSubtotal()) * 100;

        // $taxrates = $lineItem->getTaxCategory()->getTaxRates();
        // $totalrate = 0;
        // foreach ($taxrates as $taxrate) {
        //     $rate = $taxrate->rate;
        //     $totalrate = floatval($totalrate) + floatval($rate);
        // }
        // $percent = $totalrate * 100;

        return number_format($percent, 2, ',', '');
    }

    /**
     * Helper function for getting a LineItems discount percentage.
     *
     * @param craft\commerce\models\LineItem $lineItem
     */
    private function getLineItemSalePercentage(LineItem $lineItem)
    {
        $percentage = abs($lineItem->saleAmount) / $lineItem->price * 100;

        return number_format($percentage, 2, ',', '');
    }

    /**
     * Helper function for getting the total amount of the order excluding
     * shipping costs and discount.
     *
     * @param craft\commerce\elements\Order $order
     */
    private function getOrderAmount(Order $order)
    {
        // Order total without adjusters
        $amount = $order->getItemSubtotal();
        // Order total with tax adjuster
        $amount = $amount + $order->getAdjustmentsTotalByType('tax');
        // Order total with discount adjuster
        $amount = $amount + $order->getAdjustmentsTotalByType('discount');

        return number_format($amount, 2, ',', '');
    }
}
