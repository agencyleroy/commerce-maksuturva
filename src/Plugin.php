<?php

namespace craft\commerce\maksuturva;

use craft\commerce\maksuturva\gateways\Gateway;
use craft\commerce\maksuturva\models\Settings;
use craft\commerce\services\Gateways;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;

/**
 * Plugin represents the Maksuturva integration plugin.
 *
 * @author Agency Leroy. <support@agencyleroy.com>
 * @since  1.0
 */
class Plugin extends \craft\base\Plugin
{

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        Event::on(Gateways::class, Gateways::EVENT_REGISTER_GATEWAY_TYPES,  [$this, 'registerGatewayTypes']);
    }

    /**
     * Register Maksuturva commerce gateway.
     */
    public function registerGatewayTypes(RegisterComponentTypesEvent $event)
    {
        $event->types[] = Gateway::class;
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }
}
