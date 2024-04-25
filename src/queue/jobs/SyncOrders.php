<?php

namespace fostercommerce\klaviyoconnect\queue\jobs;

use craft\commerce\elements\Order;
use craft\queue\BaseJob;
use fostercommerce\klaviyoconnect\Plugin;

class SyncOrders extends BaseJob
{
    // Properties
    // =========================================================================

    public $orderId;


    // Public Methods
    // =========================================================================

    /**
     * execute.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed   $queue
     */
    public function execute($queue): void
    {
        $this->setProgress($queue, 1);

        if ($this->orderId) {
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

        return;
    }


    // Protected Methods
    // =========================================================================

    /**
     * defaultDescription.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	protected
     * @return	mixed
     */
    protected function defaultDescription(): string
    {
        return 'Syncing orders to Klaviyo';
    }
}
