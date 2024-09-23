<?php

namespace fostercommerce\klaviyoconnect\queue\jobs;

use craft\commerce\elements\Order;

use craft\queue\BaseJob;

use fostercommerce\klaviyoconnect\Plugin;

use yii\base\Event;

class TrackOrderComplete extends BaseJob
{
	public string $name;

	public int $orderId;

	public function execute($queue): void
	{
		$this->setProgress($queue, 1);

		$order = Order::find()->id($this->orderId)->one();

		if ($order) {
			// Construct the event and pass like we normally would
			$event = new Event([
				'name' => $this->name,
				'sender' => $order,
			]);

			Plugin::getInstance()->track->onOrderCompleted($event);
		}
	}

	protected function defaultDescription(): string
	{
		return 'Sending `Order Complete` event to Klaviyo';
	}
}
