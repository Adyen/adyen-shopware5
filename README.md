# Adyen Shopware Plugin by Meteor

##Testing
This command will check if the plugin is compatible with the supported PHP version.
``` bash
composer test-php-compatibility
```

##Formating
You can run the following command to format the code in PSR2.
``` bash
composer format
```

##Packaging
This command will generate a zip which include the correct vendor folder. Files which are not allowed by the Shopware store will be removed.

Create package
``` bash
sh bin/build.sh
```

Create package specific for Shopware 5.3.7
``` bash
sh bin/build-5.3.7.sh
```