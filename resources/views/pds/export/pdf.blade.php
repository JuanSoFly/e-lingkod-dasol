<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10mm 10mm; size: 8.5in 11in; }
        body { font-family: 'Arial Narrow', Arial, sans-serif; font-size: 8.5px; color: #000; line-height: 1.1; }
        
        /* Utility Classes */
        .w-100 { width: 100%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .italic { font-style: italic; }
        .uppercase { text-transform: uppercase; }
        .no-border { border: none !important; }
        .border-bottom { border-bottom: 1px solid #000; }
        .page-break { page-break-after: always; }
        
        /* Table Styles */
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td, th { border: 1px solid #000; padding: 1.5px 2px; vertical-align: top; overflow: hidden; }
        
        /* Specific Form Styles */
        .header-text { font-family: "Arial Narrow", Arial, sans-serif; font-weight: bold; }
        .section-header { 
            background-color: #a6a6a6; 
            color: #fff; 
            font-style: italic; 
            font-weight: bold; 
            border: 1px solid #000; 
            padding: 2px 5px;
        }
        .field-label { 
            background-color: #f2f2f2; 
            font-size: 7.3px; 
            padding: 1.5px 2px;
        }
        .field-content { 
            font-size: 9px; 
            font-weight: bold; 
            padding: 1.5px 3px;
            text-transform: uppercase;
        }
        .input-box {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 20px;
            text-align: center;
        }
        .checkbox {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #000;
            margin-right: 3px;
            vertical-align: middle;
            text-align: center;
            line-height: 8px;
            font-size: 8px;
        }
        
        /* Character Box Styles */
        .char-box-container {
            display: inline-block;
            vertical-align: middle;
        }
        .char-box {
            display: inline-block;
            width: 11px;
            height: 13px;
            border: 1px solid #000;
            text-align: center;
            line-height: 13px;
            font-size: 8.5px;
            font-weight: bold;
            margin-right: -1px; /* Collapse borders */
            vertical-align: middle;
            background-color: #fff;
        }
        
        /* Grid helpers */
        .row-height-1 { height: 20px; }
        .row-height-2 { height: 40px; }
        
        /* Warning Text */
        .warning-text {
            font-size: 7.5px;
            font-style: italic;
            font-weight: bold;
            color: #000;
        }

        .signature-block {
            margin-top: 6px;
        }
        .signature-line {
            display: inline-block;
            border-bottom: 1px solid #000;
            height: 16px;
            vertical-align: bottom;
        }
    </style>
</head>
<body>
    {{-- PAGE 1 --}}
    
    {{-- Header --}}
    <div class="header-text text-left" style="font-size: 8px; font-weight: bold; font-family: 'Arial Narrow', Arial, sans-serif;">CS Form No. 212</div>
    <div class="header-text text-left" style="font-size: 8px; font-weight: bold; font-family: 'Arial Narrow', Arial, sans-serif;">Revised 2017</div>
    
    <div class="header-text text-center" style="font-size: 24px; margin-top: 10px; font-weight: 900;">PERSONAL DATA SHEET</div>
    
    <div class="header-text text-left warning-text" style="margin-top: 10px;">
        WARNING: Any misrepresentation made in the Personal Data Sheet and the Work Experience Sheet shall cause the filing of administrative/criminal case/s against the person concerned.
    </div>
    
    <div class="header-text text-left warning-text" style="margin-bottom: 5px;">
        READ THE ATTACHED GUIDE TO FILLING OUT THE PERSONAL DATA SHEET (PDS) BEFORE ACCOMPLISHING THE PDS FORM.
    </div>
    
    <div style="display: flex; justify-content: space-between; font-size: 7.5px; align-items: flex-end; margin-bottom: 3px;">
        <div style="width: 72%;">Print legibly. Tick appropriate boxes ( ) and use separate sheet if necessary. Indicate N/A if not applicable. <strong>DO NOT ABBREVIATE.</strong></div>
        <div style="border: 1px solid #000; padding: 2px; width: 25%; display: flex;">
            <div style="width: 35%; background-color: #f2f2f2; font-size: 6.5px; border-right: 1px solid #000; padding: 1px;">1. CS ID No.<br><span style="font-weight: normal;">(Do not fill up. For CSC use only)</span></div>
            <div style="width: 65%;"></div>
        </div>
    </div>
    
    {{-- I. PERSONAL INFORMATION --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">I. PERSONAL INFORMATION</div>

    {{-- Name block --}}
    <table style="border: 2px solid #000;">
        <tr>
            <td class="field-label" style="width: 18%;">2. SURNAME</td>
            <td class="field-content" colspan="5">{{ $employee->last_name }}</td>
        </tr>
        <tr>
            <td class="field-label">FIRST NAME</td>
            <td class="field-content" colspan="3">{{ $employee->first_name }}</td>
            <td class="field-label" style="width: 18%; text-align: center;">NAME EXTENSION (JR., SR)</td>
            <td class="field-content" style="width: 12%; text-align: center;">{{ $employee->name_extension ?? $employee->suffix ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">MIDDLE NAME</td>
            <td class="field-content" colspan="5">{{ $employee->middle_name ?? '' }}</td>
        </tr>
    </table>

    {{-- Personal info rows 3-8 and 16-17 --}}
    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td class="field-label" style="width: 20%;">3. DATE OF BIRTH<br><span style="font-size: 7px; font-weight: normal;">(mm/dd/yyyy)</span></td>
            <td class="field-content" style="width: 25%;">
                <div class="char-box-container">
                    @if($employee->birth_date)
                        @foreach(str_split(optional($employee->birth_date)->format('m')) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                        <span style="margin: 0 2px;">/</span>
                        @foreach(str_split(optional($employee->birth_date)->format('d')) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                        <span style="margin: 0 2px;">/</span>
                        @foreach(str_split(optional($employee->birth_date)->format('Y')) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
            <td class="field-label" style="width: 18%;">16. CITIZENSHIP</td>
            <td class="field-content" style="width: 37%;">
                <div style="margin-bottom: 2px;">
                    <span class="checkbox">{{ $employee->citizenship == 'Filipino' ? 'X' : '' }}</span> Filipino
                    <span class="checkbox" style="margin-left: 10px;">{{ $employee->citizenship && $employee->citizenship != 'Filipino' ? 'X' : '' }}</span> Dual Citizenship
                </div>
                <div style="font-size: 7px; margin-left: 15px; margin-top: 1px;">
                    <span class="checkbox"></span> by birth
                    <span class="checkbox" style="margin-left: 8px;"></span> by naturalization
                </div>
                <div style="margin-top: 3px;">If holder of dual citizenship, please indicate the details. Pls. indicate country:
                    <span style="border-bottom: 1px solid #000; min-width: 60px; display: inline-block;">{{ $employee->citizenship != 'Filipino' ? $employee->citizenship : '' }}</span>
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">4. PLACE OF BIRTH</td>
            <td class="field-content">{{ $employee->place_of_birth }}</td>
            <td class="field-label" rowspan="5" style="vertical-align: top;">17. RESIDENTIAL ADDRESS</td>
            <td class="field-content" rowspan="5">
                <div style="display: flex; flex-wrap: wrap;">
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $residentialAddress['house_block_lot'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">House/Block/Lot No.</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $residentialAddress['street'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Street</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $residentialAddress['subdivision'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Subdivision/Village</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $residentialAddress['barangay'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Barangay</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $residentialAddress['city_municipality'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">City/Municipality</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $residentialAddress['province'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Province</div>
                    </div>
                    <div style="width: 100%; margin-top: 2px;">
                        <div style="font-size: 7px; text-align: left; margin-bottom: 2px;">ZIP CODE</div>
                        <div class="char-box-container">
                            @if($residentialAddress['zip_code'])
                                @foreach(str_split($residentialAddress['zip_code']) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">5. SEX</td>
            <td class="field-content">
                <span class="checkbox">{{ $employee->gender == 'Male' ? 'X' : '' }}</span> Male
                <span class="checkbox" style="margin-left: 8px;">{{ $employee->gender == 'Female' ? 'X' : '' }}</span> Female
            </td>
        </tr>
        <tr>
            <td class="field-label">6. CIVIL STATUS</td>
            <td class="field-content">
                <div style="display: inline-block; width: 48%; vertical-align: top;">
                    <span class="checkbox">{{ $employee->civil_status == 'Single' ? 'X' : '' }}</span> Single<br>
                    <span class="checkbox">{{ $employee->civil_status == 'Married' ? 'X' : '' }}</span> Married<br>
                    <span class="checkbox">{{ $employee->civil_status == 'Widowed' ? 'X' : '' }}</span> Widowed
                </div>
                <div style="display: inline-block; width: 48%; vertical-align: top;">
                    <span class="checkbox">{{ $employee->civil_status == 'Separated' ? 'X' : '' }}</span> Separated<br>
                    <span class="checkbox">{{ $employee->civil_status == 'Other' ? 'X' : '' }}</span> Other/s:
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">7. HEIGHT (m)</td>
            <td class="field-content">{{ $employee->height }}</td>
        </tr>
        <tr>
            <td class="field-label">8. WEIGHT (kg)</td>
            <td class="field-content">{{ $employee->weight }}</td>
        </tr>
    </table>

    {{-- Items 9-21 and permanent address --}}
    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td class="field-label" style="width: 20%;">9. BLOOD TYPE</td>
            <td class="field-content" style="width: 25%;">{{ $employee->blood_type }}</td>
            <td class="field-label" style="width: 18%;" rowspan="4">18. PERMANENT ADDRESS</td>
            <td class="field-content" style="width: 37%;" rowspan="4">
                <div style="display: flex; flex-wrap: wrap;">
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $permanentAddress['house_block_lot'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">House/Block/Lot No.</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $permanentAddress['street'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Street</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $permanentAddress['subdivision'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Subdivision/Village</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $permanentAddress['barangay'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Barangay</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $permanentAddress['city_municipality'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">City/Municipality</div>
                    </div>
                    <div style="width: 50%; display: inline-block; margin-bottom: 4px;">
                        <div style="border-bottom: 1px solid #000; margin-bottom: 2px; min-height: 12px;">{{ $permanentAddress['province'] }}</div>
                        <div style="font-size: 6.5px; text-align: center; font-style: italic;">Province</div>
                    </div>
                    <div style="width: 100%; margin-top: 2px;">
                        <div style="font-size: 7px; text-align: left; margin-bottom: 2px;">ZIP CODE</div>
                        <div class="char-box-container">
                            @if($permanentAddress['zip_code'])
                                @foreach(str_split($permanentAddress['zip_code']) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                            @endif
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">10. GSIS ID NO.</td>
            <td class="field-content">
                <div class="char-box-container">
                    @if($employee->gsis_number)
                        @foreach(str_split(str_replace([' ','-'], '', $employee->gsis_number)) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">11. PAG-IBIG ID NO.</td>
            <td class="field-content">
                <div class="char-box-container">
                    @if($employee->pagibig_number)
                        @foreach(str_split(str_replace([' ','-'], '', $employee->pagibig_number)) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">12. PHILHEALTH NO.</td>
            <td class="field-content">
                <div class="char-box-container">
                    @if($employee->philhealth_number)
                        @foreach(str_split(str_replace([' ','-'], '', $employee->philhealth_number)) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
        </tr>
        <tr>
            <td class="field-label">13. SSS NO.</td>
            <td class="field-content">
                <div class="char-box-container">
                    @if($employee->sss_number)
                        @foreach(str_split(str_replace([' ','-'], '', $employee->sss_number)) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
            <td class="field-label">19. TELEPHONE NO.</td>
            <td class="field-content">{{ $employee->telephone_no }}</td>
        </tr>
        <tr>
            <td class="field-label">14. TIN NO.</td>
            <td class="field-content">
                <div class="char-box-container">
                    @if($employee->tin_number)
                        @foreach(str_split(str_replace([' ','-'], '', $employee->tin_number)) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
            <td class="field-label">20. MOBILE NO.</td>
            <td class="field-content">{{ $employee->mobile_no ?? $employee->contact_number }}</td>
        </tr>
        <tr>
            <td class="field-label">15. AGENCY EMPLOYEE NO.</td>
            <td class="field-content">
                <div class="char-box-container">
                    @if($employee->agency_employee_no ?? $employee->employee_number)
                        @foreach(str_split(str_replace([' ','-'], '', $employee->agency_employee_no ?? $employee->employee_number)) as $char)<span class="char-box">{{ $char }}</span>@endforeach
                    @endif
                </div>
            </td>
            <td class="field-label">21. E-MAIL ADDRESS (if any)</td>
            <td class="field-content">{{ $employee->email }}</td>
        </tr>
    </table>

    {{-- II. FAMILY BACKGROUND --}}
    <div class="section-header" style="margin-top: 5px;">II. FAMILY BACKGROUND</div>
    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td class="field-label" style="width: 15%;">22. SPOUSE'S SURNAME</td>
            <td class="field-content" colspan="3">{{ $family->spouse_surname }}</td>
            <td class="field-label" style="width: 25%; background-color: #eaeaea;">23. NAME OF CHILDREN (Write full name and list all)</td>
            <td class="field-label" style="width: 15%; background-color: #eaeaea;">DATE OF BIRTH (mm/dd/yyyy)</td>
        </tr>
        <tr>
            <td class="field-label">FIRST NAME</td>
            <td class="field-content" colspan="2">{{ $family->spouse_first_name }}</td>
            <td class="field-label" style="width: 10%;">
                <div style="font-size: 6px;">NAME EXTENSION (JR., SR)</div>
                <div class="field-content">{{ $family->spouse_name_extension ?? 'N/A' }}</div>
            </td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[0]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[0]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">MIDDLE NAME</td>
            <td class="field-content" colspan="3">{{ $family->spouse_middle_name }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[1]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[1]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">OCCUPATION</td>
            <td class="field-content" colspan="3">{{ $family->spouse_occupation }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[2]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[2]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">EMPLOYER/BUSINESS NAME</td>
            <td class="field-content" colspan="3">{{ $family->spouse_employer ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[3]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[3]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">BUSINESS ADDRESS</td>
            <td class="field-content" colspan="3">{{ $family->spouse_business_address ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[4]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[4]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">TELEPHONE NO.</td>
            <td class="field-content" colspan="3">{{ $family->spouse_telephone ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[5]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[5]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">24. FATHER'S SURNAME</td>
            <td class="field-content" colspan="3">{{ $family->father_surname }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[6]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[6]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">FIRST NAME</td>
            <td class="field-content" colspan="2">{{ $family->father_first_name }}</td>
            <td class="field-label">
                <div style="font-size: 6px;">NAME EXTENSION (JR., SR)</div>
                <div class="field-content">{{ $family->father_name_extension ?? 'N/A' }}</div>
            </td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[7]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[7]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">MIDDLE NAME</td>
            <td class="field-content" colspan="3">{{ $family->father_middle_name }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[8]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[8]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">25. MOTHER'S MAIDEN NAME</td>
            <td class="field-content" colspan="3">{{ $family->mother_maiden_name ?? $family->mother_surname }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[9]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[9]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">SURNAME</td>
            <td class="field-content" colspan="3">{{ $family->mother_surname }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[10]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[10]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">FIRST NAME</td>
            <td class="field-content" colspan="3">{{ $family->mother_first_name }}</td>
            <td class="field-content" style="vertical-align: middle;">{{ $children[11]['name'] ?? 'N/A' }}</td>
            <td class="field-content" style="vertical-align: middle; text-align: center;">{{ $children[11]['birth_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="field-label">MIDDLE NAME</td>
            <td class="field-content" colspan="3">{{ $family->mother_middle_name }}</td>
            <td class="field-label" colspan="2" style="text-align: center; font-style: italic; color: #666;">(Continue on separate sheet if necessary)</td>
        </tr>
    </table>

    {{-- III. EDUCATIONAL BACKGROUND --}}
    <div class="section-header" style="margin-top: 5px;">III. EDUCATIONAL BACKGROUND</div>
    <table style="border: 2px solid #000; border-top: none;">
        <tr class="text-center field-label">
            <td rowspan="2" style="width: 15%;">26. LEVEL</td>
            <td rowspan="2" style="width: 25%;">NAME OF SCHOOL<br>(Write in full)</td>
            <td rowspan="2" style="width: 25%;">BASIC EDUCATION/DEGREE/COURSE<br>(Write in full)</td>
            <td colspan="2" style="width: 15%;">PERIOD OF ATTENDANCE</td>
            <td rowspan="2" style="width: 10%;">HIGHEST LEVEL/UNITS EARNED<br>(if not graduated)</td>
            <td rowspan="2" style="width: 10%;">YEAR GRADUATED</td>
            <td rowspan="2" style="width: 10%;">SCHOLARSHIP/ ACADEMIC HONORS RECEIVED</td>
        </tr>
        <tr class="text-center field-label">
            <td>FROM</td>
            <td>TO</td>
        </tr>
        
        @foreach($education as $edu)
        <tr class="text-center">
            <td class="field-label" style="text-align: left;">{{ $edu['level'] }}</td>
            <td class="field-content">{{ $edu['school_name'] }}</td>
            <td class="field-content">{{ $edu['degree_course'] }}</td>
            <td class="field-content">{{ $edu['period_from'] }}</td>
            <td class="field-content">{{ $edu['period_to'] }}</td>
            <td class="field-content">{{ $edu['highest_level'] }}</td>
            <td class="field-content">{{ $edu['year_graduated'] }}</td>
            <td class="field-content">{{ $edu['honors'] }}</td>
        </tr>
        @endforeach
    </table>
    
    <div class="text-center" style="font-style: italic; font-size: 7px; margin-top: 2px;">(Continue on separate sheet if necessary)</div>

    <div class="signature-block">
        <div>
            <span class="signature-line" style="width: 55%;"></span>
            <span style="display: inline-block; width: 6%;"></span>
            <span class="signature-line" style="width: 20%;"></span>
        </div>
        <div style="font-size: 8px; margin-top: 2px;">
            <span style="display: inline-block; width: 55%;">SIGNATURE</span>
            <span style="display: inline-block; width: 6%;"></span>
            <span style="display: inline-block; width: 20%;">DATE</span>
        </div>
    </div>
    
    <div class="text-right" style="font-size: 8px; font-weight: bold; margin-top: 5px;">CS FORM 212 (Revised 2017), Page 1 of 4</div>
    
    <div class="page-break"></div>
    
    {{-- PAGE 2 --}}
    
    {{-- IV. CIVIL SERVICE ELIGIBILITY --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">IV. CIVIL SERVICE ELIGIBILITY</div>
    <table style="border: 2px solid #000;">
        <tr class="text-center field-label">
            <td rowspan="2" style="width: 27%; border-bottom: 1px solid #000; border-right: 1px solid #000;">27. CAREER SERVICE/ RA 1080 (BOARD/ BAR) UNDER SPECIAL LAWS/ CES/ CSEE BARANGAY ELIGIBILITY / DRIVER'S LICENSE</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">RATING<br>(If Applicable)</td>
            <td rowspan="2" style="width: 15%; border-bottom: 1px solid #000; border-right: 1px solid #000;">DATE OF EXAMINATION / CONFERMENT</td>
            <td rowspan="2" style="width: 28%; border-bottom: 1px solid #000; border-right: 1px solid #000;">PLACE OF EXAMINATION / CONFERMENT</td>
            <td colspan="2" style="width: 20%; border-bottom: 1px solid #000;">LICENSE (if applicable)</td>
        </tr>
        <tr class="text-center field-label">
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">NUMBER</td>
            <td style="border-bottom: 1px solid #000;">Date of Validity</td>
        </tr>
        
        @foreach($eligibilities as $el)
        <tr class="text-center">
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $el['eligibility_name'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $el['rating'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $el['exam_date'] }}</td>
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $el['exam_place'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $el['license_number'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000;">{{ $el['validity_date'] }}</td>
        </tr>
        @endforeach
        {{-- Fill empty rows if needed, but for now just dynamic --}}
    </table>
    <div class="text-center" style="font-style: italic; font-size: 7px; margin-top: 2px;">(Continue on separate sheet if necessary)</div>

    {{-- V. WORK EXPERIENCE --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">V. WORK EXPERIENCE 
        <span style="font-size: 7px; font-weight: normal; margin-left: 10px;">(Include private employment.  Start from your recent work) Description of duties should be indicated in the attached Work Experience sheet.</span>
    </div>
    <table style="border: 2px solid #000;">
        <tr class="text-center field-label">
            <td colspan="2" style="width: 17%; border-bottom: 1px solid #000; border-right: 1px solid #000;">28. INCLUSIVE DATES (mm/dd/yyyy)</td>
            <td rowspan="2" style="width: 28%; border-bottom: 1px solid #000; border-right: 1px solid #000;">POSITION TITLE<br>(Write in full/Do not abbreviate)</td>
            <td rowspan="2" style="width: 25%; border-bottom: 1px solid #000; border-right: 1px solid #000;">DEPARTMENT / AGENCY / OFFICE / COMPANY<br>(Write in full/Do not abbreviate)</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">MONTHLY SALARY</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">SALARY/ JOB/ PAY GRADE (if applicable)& STEP  (Format "00-0")/ INCREMENT</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">STATUS OF APPOINTMENT</td>
            <td rowspan="2" style="width: 5%; border-bottom: 1px solid #000;">GOV'T SERVICE (Y/N)</td>
        </tr>
        <tr class="text-center field-label">
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">FROM</td>
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">TO</td>
        </tr>
        
        @foreach($workExperiences as $work)
        <tr class="text-center">
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['from'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['to'] }}</td>
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['position'] }}</td>
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['department'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['monthly_salary'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['salary_grade'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $work['appointment_status'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000;">{{ $work['is_government_service'] == 'Yes' ? 'Y' : 'N' }}</td>
        </tr>
        @endforeach
    </table>
    <div class="text-center" style="font-style: italic; font-size: 7px; margin-top: 2px;">(Continue on separate sheet if necessary)</div>

    <div class="signature-block">
        <div>
            <span class="signature-line" style="width: 55%;"></span>
            <span style="display: inline-block; width: 6%;"></span>
            <span class="signature-line" style="width: 20%;"></span>
        </div>
        <div style="font-size: 8px; margin-top: 2px;">
            <span style="display: inline-block; width: 55%;">SIGNATURE</span>
            <span style="display: inline-block; width: 6%;"></span>
            <span style="display: inline-block; width: 20%;">DATE</span>
        </div>
    </div>
    
    <div class="text-right" style="font-size: 8px; font-weight: bold; margin-top: 5px; font-family: 'Arial Narrow', Arial, sans-serif;">CS FORM 212 (Revised 2017), Page 2 of 4</div>
    
    <div class="page-break"></div>
    
    {{-- PAGE 3 --}}
    
    {{-- VI. VOLUNTARY WORK --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATION/S</div>
    <table style="border: 2px solid #000;">
        <tr class="text-center field-label">
            <td rowspan="2" style="width: 40%; border-bottom: 1px solid #000; border-right: 1px solid #000;">29. NAME & ADDRESS OF ORGANIZATION<br>(Write in full)</td>
            <td colspan="2" style="width: 20%; border-bottom: 1px solid #000; border-right: 1px solid #000;">INCLUSIVE DATES<br>(mm/dd/yyyy)</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">NUMBER OF HOURS</td>
            <td rowspan="2" style="width: 30%; border-bottom: 1px solid #000;">POSITION / NATURE OF WORK</td>
        </tr>
        <tr class="text-center field-label">
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">FROM</td>
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">TO</td>
        </tr>
        
        @foreach($voluntaryWork as $vol)
        <tr class="text-center">
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $vol['organization'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $vol['from'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $vol['to'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $vol['hours'] }}</td>
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000;">{{ $vol['position'] }}</td>
        </tr>
        @endforeach
    </table>
    <div class="text-center" style="font-style: italic; font-size: 7px; margin-top: 2px;">(Continue on separate sheet if necessary)</div>

    {{-- VII. LEARNING AND DEVELOPMENT --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">VII. LEARNING AND DEVELOPMENT (L&D) INTERVENTIONS/TRAINING PROGRAMS ATTENDED<br>
        <span style="font-size: 7px; font-weight: normal;">(Start from the most recent L&D/training program and include only the relevant L&D/training taken for the last five (5) years for Division Chief/Executive/Managerial positions)</span>
    </div>
    <table style="border: 2px solid #000;">
        <tr class="text-center field-label">
            <td rowspan="2" style="width: 40%; border-bottom: 1px solid #000; border-right: 1px solid #000;">30. TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS/TRAINING PROGRAMS<br>(Write in full)</td>
            <td colspan="2" style="width: 20%; border-bottom: 1px solid #000; border-right: 1px solid #000;">INCLUSIVE DATES OF ATTENDANCE<br>(mm/dd/yyyy)</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">NUMBER OF HOURS</td>
            <td rowspan="2" style="width: 10%; border-bottom: 1px solid #000; border-right: 1px solid #000;">Type of LD<br>( Managerial/ Supervisory/ Technical/etc)</td>
            <td rowspan="2" style="width: 20%; border-bottom: 1px solid #000;">CONDUCTED/ SPONSORED BY<br>(Write in full)</td>
        </tr>
        <tr class="text-center field-label">
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">FROM</td>
            <td style="border-bottom: 1px solid #000; border-right: 1px solid #000;">TO</td>
        </tr>
        
        @foreach($trainings as $training)
        <tr class="text-center">
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $training['title'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $training['from'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $training['to'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $training['hours'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $training['type'] }}</td>
            <td class="field-content" style="text-align: left; border-bottom: 1px solid #000;">{{ $training['conducted_by'] }}</td>
        </tr>
        @endforeach
    </table>
    <div class="text-center" style="font-style: italic; font-size: 7px; margin-top: 2px;">(Continue on separate sheet if necessary)</div>

    {{-- VIII. OTHER INFORMATION --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">VIII. OTHER INFORMATION</div>
    <table style="border: 2px solid #000;">
        <tr class="text-center field-label">
            <td style="width: 33%; border-bottom: 1px solid #000; border-right: 1px solid #000;">31. SPECIAL SKILLS and HOBBIES</td>
            <td style="width: 33%; border-bottom: 1px solid #000; border-right: 1px solid #000;">32. NON-ACADEMIC DISTINCTIONS / RECOGNITION<br>(Write in full)</td>
            <td style="width: 33%; border-bottom: 1px solid #000;">33. MEMBERSHIP IN ASSOCIATION/ORGANIZATION<br>(Write in full)</td>
        </tr>
        
        <tr>
            <td class="field-content" style="height: 90px; border-right: 1px solid #000; vertical-align: top;">{{ $otherInfo['skills'] }}</td>
            <td class="field-content" style="border-right: 1px solid #000; vertical-align: top;">{{ $otherInfo['distinctions'] }}</td>
            <td class="field-content" style="vertical-align: top;">{{ $otherInfo['memberships'] }}</td>
        </tr>
    </table>
    <div class="text-center" style="font-style: italic; font-size: 7px; margin-top: 2px;">(Continue on separate sheet if necessary)</div>

    <div class="signature-block">
        <div>
            <span class="signature-line" style="width: 55%;"></span>
            <span style="display: inline-block; width: 6%;"></span>
            <span class="signature-line" style="width: 20%;"></span>
        </div>
        <div style="font-size: 8px; margin-top: 2px;">
            <span style="display: inline-block; width: 55%;">SIGNATURE</span>
            <span style="display: inline-block; width: 6%;"></span>
            <span style="display: inline-block; width: 20%;">DATE</span>
        </div>
    </div>
    
    <div class="text-right" style="font-size: 8px; font-weight: bold; margin-top: 5px; font-family: 'Arial Narrow', Arial, sans-serif;">CS FORM 212 (Revised 2017), Page 3 of 4</div>
    
    <div class="page-break"></div>
    
    {{-- PAGE 4 --}}
    
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be apppointed,</div>
    
    <table style="border: 2px solid #000;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px; border-bottom: 1px solid #000;">
                a. within the third degree?
            </td>
            <td style="width: 35%; padding: 2px; border-bottom: 1px solid #000;">
                <span class="checkbox">{{ $questionnaire['q34_3rd_degree'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q34_3rd_degree'] === false ? 'X' : '' }}</span> NO
            </td>
        </tr>
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                b. within the fourth degree (for Local Government Unit - Career Employees)?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q34b_4th_degree'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q34b_4th_degree'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['34b'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px; border-bottom: 1px solid #000;">
                35. a. Have you ever been found guilty of any administrative offense?
            </td>
            <td style="width: 35%; padding: 2px; border-bottom: 1px solid #000;">
                <span class="checkbox">{{ $questionnaire['q35a_administrative_offense'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q35a_administrative_offense'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['35a'] ?? '' }}</span></div>
            </td>
        </tr>
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                b. Have you been criminally charged before any court?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q35b_criminal_charge'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q35b_criminal_charge'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['35b'] ?? '' }}</span></div>
                <div style="margin-top: 2px;">Date Filed: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['35b_date'] ?? '' }}</span></div>
                <div style="margin-top: 2px;">Status of Case/s: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['35b_status'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q36_conviction'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q36_conviction'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['36'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                37. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q37_separation'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q37_separation'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['37'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px; border-bottom: 1px solid #000;">
                38. a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?
            </td>
            <td style="width: 35%; padding: 2px; border-bottom: 1px solid #000;">
                <span class="checkbox">{{ $questionnaire['q38a_election_candidacy'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q38a_election_candidacy'] === false ? 'X' : '' }}</span> NO
            </td>
        </tr>
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q38b_resignation_campaign'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q38b_resignation_campaign'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['38b'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                39. Have you acquired the status of an immigrant or permanent resident of another country?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q39_immigrant_status'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q39_immigrant_status'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, give details (country): <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['39'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    <table style="border: 2px solid #000; border-top: none;">
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px; border-bottom: 1px solid #000;">
                40. Pursuant to: (a) Indigenous People's Act (RA 8371); (b) Magna Carta for Disabled Persons (RA 7277); and (c) Solo Parents Welfare Act of 2000 (RA 8972), please answer the following items:
            </td>
            <td style="width: 35%; padding: 2px; border-bottom: 1px solid #000;">
            </td>
        </tr>
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px; border-bottom: 1px solid #000;">
                a. Are you a member of any indigenous group?
            </td>
            <td style="width: 35%; padding: 2px; border-bottom: 1px solid #000;">
                <span class="checkbox">{{ $questionnaire['q40a_indigenous_group'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q40a_indigenous_group'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, please specify: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['40a'] ?? '' }}</span></div>
            </td>
        </tr>
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px; border-bottom: 1px solid #000;">
                b. Are you a person with disability?
            </td>
            <td style="width: 35%; padding: 2px; border-bottom: 1px solid #000;">
                <span class="checkbox">{{ $questionnaire['q40b_person_with_disability'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q40b_person_with_disability'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, please specify ID No: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['40b'] ?? '' }}</span></div>
            </td>
        </tr>
        <tr>
            <td style="width: 65%; border-right: 1px solid #000; padding: 2px;">
                c. Are you a solo parent?
            </td>
            <td style="width: 35%; padding: 2px;">
                <span class="checkbox">{{ $questionnaire['q40c_solo_parent'] === true ? 'X' : '' }}</span> YES
                <span class="checkbox" style="margin-left: 10px;">{{ $questionnaire['q40c_solo_parent'] === false ? 'X' : '' }}</span> NO
                <div style="margin-top: 2px;">If YES, please specify ID No: <span style="border-bottom: 1px solid #000;">{{ $questionnaire['details']['40c'] ?? '' }}</span></div>
            </td>
        </tr>
    </table>

    {{-- 41. REFERENCES --}}
    <div class="section-header" style="margin-top: 5px; border-bottom: none;">41. REFERENCES <span style="font-size: 7px; font-weight: normal;">(Person not related by consanguinity or affinity to applicant /appointee)</span></div>
    <table style="border: 2px solid #000;">
        <tr class="text-center field-label">
            <td style="width: 30%; border-bottom: 1px solid #000; border-right: 1px solid #000;">NAME</td>
            <td style="width: 40%; border-bottom: 1px solid #000; border-right: 1px solid #000;">ADDRESS</td>
            <td style="width: 30%; border-bottom: 1px solid #000;">TEL. NO.</td>
        </tr>
        @foreach($referencesData as $ref)
        <tr class="text-center">
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $ref['name'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000; border-right: 1px solid #000;">{{ $ref['address'] }}</td>
            <td class="field-content" style="border-bottom: 1px solid #000;">{{ $ref['telephone'] }}</td>
        </tr>
        @endforeach
    </table>

    <div class="section-header" style="margin-top: 5px; border-bottom: none; text-align: justify;">42. I declare under oath that I have personally accomplished this Personal Data Sheet which is a true, correct and complete statement pursuant to the provisions of pertinent laws, rules and regulations of the Republic of the Philippines. I authorize the agency head/authorized representative to verify/validate the contents stated herein.  I agree that any misrepresentation made in this Personal Data Sheet and its attachments shall cause the filing of administrative/criminal case/s against me.</div>
    
    <table style="border: 2px solid #000;">
        <tr>
            <td style="width: 35%; padding: 5px;">
                <div style="border: 1px solid #000; padding: 5px; margin-bottom: 5px;">
                    <div style="font-size: 7px;">Government Issued ID (i.e., Passport, GSIS, SSS, PRC, Driver's License, etc.)</div>
                    <div style="font-size: 7px; margin-top: 5px;">PLEASE INDICATE ID Number and Date of Issuance</div>
                    <div style="margin-top: 5px;">Government Issued ID: <span class="field-content">{{ $employee->gov_id_type }}</span></div>
                    <div style="margin-top: 5px;">ID/License/Passport No.: <span class="field-content">{{ $employee->gov_id_number }}</span></div>
                    <div style="margin-top: 5px;">Date/Place of Issuance: <span class="field-content">{{ $employee->gov_id_date_issued ? \Carbon\Carbon::parse($employee->gov_id_date_issued)->format('m/d/Y') : '' }} {{ $employee->gov_id_place_issued }}</span></div>
                </div>
            </td>
            <td style="width: 35%; padding: 5px;">
                <div style="height: 80px; border-bottom: 1px solid #000;"></div>
                <div style="text-align: center; font-size: 7px;">Signature (Sign inside the box)</div>
                <div style="height: 30px; border-bottom: 1px solid #000; margin-top: 10px;"></div>
                <div style="text-align: center; font-size: 7px;">Date Accomplished</div>
            </td>
            <td style="width: 30%; padding: 5px;">
                <div style="border: 1px solid #000; height: 120px; text-align: center; display: flex; align-items: center; justify-content: center;">
                    @if($photoData)
                        <img src="{{ $photoData }}" style="max-width: 100%; max-height: 100%;">
                    @else
                        <div style="margin-top: 50px; font-size: 8px;">ID PICTURE TAKEN WITHIN THE LAST 6 MONTHS<br>3.5 cm X 4.5 cm<br>(Passport Size)<br><br>With full and handwritten name tag and signature over printed name<br><br>Computer generated or photocopied picture is not acceptable</div>
                    @endif
                </div>
                <div style="text-align: center; font-size: 7px; margin-top: 2px;">PHOTO</div>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <table class="no-border">
                    <tr>
                        <td class="no-border" style="width: 30%;">
                            <div style="text-align: center; margin-top: 20px;">
                                <div style="font-size: 7px;">SUBSCRIBED AND SWORN to before me this</div>
                                <div style="border-bottom: 1px solid #000; width: 80%; margin: 0 auto; height: 15px;"></div>
                            </div>
                        </td>
                        <td class="no-border" style="width: 40%;">
                            <div style="border: 1px solid #000; height: 60px; width: 100px; margin: 0 auto;">
                                @if($thumbmarkData)
                                    <img src="{{ $thumbmarkData }}" style="width: 100%; height: 100%; object-fit: contain;">
                                @endif
                            </div>
                            <div style="text-align: center; font-size: 7px;">Right Thumbmark</div>
                        </td>
                        <td class="no-border" style="width: 30%;">
                            <div style="text-align: center; margin-top: 20px;">
                                <div style="border-bottom: 1px solid #000; width: 90%; margin: 0 auto; height: 15px;"></div>
                                <div style="font-size: 7px;">Person Administering Oath</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    
    <div class="text-right" style="font-size: 8px; font-weight: bold; margin-top: 5px; font-family: 'Arial Narrow', Arial, sans-serif;">CS FORM 212 (Revised 2017), Page 4 of 4</div>

</body>
</html>
