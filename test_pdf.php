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
    echo "PDS data collected successfully\n";

    // Generate PDF
    $pdf = Pdf::loadView('pds.pdf.form212', [
        'employee' => $employee,
        'pdsData' => $pdsData
    ]);

    $pdf->setPaper('A4', 'portrait');
    echo "PDF template loaded successfully\n";

    // Save to file
    $filename = '/tmp/test_pds_' . $employee->id . '.pdf';
    $pdf->save($filename);
    
    echo "PDF saved to: $filename\n";
    echo "File size: " . filesize($filename) . " bytes\n";
    echo "File type: " . mime_content_type($filename) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}