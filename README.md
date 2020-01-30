# Magento2-BmlPayment
Magento 2 BML PAYMENT GATEWAY ( Bank of Maldives )

![Magento >= 2.0.0](https://img.shields.io/badge/magento-%3E=2.0.0-blue.svg)

Installation 
--------------

- ### php bin/magento module:enable Elightwalk_BmlPayment
- ### php bin/magento setup:upgrade
- ### php bin/magento setup:di:compile

Module Features 
--------------

- ### Redirect to BML Payment Gateway for transaction.
- ### Responce Invoice created with transaction id Tracking.
- ### transaction failure make order status cancel.


Troubleshoot 
--------------

- If you facing merory issue during the above commands follow to add params in the command -d memory_limit=-1.
for example php bin/magento -d memory_limit=-1 setup:upgrade


Help & Contact  
--------------

For extra help contact us on https://www.elightwalk.com/

