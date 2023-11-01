# Magento Data Services

The **Magento Data Services Multishipping Module** is responsible for brokering data needed to train machine learning models and build out Magento data-driven features such as Product Recommendations.


## Documentation
Please use this [link](docs) to access the latest Product Recommendations documentation.


## Installation

Refresh the **Magento** instance for the module to take effect.

```bash
cd <magento directory>
php bin/magento module:enable Magento_DataServicesMultishipping
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:clean
```

