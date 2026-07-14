Exception Report AWS Forwarder
===============================

Forwards exception reports stored as individual files to AWS CloudWatch Logs.

This something that AWS CloudWatch Agent cannot do.

You should run it in cron every couple of minutes.

### Requirements

PHP >= 8.1 (the binary name carries the minimum PHP version, e.g. `aws-excfwd-php81`).
On servers with older default PHP, point cron at a newer interpreter explicitly:

```shell
/usr/bin/php8.1 /usr/local/bin/aws-excfwd-php81 push:directory ...
```

For PHP 7.x use an older release (`aws-excfwd`, up to and including the last
release before the PHP 8.1 requirement) from
[GitHub releases](https://github.com/mageops/php-report-cloudwatch-forwarder/releases).

### Example usage for Magento

```
bin/cli.php push:directory -v --formatter=serialized_array --region=eu-central-1 --group=magento-exc-report /var/www/magento-base/current/var/report/  
```

### Build

```
composer install
composer build
```

### Install

#### Fetch from GitHub releases

Find the latest release and install the PHAR directly.

```shell
curl -Ls https://github.com/mageops/php-report-cloudwatch-forwarder/releases/latest/download/aws-excfwd-php81 -o /usr/local/bin/aws-excfwd-php81 && chmod +x /usr/local/bin/aws-excfwd-php81
```


#### CentOS RPM Package

Install the package from [MageOps RPM Repository](https://mageops.github.io/rpm/).


#### Use composer global install (not recommened)

```shell
composer global require creativestyle/mageops-report-cloudwatch-forwarder
```
