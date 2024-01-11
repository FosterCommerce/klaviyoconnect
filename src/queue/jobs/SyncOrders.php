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

    /**
     * execute.
     *
     * @author	Unknown
     * @since	v0.0.1
     * @version	v1.0.0	Monday, May 23rd, 2022.
     * @access	public
     * @param	mixed	$queue
     * @return	void
     */
    public function execute($queue): void
    {
        $this->setProgress($queue, 1);

        if ($this->orderId) {
            $order = Order::find()->id($this->orderId)->one();

            if ($order) {
                // When syncing orders we want to use the timestamp from the order
                // instead of the time the sync operation was performed.
                $timestamp = $order->dateOrdered
                    ? $order->dateOrdered->getTimestamp()
                    : null;

                Plugin::getInstance()->track->trackOrder(
                    'Placed Order',
                    $order,
                    null,
                    $timestamp
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
