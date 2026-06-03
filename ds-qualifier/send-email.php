<?php
/**
 * Email Submission Handler for Digital Sovereignty Readiness Assessment
 *
 * Receives submission from the "Send to Sinar Project team" button on results.php,
 * reads assessment data from session, generates a PDF, and sends via Symfony Mailer.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Start session to retrieve assessment data
session_start();

// Check if we have assessment data in session
if (!isset($_SESSION['assessment_data']) || empty($_SESSION['assessment_data'])) {
    header('Location: results.php?error=no_data');
    exit;
}

$assessmentData = $_SESSION['assessment_data'];
$respondentData = $_SESSION['respondent_data'] ?? [];

// Validate respondent data exists
if (empty($respondentData)) {
    header('Location: results.php?error=no_respondent');
    exit;
}

// Load questions configuration
$questions = require_once __DIR__ . '/config.php';

// Load profiles and get selected profile
$profiles = require_once __DIR__ . '/profiles.php';
$selectedProfile = isset($assessmentData['profile']) ? $assessmentData['profile'] : 'balanced';

// Validate profile exists
if (!isset($profiles[$selectedProfile])) {
    $selectedProfile = 'balanced';
}

$profileData = $profiles[$selectedProfile];

// Handle custom weights if custom profile is selected
if ($selectedProfile === 'custom') {
    $domainWeights = [];
    foreach ($questions as $domainName => $domainData) {
        $paramName = 'custom_weight_' . str_replace(' ', '_', $domainName);
        if (isset($assessmentData[$paramName])) {
            $weight = floatval($assessmentData[$paramName]);
            $domainWeights[$domainName] = max(1.0, min(2.0, $weight));
        } else {
            $domainWeights[$domainName] = 1.0;
        }
    }
} else {
    $domainWeights = $profileData['weights'];
}

// Initialize scoring arrays
$totalScore = 0;
$weightedScore = 0;
$maxScore = 21;
$domainScores = [];
$domainMaxScores = [];
$domainWeightedScores = [];
$domainResponses = [];
$unknownQuestions = [];

// Initialize domain scores
foreach ($questions as $domainName => $domainData) {
    $domainScores[$domainName] = 0;
    $domainMaxScores[$domainName] = count($domainData['questions']);
    $domainResponses[$domainName] = [];
}

// Calculate scores
foreach ($assessmentData as $key => $value) {
    if (preg_match('/^(ds|ts|os|as|oss|eo|ms)\d+$/', $key)) {
        foreach ($questions as $domainName => $domainData) {
            foreach ($domainData['questions'] as $question) {
                if ($question['id'] === $key) {
                    if ($value === 'unknown') {
                        $unknownQuestions[] = [
                            'domain' => $domainName,
                            'question' => $question['text'],
                            'tooltip' => $question['tooltip'] ?? '',
                            'link' => $question['link'] ?? null
                        ];
                    } else {
                        $intValue = intval($value);
                        $totalScore += $intValue;
                        $domainScores[$domainName] += $intValue;
                        if ($intValue > 0) {
                            $domainResponses[$domainName][] = $question['text'];
                        }
                    }
                    break 2;
                }
            }
        }
    }
}

// Calculate weighted scores
$totalWeight = 0;
$weightedSum = 0;

foreach ($domainScores as $domainName => $score) {
    $maxForDomain = $domainMaxScores[$domainName];
    $weight = $domainWeights[$domainName] ?? 1.0;
    $domainPercentage = $maxForDomain > 0 ? ($score / $maxForDomain) : 0;
    $weightedDomainScore = $domainPercentage * $weight;
    $domainWeightedScores[$domainName] = $weightedDomainScore;
    $weightedSum += $weightedDomainScore;
    $totalWeight += $weight;
}

$weightedScore = $totalWeight > 0 ? ($weightedSum / $totalWeight) * 21 : 0;

// Determine maturity level
if ($weightedScore <= 4.2) {
    $maturityLevel = 'Initial';
    $maturityColor = '#c9190b';
} elseif ($weightedScore <= 8.4) {
    $maturityLevel = 'Managed';
    $maturityColor = '#ec7a08';
} elseif ($weightedScore <= 12.6) {
    $maturityLevel = 'Defined';
    $maturityColor = '#ffc107';
} elseif ($weightedScore <= 16.8) {
    $maturityLevel = 'Quantitatively Managed';
    $maturityColor = '#8bc34a';
} else {
    $maturityLevel = 'Optimizing';
    $maturityColor = '#2aaa04';
}

$scorePercentage = round(($weightedScore / $maxScore) * 100);
$timezone = new DateTimeZone('Asia/Kuala_Lumpur');
$assessmentDate = (new DateTime('now', $timezone))->format('F j, Y \a\t g:i A');

// Build email body
$emailText = "Digital Sovereignty Readiness Assessment Results\n";
$emailText .= "==============================================\n\n";
$emailText .= "Assessment Date: {$assessmentDate}\n\n";
$emailText .= "--- Respondent Details ---\n";
$emailText .= "Position: {$respondentData['position']}\n";
$emailText .= "Organisation: {$respondentData['org']}\n";
$emailText .= "Size: {$respondentData['size']}\n";
$emailText .= "State: {$respondentData['state']}\n\n";
$emailText .= "--- Assessment Results ---\n";
$emailText .= "Profile: {$profileData['name']}\n";
$emailText .= "Maturity Level: {$maturityLevel}\n";
$emailText .= "Weighted Score: {$scorePercentage}% (" . number_format($weightedScore, 1) . "/{$maxScore})\n";
$emailText .= "Raw Score: {$totalScore}/{$maxScore}\n\n";
$emailText .= "Per-Domain Breakdown:\n";

foreach ($questions as $domainName => $domainData) {
    $score = $domainScores[$domainName] ?? 0;
    $maxDomainScore = count($domainData['questions']);
    $percentage = $maxDomainScore > 0 ? round(($score / $maxDomainScore) * 100) : 0;
    $weight = $domainWeights[$domainName] ?? 1.0;
    $emailText .= "  {$domainName}: {$score}/{$maxDomainScore} ({$percentage}%) - Weight: {$weight}x\n";
}

if (!empty($unknownQuestions)) {
    $emailText .= "\nQuestions marked as 'Don't Know':\n";
    foreach ($unknownQuestions as $uq) {
        $emailText .= "  - [{$uq['domain']}] {$uq['question']}\n";
    }
}

// Generate PDF attachment
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Digital Sovereignty Readiness Assessment Results</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; padding: 20px; font-size: 11pt; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid ' . $maturityColor . '; padding-bottom: 20px; }
        .header h1 { color: #151515; font-size: 24px; }
        .score-card { background: ' . $maturityColor . '; color: white; padding: 20px; text-align: center; margin-bottom: 30px; }
        .score-card h2 { font-size: 26px; }
        .score-circle { font-size: 42px; font-weight: bold; margin: 15px 0; }
        .section { margin-bottom: 25px; }
        .section h3 { color: ' . $maturityColor . '; border-bottom: 2px solid ' . $maturityColor . '; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th { background: #f5f5f5; padding: 8px; border: 1px solid #ddd; text-align: left; }
        table td { padding: 8px; border: 1px solid #ddd; }
        .respondent-section { background: #f9f9f9; padding: 15px; border-left: 4px solid ' . $maturityColor . '; margin-bottom: 20px; }
        .respondent-section h3 { margin-top: 0; border-bottom: none; }
        .footer { text-align: center; margin-top: 30px; padding-top: 15px; border-top: 1px solid #ddd; font-size: 9pt; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Digital Sovereignty Readiness Assessment Results</h1>
        <div class="date" style="color: #666; font-size: 11px;">Assessment Date: ' . htmlspecialchars($assessmentDate) . '</div>
    </div>

    <div class="respondent-section">
        <h3 style="color: ' . $maturityColor . ';">Respondent Information</h3>
        <p><strong>Position:</strong> ' . htmlspecialchars($respondentData['position']) . '</p>
        <p><strong>Organisation:</strong> ' . htmlspecialchars($respondentData['org']) . '</p>
        <p><strong>Size:</strong> ' . htmlspecialchars($respondentData['size']) . '</p>
        <p><strong>State:</strong> ' . htmlspecialchars($respondentData['state']) . '</p>
    </div>

    <div class="score-card">
        <h2>' . htmlspecialchars($maturityLevel) . ' Maturity Level</h2>
        <div class="score-circle">' . $scorePercentage . '%</div>
        <div class="score-detail" style="font-size: 13px;">' . number_format($weightedScore, 1) . ' of ' . $maxScore . ' points (weighted) | Raw: ' . $totalScore . ' pts</div>
        <div class="score-detail" style="font-size: 11px;">Profile: ' . htmlspecialchars($profileData['name']) . '</div>
    </div>

    <div class="section">
        <h3>Domain Analysis</h3>
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th style="text-align: center;">Score</th>
                    <th style="text-align: center;">Weight</th>
                    <th style="text-align: center;">Percentage</th>
                    <th>Maturity Level</th>
                </tr>
            </thead>
            <tbody>';

foreach ($questions as $domainName => $domainData) {
    $score = $domainScores[$domainName] ?? 0;
    $maxDomainScore = count($domainData['questions']);
    $percentage = $maxDomainScore > 0 ? round(($score / $maxDomainScore) * 100) : 0;
    $weight = $domainWeights[$domainName] ?? 1.0;

    if ($percentage <= 20) { $badge = 'initial'; $levelText = 'Initial'; }
    elseif ($percentage <= 40) { $badge = 'managed'; $levelText = 'Managed'; }
    elseif ($percentage <= 60) { $badge = 'defined'; $levelText = 'Defined'; }
    elseif ($percentage <= 80) { $badge = 'quantitative'; $levelText = 'Quantitatively Managed'; }
    else { $badge = 'optimizing'; $levelText = 'Optimizing'; }

    $html .= '<tr>
                <td><strong>' . htmlspecialchars($domainName) . '</strong></td>
                <td style="text-align: center;">' . $score . '/' . $maxDomainScore . '</td>
                <td style="text-align: center;">' . number_format($weight, 1) . 'x</td>
                <td style="text-align: center;">' . $percentage . '%</td>
                <td>' . $levelText . '</td>
              </tr>';
}

$html .= '    </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Generated by Viewfinder Digital Sovereignty Readiness Assessment</p>
        <p>' . htmlspecialchars($assessmentDate) . '</p>
    </div>
</body>
</html>';

// Generate PDF
$pdfOptions = new Options();
$pdfOptions->set('isHtml5ParserEnabled', true);
$pdfOptions->set('isRemoteEnabled', false);
$pdfOptions->set('defaultFont', 'Arial');

$dompdf = new Dompdf($pdfOptions);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfContent = $dompdf->output();

// Send email via Symfony Mailer
$mailerDsn = $_SERVER['MAILER_DSN'] ?? getenv('MAILER_DSN');

if (empty($mailerDsn)) {
    header('Location: results.php?error=mailer_not_configured');
    exit;
}

try {
    $transport = Transport::fromDsn($mailerDsn);
    $mailer = new Mailer($transport);

    $mailerFrom = $_SERVER['MAILER_FROM'] ?? getenv('MAILER_FROM') ?: 'noreply@sinarproject.org';
    $mailerTo = $_SERVER['MAILER_TO'] ?? getenv('MAILER_TO') ?: 'team@sinarproject.org';

    $orgName = htmlspecialchars($respondentData['org']);
    $email = (new Email())
        ->from($mailerFrom)
        ->to($mailerTo)
        ->subject("DSRA Assessment Results - {$orgName}")
        ->text($emailText)
        ->attach($pdfContent, 'DS-Readiness-Assessment.pdf', 'application/pdf');

    $mailer->send($email);

    header('Location: results.php?sent=1');
} catch (Exception $e) {
    error_log('Failed to send assessment email: ' . $e->getMessage());
    header('Location: results.php?error=send_failed');
}
exit;
