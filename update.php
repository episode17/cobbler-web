<?php
$update = false;
$redis = new Redis();

try {
    $redis->connect('127.0.0.1', 6379);
    
    if (isset($_GET['c']) && !empty($_GET['c'])) {
        $redis->set('cobbler:count', $_GET['c']);
        $update = true;
    }
    
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        $redis->set('cobbler:speed', $_GET['s']);
        $update = true;
    }
    
    if ($update) {
        $redis->publish('cobbler', 'update');
        echo 'OK';
    } else {
        echo 'NO_UP';
    }

    $redis->close();
} catch (Exception $e) {
    echo 'ERROR ' . $e->getMessage();
}
