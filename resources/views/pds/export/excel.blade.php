<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
</head>
<body>
    @foreach($employeesData as $index => $data)
        @php
            $employee = $data['employee'];
            $family = $data['family'];
            $children = $data['children'];
            $education = $data['education'];
            $eligibilities = $data['eligibilities'];
            $workExperiences = $data['workExperiences'];
            $voluntaryWork = $data['voluntaryWork'];
            $trainings = $data['trainings'];
            $otherInfo = $data['otherInfo'];
            $referencesData = $data['referencesData'];
            $questionnaire = $data['questionnaire'];
            $res = $data['res'];
            $perm = $data['perm'];
        @endphp

        {{-- Spacing between multiple employees --}}
        @if($index > 0)
            <table>
                <tr><td colspan="8" style="height: 30px;"></td></tr>
            </table>
        @endif

        {{-- Title Block --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 16px; text-align: center; background-color: #2F5597; color: #FFFFFF; height: 40px; vertical-align: middle;">
                    PERSONAL DATA SHEET (CS FORM NO. 212)
                </td>
            </tr>
            <tr>
                <td colspan="8" style="font-style: italic; font-size: 10px; text-align: right; height: 20px; vertical-align: middle;">
                    Revised 2017
                </td>
            </tr>
        </table>

        {{-- I. PERSONAL INFORMATION --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    I. PERSONAL INFORMATION
                </td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; width: 20%;">EMPLOYEE NUMBER</td>
                <td colspan="3" style="border: 1px solid #000000; width: 30%;">{{ $employee->employee_number }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; width: 20%;">AGENCY EMPLOYEE NO.</td>
                <td colspan="3" style="border: 1px solid #000000; width: 30%;">{{ $employee->agency_employee_no }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">SURNAME</td>
                <td colspan="7" style="border: 1px solid #000000; font-weight: bold;">{{ $employee->last_name }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">FIRST NAME</td>
                <td colspan="4" style="border: 1px solid #000000; font-weight: bold;">{{ $employee->first_name }}</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; text-align: center;">NAME EXTENSION (JR., SR)</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold;">{{ $employee->name_extension ?? $employee->suffix ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">MIDDLE NAME</td>
                <td colspan="7" style="border: 1px solid #000000; font-weight: bold;">{{ $employee->middle_name }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">DATE OF BIRTH</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->birth_date ? \Carbon\Carbon::parse($employee->birth_date)->format('m/d/Y') : '' }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">CITIZENSHIP</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->citizenship }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">PLACE OF BIRTH</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->place_of_birth }}</td>
                <td rowspan="3" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; vertical-align: top;">RESIDENTIAL ADDRESS</td>
                <td colspan="3" rowspan="3" style="border: 1px solid #000000; vertical-align: top;">
                    {{ collect($res)->filter()->implode(', ') }}
                </td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">SEX</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->gender ?? $employee->sex }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">CIVIL STATUS</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->civil_status }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">HEIGHT (m)</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->height }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">RESIDENTIAL ZIP</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $res['zip_code'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">WEIGHT (kg)</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->weight }}</td>
                <td rowspan="3" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; vertical-align: top;">PERMANENT ADDRESS</td>
                <td colspan="3" rowspan="3" style="border: 1px solid #000000; vertical-align: top;">
                    {{ collect($perm)->filter()->implode(', ') }}
                </td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">BLOOD TYPE</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->blood_type }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">GSIS ID NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->gsis_number }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">PAG-IBIG ID NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->pagibig_number }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">PERMANENT ZIP</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $perm['zip_code'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">PHILHEALTH NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->philhealth_number }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">TELEPHONE NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->telephone_no }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">SSS NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->sss_number }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">MOBILE NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->mobile_no ?? $employee->contact_number }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">TIN NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->tin_number }}</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">E-MAIL ADDRESS</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $employee->email }}</td>
            </tr>
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- II. FAMILY BACKGROUND --}}
        <table>
            <tr>
                <td colspan="4" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    II. FAMILY BACKGROUND
                </td>
                <td colspan="4" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000; text-align: center;">
                    NAME OF CHILDREN
                </td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; width: 20%;">SPOUSE'S SURNAME</td>
                <td colspan="3" style="border: 1px solid #000000; width: 30%;">{{ $family->spouse_surname ?? '' }}</td>
                <td colspan="3" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; text-align: center; width: 35%;">FULL NAME</td>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; text-align: center; width: 15%;">DATE OF BIRTH</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">SPOUSE'S FIRST NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->spouse_first_name ?? '' }} {{ isset($family->spouse_name_extension) && $family->spouse_name_extension ? '('.$family->spouse_name_extension.')' : '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[0]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[0]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">SPOUSE'S MIDDLE NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->spouse_middle_name ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[1]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[1]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">OCCUPATION</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->spouse_occupation ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[2]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[2]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">EMPLOYER/BUSINESS NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->spouse_employer ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[3]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[3]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">BUSINESS ADDRESS</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->spouse_business_address ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[4]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[4]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">TELEPHONE NO.</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->spouse_telephone ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[5]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[5]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">FATHER'S SURNAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->father_surname ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[6]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[6]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">FATHER'S FIRST NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->father_first_name ?? '' }} {{ isset($family->father_name_extension) && $family->father_name_extension ? '('.$family->father_name_extension.')' : '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[7]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[7]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">FATHER'S MIDDLE NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->father_middle_name ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[8]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[8]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">MOTHER'S MAIDEN SURNAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->mother_surname ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[9]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[9]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">MOTHER'S FIRST NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->mother_first_name ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[10]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[10]['birth_date'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">MOTHER'S MIDDLE NAME</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $family->mother_middle_name ?? '' }}</td>
                <td colspan="3" style="border: 1px solid #000000;">{{ $children[11]['name'] ?? '' }}</td>
                <td style="border: 1px solid #000000; text-align: center;">{{ $children[11]['birth_date'] ?? '' }}</td>
            </tr>
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- III. EDUCATIONAL BACKGROUND --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    III. EDUCATIONAL BACKGROUND
                </td>
            </tr>
            <tr>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 15%;">LEVEL</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 25%;">NAME OF SCHOOL</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 25%;">BASIC EDUCATION/DEGREE/COURSE</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">PERIOD (FROM-TO)</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">YEAR GRADUATED</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 15%;">HONORS RECEIVED</td>
            </tr>
            @foreach($education as $edu)
                <tr>
                    <td style="border: 1px solid #000000; font-weight: bold;">{{ $edu['level'] }}</td>
                    <td colspan="2" style="border: 1px solid #000000;">{{ $edu['school_name'] }}</td>
                    <td colspan="2" style="border: 1px solid #000000;">{{ $edu['degree_course'] }}</td>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $edu['period_from'] }} - {{ $edu['period_to'] }}</td>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $edu['year_graduated'] }}</td>
                    <td style="border: 1px solid #000000;">{{ $edu['honors'] }}</td>
                </tr>
            @endforeach
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- IV. CIVIL SERVICE ELIGIBILITY --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    IV. CIVIL SERVICE ELIGIBILITY
                </td>
            </tr>
            <tr>
                <td colspan="3" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 35%;">CAREER SERVICE / BOARD / BAR / SPECIAL LAWS</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">RATING</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 15%;">DATE OF EXAM</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 25%;">PLACE OF EXAM</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 15%;">LICENSE NO. / VALIDITY</td>
            </tr>
            @foreach($eligibilities as $el)
                @if(!empty($el['eligibility_name']))
                    <tr>
                        <td colspan="3" style="border: 1px solid #000000;">{{ $el['eligibility_name'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $el['rating'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $el['exam_date'] }}</td>
                        <td colspan="2" style="border: 1px solid #000000;">{{ $el['exam_place'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $el['license_number'] }} {{ $el['validity_date'] ? '('.$el['validity_date'].')' : '' }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- V. WORK EXPERIENCE --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    V. WORK EXPERIENCE
                </td>
            </tr>
            <tr>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 20%;">INCLUSIVE DATES (FROM - TO)</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 30%;">POSITION TITLE</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 30%;">DEPARTMENT / AGENCY / OFFICE / COMPANY</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">SALARY</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">STATUS / GOVT</td>
            </tr>
            @foreach($workExperiences as $work)
                @if(!empty($work['position']))
                    <tr>
                        <td colspan="2" style="border: 1px solid #000000; text-align: center;">{{ $work['from'] }} - {{ $work['to'] }}</td>
                        <td colspan="2" style="border: 1px solid #000000;">{{ $work['position'] }}</td>
                        <td colspan="2" style="border: 1px solid #000000;">{{ $work['department'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $work['monthly_salary'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $work['appointment_status'] }} ({{ $work['is_government_service'] }})</td>
                    </tr>
                @endif
            @endforeach
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- VI. VOLUNTARY WORK --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    VI. VOLUNTARY WORK OR INVOLVEMENT IN CIVIC / NON-GOVERNMENT / PEOPLE / VOLUNTARY ORGANIZATIONS
                </td>
            </tr>
            <tr>
                <td colspan="3" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 40%;">NAME &amp; ADDRESS OF ORGANIZATION</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 20%;">INCLUSIVE DATES (FROM - TO)</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">HOURS</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 30%;">POSITION / NATURE OF WORK</td>
            </tr>
            @foreach($voluntaryWork as $vol)
                @if(!empty($vol['organization']))
                    <tr>
                        <td colspan="3" style="border: 1px solid #000000;">{{ $vol['organization'] }}</td>
                        <td colspan="2" style="border: 1px solid #000000; text-align: center;">{{ $vol['from'] }} - {{ $vol['to'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $vol['hours'] }}</td>
                        <td colspan="2" style="border: 1px solid #000000;">{{ $vol['position'] }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- VII. LEARNING AND DEVELOPMENT --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    VII. LEARNING AND DEVELOPMENT (L&amp;D) INTERVENTIONS / TRAINING PROGRAMS ATTENDED
                </td>
            </tr>
            <tr>
                <td colspan="3" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 40%;">TITLE OF LEARNING AND DEVELOPMENT INTERVENTIONS</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 20%;">INCLUSIVE DATES (FROM - TO)</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">HOURS</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">TYPE</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 20%;">CONDUCTED / SPONSORED BY</td>
            </tr>
            @foreach($trainings as $tr)
                @if(!empty($tr['title']))
                    <tr>
                        <td colspan="3" style="border: 1px solid #000000;">{{ $tr['title'] }}</td>
                        <td colspan="2" style="border: 1px solid #000000; text-align: center;">{{ $tr['from'] }} - {{ $tr['to'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $tr['hours'] }}</td>
                        <td style="border: 1px solid #000000; text-align: center;">{{ $tr['type'] }}</td>
                        <td style="border: 1px solid #000000;">{{ $tr['conducted_by'] }}</td>
                    </tr>
                @endif
            @endforeach
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- VIII. OTHER INFORMATION --}}
        <table>
            <tr>
                <td colspan="8" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    VIII. OTHER INFORMATION
                </td>
            </tr>
            <tr>
                <td colspan="3" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 33%;">SPECIAL SKILLS / HOBBIES</td>
                <td colspan="3" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 33%;">NON-ACADEMIC DISTINCTIONS / RECOGNITION</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 34%;">MEMBERSHIP IN ASSOCIATIONS</td>
            </tr>
            <tr>
                <td colspan="3" style="border: 1px solid #000000; vertical-align: top; height: 40px;">{{ $otherInfo['skills'] }}</td>
                <td colspan="3" style="border: 1px solid #000000; vertical-align: top;">{{ $otherInfo['distinctions'] }}</td>
                <td colspan="2" style="border: 1px solid #000000; vertical-align: top;">{{ $otherInfo['memberships'] }}</td>
            </tr>
        </table>

        {{-- Spacer --}}
        <table><tr><td colspan="8" style="height: 10px;"></td></tr></table>

        {{-- REFERENCES & QUESTIONNAIRE SUMMARY --}}
        <table>
            <tr>
                <td colspan="4" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    REFERENCES
                </td>
                <td colspan="4" style="font-weight: bold; font-size: 12px; background-color: #DDEBF7; color: #000000; height: 25px; vertical-align: middle; border: 1px solid #000000;">
                    GOVERNMENT ISSUED ID
                </td>
            </tr>
            <tr>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 25%;">NAME</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 15%;">ADDRESS</td>
                <td style="background-color: #F2F2F2; font-weight: bold; text-align: center; border: 1px solid #000000; width: 10%;">TEL. NO.</td>
                <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000; width: 25%;">ID TYPE</td>
                <td colspan="2" style="border: 1px solid #000000; width: 25%;">{{ $employee->gov_id_type }}</td>
            </tr>
            @foreach($referencesData as $indexRef => $ref)
                <tr>
                    <td colspan="2" style="border: 1px solid #000000;">{{ $ref['name'] }}</td>
                    <td style="border: 1px solid #000000;">{{ $ref['address'] }}</td>
                    <td style="border: 1px solid #000000; text-align: center;">{{ $ref['telephone'] }}</td>
                    @if($indexRef === 0)
                        <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">ID NUMBER</td>
                        <td colspan="2" style="border: 1px solid #000000;">{{ $employee->gov_id_number }}</td>
                    @elseif($indexRef === 1)
                        <td colspan="2" style="background-color: #F2F2F2; font-weight: bold; border: 1px solid #000000;">DATE/PLACE ISSUED</td>
                        <td colspan="2" style="border: 1px solid #000000;">{{ $employee->gov_id_date_issued ? \Carbon\Carbon::parse($employee->gov_id_date_issued)->format('m/d/Y') : '' }} {{ $employee->gov_id_place_issued }}</td>
                    @else
                        <td colspan="4" style="border: 1px solid #000000;"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endforeach
</body>
</html>
