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

    protected $fillable = [
        'employee_id',
        'questions_answers',
        'question_details',
    ];

    protected $casts = [
        'questions_answers' => 'array',
        'question_details' => 'array',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function getQuestionAnswer($questionCode): ?bool
    {
        return $this->questions_answers[$questionCode] ?? null;
    }

    public function getQuestionDetail($questionCode): ?string
    {
        return $this->question_details[$questionCode] ?? null;
    }

    public function setQuestionAnswer($questionCode, $answer, $details = null): void
    {
        $answers = $this->questions_answers ?? [];
        $answers[$questionCode] = $answer;
        $this->questions_answers = $answers;

        if ($details && $answer === true) {
            $questionDetails = $this->question_details ?? [];
            $questionDetails[$questionCode] = $details;
            $this->question_details = $questionDetails;
        }
    }

    public function getHasYesAnswersAttribute(): bool
    {
        return collect($this->questions_answers)->contains(true);
    }

    public static function getQuestionLabels(): array
    {
        return [
            'q34a' => 'Have you ever been found guilty of any administrative offense?',
            'q34b' => 'Have you been criminally charged before any court in the Philippines?',
            'q35a' => 'Have you ever been convicted of any crime or violation of any law, decree, ordinance or regulation by any court or tribunal?',
            'q35b' => 'Have you ever been separated from the service in any of the following modes: resignation, retirement, dropped from the rolls, dismissal, termination, end of term, finished contract or phased out in the public or private sector?',
            'q36' => 'Have you ever been a candidate in a national or local election held within the last year (except Barangay election)?',
            'q37' => 'Have you resigned from the government service during the three (3)-month period before the last election to promote/actively campaign for a national or local candidate?',
            'q38a' => 'Have you acquired the status of an immigrant or permanent resident of another country?',
            'q38b' => 'Are you a member of any indigenous group?',
            'q38c' => 'Are you a solo parent?',
            'q40a' => 'Are you related by consanguinity or affinity to the appointing or recommending authority, or to the chief of bureau or office or to the person who has immediate supervision over you in the Office, Bureau or Department where you will be appointed, within the third degree?',
            'q40b' => 'Have you been previously employed in this office/department?',
        ];
    }
}