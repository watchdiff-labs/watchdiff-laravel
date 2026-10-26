<?php

declare(strict_types=1);

namespace WatchDiff\Laravel\Events;

use WatchDiff\Core\Model\DiffReport;

/**
 * Dispatched by `watchdiff:tick` for every watcher that produced a change,
 * in addition to (not instead of) any `onChange` closure set directly on
 * the WatchConfig. This is the integration point for anything that
 * shouldn't live inside a config-file-driven onChange closure (which can't
 * be set from JSON): queue a notification, dispatch a job, log via the
 * configured logger, record metrics, ...
 */
final class WatcherChanged
{
    public function __construct(
        public readonly DiffReport $report,
    ) {
    }
}
