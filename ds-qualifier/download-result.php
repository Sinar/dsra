<?php
/**
 * Download Handler for Encrypted Assessment Results
 *
 * Authenticates download requests via token, validates expiry and download
 * limits, then decrypts and serves the requested file.
 */

$dataDir = $_SERVER['DATA_DIR'] ?? getenv('DATA_DIR') ?: __DIR__ . '/../data';
$tokensFile = $dataDir . '/tokens.json';
$gpgKeyId = $_SERVER['GPG_KEY_ID'] ?? getenv('GPG_KEY_ID') ?: 'team@sinarproject.org';

$token = $_GET['token'] ?? '';
$format = $_GET['format'] ?? ''; // 'json' or 'csv'

if (empty($token)) {
    http_response_code(400);
    die('Missing token parameter.');
}

// Load tokens
$tokens = [];
if (file_exists($tokensFile)) {
    $tokens = json_decode(file_get_contents($tokensFile), true) ?? [];
}

if (!isset($tokens[$token])) {
    http_response_code(404);
    echo '<!DOCTYPE html>
<html lang="en-us" class="pf-theme-dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Invalid Link - Digital Sovereignty Readiness Assessment</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css" />
<link rel="stylesheet" href="../css/tab-dark.css" />
<link rel="stylesheet" href="../css/patternfly.css" />
<link rel="stylesheet" href="../css/patternfly-addons.css" />
<script src="https://kit.fontawesome.com/8a8c57f9cf.js" crossorigin="anonymous"></script>
</head>
<body style="background-color: #151515; color: #ccc; padding: 2rem;">
<div class="container" style="max-width: 600px; margin: 0 auto;">
<div style="text-align: center; padding: 2rem; background: #3a1a1a; border-radius: 8px; border: 2px solid #c9190b;">
<i class="fa-solid fa-triangle-exclamation" style="color: #c9190b; font-size: 2rem; margin-bottom: 1rem;"></i>
<h2 style="color: #c9190b;">Invalid or Expired Link</h2>
<p style="color: #ccc;">This download link is not valid. It may have expired or the token is incorrect.</p>
<p style="margin-top: 1.5rem;"><a href="https://github.com/Sinar/dsra" style="color: #9ec7fc;">Sinar Project DSRA</a></p>
</div></div></body></html>';
    exit;
}

$meta = $tokens[$token];
$jsonFile = $dataDir . '/' . $meta['file_json'];
$csvFile = $dataDir . '/' . $meta['file_csv'];

// Check expiry
$now = new DateTime('now', new DateTimeZone('Asia/Kuala_Lumpur'));
$expiresAt = new DateTime($meta['expires_at']);

if ($now > $expiresAt) {
    unset($tokens[$token]);
    file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT));
    http_response_code(410);
    echo '<!DOCTYPE html>
<html lang="en-us" class="pf-theme-dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Link Expired - Digital Sovereignty Readiness Assessment</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css" />
<link rel="stylesheet" href="../css/tab-dark.css" />
<link rel="stylesheet" href="../css/patternfly.css" />
<link rel="stylesheet" href="../css/patternfly-addons.css" />
<script src="https://kit.fontawesome.com/8a8c57f9cf.js" crossorigin="anonymous"></script>
</head>
<body style="background-color: #151515; color: #ccc; padding: 2rem;">
<div class="container" style="max-width: 600px; margin: 0 auto;">
<div style="text-align: center; padding: 2rem; background: #3a1a1a; border-radius: 8px; border: 2px solid #c9190b;">
<i class="fa-solid fa-clock" style="color: #c9190b; font-size: 2rem; margin-bottom: 1rem;"></i>
<h2 style="color: #c9190b;">Link Expired</h2>
<p style="color: #ccc;">This download link expired on ' . htmlspecialchars($meta['expires_at']) . '.</p>
<p style="color: #ccc;">Please contact the Sinar Project team to request a new link.</p>
<p style="margin-top: 1.5rem;"><a href="https://github.com/Sinar/dsra" style="color: #9ec7fc;">Sinar Project DSRA</a></p>
</div></div></body></html>';
    exit;
}

