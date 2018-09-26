<?php
namespace modules\workshop;

use craft\commerce\base\AdjusterInterface;
use craft\commerce\elements\Order;

class Adjuster implements AdjusterInterface
{
    /**
     * @inheritdoc
     */
    public function adjust(Order $order): array
    {
        return [];
    }
}
