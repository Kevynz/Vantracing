Param(
  [int]$Port = 5173,
  [string]$Root = (Get-Location).Path
)

# Try to use HttpListener without explicit Add-Type (PowerShell 5.1 usually loads it)
$listener = New-Object System.Net.HttpListener
$prefix = "http://localhost:$Port/"
$listener.Prefixes.Add($prefix)
$listener.Start()
Write-Host "Static server running at $prefix (root: $Root)" -ForegroundColor Green

$mime = @{ 
  '.html'='text/html; charset=utf-8';
  '.htm' ='text/html; charset=utf-8';
  '.css' ='text/css';
  '.js'  ='application/javascript';
  '.json'='application/json';
  '.png' ='image/png';
  '.jpg' ='image/jpeg';
  '.jpeg'='image/jpeg';
  '.gif' ='image/gif';
  '.svg' ='image/svg+xml';
  '.ico' ='image/x-icon';
  '.map' ='application/json'
}

try {
  while ($listener.IsListening) {
    $context = $listener.GetContext()
    $request = $context.Request
    $response = $context.Response

    $localPath = $request.Url.LocalPath.TrimStart('/')
    if ([string]::IsNullOrWhiteSpace($localPath)) { $localPath = 'index.html' }
    $filePath = Join-Path $Root $localPath

    if (Test-Path $filePath -PathType Container) {
      $filePath = Join-Path $filePath 'index.html'
    }

    if (-not (Test-Path $filePath)) {
      $response.StatusCode = 404
      $bytes = [System.Text.Encoding]::UTF8.GetBytes("Not Found: $localPath")
      $response.OutputStream.Write($bytes,0,$bytes.Length)
      $response.Close()
      continue
    }

    $ext = [System.IO.Path]::GetExtension($filePath).ToLower()
    $ct = $mime[$ext]
    if (-not $ct) { $ct = 'application/octet-stream' }

    try {
      $bytes = [System.IO.File]::ReadAllBytes($filePath)
      $response.ContentType = $ct
      $response.ContentLength64 = $bytes.Length
      $response.AddHeader('Cache-Control','no-cache, no-store, must-revalidate')
      $response.OutputStream.Write($bytes,0,$bytes.Length)
    } finally {
      $response.OutputStream.Close()
      $response.Close()
    }

    Write-Host ("{0} {1} -> {2}" -f $request.HttpMethod, $request.Url.LocalPath, $filePath)
  }
} finally {
  $listener.Stop()
  $listener.Close()
}
