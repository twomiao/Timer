<?php
require __DIR__ . '/Timer.php';

\Timer\Timer::init();


$c = 10;
$timer_id = \Timer\Timer::add(1, function () use (&$timer_id) {
    global $c;
    $c--;
    var_dump($c);

    if ($c === 2) {
        \Timer\Timer::del($timer_id);
    }

}, null, true);

$count = 60;
while ($count--) {
    sleep(1);
}