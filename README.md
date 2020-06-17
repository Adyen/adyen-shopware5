# Adyen Payment plugin for Shopware 5
Use Adyen's plugin for Shopware 5 to offer frictionless payments online, in-app, and in-store.

## Contributing
We strongly encourage you to join us in contributing to this repository so everyone can benefit from:
* New features and functionality
* Resolved bug fixes and issues
* Any general improvements

Read our [**contribution guidelines**](https://github.com/Adyen/.github/blob/master/CONTRIBUTING.md) to find out how.

## Requirements
* PHP >=7.0
* Shopware >=5.6

Note: The Adyen payment plugin is not compatible with the cookie manager plugin (<= 5.6.2), it is however compatible with the Shopware default cookie consent manager (>5.6.2).

## Installation
Please use the [official documentation](https://docs.adyen.com/plugins/shopware-5) of the plugin to see how to install it.

## Usage
Please use the [official documentation](https://docs.adyen.com/plugins/shopware-5) of the plugin to see how to configure and use it.

## Documentation
Please find the relevant documentation for
 - [Get started with Adyen](https://docs.adyen.com/user-management/get-started-with-adyen)
 - [Shopware 5 official plugin](https://docs.adyen.com/plugins/shopware-5)
 - [Adyen PHP API Library](https://docs.adyen.com/development-resources/libraries#php)

## Support
If you have a feature request, or spotted a bug or a technical problem, create a GitHub issue. For other questions, 
contact our [support team](https://support.adyen.com/hc/en-us/requests/new?ticket_form_id=360000705420).

# For developers

## Integration
The plugin integrates card component (Secured Fields) using Adyen Checkout for all card payments.

## API Library
This module is using the Adyen's API Library for PHP for all (API) connections to Adyen.
<a href="https://github.com/Adyen/adyen-php-api-library" target="_blank">This library can be found here</a>

## License
MIT license. For more information, see the [LICENSE file](LICENSE).
