# SMS Service
Use APIs exposed by Chuanglan to implement SMS-related service, which includes sending SMS, checking quota/surplus, etc.

This service provides only the most basic features, and designated to be integrated into other project as infrastructure. 

```php
use Ouarea\Sms\Chuanglan\Service as ChuanglanSmsService;

$service = new ChuanglanSmsService('account', 'password');
// - or the full version
// $service = new ChuanglanSmsService('account', 'password', $optionsOfService, $instanceOfClient);

// send message
$service->send('message', $subscriber, $optionsOfMessage);
// query quota
$quota = $service->queryQuota();
```

## API
### construct
``__construct($account, $password, array $options = [], $httpClient = null)``

* ``$account``  chuanglan's account used to send message
* ``$password`` password that goes with account, should be **MD5**'d
* ``$options``  options for creating a ChuanglanSmsService. Including:
	* ``name`` name of merchant(e.g., 【XXXX】), can be either prepend or append to the message.
	* ``affix`` 附加号码 a part of sender's number that will be used to
	* ``send_url``  url for sending message (typically, you will not change it at all, since there is no other environment prepared by chuanglan currently)
	* ``quota_url``  url for querying quota (typically, you will not change it at all, since there is no other environment prepared by chuanglan currently)
* ``$httpClient `` GuzzleHttp client instance

### send message
``send($message, $subscriber)``

* ``$message`` message to deliver
* ``$subscriber`` subscriber or a list of subscribers

### query quota
``queryQuota()``

No argument, and it returns the surplus of your account.