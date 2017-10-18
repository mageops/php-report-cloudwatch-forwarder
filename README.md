Exception Report AWS Logger
===========================

Logs exception reports stored as individual files to AWS CloudWatch Logs.

This something that `awslog` client cannot do.

You should run it in cron every couple of minutes.

### Example usage for magento

```
bin/cli.php push:directory -v --formatter=serialized_array --region=eu-central-1 --group=magento-exc-report /var/www/magento-base/current/var/report/  
```

### Build

```
composer install --dev
composer build
```

You will find the final executable in `/build/exclog`.