<?php
/**
 * Error Submission Handler for Digital Sovereignty Readiness Assessment
 *
 * Receives error reports from the results page when an email submission fails,
 * and sends the error details to the Sinar Project team via Symfony Mailer.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

$errorUrl = $_POST['error_url'] ?? 'N/A';
$errorMessage = $_POST['error_message'] ?? 'N/A';
$errorTimestamp = $_POST['error_timestamp'] ?? 'N/A';

$mailerDsn = $_SERVER['MAILER_DSN'] ?? getenv('MAILER_DSN');

if (empty($mailerDsn)) {
    echo '<!DOCTYPE html>
<html lang="en-us" class="pf-theme-dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error Report - Digital Sovereignty Readiness Assessment</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/tab-dark.css" />
  <link rel="stylesheet" href="../css/patternfly.css" />
  <link rel="stylesheet" href="../css/patternfly-addons.css" />
</head>
<body style="background-color: #151515; color: #ccc; padding: 2rem;">
  <div class="container" style="max-width: 700px; margin: 0 auto;">
    <div style="text-align: center; padding: 2rem; background: #3a1a1a; border-radius: 8px; border: 2px solid #c9190b;">
      <i class="fa-solid fa-exclamation-triangle" style="color: #c9190b; font-size: 2rem; margin-bottom: 1rem;"></i>
      <h2 style="color: #c9190b;">Email Not Configured</h2>
      <p style="color: #ccc;">The email system is not configured. Please file the issue manually on GitHub:</p>
      <p style="margin: 1rem 0;">
        <a href="https://github.com/Sinar/dsra/issues/new?title=' . urlencode('Bug: ' . $errorMessage) . '&body=' . urlencode("## Error Report\n\n- **URL:** $errorUrl\n- **Error:** $errorMessage\n- **Timestamp (UTC+8):** $errorTimestamp\n\n<!-- Please assign to @samqi -->") . '&labels[]=bug"
           target="_blank" rel="noopener noreferrer"
           style="display: inline-block; padding: 0.75rem 2rem; background: #0d60f8; color: white; text-decoration: none; border-radius: 4px; font-size: 1.1rem;">
          <i class="fa-brands fa-github"></i> Submit issue via GitHub
        </a>
      </p>
      <p style="margin-top: 1.5rem;">
        <a href="results.php" style="color: #9ec7fc;">Back to results</a>
      </p>
    </div>
  </div>
</body>
</html>';
    exit;
}

try {
    $transport = Transport::fromDsn($mailerDsn);
    $mailer = new Mailer($transport);

    $mailerFrom = $_SERVER['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'noreply@sinarproject.org';
    $mailerTo = $_SERVER['MAILER_TO'] ?? getenv('MAILER_TO') ?: 'team@sinarproject.org';

    $emailText = "DSRA Error Report\n";
    $emailText .= "================\n\n";
    $emailText .= "Error URL: {$errorUrl}\n";
    $emailText .= "Error: {$errorMessage}\n";
    $emailText .= "Timestamp (UTC+8): {$errorTimestamp}\n";

    $email = (new Email())
        ->from($mailerFrom)
        ->to($mailerTo)
        ->subject("DSRA Error Report - {$errorMessage}")
        ->text($emailText);

    $mailer->send($email);

    header('Location: results.php?error_submitted=1');
} catch (Exception $e) {
    error_log('Failed to send error report: ' . $e->getMessage());
    echo '<!DOCTYPE html>
<html lang="en-us" class="pf-theme-dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Error Report - Digital Sovereignty Readiness Assessment</title>
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <link rel="stylesheet" href="../css/style.css" />
  <link rel="stylesheet" href="../css/tab-dark.css" />
  <link rel="stylesheet" href="../css/patternfly.css" />
  <link rel="stylesheet" href="../css/patternfly-addons.css" />
  <script src="https://kit.fontawesome.com/8a8c57f9cf.js" crossorigin="anonymous"></script>
</head>
<body style="background-color: #151515; color: #ccc; padding: 2rem;">
  <div class="container" style="max-width: 700px; margin: 0 auto;">
    <div style="text-align: center; padding: 2rem; background: #3a1a1a; border-radius: 8px; border: 2px solid #c9190b;">
      <i class="fa-solid fa-exclamation-triangle" style="color: #c9190b; font-size: 2rem; margin-bottom: 1rem;"></i>
      <h2 style="color: #c9190b;">Failed to Send Error Report</h2>
      <p style="color: #ccc;">The email could not be sent. Please file the issue manually on GitHub:</p>
      <p style="margin: 1rem 0;">
        <a href="https://github.com/Sinar/dsra/issues/new?title=' . urlencode('Bug: ' . $errorMessage) . '&body=' . urlencode("## Error Report\n\n- **URL:** $errorUrl\n- **Error:** $errorMessage\n- **Timestamp (UTC+8):** $errorTimestamp\n- **Original send error:** " . $e->getMessage() . "\n\n<!-- Please assign to @samqi -->") . '&labels[]=bug"
           target="_blank" rel="noopener noreferrer"
           style="display: inline-block; padding: 0.75rem 2rem; background: #0d60f8; color: white; text-decoration: none; border-radius: 4px; font-size: 1.1rem;">
          <i class="fa-brands fa-github"></i> Submit issue via GitHub
        </a>
      </p>
      <p style="margin-top: 1.5rem;">
        <a href="results.php" style="color: #9ec7fc;">Back to results</a>
      </p>
    </div>
  </div>
</body>
</html>';
}
exit;
