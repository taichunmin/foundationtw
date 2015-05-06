<?php

$path = dirname(__DIR__);
$now = date('Y-m-d H:i:s');

exec("/bin/rm -Rf {$path}/tmp/" . date('Y') . '*');

exec("cd {$path} && /usr/bin/git pull");

exec("/usr/bin/php -q {$path}/kiang/3_list_new.php");

exec("/usr/bin/php -q {$path}/kiang/2_details.php");

exec("cd {$path} && /usr/bin/git add -A");

exec("cd {$path} && /usr/bin/git commit --author 'auto commit <noreply@localhost>' -m 'update datasets @ {$now}'");

exec("cd {$path} && /usr/bin/git push origin master");