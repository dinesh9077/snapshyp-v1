<?php

namespace App\Queue;

use Illuminate\Queue\Worker;

/**
 * Laravel 8 uses process signals (job timeouts, graceful restart) whenever the pcntl
 * extension is loaded. Shared hosting (Hostinger) loads pcntl but disables its functions,
 * so queue:work crashed on start with "Call to undefined function pcntl_signal()".
 *
 * This worker uses signals only when the functions can really be called; otherwise it
 * runs without them (no per-job timeout and no signal-based restart — --max-time and
 * --stop-when-empty still work).
 */
class SignalSafeWorker extends Worker
{
    public static function fromWorker(Worker $worker): self
    {
        return new self(
            $worker->manager,
            $worker->events,
            $worker->exceptions,
            $worker->isDownForMaintenance,
            $worker->resetScope
        );
    }

    protected function supportsAsyncSignals()
    {
        return extension_loaded('pcntl')
            && function_exists('pcntl_async_signals')
            && function_exists('pcntl_signal')
            && function_exists('pcntl_alarm');
    }
}
