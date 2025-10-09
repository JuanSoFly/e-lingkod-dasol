<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Data Sheet - CS Form No. 212</title>
    <style>
        @page {
            margin: 0.5in;
            size: A4;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.1;
            margin: 0;
            padding: 0;
            color: #000;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
        }

        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }

        .header p {
            font-size: 9px;
            margin: 2px 0;
        }

        .section {
            margin-bottom: 8px;
            page-break-inside: avoid;
        }

        .section-title {
            background-color: #d3d3d3;
            padding: 2px 4px;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            border: 1px solid #000;
        }

        .field-group {
            border: 1px solid #000;
            margin-bottom: 2px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            margin-bottom: 5px;
        }

        th, td {
            border: 1px solid #000;
            padding: 2px 3px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 6px;
            text-transform: uppercase;
        }

        .checkbox {
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            margin-right: 3px;
        }

        .checkbox.checked::after {
            content: "✓";
            font-weight: bold;
        }

        .page-break {
            page-break-before: always;
        }

        /* CSC Form 212 Specific Layout Styles */
        .field-number {
            font-weight: bold;
            color: #000;
            margin-right: 5px;
            min-width: 25px;
            display: inline-block;
        }

        .csc-field-container {
            display: flex;
            align-items: flex-start;
            margin-bottom: 3px;
        }

        .csc-field-label {
            font-weight: bold;
            margin-right: 8px;
            min-width: 100px;
            font-size: 7px;
        }

        .csc-field-input {
            border-bottom: 1px solid #000;
            flex-grow: 1;
            min-height: 12px;
            padding: 1px 2px;
            font-size: 7px;
        }

        .csc-checkbox-container {
            display: flex;
            align-items: center;
            margin-bottom: 2px;
        }

        .csc-checkbox-label {
            font-size: 6px;
            margin-left: 3px;
        }

        /* Exact CSC Form positioning */
        .csc-header-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 7px;
        }

        .csc-page-divider {
            border-top: 2px solid #000;
            margin: 10px 0;
        }

        .signature-section {
            margin-top: 20px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .signature-box {
            border: 1px solid #000;
            height: 40px;
            text-align: center;
            padding-top: 30px;
            margin-bottom: 5px;
        }


        .form-number {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 8px;
        }

        /* Field Layout System */
        .field-group {
            border: 1px solid #000;
            margin-bottom: 3px;
            padding: 2px;
        }

        .field-row {
            display: flex;
            margin-bottom: 2px;
            align-items: flex-start;
        }

        .field-row:last-child {
            margin-bottom: 0;
        }

        .field-label {
            font-weight: bold;
            font-size: 6px;
            text-transform: uppercase;
            padding: 2px 3px;
            border-right: 1px solid #000;
            background-color: #f0f0f0;
            display: flex;
            align-items: center;
            min-height: 12px;
        }

        .field-value {
            font-size: 7px;
            padding: 2px 3px;
            background-color: #fff;
            flex-grow: 1;
            min-height: 12px;
            display: flex;
            align-items: center;
        }

        /* Field Sizing Classes */
        .field-quarter {
            width: 25%;
            flex-shrink: 0;
        }

        .field-third {
            width: 33.33%;
            flex-shrink: 0;
        }

        .field-half {
            width: 50%;
            flex-shrink: 0;
        }

        .field-two-thirds {
            width: 66.66%;
            flex-shrink: 0;
        }

        .field-three-quarters {
            width: 75%;
            flex-shrink: 0;
        }

        .field-full {
            width: 100%;
            flex-shrink: 0;
        }

        /* Remove borders for adjacent fields */
        .field-label + .field-label {
            border-left: 1px solid #000;
            border-right: none;
        }

        .field-value + .field-value {
            border-left: 1px solid #000;
        }

        /* Full-width field label styling */
        .field-row .field-label.field-full {
            border-right: none;
            border-bottom: 1px solid #000;
        }

        .field-row .field-value.field-full {
            border-bottom: 1px solid #000;
        }

        /* Questionnaire specific styling */
        .question-row {
            margin-bottom: 8px;
        }

        .question-label {
            font-size: 6px;
            text-transform: uppercase;
            margin-bottom: 3px;
            line-height: 1.2;
        }

        .checkbox-group {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 7px;
        }

        .checkbox {
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 3px;
            vertical-align: middle;
        }

        .checkbox.checked::after {
            content: "✓";
            font-size: 6px;
            font-weight: bold;
            line-height: 1;
        }

        /* Sub-checkbox styling for question 40 */
        .sub-question {
            margin-left: 15px;
            margin-top: 3px;
            display: flex;
            align-items: center;
            font-size: 6px;
        }

        /* Address specific layout fixes */
        .address-table {
            width: 100%;
            border-collapse: collapse;
        }

        .address-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            font-size: 7px;
            vertical-align: top;
        }

        .address-table .label-cell {
            background-color: #f0f0f0;
            font-weight: bold;
            font-size: 6px;
            text-transform: uppercase;
            width: 40%;
        }

        /* Government form specific styling */
        .gov-form-text {
            font-family: 'Times New Roman', 'Arial', serif;
            color: #000;
        }

        /* Enhanced checkbox styling */
        .checkbox-box {
            width: 10px;
            height: 10px;
            border: 1px solid #000;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 5px;
            vertical-align: middle;
            background-color: #fff;
        }

        .checkbox-box.checked::after {
            content: "X";
            font-size: 8px;
            font-weight: bold;
            line-height: 1;
        }

        /* Field border fixes for adjacent cells */
        .field-group .field-row .field-value {
            border-left: 1px solid #000;
        }

        .field-group .field-row .field-label:first-child {
            border-left: 1px solid #000;
        }

        /* Ensure proper borders for field groups */
        .field-group {
            border: 1px solid #000;
        }

        /* Fix for empty field values */
        .field-value:empty::before {
            content: "\00A0";
            white-space: pre;
        }
    </style>
