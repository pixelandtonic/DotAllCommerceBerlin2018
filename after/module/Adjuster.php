<?php
namespace modules\workshop;

use Craft;
use craft\commerce\adjusters\Discount;
use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;
use craft\commerce\models\OrderAdjustment;

class Adjuster implements AdjusterInterface
{
    /**
     * @inheritdoc
     */
    public function adjust(Order $order): array
    {
        $orderUser = $order->getUser();

        $adjustments = [];

        if ($orderUser && $orderUser->isInGroup(getenv('PERK_GROUP'))) {
            $shippingCost = $order->getAdjustmentsTotalByType('shipping');
            $adjustment = new OrderAdjustment();
            $adjustment->amount = -1 * $shippingCost;
            $adjustment->type = Discount::ADJUSTMENT_TYPE;
            $adjustment->orderId = $order->id;
            $adjustment->description = 'Premium shipping';

            $adjustments[] = $adjustment;
        }

        return $adjustments;
    }
}
