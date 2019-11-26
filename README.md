Exception Report AWS Forwarder
===============================

Forwards exception reports stored as individual files to AWS CloudWatch Logs.

This something that AWS CloudWatch Agent cannot do.

You should run it in cron every couple of minutes.

### Example usage for Magento

```
bin/cli.php push:directory -v --formatter=serialized_array --region=eu-central-1 --group=magento-exc-report /var/www/magento-base/current/var/report/  
```

### Build

```
composer install --dev
composer build
```

### Install

#### CentOS RPM Package

Install the package from [MageOps RPM Repository](https://mageops.github.io/rpm/).