</head>
<body>
    <div class="form-number">CS Form No. 212</div>

    <div class="header">
        <h1>CS Form No. 212, Page 1</h1>
        <p>Revised 2017</p>
        <p style="font-size: 6px;">(Please see attached User's Guide for the filling-out of this form.)</p>
        <p style="font-size: 7px; font-style: italic;">
            WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.
        </p>
        <p style="font-size: 7px; font-style: italic;">
            READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.
        </p>
    </div>

    <!-- I. PERSONAL INFORMATION -->
    <div class="section">
        <div class="section-title"><span class="field-number">1</span>PERSONAL INFORMATION</div>

        <!-- Name Information Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">surname</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['surname'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">first name</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['first_name'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">middle name</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['middle_name'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">name extension (jr, sr)</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['name_extension'] ?? '' }}</td>
            </tr>
        </table>

        <!-- Birth and Basic Information Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">date of birth (mm/dd/yyyy)</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['date_of_birth'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">place of birth</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['place_of_birth'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">sex</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['sex'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">civil status</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['civil_status'] ?? '' }}</td>
            </tr>
        </table>

        <!-- Physical Characteristics Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">height (m)</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['height'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">weight (kg)</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['weight'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">blood type</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['blood_type'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">citizenship</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['citizenship'] ?? '' }}</td>
            </tr>
        </table>

        <!-- Government ID Numbers Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">gsis id no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['gsis_id'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 25%;">pag-ibig id no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 25%;">{{ $pdsData['personal_info']['pagibig_id'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">philhealth no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['philhealth_id'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">sss no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['sss_id'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">tin no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['tin_id'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase;">agency employee no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px;">{{ $pdsData['personal_info']['agency_employee_no'] ?? '' }}</td>
            </tr>
        </table>

        <!-- Address Information Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="border: 1px solid #000; background-color: #d3d3d3; padding: 3px; font-weight: bold; font-size: 8px; text-transform: uppercase; text-align: center; width: 50%;">RESIDENTIAL ADDRESS</td>
                <td style="border: 1px solid #000; background-color: #d3d3d3; padding: 3px; font-weight: bold; font-size: 8px; text-transform: uppercase; text-align: center; width: 50%;">PERMANENT ADDRESS</td>
            </tr>
            <tr>
                <td style="border: 1px solid #000; padding: 0; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 40%;">house/block/lot no.</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['house_block_lot_no'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">street</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['street'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">subdivision/village</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['subdivision_village'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">barangay</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['barangay'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">city/municipality</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['city_municipality'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">province</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['province'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">zip code</td>
                            <td style="padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['residential_address']['zip_code'] ?? '' }}</td>
                        </tr>
                    </table>
                </td>
                <td style="border: 1px solid #000; padding: 0; vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 40%;">house/block/lot no.</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['house_block_lot_no'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">street</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['street'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">subdivision/village</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['subdivision_village'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">barangay</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['barangay'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">city/municipality</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['city_municipality'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="border-bottom: 1px solid #000; background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">province</td>
                            <td style="border-bottom: 1px solid #000; padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['province'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td style="background-color: #f0f0f0; padding: 2px; font-weight: bold; font-size: 6px; text-transform: uppercase;">zip code</td>
                            <td style="padding: 2px; font-size: 7px;">{{ $pdsData['personal_info']['permanent_address']['zip_code'] ?? '' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Contact Information Table -->
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 5px;">
            <tr>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 16.66%;">telephone no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 16.67%;">{{ $pdsData['personal_info']['contact_info']['telephone_no'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 16.66%;">mobile no.</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 16.67%;">{{ $pdsData['personal_info']['contact_info']['mobile_no'] ?? '' }}</td>
                <td style="border: 1px solid #000; background-color: #f0f0f0; padding: 3px; font-weight: bold; font-size: 7px; text-transform: uppercase; width: 16.66%;">email address</td>
                <td style="border: 1px solid #000; padding: 3px; font-size: 8px; width: 16.67%;">{{ $pdsData['personal_info']['contact_info']['email_address'] ?? '' }}</td>
            </tr>
        </table>
    </div>

    <!-- II. FAMILY BACKGROUND -->
    <div class="section">
        <div class="section-title"><span class="field-number">2</span>FAMILY BACKGROUND</div>

        <!-- Spouse Information -->
        @if($pdsData['family_background']['spouse'])
        <div class="field-group">
            <div class="field-row">
                <div class="field-label field-quarter">spouse's surname</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['spouse']['surname'] ?? '' }}</div>
                <div class="field-label field-quarter">first name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['spouse']['first_name'] ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label field-quarter">middle name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['spouse']['middle_name'] ?? '' }}</div>
                <div class="field-label field-quarter">occupation</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['spouse']['occupation'] ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label field-quarter">employer/business name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['spouse']['employer'] ?? '' }}</div>
                <div class="field-label field-quarter">business address</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['spouse']['business_address'] ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label field-half">telephone no.</div>
                <div class="field-value field-half">{{ $pdsData['family_background']['spouse']['telephone_no'] ?? '' }}</div>
            </div>
        </div>
        @endif

        <!-- Parents Information -->
        @if($pdsData['family_background']['parents'])
        <div class="field-group">
            <div class="field-row">
                <div class="field-label field-quarter">father's surname</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['parents']['father']['surname'] ?? '' }}</div>
                <div class="field-label field-quarter">first name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['parents']['father']['first_name'] ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label field-half">middle name</div>
                <div class="field-value field-half">{{ $pdsData['family_background']['parents']['father']['middle_name'] ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label field-quarter">mother's maiden name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['parents']['mother']['maiden_name'] ?? '' }}</div>
                <div class="field-label field-quarter">surname</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['parents']['mother']['surname'] ?? '' }}</div>
            </div>
            <div class="field-row">
                <div class="field-label field-quarter">first name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['parents']['mother']['first_name'] ?? '' }}</div>
                <div class="field-label field-quarter">middle name</div>
                <div class="field-value field-quarter">{{ $pdsData['family_background']['parents']['mother']['middle_name'] ?? '' }}</div>
            </div>
        </div>
        @endif

        <!-- Children Information -->
        @if(count($pdsData['family_background']['children']) > 0)
        <div class="field-group">
            <table style="width: 100%; border-collapse: collapse; font-size: 7px; margin-bottom: 2px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 70%;">name of children</th>
                        <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 30%;">date of birth (mm/dd/yyyy)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pdsData['family_background']['children'] as $child)
                    <tr>
                        <td style="border: 1px solid #000; padding: 2px 3px;">{{ $child['full_name'] }}</td>
                        <td style="border: 1px solid #000; padding: 2px 3px;">{{ $child['date_of_birth'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- III. EDUCATIONAL BACKGROUND -->
    <div class="section">
        <div class="section-title"><span class="field-number">3</span>EDUCATIONAL BACKGROUND</div>

        @if(count($pdsData['educational_background']) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 6px; margin-bottom: 5px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 10%;">level</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 20%;">name of school</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 25%;">basic education/degree/course</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 15%;">period of attendance</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 15%;">highest level/units earned</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 7%;">year graduated</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 8%;">scholarship/academic honors received</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['educational_background'] as $education)
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['level'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['school_name'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['degree_course'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['period_from'] }}-{{ $education['period_to'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['highest_level_units_earned'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['year_graduated'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $education['scholarship_honors_received'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- Page 2 Header -->
    <div class="header">
        <h1>CS Form No. 212, Page 2</h1>
        <p>Revised 2017</p>
    </div>

    <!-- IV. CIVIL SERVICE ELIGIBILITY -->
    <div class="section">
        <div class="section-title"><span class="field-number">4</span>CIVIL SERVICE ELIGIBILITY</div>

        @if(count($pdsData['civil_service_eligibility']) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 6px; margin-bottom: 5px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 30%;">career service/ra 1080 (board/bar) under special laws/ccs/mc/etc.</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 10%;">rating</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 15%;">date of examination/conferment</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 20%;">place of examination/conferment</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 12%;">license number</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 13%;">date of validity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['civil_service_eligibility'] as $eligibility)
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $eligibility['eligibility_name'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $eligibility['rating'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $eligibility['date_of_examination'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $eligibility['place_of_examination'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $eligibility['license_number'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $eligibility['date_of_validity'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- V. WORK EXPERIENCE -->
    <div class="section">
        <div class="section-title"><span class="field-number">5</span>WORK EXPERIENCE</div>

        @if(count($pdsData['work_experience']) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 6px; margin-bottom: 5px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 14%;">inclusive dates</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 18%;">position title</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 20%;">department/agency/office/company</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 12%;">monthly salary</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 12%;">salary/job/pay grade</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 14%;">status of appointment</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 10%;">gov't service</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['work_experience'] as $work)
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['inclusive_date_from'] }} to {{ $work['inclusive_date_to'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['position_title'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['department_agency_office'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['monthly_salary'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['salary_grade_step'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['status_of_appointment'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['is_government_service'] ? 'YES' : 'NO' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- VI. VOLUNTARY WORK -->
    <div class="section">
        <div class="section-title"><span class="field-number">6</span>VOLUNTARY WORK OR INVOLVEMENT IN CIVIC/NON-GOVERNMENT/PEOPLE/VOLUNTARY ORGANIZATION/S</div>

        @if(count($pdsData['voluntary_work']) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 6px; margin-bottom: 5px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 35%;">name & address of organization</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 20%;">inclusive dates</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 15%;">number of hours</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 30%;">position/nature of work</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['voluntary_work'] as $work)
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['organization_name_address'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['inclusive_date_from'] }} to {{ $work['inclusive_date_to'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['number_of_hours'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $work['position_nature_of_work'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- Page 3 Header -->
    <div class="header">
        <h1>CS Form No. 212, Page 3</h1>
        <p>Revised 2017</p>
    </div>

    <!-- VII. LEARNING AND DEVELOPMENT -->
    <div class="section">
        <div class="section-title"><span class="field-number">7</span>LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</div>

        @if(count($pdsData['learning_development']) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 6px; margin-bottom: 5px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 35%;">title of learning and development interventions/training programs</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 15%;">inclusive dates</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 10%;">number of hours</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 15%;">type of ld</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 25%;">conducted/sponsored by</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['learning_development'] as $training)
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $training['training_title'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $training['inclusive_date_from'] }} to {{ $training['inclusive_date_to'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $training['number_of_hours'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $training['type_of_ld'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $training['conducted_sponsored_by'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- VIII. OTHER INFORMATION -->
    <div class="section">
        <div class="section-title"><span class="field-number">8</span>OTHER INFORMATION</div>

        <!-- Special Skills and Hobbies -->
        <div class="field-group">
            <div class="field-row">
                <div class="field-label field-full">32. special skills and hobbies</div>
            </div>
            <div class="field-row">
                <div class="field-value field-full">
                    @if(count($pdsData['other_information']['special_skills']) > 0)
                        @foreach($pdsData['other_information']['special_skills'] as $index => $skill)
                            {{ $skill['description'] }}{{ !$loop->last ? '; ' : '' }}
                        @endforeach
                    @else
                        &nbsp;
                    @endif
                </div>
            </div>
        </div>

        <!-- Non-Academic Distinctions/Recognition -->
        <div class="field-group">
            <div class="field-row">
                <div class="field-label field-full">33. non-academic distinctions / recognition</div>
            </div>
            <div class="field-row">
                <div class="field-value field-full">
                    @if(count($pdsData['other_information']['distinctions']) > 0)
                        @foreach($pdsData['other_information']['distinctions'] as $index => $distinction)
                            {{ $distinction['description'] }}{{ !$loop->last ? '; ' : '' }}
                        @endforeach
                    @else
                        &nbsp;
                    @endif
                </div>
            </div>
        </div>

        <!-- Membership in Association/Organization -->
        <div class="field-group">
            <div class="field-row">
                <div class="field-label field-full">34. membership in association/organization</div>
            </div>
            <div class="field-row">
                <div class="field-value field-full">
                    @if(count($pdsData['other_information']['memberships']) > 0)
                        @foreach($pdsData['other_information']['memberships'] as $index => $membership)
                            {{ $membership['description'] }}{{ !$loop->last ? '; ' : '' }}
                        @endforeach
                    @else
                        &nbsp;
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- IX. REFERENCES -->
    <div class="section">
          <!-- IX. REFERENCES moved to Page 4 -->

        @if(count($pdsData['references']) > 0)
        <table style="width: 100%; border-collapse: collapse; font-size: 6px; margin-bottom: 5px;">
            <thead>
                <tr>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 30%;">name</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 50%;">address</th>
                    <th style="border: 1px solid #000; padding: 2px 3px; background-color: #f0f0f0; font-weight: bold; font-size: 6px; text-transform: uppercase; width: 20%;">tel. no.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['references'] as $reference)
                <tr>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $reference['full_name'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $reference['address'] }}</td>
                    <td style="border: 1px solid #000; padding: 2px 3px; font-size: 6px;">{{ $reference['telephone_no'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- X. QUESTIONNAIRE -->
    <div class="section">
        <div class="section-title">X. QUESTIONS</div>

        @if($pdsData['questionnaire'])
        <div class="field-group">
            <!-- Question 35 -->
            <div class="question-row">
                <div class="question-label">35. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed?</div>
                <div class="checkbox-group">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q35_gov_service_related']) && $pdsData['questionnaire']['q35_gov_service_related'] ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q35_gov_service_related']) && !$pdsData['questionnaire']['q35_gov_service_related'] ? 'checked' : '' }}"></span> NO
                </div>
            </div>

            <!-- Question 36 -->
            <div class="question-row">
                <div class="question-label">36. Have you ever been formally charged with or administratively found guilty of any administrative offense?</div>
                <div class="checkbox-group">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q36_administrative_offense']) && $pdsData['questionnaire']['q36_administrative_offense'] ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q36_administrative_offense']) && !$pdsData['questionnaire']['q36_administrative_offense'] ? 'checked' : '' }}"></span> NO
                </div>
            </div>

            <!-- Question 37 -->
            <div class="question-row">
                <div class="question-label">37. Have you ever been criminally charged before any court?</div>
                <div class="checkbox-group">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q37_criminal_charged']) && $pdsData['questionnaire']['q37_criminal_charged'] ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q37_criminal_charged']) && !$pdsData['questionnaire']['q37_criminal_charged'] ? 'checked' : '' }}"></span> NO
                </div>
            </div>

            <!-- Question 38 -->
            <div class="question-row">
                <div class="question-label">38. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</div>
                <div class="checkbox-group">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q38_convicted']) && $pdsData['questionnaire']['q38_convicted'] ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q38_convicted']) && !$pdsData['questionnaire']['q38_convicted'] ? 'checked' : '' }}"></span> NO
                </div>
            </div>

            <!-- Question 39 -->
            <div class="question-row">
                <div class="question-label">39. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?</div>
                <div class="checkbox-group">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q39_separated_service']) && $pdsData['questionnaire']['q39_separated_service'] ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q39_separated_service']) && !$pdsData['questionnaire']['q39_separated_service'] ? 'checked' : '' }}"></span> NO
                </div>
            </div>

            <!-- Question 40 -->
            <div class="question-row">
                <div class="question-label">40. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</div>
                <div class="checkbox-group">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q40_candidate_resigned']) && $pdsData['questionnaire']['q40_candidate_resigned'] ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['q40_candidate_resigned']) && !$pdsData['questionnaire']['q40_candidate_resigned'] ? 'checked' : '' }}"></span> NO
                </div>
            </div>

            <!-- Question 41 - Sub-questions -->
            <div class="question-row">
                <div class="question-label">41. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:</div>
            </div>

            <div class="sub-question">
                <span class="checkbox {{ isset($pdsData['questionnaire']['q41_indigenous']) && $pdsData['questionnaire']['q41_indigenous'] ? 'checked' : '' }}"></span> YES
                <span class="checkbox {{ isset($pdsData['questionnaire']['q41_indigenous']) && !$pdsData['questionnaire']['q41_indigenous'] ? 'checked' : '' }}"></span> NO
                <span style="margin-left: 5px; font-size: 6px;">Are you a member of any indigenous group?</span>
            </div>

            <div class="sub-question">
                <span class="checkbox {{ isset($pdsData['questionnaire']['q41_pwd']) && $pdsData['questionnaire']['q41_pwd'] ? 'checked' : '' }}"></span> YES
                <span class="checkbox {{ isset($pdsData['questionnaire']['q41_pwd']) && !$pdsData['questionnaire']['q41_pwd'] ? 'checked' : '' }}"></span> NO
                <span style="margin-left: 5px; font-size: 6px;">Are you a person with disability?</span>
            </div>

            <div class="sub-question">
                <span class="checkbox {{ isset($pdsData['questionnaire']['q41_solo_parent']) && $pdsData['questionnaire']['q41_solo_parent'] ? 'checked' : '' }}"></span> YES
                <span class="checkbox {{ isset($pdsData['questionnaire']['q41_solo_parent']) && !$pdsData['questionnaire']['q41_solo_parent'] ? 'checked' : '' }}"></span> NO
                <span style="margin-left: 5px; font-size: 6px;">Are you a solo parent?</span>
            </div>
        </div>
        @endif
    </div>

  </div>

<!-- PAGE 4 - CSC Form No. 212 -->
<div class="page-break">
    <!-- Page 4 Header -->
    <div class="header">
        <h1>CS Form No. 212, Page 4</h1>
        <p>Revised 2017</p>
        <p style="font-size: 6px;">(Please see attached User's Guide for the filling-out of this form.)</p>
    </div>

    <!-- Field 34: Relationship Details -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">34</span>RELATIONSHIP TO APPOINTING AUTHORITY</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <div class="csc-field-container">
            <span class="csc-field-label">34.</span>
            <div class="csc-field-input" style="min-height: 20px;">
                {{ $employee->questionnaire->field_34_relationship ?? '' }}
            </div>
        </div>
        <p style="font-size: 6px; margin: 3px 0;">
            Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed, within the third degree? If YES, give name and relationship.
        </p>
    </div>

    <!-- Field 35: Administrative/Criminal Charges -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">35</span>ADMINISTRATIVE/CRIMINAL CHARGES</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <div class="csc-field-container">
            <span class="csc-field-label">35.</span>
            <div class="csc-field-input" style="min-height: 40px;">
                {{ $employee->questionnaire->field_35_charges ?? '' }}
            </div>
        </div>
        <p style="font-size: 6px; margin: 3px 0;">
            Have you ever been found guilty of any administrative offense or have you been criminally charged before any court? If YES, give full details.
        </p>
    </div>

    <!-- Field 36: Election Candidacy -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">36</span>ELECTION CANDIDACY</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <div class="csc-field-container">
            <span class="csc-field-label">36.</span>
            <div class="csc-field-input" style="min-height: 20px;">
                {{ $employee->questionnaire->field_36_candidate ?? '' }}
            </div>
        </div>
        <p style="font-size: 6px; margin: 3px 0;">
            Have you ever been a candidate in a national or local election held within the last year (except Barangay election)? If YES, give position and year.
        </p>
    </div>

    <!-- Field 37: Resignation to Campaign -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">37</span>RESIGNATION TO CAMPAIGN</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <div class="csc-field-container">
            <span class="csc-field-label">37.</span>
            <div class="csc-field-input" style="min-height: 20px;">
                {{ $employee->questionnaire->field_37_resignation ?? '' }}
            </div>
        </div>
        <p style="font-size: 6px; margin: 3px 0;">
            Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate? If YES, give name of candidate and political party.
        </p>
    </div>

    <!-- Field 38: Immigrant Status -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">38</span>IMMIGRANT STATUS</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <div class="csc-field-container">
            <span class="csc-field-label">38.</span>
            <div class="csc-field-input" style="min-height: 20px;">
                {{ $employee->questionnaire->field_38_immigrant ?? '' }}
            </div>
        </div>
        <p style="font-size: 6px; margin: 3px 0;">
            Have you acquired the status of an immigrant or permanent resident of another country? If YES, specify country.
        </p>
    </div>

    <!-- Field 39: Government ID -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">39</span>GOVERNMENT ID</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <table style="margin-top: 3px;">
            <tr>
                <th style="width: 25%;">TYPE OF ID</th>
                <th style="width: 35%;">ID NUMBER</th>
                <th style="width: 20%;">DATE ISSUED</th>
                <th style="width: 20%;">PLACE ISSUED</th>
            </tr>
            <tr>
                <td style="text-transform: uppercase;">{{ $employee->questionnaire->field_39_gov_id_number ?? $employee->gov_id_type ?? 'PSA' }}</td>
                <td>{{ $employee->questionnaire->field_39_gov_id_number ?? $employee->gov_id_number ?? '' }}</td>
                <td>{{ $employee->questionnaire->field_39_gov_id_date_issued ? $employee->questionnaire->field_39_gov_id_date_issued->format('m/d/Y') : ($employee->gov_id_date_issued ? $employee->gov_id_date_issued->format('m/d/Y') : '') }}</td>
                <td style="text-transform: uppercase;">{{ $employee->questionnaire->field_39_gov_id_place_issued ?? $employee->gov_id_place_issued ?? '' }}</td>
            </tr>
        </table>
    </div>

    <!-- Field 40: References -->
    <div class="section">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span><span class="field-number">40</span>REFERENCES</span>
            <span>DO NOT WRITE IN THIS SPACE</span>
        </div>

        <table style="margin-top: 3px;">
            <tr>
                <th style="width: 5%;">NO.</th>
                <th style="width: 35%;">NAME (Full name, include middle name or initial)</th>
                <th style="width: 35%;">ADDRESS (No., Street, City/Town, Province)</th>
                <th style="width: 25%;">TELEPHONE NO.</th>
            </tr>
            @php
                $references = $employee->references()->take(3)->get();
                for ($i = 1; $i <= 3; $i++):
                    $ref = $references->where('reference_order', $i)->first();
            @endphp
            <tr>
                <td style="text-align: center; height: 25px;">{{ $i }}</td>
                <td style="text-transform: uppercase;">{{ $ref ? $ref->full_name : '' }}</td>
                <td style="text-transform: uppercase;">{{ $ref ? $ref->address : '' }}</td>
                <td>{{ $ref ? $ref->telephone_no : '' }}</td>
            </tr>
            @php endfor; @endphp
        </table>

        <p style="font-size: 6px; margin-top: 3px;">
            <strong>IMPORTANT:</strong> References should not be relatives from the degree of consanguinity or affinity to the applicant or to the appointing authority.
        </p>
    </div>

    <!-- Government ID Section -->
    <div class="section" style="margin-top: 15px;">
        <div class="section-title" style="display: flex; justify-content: space-between;">
            <span>GOVERNMENT ISSUED IDs (Please attach at least one (1) valid government issued ID)</span>
        </div>

        <table style="margin-top: 3px;">
            <tr>
                <th style="width: 20%;">TYPE OF ID</th>
                <th style="width: 30%;">ID NUMBER</th>
                <th style="width: 25%;">DATE ISSUED</th>
                <th style="width: 25%;">PLACE ISSUED</th>
            </tr>
            <tr>
                <td style="text-transform: uppercase;">{{ $employee->gov_id_type ?? 'PSA' }}</td>
                <td>{{ $employee->gov_id_number ?? '' }}</td>
                <td>{{ $employee->gov_id_date_issued ? $employee->gov_id_date_issued->format('m/d/Y') : '' }}</td>
                <td style="text-transform: uppercase;">{{ $employee->gov_id_place_issued ?? '' }}</td>
            </tr>
        </table>
    </div>

    <!-- Photo and Thumbmark Section -->
    <div class="section" style="margin-top: 15px;">
        <div style="display: flex; justify-content: space-between;">
            <!-- Photo Section -->
            <div style="width: 45%;">
                <div class="section-title" style="text-align: center;">
                    <strong>IDENTIFICATION PICTURE</strong>
                    <br><span style="font-size: 6px;">(3.5 cm × 4.5 cm)</span>
                </div>
                <div style="border: 2px solid #000; height: 135px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;">
                    @if($employee->activePhoto && $employee->activePhoto->photo_path)
                        <img src="{{ $employee->activePhoto->photo_url }}" alt="Employee Photo" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                    @else
                        <span style="font-size: 8px; color: #666;">[PHOTO]</span>
                    @endif
                </div>
                <p style="font-size: 6px; text-align: center; margin-top: 3px;">
                    <strong>With handwritten name tag</strong>
                </p>
            </div>

            <!-- Thumbmark Section -->
            <div style="width: 45%;">
                <div class="section-title" style="text-align: center;">
                    <strong>RIGHT THUMBMARK</strong>
                </div>
                <div style="border: 2px solid #000; height: 135px; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;">
                    @if($employee->activePhoto && $employee->activePhoto->thumbmark_path)
                        <img src="{{ $employee->activePhoto->thumbmark_url }}" alt="Thumbmark" style="max-width: 100%; max-height: 100%; object-fit: cover;">
                    @else
                        <span style="font-size: 8px; color: #666;">[THUMBMARK]</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Oath/Sworn Declaration Section -->
    <div class="section" style="margin-top: 20px;">
        <div class="section-title" style="text-align: center; font-weight: bold;">
            OATH/SWORN DECLARATION
        </div>

        <div style="border: 1px solid #000; padding: 8px; font-size: 7px; line-height: 1.2;">
            <p style="margin: 0 0 5px 0; text-align: justify;">
                I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement
                pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency
                head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this
                document and its attachments shall cause the filing of administrative/criminal case/s against me.
            </p>

            <p style="margin: 8px 0; text-align: justify;">
                I am aware that any false or incomplete information may prejudice the evaluation of my appointment/employment.
            </p>

            <p style="margin: 8px 0; text-align: justify;">
                <strong>SUBSCRIBED AND SWORN to</strong> before me this {{ date('F d, Y') }} in {{ config('app.city', 'Dasol') }},
                {{ config('app.province', 'Pangasinan') }}, Philippines, affiant exhibiting competent proof of identity.
            </p>
        </div>
    </div>

    <!-- Signature Lines -->
    <div style="margin-top: 30px;">
        <div style="display: flex; justify-content: space-between;">
            <!-- Applicant's Signature -->
            <div style="width: 45%; text-align: center;">
                <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px;"></div>
                <p style="font-size: 7px; margin: 0; font-weight: bold;">Signature of Applicant</p>
                <p style="font-size: 6px; margin: 0;">Over Printed Name</p>
                <p style="font-size: 6px; margin: 5px 0 0 0;">Date: {{ date('m/d/Y') }}</p>
            </div>

            <!-- Administering Officer -->
            <div style="width: 45%; text-align: center;">
                <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px;"></div>
                <p style="font-size: 7px; margin: 0; font-weight: bold;">Signature of Person Administering Oath</p>
                <p style="font-size: 6px; margin: 0;">(Print name and title)</p>
            </div>
        </div>
    </div>

    <!-- Bottom Notice -->
    <div style="margin-top: 40px; border-top: 1px solid #000; padding-top: 8px;">
        <p style="font-size: 6px; margin: 0; text-align: center; font-weight: bold;">
            CS Form No. 212, Page 4 | Revised 2017
        </p>
    </div>
</div>

</body>
</html>
