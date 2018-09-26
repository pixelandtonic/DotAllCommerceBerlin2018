<?php

Event::on(OrderAdjustments::class, OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS, function(RegisterComponentTypesEvent $event) {
    $types = $event->types;
    if (($key = array_search(Shipping::class, $types)) !== false) {
        $types[$key] = Adjuster::class;
    }
    $event->types = $types;
});