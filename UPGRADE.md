# Upgrading

## From v1 to v2 (workflow-engine-core 3)

1. `composer require solution-forest/workflow-engine-laravel:^2.0` (pulls workflow-engine-core `^3.0`).
2. Add the new columns to your table:

   ```bash
   php artisan vendor:publish --tag=workflow-engine-upgrade-migrations
   php artisan migrate
   ```

   Existing rows get `revision = 1`. New installs publish `workflow-engine-migrations` as before.
3. Run the Laravel scheduler (`php artisan schedule:work`, or cron `schedule:run` every minute):
   `workflow:resume-due` is scheduled for you. Set `WORKFLOW_SCHEDULE_RESUME_DUE=false` to schedule it yourself.
4. Check these behaviour changes:
   - `DelayAction` / `->delay()` no longer sleeps. The workflow becomes `waiting` and continues when
     `workflow:resume-due` runs after the time has passed.
   - Step timeouts are no longer enforced with SIGALRM by default. Run long steps on the queue
     (`StartWorkflow` / `SignalWorkflow`, timeout `WORKFLOW_QUEUE_TIMEOUT`), or set
     `WORKFLOW_SIGNAL_TIMEOUTS=true` in a CLI worker that runs one workflow at a time.
   - A save from a stale copy throws `ConcurrentModificationException`. Load the instance again and repeat.
   - `resume()` refuses an instance that waits for a signal; use `signal()`.
   - Do not save `Models\WorkflowInstance` yourself; it is a read model.
   - The `workflow-engine` placeholder command is gone.
5. If you implement your own `StorageAdapter`, see the core package's UPGRADE.md.
