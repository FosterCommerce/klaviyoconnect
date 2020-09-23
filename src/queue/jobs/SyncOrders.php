<?php
namespace fostercommerce\klaviyoconnect\queue\jobs;

use Craft;
use craft\queue\BaseJob;
use craft\commerce\elements\Order;
use yii\base\Event;
use fostercommerce\klaviyoconnect\Plugin;

class SyncOrders extends BaseJob
{
    // Properties
    // =========================================================================

    public $orderId;


    // Public Methods
    // =========================================================================

    public function execute($queue)
    {
        $this->setProgress($queue, 1);

        if ($this->orderId) {
            $order = Order::find()->id($this->orderId)->one();

            if ($order) {
                Plugin::getInstance()->track->trackOrder('Placed Order', $order);
            }
        }

        return true;
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return 'Syncing orders to Klaviyo';
    }
}
