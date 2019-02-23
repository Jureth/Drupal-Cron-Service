# Cron Service

## Description
The module provides a service which executes on every hook_cron() and calls to 
execute() method for every other service with `cron_service` tag.

## Usage
The simpliest way to use it is to add `cron_service` tag to your service:
```yml
services:
  your_service_name:
    class: \Drupal\your_module\your_class
    tags: [ { name: cron_service } ]
```
And be sure that your_class implements at least
`\Drupal\cron_service\CronServiceInterface`

## API

### Interfaces

- `\Drupal\cron_service\CronServiceInterface` - declares `execute()` method
which must be the entry point of the desired logic. This method will be invoked
on every cron run.
- `\Drupal\cron_service\ScheduledCronServiceInterface` additionally to
CronServiceInterface declares `getNextExecutionTime():int` method which should
return the timestamp when execute() method should be executed next time.
- `\Drupal\cron_service\TimeControllingCronServiceInterface` additionally to
CronServiceInterface declares `shouldRunNow():bool` method which is called
before every call to execute() and can prevent the execution by returning FALSE.
It can contain additional checks of the current time or the current environment.

Interfaces can be freely combined and the manager will invoke the execute() 
method only when all of the checks would pass.

### Service Manager

Public methods:
- `execute()` - Performs checks and execution of all the tagged services. This
method is called by cron hook.
- `addHandler(CronServiceInterface $service, string $id)` - Adds a service to
the internal list of service handlers. Its called by Drupal on building the
services list and must not be used manually.
- `executeHandler(id, force = FALSE)` - Tries to execute the service with the
given id. By default it still checks next execution time and other checks if
they are provided by the service. The force argument allows you skipping that
checks and execute the service in any case. The id of the service is taken from
the service container.
- `forceNextExecution(id): bool` - Forces to bypass checks for the given service
on the next cron run. The service will NOT be called immediately but on the next
cron run the manager will call execute() method without any schedule checks.
Returns TRUE if the execute() method was called, FALSE otherwise.
- `getScheduledCronRunTime(id):int` - Returns the STORED timestamp of the next
execution time for given service or 0 if the service does not provide the
required interface.
