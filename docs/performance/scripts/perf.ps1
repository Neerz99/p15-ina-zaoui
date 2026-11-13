param([string]$Label = 'run', [int]$N = 5, [string[]]$Pages = @('/','/guests','/guest/2','/portfolio','/portfolio/1','/about'))
$proj = (Resolve-Path "$PSScriptRoot\..\..\..").Path; $out = $PSScriptRoot
$csv = "$out\tokens-$Label.csv"; Remove-Item $csv -ErrorAction SilentlyContinue
foreach($u in $Pages){ Invoke-WebRequest "http://127.0.0.1:8015$u" -UseBasicParsing -TimeoutSec 120 | Out-Null } # warmup
foreach($i in 1..$N){ foreach($u in $Pages){
  $sw = [Diagnostics.Stopwatch]::StartNew(); $r = Invoke-WebRequest "http://127.0.0.1:8015$u" -UseBasicParsing -TimeoutSec 120; $sw.Stop()
  "$Label,$u,$($sw.ElapsedMilliseconds),$($r.Headers['X-Debug-Token'])" | Add-Content $csv } }
php "$out\perf.php" $proj $csv | Set-Content "$out\result-$Label.csv"
Get-Content "$out\result-$Label.csv"
