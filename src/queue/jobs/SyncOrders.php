<?php
namespace fostercommerce\klaviyoconnect\queue\jobs;

use fostercommerce\klaviyoconnect\Plugin;

use craft\commerce\elements\Order;

use Craft;
use craft\queue\BaseJob;

use yii\base\Event;

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
                //
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
