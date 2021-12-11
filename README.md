# Use case 
With this plugin, new customers can subscribe to your newsletter directly when registering in the online shop. This automatically increases the number of newsletter customers, as customers no longer have to register separately, but have the option to subscribe when they register.

# Installation

## a) Composer
```bash
composer config repositories.itscark-shopware-newsletter '{"type": "vcs", "url": "git@github.com:itscark/shopware-newsletter.git"}'
```

```bash
composer require itscark/shopware-newsletter
```

```bash
bin/console plugin:refresh && bin/console plugin:install CarkNewsletter --activate
```

## Snippets

- Newsletter -> `cark-newsletter.account.registerNewsletter`
