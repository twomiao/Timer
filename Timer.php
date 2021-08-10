<?php
declare(strict_types=1);

namespace Timer;

class Timer
{
    protected static $_timerId = 0;

    protected static $_tasks = [];

    protected static $_taskStatus = [];

    public static function init()
    {
        if (function_exists('pcntl_signal') && version_compare(PHP_VERSION, '7.1.0', '>=')) {
            \pcntl_async_signals(true);
            \pcntl_signal(\SIGALRM, '\Timer\Timer::alarmHandler', false);
        }
    }

    public static function alarmHandler(): void
    {
        \pcntl_alarm(1);

        if (empty(self::$_tasks)) {
            \pcntl_alarm(0);
            return;
        }

        $now = time();

        foreach (static::$_tasks as $run_now => $task_item) {
            if ($run_now <= $now) {
                foreach ($task_item as $timerId => $task) {
                    $interval = $task[0];
                    $callable = $task[1];
                    $args = $task[2];
                    $persistent = $task[3];

                    try {
                        $callable($args);
                    } catch (\Exception $e) {
                    }

                    if ($persistent && !empty(static::$_taskStatus[$timerId])) {
                        $new_run_now = time() + $interval;
                        static::$_tasks[$new_run_now][$timerId] = $task;
                    }
                }
                unset(static::$_tasks[$run_now]);
            }
        }
    }

    public static function add(int $interval, callable $task, ?array $args = null, ?bool $persistent = null)
    {
        if ($interval < 1) {
            throw new \InvalidArgumentException("Invalid interval: {$interval}.");
        }

        if (empty(static::$_tasks)) {
            \pcntl_alarm(1);
        }

        $timerId = (static::$_timerId > PHP_INT_MAX) ? 1 : ++static::$_timerId;

        $run_now = $interval + time();

        $timer = [
            $interval,
            $task,
            $args ?: [],
            $persistent ? true : false
        ];

        static::$_taskStatus[$timerId] = true;
        static::$_tasks[$run_now][$timerId] = $timer;

        return $timerId;
    }

    public static function del(int $timerId): bool
    {
        if ($timerId < 1) {
            throw new \InvalidArgumentException("Invalid timerId: {$timerId}.");
        }

        foreach (static::$_tasks as $now_time => $task_item) {
            if (array_key_exists($timerId, $task_item)) {
                unset(static::$_tasks[$now_time][$timerId]);
            }
        }

        if (isset(static::$_taskStatus[$timerId])) {
            unset(static::$_taskStatus[$timerId]);
        }

        return true;
    }

    public static function delAll()
    {
        \pcntl_alarm(0);
        static::$_tasks = static::$_taskStatus = null;
        static::$_timerId = 0;
    }
}