// Check download limit
if ($meta['downloads_remaining'] <= 0) {
    http_response_code(429);
    echo '<!DOCTYPE html>
<html lang="en-us" class="pf-theme-dark">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Download Limit Reached - Digital Sovereignty Readiness Assessment</title>
<link rel="stylesheet" href="../css/bootstrap.min.css">
<link rel="stylesheet" href="../css/style.css" />
<link rel="stylesheet" href="../css/tab-dark.css" />
<link rel="stylesheet" href="../css/patternfly.css" />
<link rel="stylesheet" href="../css/patternfly-addons.css" />
<script src="https://kit.fontawesome.com/8a8c57f9cf.js" crossorigin="anonymous"></script>
</head>
<body style="background-color: #151515; color: #ccc; padding: 2rem;">
<div class="container" style="max-width: 600px; margin: 0 auto;">
<div style="text-align: center; padding: 2rem; background: #3a1a1a; border-radius: 8px; border: 2px solid #c9190b;">
<i class="fa-solid fa-download" style="color: #c9190b; font-size: 2rem; margin-bottom: 1rem;"></i>
<h2 style="color: #c9190b;">Download Limit Reached</h2>
<p style="color: #ccc;">This file has been downloaded the maximum number of times (5/5).</p>
<p style="color: #ccc;">Please contact the Sinar Project team to request a new link.</p>
<p style="margin-top: 1.5rem;"><a href="https://github.com/Sinar/dsra" style="color: #9ec7fc;">Sinar Project DSRA</a></p>
</div></div></body></html>';
    exit;
}

// If a specific format is requested, decrypt and stream the file
if ($format === 'json' || $format === 'csv') {
    $file = ($format === 'json') ? $jsonFile : $csvFile;
    $mimeType = ($format === 'json') ? 'application/json' : 'text/csv';
    $filename = ($format === 'json') ? 'assessment-results.json' : 'assessment-results.csv';

    if (!file_exists($file)) {
        http_response_code(404);
        die('File not found.');
    }

    // Decrement download count
    $tokens[$token]['downloads_remaining']--;
    file_put_contents($tokensFile, json_encode($tokens, JSON_PRETTY_PRINT));

    // Decrypt and stream
    $command = sprintf(
        'gpg --decrypt --recipient %s --trust-model always 2>/dev/null',
        escapeshellarg($gpgKeyId)
    );
    $process = proc_open(
        $command,
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );

    if (!is_resource($process)) {
        http_response_code(500);
        die('Failed to decrypt file.');
    }

    fwrite($pipes[0], file_get_contents($file));
    fclose($pipes[0]);
    $decrypted = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    proc_close($process);

    if ($decrypted === false || empty($decrypted)) {
        http_response_code(500);
        die('Failed to decrypt file. The GPG key may not be available.');
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($decrypted));
    echo $decrypted;
    exit;
}

// Show landing page with download buttons
$remaining = $meta['downloads_remaining'];
$expiresDisplay = (new DateTime($meta['expires_at']))->format('F j, Y');

echo '<!DOCTYPE html>
<html lang="en-us" class="pf-theme-dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Download Results - Digital Sovereignty Readiness Assessment</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/tab-dark.css" />
  <link rel="stylesheet" href="../css/patternfly.css" />
  <link rel="stylesheet" href="../css/patternfly-addons.css" />
  <script src="https://kit.fontawesome.com/8a8c57f9cf.js" crossorigin="anonymous"></script>
  <style>
    body { background-color: #151515; color: #ccc; padding: 2rem; }
    .download-card { max-width: 500px; margin: 2rem auto; text-align: center; padding: 2rem; background: #1a1a1a; border-radius: 8px; border: 1px solid #444; }
    .download-card h2 { color: #9ec7fc; margin-bottom: 0.5rem; }
    .org-name { color: #fff; font-size: 1.1rem; margin-bottom: 1.5rem; }
    .btn-download { display: inline-block; padding: 0.75rem 2rem; margin: 0.5rem; font-size: 1.1rem; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: #fff; }
    .btn-download.json { background: #0d60f8; }
    .btn-download.csv { background: #2b9c2b; }
    .btn-download:hover { opacity: 0.9; }
    .meta-info { margin-top: 1.5rem; font-size: 0.85rem; color: #999; }
    .meta-info span { display: block; margin-top: 0.25rem; }
  </style>
</head>
<body>
  <div class="download-card">
    <i class="fa-solid fa-file-shield" style="color: #2b9c2b; font-size: 2.5rem; margin-bottom: 1rem;"></i>
    <h2>Assessment Results Available</h2>
    <p class="org-name">' . htmlspecialchars($meta['org'] ?? 'Sinar Project DSRA') . '</p>
    <p>Your assessment results are ready for download. These files are decrypted server-side — no GPG software needed.</p>
    <div style="margin-top: 1.5rem;">
      <a href="?token=' . urlencode($token) . '&format=json" class="btn-download json"><i class="fa-solid fa-file-code"></i> Download JSON</a>
      <a href="?token=' . urlencode($token) . '&format=csv" class="btn-download csv"><i class="fa-solid fa-file-csv"></i> Download CSV</a>
    </div>
    <div class="meta-info">
      <span><i class="fa-solid fa-clock"></i> Expires: ' . htmlspecialchars($expiresDisplay) . '</span>
      <span><i class="fa-solid fa-download"></i> Downloads remaining: ' . $remaining . '/5</span>
    </div>
  </div>
</body>
</html>';
