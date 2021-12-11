<?php

declare(strict_types=1);

namespace ItsCark\Shopware\Newsletter\Traits;

use Shopware\Core\Content\Newsletter\SalesChannel\NewsletterSubscribeRoute;
use Shopware\Storefront\Controller\FormController;

trait SubscriptionAware
{
    private function getSubscriptionOption($config): string
    {
        if ($config) {
            return NewsletterSubscribeRoute::STATUS_DIRECT;
        }

        return FormController::SUBSCRIBE;
    }
}
