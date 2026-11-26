<#
  Smoke test otomatis untuk situs yang sudah ter-deploy (IP publik).
  Pemakaian:  powershell -File deploy/smoke-test.ps1 -BaseUrl http://1.2.3.4
  Hanya membaca (GET) + 1 percobaan login salah; tidak mengubah data.
#>
param([Parameter(Mandatory)][string]$BaseUrl)

$BaseUrl = $BaseUrl.TrimEnd('/')
$script:fail = 0

function Check($name, [scriptblock]$test) {
    try { $r = & $test; if ($r) { Write-Host "[PASS] $name" -ForegroundColor Green } else { Write-Host "[FAIL] $name" -ForegroundColor Red; $script:fail++ } }
    catch { Write-Host "[FAIL] $name -> $($_.Exception.Message)" -ForegroundColor Red; $script:fail++ }
}

function Get-Status($path) {
    # HttpWebRequest tanpa auto-redirect: di Windows PowerShell 5.1, Invoke-WebRequest -MaximumRedirection 0
    # melempar "Operation is not valid due to the current state of the object" untuk respons 302.
    $req = [System.Net.HttpWebRequest]::Create("$BaseUrl$path")
    $req.AllowAutoRedirect = $false
    $req.Timeout = 20000
    try { $resp = $req.GetResponse() }
    catch [System.Net.WebException] { $resp = $_.Exception.Response; if (-not $resp) { throw } }
    try { return @{ Code = [int]$resp.StatusCode; Headers = $resp.Headers } } finally { $resp.Close() }
}
# 1. Halaman publik harus 200
foreach ($p in '/', '/articles', '/archives', '/jurnal', '/login', '/register',
               '/kebijakan-privasi', '/syarat-penggunaan', '/etika-publikasi') {
    Check "GET $p = 200" { (Get-Status $p).Code -eq 200 }
}

# 2. Halaman terlindungi harus mengalihkan tamu ke /login (302)
foreach ($p in '/dashboard', '/profil', '/notifikasi', '/admin/dashboard', '/author/dashboard',
               '/editor/dashboard', '/reviewer/dashboard') {
    Check "Tamu ke $p dialihkan (302)" { (Get-Status $p).Code -eq 302 }
}

# 3. 404 bukan 500
Check "URL ngawur = 404" { (Get-Status '/tidak-ada-halaman-ini').Code -eq 404 }

# 4. Backdoor dev-login tidak boleh ada di produksi
Check "/dev-login/admin TIDAK tersedia (404)" { (Get-Status '/dev-login/admin').Code -eq 404 }

# 5. Berkas sensitif tidak boleh terbaca
foreach ($p in '/.env', '/.git/config', '/storage/logs/laravel.log', '/composer.json') {
    Check "$p tidak terbuka" { (Get-Status $p).Code -in 403, 404 }
}

# 6. Header keamanan (nginx)
$h = (Get-Status '/').Headers
Check "Header X-Content-Type-Options" { $h['X-Content-Type-Options'] -eq 'nosniff' }
Check "Header X-Frame-Options"        { [bool]$h['X-Frame-Options'] }
Check "Header Referrer-Policy"        { [bool]$h['Referrer-Policy'] }
Check "Server tidak membocorkan versi PHP (X-Powered-By)" { -not $h['X-Powered-By'] }

# 7. Metadata ekspor (jurnal id 1)
Check "metadata.json jurnal 1 valid JSON" { $null -ne (Invoke-RestMethod "$BaseUrl/jurnal/1/metadata.json" -TimeoutSec 20) }

# 8. Waktu respon beranda < 3 detik
Check "Beranda < 3 detik" { (Measure-Command { Invoke-WebRequest "$BaseUrl/" -UseBasicParsing | Out-Null }).TotalSeconds -lt 3 }

# 9. HTTPS (informasi saja)
if ($BaseUrl -like 'http://*') { Write-Host "[INFO] Situs masih HTTP; catat sebagai temuan jika HTTPS diwajibkan." -ForegroundColor Yellow }

Write-Host ""
if ($script:fail -eq 0) { Write-Host "SEMUA LOLOS" -ForegroundColor Green; exit 0 }
Write-Host "$script:fail pemeriksaan GAGAL" -ForegroundColor Red; exit 1
