<?php
require_once 'vendor/autoload.php';

use App\Models\Employee;
use Barryvdh\DomPDF\Facade\Pdf;

// Initialize Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // Get first employee
    $employee = Employee::with([
        'familyBackground',
        'children',
        'education',
        'pdsEligibilities',
        'workExperiences',
        'voluntaryWork',
        'trainings',
        'specialSkills',
        'distinctions',
        'memberships',
        'references',
        'questionnaire'
    ])->first();

    if (!$employee) {
        echo "No employee found\n";
        exit(1);
    }

    echo "Testing PDF generation for: " . $employee->full_name . "\n";

    // Get PDS data
    $pdsData = $employee->getPdsDataForPdf();
    echo "PDS data structure:\n";
    echo "- Personal info: " . (isset($pdsData['personal_info']) ? "Yes" : "No") . "\n";
    echo "- Family background: " . (isset($pdsData['family_background']) ? "Yes" : "No") . "\n";
    echo "- Education count: " . count($pdsData['educational_background']) . "\n";
    echo "- Work experience count: " . count($pdsData['work_experience']) . "\n";

    // Try to render just the HTML first
    $html = view('pds.pdf.form212', [
        'employee' => $employee,
        'pdsData' => $pdsData
    ])->render();
    
    echo "HTML template rendered successfully, length: " . strlen($html) . "\n";
    
    // Save HTML for debugging
    file_put_contents('/tmp/test_pds_debug.html', $html);
    echo "HTML saved to /tmp/test_pds_debug.html\n";

    // Now try PDF generation with debug options
    $pdf = Pdf::loadHTML($html);
    $pdf->setPaper('A4', 'portrait');
    $pdf->setOption('isHtml5ParserEnabled', true);
    $pdf->setOption('isRemoteEnabled', true);
    $pdf->setOption('debugKeepTemp', true);
    
    echo "PDF options set\n";

    // Save to file
    $filename = '/tmp/test_pds_debug.pdf';
    $output = $pdf->output();
    file_put_contents($filename, $output);
    
    echo "PDF saved to: $filename\n";
    echo "File size: " . filesize($filename) . " bytes\n";
    
    // Check PDF info
    $pdfInfo = new SplFileInfo($filename);
    echo "File extension: " . $pdfInfo->getExtension() . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}