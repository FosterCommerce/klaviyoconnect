<?php

namespace fostercommerce\klaviyoconnect\queue\jobs;

use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use fostercommerce\klaviyoconnect\Plugin;

class SyncOrders extends BaseJob
{
    public int $orderId;

    public function execute($queue): void
    {
        $this->setProgress($queue, 1);

        if ($this->orderId !== 0) {
            $order = Order::find()->id($this->orderId)->one();

            if ($order) {
                // When syncing orders we want to use the timestamp from the order
                // instead of the time the sync operation was performed.
                Plugin::getInstance()->track->trackOrder(
                    'Placed Order',
                    $order,
                    null,
                    $order->dateOrdered?->getTimestamp(),
                );
            }
        }
    }

    protected function defaultDescription(): string
    {
        return 'Syncing orders to Klaviyo';
    }
}
