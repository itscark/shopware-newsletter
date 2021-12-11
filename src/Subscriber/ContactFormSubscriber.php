<?php declare(strict_types=1);

namespace ItsCark\Shopware\Newsletter\Subscriber;

use ItsCark\Shopware\Newsletter\Traits\SubscriptionAware;
use Shopware\Core\Content\ContactForm\Event\ContactFormEvent;
use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Framework\Event\BusinessEvents;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\AbstractSalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ContactFormSubscriber implements EventSubscriberInterface
{
    use SubscriptionAware;

    private RequestStack $requestStack;

    private SystemConfigService $config;

    private AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute;

    private AbstractSalesChannelContextFactory $salesChannelContextFactory;

    public function __construct(
        RequestStack $requestStack,
        SystemConfigService $config,
        AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute,
        AbstractSalesChannelContextFactory $salesChannelContextFactory
    ) {
        $this->requestStack = $requestStack;
        $this->config = $config;
        $this->newsletterSubscribeRoute = $newsletterSubscribeRoute;

        $this->salesChannelContextFactory = $salesChannelContextFactory;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BusinessEvents::CONTACT_FORM => 'onContactFormSent',
        ];
    }

    public function onContactFormSent(ContactFormEvent $event): void
    {
        $formData = $event->getContactFormData();

        if (!\array_key_exists('newsletter', $formData) || !$formData['newsletter']) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        $dataBag = $this->createDataBagFromContact($formData);

        $dataBag->set(
            'option',
            $this->getSubscriptionOption(
                $this->config->get('CarkNewsletter.config.isDirectContactSubscription')
            )
        );
        $dataBag->set(
            'storefrontUrl',
            $request->attributes->get(RequestTransformer::STOREFRONT_URL)
        );

        $salesChannelId = $event->getSalesChannelId();
        $languageId = $event->getContext()->getLanguageId();

        $salesChannelContext = $this->createSalesChannelContext($salesChannelId, $languageId);

        $this->newsletterSubscribeRoute->subscribe($dataBag, $salesChannelContext, true);
    }

    private function createDataBagFromContact(array $data): RequestDataBag
    {
        $dataBag = new RequestDataBag();

        $dataBag->set('email', $data['email']);
        $dataBag->set('salutationId', $data['salutationId']);
        $dataBag->set('firstName', $data['firstName']);
        $dataBag->set('lastName', $data['lastName']);

        return $dataBag;
    }

    public function createSalesChannelContext(string $salesChannelId, string $languageId): SalesChannelContext
    {
        return $this->salesChannelContextFactory->create(
            '',
            $salesChannelId,
            [SalesChannelContextService::LANGUAGE_ID => $languageId]
        );
    }
}
