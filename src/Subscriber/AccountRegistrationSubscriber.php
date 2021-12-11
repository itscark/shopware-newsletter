<?php

declare(strict_types=1);

namespace ItsCark\Shopware\Newsletter\Subscriber;

use ItsCark\Shopware\Newsletter\Traits\SubscriptionAware;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\CustomerEvents;
use Shopware\Core\Checkout\Customer\Event\CustomerRegisterEvent;
use Shopware\Core\Checkout\Customer\Event\GuestCustomerRegisterEvent;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Framework\Event\DataMappingEvent;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class AccountRegistrationSubscriber implements EventSubscriberInterface
{
    use SubscriptionAware;

    private RequestStack $requestStack;

    private SystemConfigService $config;

    private AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute;

    public function __construct(
        RequestStack $requestStack,
        SystemConfigService $config,
        AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute
    ) {
        $this->requestStack = $requestStack;
        $this->config = $config;
        $this->newsletterSubscribeRoute = $newsletterSubscribeRoute;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerEvents::MAPPING_REGISTER_CUSTOMER => 'onMapCustomerData',
            CustomerRegisterEvent::class              => 'onCustomerRegister',
            GuestCustomerRegisterEvent::class         => 'onCustomerRegister',
        ];
    }

    public function onMapCustomerData(DataMappingEvent $event): void
    {
        $customerData = $event->getOutput();
        $customerData['newsletter'] = $event->getInput()->getBoolean('newsletter');

        $event->setOutput($customerData);
    }

    public function onCustomerRegister(CustomerRegisterEvent $event): void
    {
        $customer = $event->getCustomer();
        $request = $this->requestStack->getCurrentRequest();

        if (!$customer->getNewsletter() || !$request instanceof Request) {
            return;
        }

        $dataBag = $this->createDataBagFromCustomer($customer);
        $dataBag->set(
            'option',
            $this->getSubscriptionOption($this->config->get('CarkNewsletter.config.isDirectSubscription'))
        );
        $dataBag->set(
            'storefrontUrl',
            $request->attributes->get(RequestTransformer::STOREFRONT_URL)
        );

        $this->newsletterSubscribeRoute->subscribe($dataBag, $event->getSalesChannelContext(), true);
    }

    private function createDataBagFromCustomer(CustomerEntity $customer): RequestDataBag
    {
        $dataBag = new RequestDataBag();

        $dataBag->set('email', $customer->getEmail());
        $dataBag->set('salutationId', $customer->getSalutationId());
        $dataBag->set('title', $customer->getTitle());
        $dataBag->set('firstName', $customer->getFirstName());
        $dataBag->set('lastName', $customer->getLastName());

        $customerAddresses = $customer->getAddresses();
        if (!empty($customerAddresses)
            && !empty($customerAddresses->get($customer->getDefaultShippingAddressId()))) {
            $address = $customerAddresses->get($customer->getDefaultShippingAddressId());
            $dataBag->set('zipCode', $address->getZipCode());
            $dataBag->set('city', $address->getCity());
            $dataBag->set('street', $address->getStreet());
        }

        return $dataBag;
    }
}
