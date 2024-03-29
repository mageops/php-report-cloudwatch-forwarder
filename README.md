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

#### Fetch from GitHub releases

Find the latest release and install the PHAR directly.

```shell
curl -Ls https://github.com/mageops/php-report-cloudwatch-forwarder/releases/latest/download/aws-excfwd -o /usr/local/bin/aws-excfwd && chmod +x /usr/local/bin/aws-excfwd
```


#### CentOS RPM Package

Install the package from [MageOps RPM Repository](https://mageops.github.io/rpm/).


#### Use composer global install (not recommened)

```shell
composer global require creativestyle/mageops-report-cloudwatch-forwarder
```
