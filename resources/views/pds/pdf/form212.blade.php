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
    </style>
</head>
<body>
    <div class="form-number">CS Form No. 212</div>
    
    <div class="header">
        <h1>PERSONAL DATA SHEET</h1>
        <p>(Revised 2017)</p>
        <p style="font-size: 7px; font-style: italic;">
            WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.
        </p>
        <p style="font-size: 7px; font-style: italic;">
            READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.
        </p>
    </div>

    <!-- I. PERSONAL INFORMATION -->
    <div class="section">
        <div class="section-title">I. PERSONAL INFORMATION</div>
        
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
        <div class="section-title">II. FAMILY BACKGROUND</div>
        
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

        @if(count($pdsData['family_background']['children']) > 0)
        <div class="field-group">
            <div class="section-title">children</div>
            <table>
                <thead>
                    <tr>
                        <th>name of children</th>
                        <th>date of birth</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pdsData['family_background']['children'] as $child)
                    <tr>
                        <td>{{ $child['full_name'] }}</td>
                        <td>{{ $child['date_of_birth'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <!-- III. EDUCATIONAL BACKGROUND -->
    <div class="section">
        <div class="section-title">III. EDUCATIONAL BACKGROUND</div>
        
        @if(count($pdsData['educational_background']) > 0)
        <table>
            <thead>
                <tr>
                    <th>level</th>
                    <th>name of school</th>
                    <th>basic education/degree/course</th>
                    <th>period of attendance</th>
                    <th>highest level/units earned</th>
                    <th>year graduated</th>
                    <th>scholarship/academic honors received</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['educational_background'] as $education)
                <tr>
                    <td>{{ $education['level'] }}</td>
                    <td>{{ $education['school_name'] }}</td>
                    <td>{{ $education['degree_course'] }}</td>
                    <td>{{ $education['period_from'] }}-{{ $education['period_to'] }}</td>
                    <td>{{ $education['highest_level_units_earned'] }}</td>
                    <td>{{ $education['year_graduated'] }}</td>
                    <td>{{ $education['scholarship_honors_received'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- IV. CIVIL SERVICE ELIGIBILITY -->
    <div class="section">
        <div class="section-title">IV. CIVIL SERVICE ELIGIBILITY</div>
        
        @if(count($pdsData['civil_service_eligibility']) > 0)
        <table>
            <thead>
                <tr>
                    <th>career service/ra 1080 (board/bar) under special laws/ccs/mc/etc.</th>
                    <th>rating</th>
                    <th>date of examination/conferment</th>
                    <th>place of examination/conferment</th>
                    <th>license number</th>
                    <th>date of validity</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['civil_service_eligibility'] as $eligibility)
                <tr>
                    <td>{{ $eligibility['eligibility_name'] }}</td>
                    <td>{{ $eligibility['rating'] }}</td>
                    <td>{{ $eligibility['date_of_examination'] }}</td>
                    <td>{{ $eligibility['place_of_examination'] }}</td>
                    <td>{{ $eligibility['license_number'] }}</td>
                    <td>{{ $eligibility['date_of_validity'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- V. WORK EXPERIENCE -->
    <div class="section">
        <div class="section-title">V. WORK EXPERIENCE</div>
        
        @if(count($pdsData['work_experience']) > 0)
        <table>
            <thead>
                <tr>
                    <th>inclusive dates</th>
                    <th>position title</th>
                    <th>department/agency/office/company</th>
                    <th>monthly salary</th>
                    <th>salary/job/pay grade</th>
                    <th>status of appointment</th>
                    <th>gov't service</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['work_experience'] as $work)
                <tr>
                    <td>{{ $work['inclusive_date_from'] }} to {{ $work['inclusive_date_to'] }}</td>
                    <td>{{ $work['position_title'] }}</td>
                    <td>{{ $work['department_agency_office'] }}</td>
                    <td>{{ $work['monthly_salary'] }}</td>
                    <td>{{ $work['salary_grade_step'] }}</td>
                    <td>{{ $work['status_of_appointment'] }}</td>
                    <td>{{ $work['is_government_service'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- VI. VOLUNTARY WORK -->
    <div class="section">
        <div class="section-title">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC/NON-GOVERNMENT/PEOPLE/VOLUNTARY ORGANIZATION/S</div>
        
        @if(count($pdsData['voluntary_work']) > 0)
        <table>
            <thead>
                <tr>
                    <th>name & address of organization</th>
                    <th>inclusive dates</th>
                    <th>number of hours</th>
                    <th>position/nature of work</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['voluntary_work'] as $work)
                <tr>
                    <td>{{ $work['organization_name_address'] }}</td>
                    <td>{{ $work['inclusive_date_from'] }} to {{ $work['inclusive_date_to'] }}</td>
                    <td>{{ $work['number_of_hours'] }}</td>
                    <td>{{ $work['position_nature_of_work'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- VII. LEARNING AND DEVELOPMENT -->
    <div class="section">
        <div class="section-title">VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED</div>
        
        @if(count($pdsData['learning_development']) > 0)
        <table>
            <thead>
                <tr>
                    <th>title of learning and development interventions/training programs</th>
                    <th>inclusive dates</th>
                    <th>number of hours</th>
                    <th>type of ld</th>
                    <th>conducted/sponsored by</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['learning_development'] as $training)
                <tr>
                    <td>{{ $training['training_title'] }}</td>
                    <td>{{ $training['inclusive_date_from'] }} to {{ $training['inclusive_date_to'] }}</td>
                    <td>{{ $training['number_of_hours'] }}</td>
                    <td>{{ $training['type_of_ld'] }}</td>
                    <td>{{ $training['conducted_by_sponsor'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <!-- VIII. OTHER INFORMATION -->
    <div class="section">
        <div class="section-title">VIII. OTHER INFORMATION</div>
        
        @if(count($pdsData['other_information']['special_skills']) > 0)
        <div class="field-group">
            <div class="section-title">special skills and hobbies</div>
            @foreach($pdsData['other_information']['special_skills'] as $skill)
            <div class="field-row">
                <div class="field-value">{{ $skill['description'] }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(count($pdsData['other_information']['distinctions']) > 0)
        <div class="field-group">
            <div class="section-title">non-academic distinctions / recognition</div>
            @foreach($pdsData['other_information']['distinctions'] as $distinction)
            <div class="field-row">
                <div class="field-value">{{ $distinction['description'] }}</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(count($pdsData['other_information']['memberships']) > 0)
        <div class="field-group">
            <div class="section-title">membership in association/organization</div>
            @foreach($pdsData['other_information']['memberships'] as $membership)
            <div class="field-row">
                <div class="field-value">{{ $membership['description'] }}</div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <!-- IX. REFERENCES -->
    <div class="section">
        <div class="section-title">IX. REFERENCES</div>
        
        @if(count($pdsData['references']) > 0)
        <table>
            <thead>
                <tr>
                    <th>name</th>
                    <th>address</th>
                    <th>tel. no.</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pdsData['references'] as $reference)
                <tr>
                    <td>{{ $reference['full_name'] }}</td>
                    <td>{{ $reference['address'] }}</td>
                    <td>{{ $reference['telephone_no'] }}</td>
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
            <div class="field-row">
                <div class="field-label">34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed?</div>
                <div class="field-value">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['government_service_related']) && $pdsData['questionnaire']['government_service_related'] == 'Yes' ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['government_service_related']) && $pdsData['questionnaire']['government_service_related'] == 'No' ? 'checked' : '' }}"></span> NO
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">35. Have you ever been formally charged with or administratively found guilty of any administrative offense?</div>
                <div class="field-value">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['administrative_offense']) && $pdsData['questionnaire']['administrative_offense'] == 'Yes' ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['administrative_offense']) && $pdsData['questionnaire']['administrative_offense'] == 'No' ? 'checked' : '' }}"></span> NO
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">36. Have you ever been criminally charged before any court?</div>
                <div class="field-value">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['criminal_charged']) && $pdsData['questionnaire']['criminal_charged'] == 'Yes' ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['criminal_charged']) && $pdsData['questionnaire']['criminal_charged'] == 'No' ? 'checked' : '' }}"></span> NO
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">37. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?</div>
                <div class="field-value">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['convicted']) && $pdsData['questionnaire']['convicted'] == 'Yes' ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['convicted']) && $pdsData['questionnaire']['convicted'] == 'No' ? 'checked' : '' }}"></span> NO
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">38. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?</div>
                <div class="field-value">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['separated_service']) && $pdsData['questionnaire']['separated_service'] == 'Yes' ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['separated_service']) && $pdsData['questionnaire']['separated_service'] == 'No' ? 'checked' : '' }}"></span> NO
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">39. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?</div>
                <div class="field-value">
                    <span class="checkbox {{ isset($pdsData['questionnaire']['candidate_resigned']) && $pdsData['questionnaire']['candidate_resigned'] == 'Yes' ? 'checked' : '' }}"></span> YES
                    <span class="checkbox {{ isset($pdsData['questionnaire']['candidate_resigned']) && $pdsData['questionnaire']['candidate_resigned'] == 'No' ? 'checked' : '' }}"></span> NO
                </div>
            </div>
            
            <div class="field-row">
                <div class="field-label">40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:</div>
                <div class="field-value">
                    <div>Are you a member of any indigenous group? 
                        <span class="checkbox {{ isset($pdsData['questionnaire']['immigrant_indigenous']) && $pdsData['questionnaire']['immigrant_indigenous'] == 'Yes' ? 'checked' : '' }}"></span> YES
                        <span class="checkbox {{ isset($pdsData['questionnaire']['immigrant_indigenous']) && $pdsData['questionnaire']['immigrant_indigenous'] == 'No' ? 'checked' : '' }}"></span> NO
                    </div>
                    <div>Are you a person with disability? 
                        <span class="checkbox {{ isset($pdsData['questionnaire']['pwd']) && $pdsData['questionnaire']['pwd'] == 'Yes' ? 'checked' : '' }}"></span> YES
                        <span class="checkbox {{ isset($pdsData['questionnaire']['pwd']) && $pdsData['questionnaire']['pwd'] == 'No' ? 'checked' : '' }}"></span> NO
                    </div>
                    <div>Are you a solo parent? 
                        <span class="checkbox {{ isset($pdsData['questionnaire']['solo_parent']) && $pdsData['questionnaire']['solo_parent'] == 'Yes' ? 'checked' : '' }}"></span> YES
                        <span class="checkbox {{ isset($pdsData['questionnaire']['solo_parent']) && $pdsData['questionnaire']['solo_parent'] == 'No' ? 'checked' : '' }}"></span> NO
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Signature Section -->
    <div class="signature-section">
        <p style="font-size: 7px; font-style: italic; margin-bottom: 10px;">
            I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein. I agree that any misrepresentation made in this document and its attachments shall cause the filing of administrative/criminal case/s against me.
        </p>
        
        <div style="display: flex; justify-content: space-between; margin-top: 20px;">
            <div style="width: 48%;">
                <div class="signature-box">
                    Signature
                </div>
                <div class="signature-box">
                    Date Accomplished
                </div>
            </div>
            <div style="width: 48%;">
                <div class="signature-box">
                    Right Thumbmark
                </div>
                <div class="signature-box">
                    Date Accomplished
                </div>
            </div>
        </div>
    </div>
</body>
</html>