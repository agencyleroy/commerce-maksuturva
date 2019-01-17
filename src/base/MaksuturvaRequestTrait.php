<?php

namespace craft\commerce\maksuturva\base;

trait MaksuturvaRequestTrait {

    /**
     * Action code
     *
     * @var string
     */
    public $pmt_action = 'NEW_PAYMENT_EXTENDED';

    /**
     * Message version
     *
     * @var string
     */
    public $pmt_version = '0004';

    /**
     * Seller ID
     *
     * @var string
     */
    public $pmt_sellerid;

    /**
     * Unique Payment ID
     *
     * @var string
     */
    public $pmt_id;

    /**
     * Order id
     *
     * @var string
     */
    public $pmt_orderid;

    /**
     * Payment reference number
     *
     * @var string
     */
    public $pmt_reference;

    /**
     * Payment due date
     *
     * @var string
     */
    public $pmt_duedate;

    /**
     * Buyer's (user's) locale
     *
     * @var string
     */
    public $pmt_userlocale = 'fi_FI';

    /**
     * Sum of products and services of the order
     *
     * @var string
     */
    public $pmt_amount;

    /**
     * Payment currency
     *
     * @var string
     */
    public $pmt_currency;

    /**
     * Ok return address (URL)
     *
     * @var string
     */
    public $pmt_okreturn;

    /**
     * Error return address (URL)
     *
     * @var string
     */
    public $pmt_errorreturn;

    /**
     * Cancel return address (URL)
     *
     * @var string
     */
    public $pmt_cancelreturn;

    /**
     * Delayed payment return address (URL)
     *
     * @var string
     */
    public $pmt_delayedpayreturn;

    /**
     * Escrow in use
     *
     * @var string
     */
    public $pmt_escrow = 'N';

    /**
     * Escrow option changeable by buyer
     *
     * @var string
     */
    public $pmt_escrowchangeallowed = 'N';

    /**
     * Will webstore sends an invoice to the payer
     *
     * @var string
     */
    public $pmt_invoicefromseller = 'N';

    /**
     * Payment method pre-chosen in webstore
     *
     * @var string
     */
    public $pmt_paymentmethod;

    /**
     * Buyer’s social security number or company’s business identification code
     *
     * @var string
     */
    public $pmt_buyeridentificationcode;

    /**
     * Buyer's name
     *
     * @var string
     */
    public $pmt_buyername;

    /**
     * Buyer's billing address
     *
     * @var string
     */
    public $pmt_buyeraddress;

    /**
     * Buyer's postal code
     *
     * @var string
     */
    public $pmt_buyerpostalcode;

    /**
     * Buyer's city
     *
     * @var string
     */
    public $pmt_buyercity;

    /**
     * Buyer's country code
     *
     * @var string
     */
    public $pmt_buyercountry = 'FI';

    /**
     * Buyer's phone number
     *
     * @var string
     */
    public $pmt_buyerphone;

    /**
     * Buyer's email address
     *
     * @var string
     */
    public $pmt_buyeremail;

    /**
     * Delivery recipient's name
     *
     * @var string
     */
    public $pmt_deliveryname;

    /**
     * Delivery recipient's address
     *
     * @var string
     */
    public $pmt_deliveryaddress;

    /**
     * Delivery recipient's postal code
     *
     * @var string
     */
    public $pmt_deliverypostalcode;

    /**
     * Delivery recipient's city
     *
     * @var string
     */
    public $pmt_deliverycity;

    /**
     * Delivery recipient's country code
     *
     * @var string
     */
    public $pmt_deliverycountry = 'FI';

    /**
     * Sum of handling and delivery costs of the order
     *
     * @var string
     */
    public $pmt_sellercosts = '0,00';

    /**
     * Order row count (number of order rows)
     *
     * @var string
     */
    public $pmt_rows = 0;

    /**
     * Hash calculation character encoding
     *
     * @var string
     */
    public $pmt_charset = 'UTF-8';

    /**
     * Character encoding of input data (and of webstore)
     *
     * @var string
     */
    public $pmt_charsethttp = 'UTF-8';

    /**
     * Hash algorithm
     *
     * @var string
     */
    public $pmt_hashversion = 'MD5';

    /**
     * Hash
     *
     * @var string
     */
    public $pmt_hash;

    /**
     * Secret key generation
     *
     * @var string
     */
    public $pmt_keygeneration = '001';
}
