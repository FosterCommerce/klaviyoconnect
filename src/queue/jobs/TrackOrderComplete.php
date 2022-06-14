<?php
namespace fostercommerce\klaviyoconnect\queue\jobs;

use fostercommerce\klaviyoconnect\Plugin;

use craft\commerce\elements\Order;

use Craft;
use craft\queue\BaseJob;

use yii\base\Event;

class TrackOrderComplete extends BaseJob
{
    // Properties
    // =========================================================================

    public $name;
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
     * @return	boolean
     */
    public function execute($queue): void
    {
        $this->setProgress($queue, 1);

        if ($this->orderId) {
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
        
        return;
    }


    // Protected Methods
    // =========================================================================

    protected function defaultDescription(): string
    {
        return 'Sending `Order Complete` event to Klaviyo';
    }
}
