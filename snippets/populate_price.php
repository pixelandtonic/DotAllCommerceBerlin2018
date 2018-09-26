Event::on(LineItems::class, LineItems::EVENT_POPULATE_LINE_ITEM, [$this, 'handlePopulateLineItem']);


public function handlePopulateLineItem(LineItemEvent $event)
{
    $options = $event->lineItem->getOptions();
    if (isset($options['giftWrapped']) && $options['giftWrapped'] == 'yes') {
        $event->lineItem->price = $event->lineItem->price + 2;
    }
}