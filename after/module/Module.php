<?php

namespace modules\workshop;

use Craft;
use craft\commerce\base\Plan;
use craft\commerce\elements\Subscription;
use craft\commerce\events\LineItemEvent;
use craft\commerce\services\LineItems;
use craft\commerce\events\SubscriptionEvent;
use craft\commerce\services\OrderAdjustments;
use craft\commerce\services\Subscriptions;
use craft\events\RegisterComponentTypesEvent;
use yii\base\Event;

/**
 * Custom module class.
 *
 * This class will be available throughout the system via:
 * `Craft::$app->getModule('my-module')`.
 *
 * You can change its module ID ("my-module") to something else from
 * config/app.php.
 *
 * If you want the module to get loaded on every request, uncomment this line
 * in config/app.php:
 *
 *     'bootstrap' => ['my-module']
 *
 * Learn more about Yii module development in Yii's documentation:
 * http://www.yiiframework.com/doc-2.0/guide-structure-modules.html
 */
class Module extends \yii\base\Module
{
    /**
     * Initializes the module.
     */
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);

        // Set the controllerNamespace based on whether this is a console or web request
        if (Craft::$app->getRequest()->getIsConsoleRequest()) {
            $this->controllerNamespace = 'modules\\console\\controllers';
        } else {
            $this->controllerNamespace = 'modules\\controllers';
        }

        parent::init();

        Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_CREATE_SUBSCRIPTION, [$this, 'handleNewSubscription']);
        Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_CANCEL_SUBSCRIPTION, [$this, 'handleExpiredSubscription']);
        Event::on(Subscriptions::class, Subscriptions::EVENT_AFTER_EXPIRE_SUBSCRIPTION, [$this, 'handleExpiredSubscription']);
        Event::on(Subscriptions::class, Subscriptions::EVENT_BEFORE_CREATE_SUBSCRIPTION, [$this, 'addTrialDays']);

        Event::on(OrderAdjustments::class, OrderAdjustments::EVENT_REGISTER_ORDER_ADJUSTERS, function(RegisterComponentTypesEvent $event) {
            $event->types[] = Adjuster::class;
        });

        Event::on(LineItems::class, LineItems::EVENT_POPULATE_LINE_ITEM, [$this, 'handlePopulateLineItem']);
    }

    public function handlePopulateLineItem(LineItemEvent $event)
    {
        $options = $event->lineItem->getOptions();
        if (isset($options['giftWrapped']) && $options['giftWrapped'] == 'yes') {
            $event->lineItem->price = $event->lineItem->price + 2;
        }
    }

    public function addTrialDays(CreateSubscriptionEvent $e)
    {
        $user = $e->user;

        if ($user->isInGroup(getenv('PERK_PLAN'))) {
            $e->parameters->trialDays = 7;
        }
    }

    public function handleNewSubscription(SubscriptionEvent $e)
    {
        $subscription = $e->subscription;
        /** @var Plan $plan */
        $plan = $subscription->getPlan();


        if ($plan->handle === getenv('PERK_PLAN')) {
            $user = $subscription->getSubscriber();
            $groups = $user->getGroups();
            $targetGroup = Craft::$app->getUserGroups()->getGroupByHandle(getenv('PERK_GROUP'));

            if (!$targetGroup) {
                return;
            }

            $groupIds = [];

            foreach ($groups as $existingGroup) {
                if ($existingGroup->id == $targetGroup->id) {
                    return;
                }
                $groupIds[] = $existingGroup->id;
            }

            $groupIds[] = $targetGroup->id;

            Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
        }
    }

    public function handleExpiredSubscription(Event $e)
    {
        /** @var Subscription $subscription */
        $subscription = $e->subscription;

        /** @var Plan $plan */
        $plan = $subscription->getPlan();

        if ($plan->handle === getenv('PERK_PLAN')) {
            if ($subscription->isExpired) {
                $user = $subscription->getSubscriber();

                if (Subscription::find()->plan($plan)->user($user)->count() == 0) {
                    $groups = $user->getGroups();
                    $targetGroup = Craft::$app->getUserGroups()->getGroupByHandle(getenv('PERK_GROUP'));

                    if (!$targetGroup) {
                        return;
                    }

                    $groupIds = [];

                    foreach ($groups as $existingGroup) {
                        if ($existingGroup->id != $targetGroup->id) {
                            $groupIds[] = $existingGroup->id;
                        }
                    }

                    Craft::$app->getUsers()->assignUserToGroups($user->id, $groupIds);
                }
            }
        }
    }
}
