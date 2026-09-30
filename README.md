# Adyen Payment plugin for Shopware 5
Use Adyen's plugin for Shopware 5 to offer frictionless payments online, in-app, and in-store.

## Contributing
We strongly encourage you to join us in contributing to this repository so everyone can benefit from:
* New features and functionality
* Resolved bug fixes and issues
* Any general improvements

Read our [**contribution guidelines**](https://github.com/Adyen/.github/blob/master/CONTRIBUTING.md) to find out how.

## Requirements
* PHP ^7.2 | ^7.4 | ^8.0
* Shopware >=5.6.0

Note: The Adyen payment plugin is not compatible with the cookie manager plugin (<= 5.6.2), it is however compatible with the Shopware default cookie consent manager (>5.6.2).

## Documentation
Please find the relevant documentation for
 - [How to start with Adyen](https://docs.adyen.com/user-management/get-started-with-adyen)
 - [Plugin documentation](https://github.com/Adyen/adyen-shopware5/wiki)

## Support

**Note**: if you are still using an **older version** of the Adyen Shopware 5 integration (**below v4.0**) please refer to [this documentation](https://github.com/Adyen/adyen-shopware5/wiki/Home/2b286ac3ae0a3ddf9dcba1f6fb13e69e0f6d2602).

#### Important information ####
Support deprecation plan for old plugins Shopware5 (below major release v4.0):
1. Only critical functionality and security updates until June 2024.
   Note: the Shopware5 platform will also be [sunset](https://www.shopware.com/en/news/shopware-5-how-it-continues/) from July 2024 onwards.
2. Only critical security updates from June 2024 until June 2025.
3. Support will be fully suspended for old Prestashop and Shopware5 plugins from June 2025 onwards.

# For developers

## Integration
The plugin integrates card component (Secured Fields) using Adyen Checkout for all card payments. Currently, the following versions of Web components and Checkout API are utilized in the code:
* **Checkout API version:** v71
* **Checkout Web Component version:** 6.37.0

## Building and releasing

* `./tools/deploy.sh` builds the production archive `tools/AdyenPayment.zip` from the committed `composer.lock` (runtime dependencies only, E2E test code stripped).
* `./tools/deploy-test.sh` builds the same archive but keeps the `AdyenTest` controller and the `E2ETest` services for the CI end-to-end tests. Never publish that archive.
* Releases are built by CI, not on a workstation: push a bare semver tag equal to `<version>` in `plugin.xml` (for example `5.1.5`) on the `develop` -> `master` merge commit. The `Release` workflow builds the ZIP, verifies it, and attaches `AdyenPayment-<version>.zip`, its `.sha256` checksum, a CycloneDX SBOM and a build provenance attestation to the GitHub Release.
* Verify a downloaded release with `sha256sum -c AdyenPayment-<version>.zip.sha256` and `gh attestation verify AdyenPayment-<version>.zip --repo Adyen/adyen-shopware5`.

## License
MIT license. For more information, see the [LICENSE file](LICENSE).
