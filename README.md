Exception Report AWS Logger
===========================

Logs exception reports stored as individual files to AWS CloudWatch Logs.

This something that `awslog` client cannot do.

You should run it in cron.

### Build

```
composer install --dev
composer build
```

You will find the final executable in `/build/exclog`.