<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeQuestionnaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'employee_questionnaire';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        // Original JSON fields
        'questions_answers',
        'question_details',
        // CSC Form 212 - Field 34: Relationship to appointing authority
        'field_34_yes_no',
        'field_34_relationship_details',
        'field_34b_yes_no',
        'field_34b_relationship_details',
        // CSC Form 212 - Field 35: Administrative/criminal charges
        'field_35a_yes_no',
        'field_35_administrative_offense_details',
        'field_35b_yes_no',
        'field_36_criminal_charge_details',
        // CSC Form 212 - Field 36: Conviction of any crime
        'field_36_yes_no',
        'field_36_conviction_details',
        // CSC Form 212 - Field 37: Separation from service
        'field_37_yes_no',
        'field_37_separation_details',
        // CSC Form 212 - Field 38: Election candidacy and resignation
        'field_38a_yes_no',
        'field_38b_yes_no',
        'field_36_candidate_details',
        'field_37_resignation_details',
        // CSC Form 212 - Field 38: Immigrant status
        'field_39_yes_no',
        'field_38_immigrant_status',
        'field_39_immigrant_details',
        // CSC Form 212 - Field 39: Dual citizenship
        'field_39_dual_citizenship_details',
        // CSC Form 212 - Field 40: Indigenous/PWD/Solo Parent status
        'field_40a_yes_no',
        'field_40b_yes_no',
        'field_40c_yes_no',
        'field_40_indigenous_details',
        'field_40_pwd_details',
        'field_40_solo_parent_details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'questions_answers' => 'array',
        'question_details' => 'array',
    ];

    /**
     * Get the employee that owns the questionnaire.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the full text labels for each question, matching the CSC Form.
     */
    public static function getCscFieldLabels(): array
    {
        return [
            'q34a' => '34. Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed,',
            'q34b' => 'a. within the third degree?',
            'q34c' => 'b. within the fourth degree (for Local Government Unit - Career Employees)?',
            'q35a' => '35. a. Have you ever been found guilty of any administrative offense?',
            'q35b' => '35. b. Have you been criminally charged before any court?',
            'q36'  => '36. Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?',
            'q37'  => '37. Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out (abolition) in the public or private sector?',
            'q38a' => '38. a. Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?',
            'q38b' => '38. b. Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?',
            'q39'  => '39. Have you acquired the status of an immigrant or permanent resident of another country?',
            'q40a' => "40. a. Are you a member of any indigenous group?",
            'q40b' => "40. b. Are you a person with disability?",
            'q40c' => "40. c. Are you a solo parent?",
        ];
    }

    /**
     * Get answer for a specific question with backward compatibility
     */
    public function getQuestionAnswer(string $question): mixed
    {
        // Map question codes to database fields
        $fieldMap = [
            'q34a' => 'field_34_yes_no',
            'q34b' => 'field_34b_yes_no',
            'q34c' => 'field_34b_yes_no',
            'q35a' => 'field_35a_yes_no',
            'q35b' => 'field_35b_yes_no',
            'q36' => 'field_36_yes_no',
            'q37' => 'field_37_yes_no',
            'q38a' => 'field_38a_yes_no',
            'q38b' => 'field_38b_yes_no',
            'q39' => 'field_39_yes_no',
            'q40a' => 'field_40a_yes_no',
            'q40b' => 'field_40b_yes_no',
            'q40c' => 'field_40c_yes_no',
        ];

        $fieldName = $fieldMap[$question] ?? null;

        // First try to get from individual field
        if ($fieldName && isset($this->{$fieldName})) {
            return $this->{$fieldName};
        }

        // Fallback to JSON structure for backward compatibility
        return $this->questions_answers[$question] ?? null;
    }

    /**
     * Get detail for a specific question with backward compatibility
     */
    public function getQuestionDetail(string $question): ?string
    {
        // Map question codes to detail fields
        $detailMap = [
            'q34a' => 'field_34_relationship_details',
            'q34b' => 'field_34b_relationship_details',
            'q34c' => 'field_34b_relationship_details',
            'q35a' => 'field_35_administrative_offense_details',
            'q35b' => 'field_36_criminal_charge_details',
            'q36' => 'field_36_conviction_details',
            'q37' => 'field_37_separation_details',
            'q38a' => 'field_36_candidate_details',
            'q38b' => 'field_37_resignation_details',
            'q39' => 'field_39_immigrant_details',
            'q40a' => 'field_40_indigenous_details',
            'q40b' => 'field_40_pwd_details',
            'q40c' => 'field_40_solo_parent_details',
        ];

        $detailField = $detailMap[$question] ?? null;

        // First try to get from individual detail field
        if ($detailField && isset($this->{$detailField})) {
            return $this->{$detailField};
        }

        // Fallback to JSON structure for backward compatibility
        return $this->question_details[$question] ?? null;
    }

    /**
     * Set answer and detail for a specific question (unified data management)
     */
    public function setQuestionAnswer(string $question, mixed $answer, ?string $detail = null): void
    {
        // Map question codes to database fields
        $fieldMap = [
            'q34a' => 'field_34_yes_no',
            'q34b' => 'field_34b_yes_no',
            'q34c' => 'field_34b_yes_no',
            'q35a' => 'field_35a_yes_no',
            'q35b' => 'field_35b_yes_no',
            'q36' => 'field_36_yes_no',
            'q37' => 'field_37_yes_no',
            'q38a' => 'field_38a_yes_no',
            'q38b' => 'field_38b_yes_no',
            'q39' => 'field_39_yes_no',
            'q40a' => 'field_40a_yes_no',
            'q40b' => 'field_40b_yes_no',
            'q40c' => 'field_40c_yes_no',
        ];

        $detailMap = [
            'q34a' => 'field_34_relationship_details',
            'q34b' => 'field_34b_relationship_details',
            'q34c' => 'field_34b_relationship_details',
            'q35a' => 'field_35_administrative_offense_details',
            'q35b' => 'field_36_criminal_charge_details',
            'q36' => 'field_36_conviction_details',
            'q37' => 'field_37_separation_details',
            'q38a' => 'field_36_candidate_details',
            'q38b' => 'field_37_resignation_details',
            'q39' => 'field_39_immigrant_details',
            'q40a' => 'field_40_indigenous_details',
            'q40b' => 'field_40_pwd_details',
            'q40c' => 'field_40_solo_parent_details',
        ];

        // Set individual fields
        if (isset($fieldMap[$question])) {
            $this->{$fieldMap[$question]} = $answer;
        }

        if ($detail !== null && isset($detailMap[$question])) {
            $this->{$detailMap[$question]} = $detail;
        }

        // Also update JSON for backward compatibility
        $answers = $this->questions_answers ?? [];
        $details = $this->question_details ?? [];

        $answers[$question] = $answer;
        if ($detail !== null) {
            $details[$question] = $detail;
        }

        $this->questions_answers = $answers;
        $this->question_details = $details;
    }

    /**
     * Calculate completion percentage for the questionnaire
     */
    public function getCompletionPercentage(): float
    {
        $requiredQuestions = ['q34a', 'q34b', 'q35a', 'q35b', 'q36', 'q37', 'q38a', 'q38b', 'q39', 'q40a', 'q40b', 'q40c'];
        $answeredQuestions = 0;

        foreach ($requiredQuestions as $question) {
            $answer = $this->getQuestionAnswer($question);
            if ($answer !== null && $answer !== '') {
                $answeredQuestions++;
            }
        }

        return ($answeredQuestions / count($requiredQuestions)) * 100;
    }

    /**
     * Check if questionnaire is complete
     */
    public function isComplete(): bool
    {
        return $this->getCompletionPercentage() >= 100;
    }

    /**
     * Get missing required questions
     */
    public function getMissingQuestions(): array
    {
        $requiredQuestions = ['q34a', 'q34b', 'q35a', 'q35b', 'q36', 'q37', 'q38a', 'q38b', 'q39', 'q40a', 'q40b', 'q40c'];
        $missing = [];

        foreach ($requiredQuestions as $question) {
            $answer = $this->getQuestionAnswer($question);
            if ($answer === null || $answer === '') {
                $missing[] = $question;
            }
        }

        return $missing;
    }
}
