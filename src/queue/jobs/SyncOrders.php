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

		$order = Order::find()->id($this->orderId)->one();

		// Date the event with when the order was placed, not when the sync runs
		if ($order instanceof Order) {
			Plugin::getInstance()->track->trackOrder('Placed Order', $order, null, $order->dateOrdered?->format(DATE_ATOM));
		}
	}

	protected function defaultDescription(): string
	{
		return 'Sending order ' . $this->orderId . ' to Klaviyo';
	}
}
