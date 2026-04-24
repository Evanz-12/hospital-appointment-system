<?php
echo "<pre>";
echo "getenv DB_HOST: " . var_export(getenv('DB_HOST'), true) . "\n";
echo "getenv MYSQL_HOST: " . var_export(getenv('MYSQL_HOST'), true) . "\n";
echo "getenv MYSQLHOST: " . var_export(getenv('MYSQLHOST'), true) . "\n";
echo "getenv DB_PORT: " . var_export(getenv('DB_PORT'), true) . "\n";
echo "\n--- All env keys containing DB or MYSQL ---\n";
foreach (getenv() as $k => $v) {
    if (stripos($k, 'db') !== false || stripos($k, 'mysql') !== false) {
        echo "$k=" . (stripos($k, 'pass') !== false || stripos($k, 'password') !== false ? '***' : $v) . "\n";
    }
}
echo "</pre>";
