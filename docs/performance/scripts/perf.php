<?php
error_reporting(E_ALL & ~E_DEPRECATED);
// Usage: php perf.php <projectDir> <tokens.csv> -> CSV page,wall_ms,sf_ms,queries,mem_mb
$dir = $argv[1]; $tokens = array_map(fn($l) => str_getcsv($l, ',', '"', ''), file($argv[2], FILE_IGNORE_NEW_LINES));
require $dir.'/vendor/autoload.php';
(new Symfony\Component\Dotenv\Dotenv())->bootEnv($dir.'/.env');
$kernel = new App\Kernel('dev', true); $kernel->boot();
$profiler = $kernel->getContainer()->get('profiler');
echo "label,page,wall_ms,sf_ms,queries,mem_mb\n";
foreach ($tokens as [$label, $page, $wall, $token]) {
    $p = $profiler->loadProfile($token);
    if (!$p) { echo "$label,$page,$wall,NA,NA,NA\n"; continue; }
    $db = $p->getCollector('db'); $time = $p->getCollector('time'); $mem = $p->getCollector('memory');
    printf("%s,%s,%s,%.0f,%d,%.1f\n", $label, $page, $wall, $time->getDuration(), $db->getQueryCount(), $mem->getMemory()/1048576);
}
