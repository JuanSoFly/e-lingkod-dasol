<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\PerformanceEvaluation;
use App\Models\Training;
use App\Models\Report;
use App\Models\GovernmentBenefit;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HRAnalyticsService
{
    /**
     * Get comprehensive workforce analytics
     */
    public function getWorkforceAnalytics()
    {
        return Cache::remember('hr_analytics_workforce', 300, function () {
            return [
                'employee_demographics' => $this->getEmployeeDemographics(),
                'department_distribution' => $this->getDepartmentDistribution(),
                'employment_status_breakdown' => $this->getEmploymentStatusBreakdown(),
                'age_distribution' => $this->getAgeDistribution(),
                'tenure_analysis' => $this->getTenureAnalysis(),
                'gender_distribution' => $this->getGenderDistribution(),
                'education_levels' => $this->getEducationLevels(),
                'position_analysis' => $this->getPositionAnalysis()
            ];
        });
    }

    /**
     * Get turnover analytics and predictions
     */
    public function getTurnoverAnalytics()
    {
        return Cache::remember('hr_analytics_turnover', 300, function () {
            return [
                'monthly_turnover_rate' => $this->getMonthlyTurnoverRate(),
                'annual_turnover_rate' => $this->getAnnualTurnoverRate(),
                'turnover_by_department' => $this->getTurnoverByDepartment(),
                'turnover_predictions' => $this->getPredictedTurnover(),
                'retention_rate' => $this->getRetentionRate(),
                'exit_reasons' => $this->getExitReasons(),
                'high_risk_employees' => $this->getHighRiskEmployees(),
                'turnover_cost_analysis' => $this->getTurnoverCostAnalysis()
            ];
        });
    }

    /**
     * Get performance analytics
     */
    public function getPerformanceAnalytics()
    {
        return Cache::remember('hr_analytics_performance', 300, function () {
            return [
                'performance_distribution' => $this->getPerformanceDistribution(),
                'performance_trends' => $this->getPerformanceTrends(),
                'top_performers' => $this->getTopPerformers(),
                'improvement_needed' => $this->getEmployeesNeedingImprovement(),
                'goal_achievement_rates' => $this->getGoalAchievementRates(),
                'performance_by_department' => $this->getPerformanceByDepartment(),
                'performance_correlation' => $this->getPerformanceCorrelation(),
                'promotion_readiness' => $this->getPromotionReadiness()
            ];
        });
    }

    /**
     * Get training effectiveness analytics
     */
    public function getTrainingAnalytics()
    {
        return Cache::remember('hr_analytics_training', 300, function () {
            return [
                'training_completion_rates' => $this->getTrainingCompletionRates(),
                'training_effectiveness' => $this->getTrainingEffectiveness(),
                'skill_gap_analysis' => $this->getSkillGapAnalysis(),
                'training_cost_analysis' => $this->getTrainingCostAnalysis(),
                'mandatory_training_compliance' => $this->getMandatoryTrainingCompliance(),
                'training_needs_prediction' => $this->getTrainingNeedsPrediction(),
                'roi_analysis' => $this->getTrainingROI(),
                'popular_training_programs' => $this->getPopularTrainingPrograms()
            ];
        });
    }

    /**
     * Get compliance monitoring analytics
     */
    public function getComplianceAnalytics()
    {
        return Cache::remember('hr_analytics_compliance', 300, function () {
            return [
                'csc_compliance_rates' => $this->getCSCComplianceRates(),
                'document_compliance' => $this->getDocumentCompliance(),
                'benefits_enrollment_compliance' => $this->getBenefitsEnrollmentCompliance(),
                'training_compliance' => $this->getTrainingCompliance(),
                'policy_adherence' => $this->getPolicyAdherence(),
                'audit_readiness' => $this->getAuditReadiness(),
                'compliance_trends' => $this->getComplianceTrends(),
                'non_compliance_risks' => $this->getNonComplianceRisks()
            ];
        });
    }

    /**
     * Get workforce planning analytics
     */
    public function getWorkforcePlanningAnalytics()
    {
        return Cache::remember('hr_analytics_workforce_planning', 300, function () {
            return [
                'succession_planning' => $this->getSuccessionPlanningData(),
                'retirement_forecasts' => $this->getRetirementForecasts(),
                'skill_inventory' => $this->getSkillInventory(),
                'capacity_analysis' => $this->getCapacityAnalysis(),
                'recruitment_needs' => $this->getRecruitmentNeeds(),
                'organizational_structure' => $this->getOrganizationalStructure(),
                'leadership_pipeline' => $this->getLeadershipPipeline(),
                'critical_positions' => $this->getCriticalPositions()
            ];
        });
    }

    /**
     * Get HR cost analysis
     */
    public function getCostAnalytics()
    {
        return Cache::remember('hr_analytics_cost', 300, function () {
            return [
                'total_hr_costs' => $this->getTotalHRCosts(),
                'cost_per_employee' => $this->getCostPerEmployee(),
                'training_costs' => $this->getTrainingCosts(),
                'recruitment_costs' => $this->getRecruitmentCosts(),
                'benefit_costs' => $this->getBenefitCosts(),
                'turnover_costs' => $this->getTurnoverCosts(),
                'productivity_metrics' => $this->getProductivityMetrics(),
                'cost_trends' => $this->getCostTrends()
            ];
        });
    }

    /**
     * Get predictive analytics dashboard
     */
    public function getPredictiveAnalytics()
    {
        return Cache::remember('hr_analytics_predictive', 600, function () {
            return [
                'turnover_predictions' => $this->getPredictedTurnover(),
                'performance_forecasts' => $this->getPerformanceForecasts(),
                'training_needs_prediction' => $this->getTrainingNeedsPrediction(),
                'succession_risks' => $this->getSuccessionRisks(),
                'budget_forecasts' => $this->getBudgetForecasts(),
                'workforce_demand' => $this->getWorkforceDemandForecast(),
                'skill_shortage_prediction' => $this->getSkillShortagePrediction(),
                'compliance_risk_prediction' => $this->getComplianceRiskPrediction()
            ];
        });
    }

    // Employee Demographics Methods
    private function getEmployeeDemographics()
    {
        return [
            'total_employees' => Employee::where('employment_status', 'active')->count(),
            'new_hires_this_month' => Employee::whereMonth('date_hired', Carbon::now()->month)
                ->whereYear('date_hired', Carbon::now()->year)->count(),
            'average_age' => Employee::where('employment_status', 'active')
                ->whereNotNull('birth_date')
                ->avg(DB::raw('YEAR(CURDATE()) - YEAR(birth_date)')),
            'average_tenure' => Employee::where('employment_status', 'active')
                ->whereNotNull('date_hired')
                ->avg(DB::raw('DATEDIFF(CURDATE(), date_hired) / 365.25'))
        ];
    }

    private function getDepartmentDistribution()
    {
        return Employee::where('employment_status', 'active')
            ->select('department', DB::raw('COUNT(*) as count'))
            ->groupBy('department')
            ->orderBy('count', 'desc')
            ->get();
    }

    private function getEmploymentStatusBreakdown()
    {
        return Employee::select('employment_status', DB::raw('COUNT(*) as count'))
            ->groupBy('employment_status')
            ->get();
    }

    private function getAgeDistribution()
    {
        return Employee::where('employment_status', 'active')
            ->whereNotNull('birth_date')
            ->select(
                DB::raw('CASE 
                    WHEN YEAR(CURDATE()) - YEAR(birth_date) < 25 THEN "Under 25"
                    WHEN YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 25 AND 34 THEN "25-34"
                    WHEN YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 35 AND 44 THEN "35-44"
                    WHEN YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 45 AND 54 THEN "45-54"
                    WHEN YEAR(CURDATE()) - YEAR(birth_date) BETWEEN 55 AND 64 THEN "55-64"
                    ELSE "65+"
                END as age_group'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('age_group')
            ->get();
    }

    private function getTenureAnalysis()
    {
        return Employee::where('employment_status', 'active')
            ->whereNotNull('date_hired')
            ->select(
                DB::raw('CASE 
                    WHEN DATEDIFF(CURDATE(), date_hired) / 365.25 < 1 THEN "Less than 1 year"
                    WHEN DATEDIFF(CURDATE(), date_hired) / 365.25 BETWEEN 1 AND 3 THEN "1-3 years"
                    WHEN DATEDIFF(CURDATE(), date_hired) / 365.25 BETWEEN 3 AND 5 THEN "3-5 years"
                    WHEN DATEDIFF(CURDATE(), date_hired) / 365.25 BETWEEN 5 AND 10 THEN "5-10 years"
                    WHEN DATEDIFF(CURDATE(), date_hired) / 365.25 BETWEEN 10 AND 20 THEN "10-20 years"
                    ELSE "20+ years"
                END as tenure_group'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('tenure_group')
            ->get();
    }

    private function getGenderDistribution()
    {
        return Employee::where('employment_status', 'active')
            ->select('gender', DB::raw('COUNT(*) as count'))
            ->groupBy('gender')
            ->get();
    }

    private function getEducationLevels()
    {
        return Employee::where('employment_status', 'active')
            ->select('education_level', DB::raw('COUNT(*) as count'))
            ->groupBy('education_level')
            ->orderBy('count', 'desc')
            ->get();
    }

    private function getPositionAnalysis()
    {
        return Employee::where('employment_status', 'active')
            ->select('position', DB::raw('COUNT(*) as count'))
            ->groupBy('position')
            ->orderBy('count', 'desc')
            ->limit(20)
            ->get();
    }

    // Turnover Analytics Methods
    private function getMonthlyTurnoverRate()
    {
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $totalEmployees = Employee::whereDate('date_hired', '<=', $date->endOfMonth())
                ->where(function($query) use ($date) {
                    $query->whereNull('termination_date')
                        ->orWhereDate('termination_date', '>', $date->endOfMonth());
                })->count();
            
            $separations = Employee::whereYear('termination_date', $date->year)
                ->whereMonth('termination_date', $date->month)
                ->count();
            
            $turnoverRate = $totalEmployees > 0 ? ($separations / $totalEmployees) * 100 : 0;
            
            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'turnover_rate' => round($turnoverRate, 2),
                'separations' => $separations,
                'total_employees' => $totalEmployees
            ];
        }
        
        return $monthlyData;
    }

    private function getAnnualTurnoverRate()
    {
        $currentYear = Carbon::now()->year;
        $separations = Employee::whereYear('termination_date', $currentYear)->count();
        $averageEmployees = Employee::where(function($query) use ($currentYear) {
            $query->whereYear('date_hired', '<=', $currentYear)
                ->where(function($subQuery) use ($currentYear) {
                    $subQuery->whereNull('termination_date')
                        ->orWhereYear('termination_date', '>=', $currentYear);
                });
        })->count();
        
        return $averageEmployees > 0 ? ($separations / $averageEmployees) * 100 : 0;
    }

    private function getTurnoverByDepartment()
    {
        return Employee::select('department')
            ->selectRaw('COUNT(*) as total_employees')
            ->selectRaw('SUM(CASE WHEN termination_date IS NOT NULL AND YEAR(termination_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) as separations')
            ->selectRaw('(SUM(CASE WHEN termination_date IS NOT NULL AND YEAR(termination_date) = YEAR(CURDATE()) THEN 1 ELSE 0 END) / COUNT(*)) * 100 as turnover_rate')
            ->groupBy('department')
            ->having('total_employees', '>', 0)
            ->orderBy('turnover_rate', 'desc')
            ->get();
    }

    private function getPredictedTurnover()
    {
        // Simple prediction based on historical patterns and risk factors
        $employees = Employee::where('employment_status', 'active')
            ->with(['performanceEvaluations', 'leaveApplications'])
            ->get();
        
        $predictions = [];
        foreach ($employees as $employee) {
            $riskScore = 0;
            
            // Tenure risk (U-shaped curve)
            $tenure = $employee->tenure_years ?? 0;
            if ($tenure < 1 || $tenure > 20) $riskScore += 30;
            elseif ($tenure < 2 || $tenure > 15) $riskScore += 20;
            elseif ($tenure < 3 || $tenure > 10) $riskScore += 10;
            
            // Performance risk
            $lastEvaluation = $employee->performanceEvaluations()->latest()->first();
            if ($lastEvaluation && $lastEvaluation->overall_rating < 3) $riskScore += 25;
            
            // Leave pattern risk
            $excessiveLeaves = $employee->leaveApplications()
                ->where('start_date', '>=', Carbon::now()->subYear())
                ->where('status', 'approved')
                ->sum(DB::raw('DATEDIFF(end_date, start_date) + 1'));
            
            if ($excessiveLeaves > 30) $riskScore += 20;
            
            // Age risk
            $age = $employee->age ?? 0;
            if ($age > 60) $riskScore += 15;
            if ($age < 25) $riskScore += 10;
            
            $predictions[] = [
                'employee_id' => $employee->id,
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'department' => $employee->department,
                'risk_score' => min($riskScore, 100),
                'risk_level' => $riskScore >= 70 ? 'High' : ($riskScore >= 40 ? 'Medium' : 'Low')
            ];
        }
        
        return collect($predictions)->sortByDesc('risk_score')->take(20)->values();
    }

    private function getRetentionRate()
    {
        $currentYear = Carbon::now()->year;
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        $retained = Employee::where('employment_status', 'active')
            ->where('date_hired', '<', Carbon::now()->subYear())
            ->count();
        
        return $totalEmployees > 0 ? ($retained / $totalEmployees) * 100 : 0;
    }

    private function getExitReasons()
    {
        return Employee::whereNotNull('termination_date')
            ->whereYear('termination_date', Carbon::now()->year)
            ->select('termination_reason', DB::raw('COUNT(*) as count'))
            ->groupBy('termination_reason')
            ->orderBy('count', 'desc')
            ->get();
    }

    private function getHighRiskEmployees()
    {
        $predictions = $this->getPredictedTurnover();
        return $predictions->where('risk_level', 'High');
    }

    private function getTurnoverCostAnalysis()
    {
        $separations = Employee::whereYear('termination_date', Carbon::now()->year)->count();
        $averageSalary = Employee::where('employment_status', 'active')->avg('salary') ?? 50000;
        
        // Estimate turnover cost as 1.5x annual salary
        $costPerTurnover = $averageSalary * 1.5;
        $totalTurnoverCost = $separations * $costPerTurnover;
        
        return [
            'separations' => $separations,
            'cost_per_turnover' => $costPerTurnover,
            'total_turnover_cost' => $totalTurnoverCost,
            'average_salary' => $averageSalary
        ];
    }

    // Performance Analytics Methods
    private function getPerformanceDistribution()
    {
        return PerformanceEvaluation::select(
                DB::raw('CASE 
                    WHEN overall_rating >= 4.5 THEN "Outstanding"
                    WHEN overall_rating >= 4.0 THEN "Exceeds Expectations"
                    WHEN overall_rating >= 3.0 THEN "Meets Expectations"
                    WHEN overall_rating >= 2.0 THEN "Below Expectations"
                    ELSE "Unsatisfactory"
                END as performance_category'),
                DB::raw('COUNT(*) as count')
            )
            ->whereYear('evaluation_date', Carbon::now()->year)
            ->groupBy('performance_category')
            ->get();
    }

    private function getPerformanceTrends()
    {
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $year = Carbon::now()->subYears($i)->year;
            $avgRating = PerformanceEvaluation::whereYear('evaluation_date', $year)
                ->avg('overall_rating');
            
            $trends[] = [
                'year' => $year,
                'average_rating' => round($avgRating ?? 0, 2)
            ];
        }
        
        return $trends;
    }

    private function getTopPerformers()
    {
        return PerformanceEvaluation::with('employee')
            ->whereYear('evaluation_date', Carbon::now()->year)
            ->where('overall_rating', '>=', 4.5)
            ->orderBy('overall_rating', 'desc')
            ->limit(10)
            ->get();
    }

    private function getEmployeesNeedingImprovement()
    {
        return PerformanceEvaluation::with('employee')
            ->whereYear('evaluation_date', Carbon::now()->year)
            ->where('overall_rating', '<', 3.0)
            ->orderBy('overall_rating', 'asc')
            ->limit(10)
            ->get();
    }

    private function getGoalAchievementRates()
    {
        return PerformanceEvaluation::select(
                DB::raw('AVG(goal_achievement_percentage) as avg_achievement'),
                DB::raw('COUNT(*) as total_evaluations'),
                DB::raw('SUM(CASE WHEN goal_achievement_percentage >= 100 THEN 1 ELSE 0 END) as goals_met'),
                DB::raw('SUM(CASE WHEN goal_achievement_percentage >= 80 THEN 1 ELSE 0 END) as goals_mostly_met')
            )
            ->whereYear('evaluation_date', Carbon::now()->year)
            ->first();
    }

    private function getPerformanceByDepartment()
    {
        return DB::table('performance_evaluations as pe')
            ->join('employees as e', 'pe.employee_id', '=', 'e.id')
            ->select('e.department', DB::raw('AVG(pe.overall_rating) as avg_rating'), DB::raw('COUNT(*) as evaluations'))
            ->whereYear('pe.evaluation_date', Carbon::now()->year)
            ->groupBy('e.department')
            ->orderBy('avg_rating', 'desc')
            ->get();
    }

    private function getPerformanceCorrelation()
    {
        // Correlation between performance and other factors
        return [
            'tenure_correlation' => $this->calculatePerformanceTenureCorrelation(),
            'training_correlation' => $this->calculatePerformanceTrainingCorrelation(),
            'department_variation' => $this->calculateDepartmentPerformanceVariation()
        ];
    }

    private function getPromotionReadiness()
    {
        return PerformanceEvaluation::with('employee')
            ->whereYear('evaluation_date', Carbon::now()->year)
            ->where('overall_rating', '>=', 4.0)
            ->where('promotion_readiness', true)
            ->orderBy('overall_rating', 'desc')
            ->limit(20)
            ->get();
    }

    // Helper methods for performance correlations
    private function calculatePerformanceTenureCorrelation()
    {
        // Simplified correlation calculation
        $data = DB::table('performance_evaluations as pe')
            ->join('employees as e', 'pe.employee_id', '=', 'e.id')
            ->select(
                'pe.overall_rating',
                DB::raw('DATEDIFF(CURDATE(), e.date_hired) / 365.25 as tenure')
            )
            ->whereYear('pe.evaluation_date', Carbon::now()->year)
            ->whereNotNull('e.date_hired')
            ->get();
        
        if ($data->count() < 2) return 0;
        
        $n = $data->count();
        $sumX = $data->sum('tenure');
        $sumY = $data->sum('overall_rating');
        $sumXY = $data->sum(function($item) { return $item->tenure * $item->overall_rating; });
        $sumX2 = $data->sum(function($item) { return $item->tenure * $item->tenure; });
        $sumY2 = $data->sum(function($item) { return $item->overall_rating * $item->overall_rating; });
        
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        return $denominator != 0 ? ($n * $sumXY - $sumX * $sumY) / $denominator : 0;
    }

    private function calculatePerformanceTrainingCorrelation()
    {
        // Correlation between training hours and performance
        $data = DB::table('performance_evaluations as pe')
            ->join('employees as e', 'pe.employee_id', '=', 'e.id')
            ->leftJoin('training_participants as tp', 'e.id', '=', 'tp.employee_id')
            ->leftJoin('trainings as t', 'tp.training_id', '=', 't.id')
            ->select(
                'pe.overall_rating',
                DB::raw('COALESCE(SUM(t.duration_hours), 0) as training_hours')
            )
            ->whereYear('pe.evaluation_date', Carbon::now()->year)
            ->whereYear('t.start_date', Carbon::now()->year)
            ->groupBy('pe.id', 'pe.overall_rating')
            ->get();
        
        // Simple correlation calculation similar to tenure
        if ($data->count() < 2) return 0;
        
        $n = $data->count();
        $sumX = $data->sum('training_hours');
        $sumY = $data->sum('overall_rating');
        $sumXY = $data->sum(function($item) { return $item->training_hours * $item->overall_rating; });
        $sumX2 = $data->sum(function($item) { return $item->training_hours * $item->training_hours; });
        $sumY2 = $data->sum(function($item) { return $item->overall_rating * $item->overall_rating; });
        
        $denominator = sqrt(($n * $sumX2 - $sumX * $sumX) * ($n * $sumY2 - $sumY * $sumY));
        
        return $denominator != 0 ? ($n * $sumXY - $sumX * $sumY) / $denominator : 0;
    }

    private function calculateDepartmentPerformanceVariation()
    {
        return DB::table('performance_evaluations as pe')
            ->join('employees as e', 'pe.employee_id', '=', 'e.id')
            ->select(
                'e.department',
                DB::raw('AVG(pe.overall_rating) as avg_rating'),
                DB::raw('STDDEV(pe.overall_rating) as rating_stddev'),
                DB::raw('COUNT(*) as evaluations')
            )
            ->whereYear('pe.evaluation_date', Carbon::now()->year)
            ->groupBy('e.department')
            ->having('evaluations', '>=', 3)
            ->orderBy('rating_stddev', 'desc')
            ->get();
    }

    // Training Analytics Methods
    private function getTrainingCompletionRates()
    {
        $totalTrainings = Training::whereYear('start_date', Carbon::now()->year)->count();
        $completedTrainings = DB::table('training_participants')
            ->join('trainings', 'training_participants.training_id', '=', 'trainings.id')
            ->whereYear('trainings.start_date', Carbon::now()->year)
            ->where('training_participants.completion_status', 'completed')
            ->count();
        
        return [
            'total_trainings' => $totalTrainings,
            'completed_trainings' => $completedTrainings,
            'completion_rate' => $totalTrainings > 0 ? ($completedTrainings / $totalTrainings) * 100 : 0
        ];
    }

    private function getTrainingEffectiveness()
    {
        return DB::table('training_participants as tp')
            ->join('trainings as t', 'tp.training_id', '=', 't.id')
            ->join('employees as e', 'tp.employee_id', '=', 'e.id')
            ->leftJoin('performance_evaluations as pe1', function($join) {
                $join->on('e.id', '=', 'pe1.employee_id')
                    ->whereRaw('pe1.evaluation_date < t.start_date')
                    ->whereRaw('pe1.evaluation_date = (SELECT MAX(evaluation_date) FROM performance_evaluations WHERE employee_id = e.id AND evaluation_date < t.start_date)');
            })
            ->leftJoin('performance_evaluations as pe2', function($join) {
                $join->on('e.id', '=', 'pe2.employee_id')
                    ->whereRaw('pe2.evaluation_date > t.end_date')
                    ->whereRaw('pe2.evaluation_date = (SELECT MIN(evaluation_date) FROM performance_evaluations WHERE employee_id = e.id AND evaluation_date > t.end_date)');
            })
            ->select(
                't.title',
                DB::raw('AVG(pe1.overall_rating) as avg_rating_before'),
                DB::raw('AVG(pe2.overall_rating) as avg_rating_after'),
                DB::raw('COUNT(*) as participants')
            )
            ->whereYear('t.start_date', Carbon::now()->year)
            ->where('tp.completion_status', 'completed')
            ->groupBy('t.id', 't.title')
            ->having('participants', '>=', 3)
            ->get();
    }

    private function getSkillGapAnalysis()
    {
        // Analyze required skills vs available skills
        return DB::table('employees as e')
            ->select(
                'e.department',
                'e.position',
                DB::raw('COUNT(*) as total_employees'),
                DB::raw('GROUP_CONCAT(DISTINCT e.skills) as available_skills')
            )
            ->where('e.employment_status', 'active')
            ->groupBy('e.department', 'e.position')
            ->get();
    }

    private function getTrainingCostAnalysis()
    {
        return Training::whereYear('start_date', Carbon::now()->year)
            ->select(
                DB::raw('SUM(cost) as total_cost'),
                DB::raw('AVG(cost) as average_cost'),
                DB::raw('COUNT(*) as total_trainings')
            )
            ->first();
    }

    private function getMandatoryTrainingCompliance()
    {
        $mandatoryTrainings = Training::where('is_mandatory', true)
            ->whereYear('start_date', Carbon::now()->year)
            ->get();
        
        $compliance = [];
        foreach ($mandatoryTrainings as $training) {
            $totalEmployees = Employee::where('employment_status', 'active')->count();
            $completedEmployees = DB::table('training_participants')
                ->where('training_id', $training->id)
                ->where('completion_status', 'completed')
                ->count();
            
            $compliance[] = [
                'training_title' => $training->title,
                'total_employees' => $totalEmployees,
                'completed_employees' => $completedEmployees,
                'compliance_rate' => $totalEmployees > 0 ? ($completedEmployees / $totalEmployees) * 100 : 0
            ];
        }
        
        return $compliance;
    }

    private function getTrainingNeedsPrediction()
    {
        // Predict training needs based on performance gaps and skill requirements
        $employees = Employee::with(['performanceEvaluations', 'trainings'])
            ->where('employment_status', 'active')
            ->get();
        
        $predictions = [];
        foreach ($employees as $employee) {
            $lastEvaluation = $employee->performanceEvaluations()->latest()->first();
            
            if ($lastEvaluation && $lastEvaluation->overall_rating < 3.5) {
                $recentTrainings = $employee->trainings()
                    ->where('start_date', '>=', Carbon::now()->subYear())
                    ->count();
                
                if ($recentTrainings < 2) {
                    $predictions[] = [
                        'employee_id' => $employee->id,
                        'name' => $employee->first_name . ' ' . $employee->last_name,
                        'department' => $employee->department,
                        'position' => $employee->position,
                        'performance_rating' => $lastEvaluation->overall_rating,
                        'recent_trainings' => $recentTrainings,
                        'priority' => $lastEvaluation->overall_rating < 3.0 ? 'High' : 'Medium'
                    ];
                }
            }
        }
        
        return collect($predictions)->sortBy('performance_rating');
    }

    private function getTrainingROI()
    {
        $totalCost = Training::whereYear('start_date', Carbon::now()->year)->sum('cost') ?? 0;
        
        // Simplified ROI calculation based on performance improvement
        $trainingParticipants = DB::table('training_participants as tp')
            ->join('trainings as t', 'tp.training_id', '=', 't.id')
            ->join('employees as e', 'tp.employee_id', '=', 'e.id')
            ->whereYear('t.start_date', Carbon::now()->year)
            ->where('tp.completion_status', 'completed')
            ->count();
        
        $averageImprovementValue = $trainingParticipants * 5000; // Estimated value per trained employee
        
        return [
            'total_investment' => $totalCost,
            'estimated_value' => $averageImprovementValue,
            'roi_percentage' => $totalCost > 0 ? (($averageImprovementValue - $totalCost) / $totalCost) * 100 : 0,
            'participants' => $trainingParticipants
        ];
    }

    private function getPopularTrainingPrograms()
    {
        return DB::table('training_participants as tp')
            ->join('trainings as t', 'tp.training_id', '=', 't.id')
            ->select(
                't.title',
                't.category',
                DB::raw('COUNT(*) as participants'),
                DB::raw('AVG(tp.satisfaction_rating) as avg_satisfaction')
            )
            ->whereYear('t.start_date', Carbon::now()->year)
            ->groupBy('t.id', 't.title', 't.category')
            ->orderBy('participants', 'desc')
            ->limit(10)
            ->get();
    }

    // Compliance Analytics Methods
    private function getCSCComplianceRates()
    {
        $reports = Report::whereYear('created_at', Carbon::now()->year)->get();
        
        $compliance = [
            'monthly_reports_submitted' => $reports->where('report_type', 'monthly')->count(),
            'annual_reports_submitted' => $reports->where('report_type', 'annual')->count(),
            'on_time_submissions' => $reports->where('submission_status', 'submitted_on_time')->count(),
            'late_submissions' => $reports->where('submission_status', 'submitted_late')->count(),
            'pending_submissions' => $reports->where('submission_status', 'pending')->count()
        ];
        
        $totalRequired = 12; // 12 monthly reports per year
        $compliance['compliance_rate'] = ($compliance['monthly_reports_submitted'] / $totalRequired) * 100;
        
        return $compliance;
    }

    private function getDocumentCompliance()
    {
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        
        $documentTypes = ['pds', 'medical_certificate', 'eligibility_certificate', 'diploma'];
        $compliance = [];
        
        foreach ($documentTypes as $type) {
            $employeesWithDocument = EmployeeDocument::where('document_type', $type)
                ->distinct('employee_id')
                ->count();
            
            $compliance[$type] = [
                'employees_with_document' => $employeesWithDocument,
                'compliance_rate' => $totalEmployees > 0 ? ($employeesWithDocument / $totalEmployees) * 100 : 0
            ];
        }
        
        return $compliance;
    }

    private function getBenefitsEnrollmentCompliance()
    {
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        
        $benefitTypes = ['gsis', 'philhealth', 'pagibig', 'sss'];
        $compliance = [];
        
        foreach ($benefitTypes as $type) {
            $enrolledEmployees = GovernmentBenefit::where('benefit_type', $type)
                ->where('enrollment_status', 'active')
                ->count();
            
            $compliance[$type] = [
                'enrolled_employees' => $enrolledEmployees,
                'enrollment_rate' => $totalEmployees > 0 ? ($enrolledEmployees / $totalEmployees) * 100 : 0
            ];
        }
        
        return $compliance;
    }

    private function getTrainingCompliance()
    {
        $currentYear = Carbon::now()->year;
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        
        // Required training hours per year (example: 40 hours)
        $requiredHours = 40;
        
        $employeesWithRequiredTraining = DB::table('employees as e')
            ->leftJoin('training_participants as tp', 'e.id', '=', 'tp.employee_id')
            ->leftJoin('trainings as t', 'tp.training_id', '=', 't.id')
            ->where('e.employment_status', 'active')
            ->whereYear('t.start_date', $currentYear)
            ->where('tp.completion_status', 'completed')
            ->groupBy('e.id')
            ->havingRaw('SUM(t.duration_hours) >= ?', [$requiredHours])
            ->count();
        
        return [
            'total_employees' => $totalEmployees,
            'compliant_employees' => $employeesWithRequiredTraining,
            'compliance_rate' => $totalEmployees > 0 ? ($employeesWithRequiredTraining / $totalEmployees) * 100 : 0,
            'required_hours' => $requiredHours
        ];
    }

    private function getPolicyAdherence()
    {
        // Example policy adherence metrics
        return [
            'attendance_policy' => $this->getAttendancePolicyAdherence(),
            'leave_policy' => $this->getLeavePolicyAdherence(),
            'performance_policy' => $this->getPerformancePolicyAdherence(),
            'code_of_conduct' => $this->getCodeOfConductAdherence()
        ];
    }

    private function getAuditReadiness()
    {
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        
        $readinessMetrics = [
            'complete_201_files' => $this->getComplete201FilesCount(),
            'updated_performance_evaluations' => $this->getUpdatedPerformanceEvaluationsCount(),
            'compliant_leave_records' => $this->getCompliantLeaveRecordsCount(),
            'proper_documentation' => $this->getProperDocumentationCount()
        ];
        
        $overallReadiness = array_sum($readinessMetrics) / (count($readinessMetrics) * $totalEmployees) * 100;
        
        return [
            'metrics' => $readinessMetrics,
            'overall_readiness' => $overallReadiness,
            'total_employees' => $totalEmployees
        ];
    }

    private function getComplianceTrends()
    {
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            $trends[] = [
                'month' => $month->format('M Y'),
                'csc_compliance' => $this->getMonthlyCSCCompliance($month),
                'document_compliance' => $this->getMonthlyDocumentCompliance($month),
                'training_compliance' => $this->getMonthlyTrainingCompliance($month)
            ];
        }
        
        return $trends;
    }

    private function getNonComplianceRisks()
    {
        return [
            'employees_missing_documents' => $this->getEmployeesMissingDocuments(),
            'overdue_performance_evaluations' => $this->getOverduePerformanceEvaluations(),
            'pending_csc_reports' => $this->getPendingCSCReports(),
            'incomplete_training_records' => $this->getIncompleteTrainingRecords()
        ];
    }

    // Workforce Planning Methods
    private function getSuccessionPlanningData()
    {
        $criticalPositions = ['mayor', 'vice_mayor', 'department_head', 'hrmo', 'budget_officer'];
        
        $successionData = [];
        foreach ($criticalPositions as $position) {
            $currentHolders = Employee::where('position', 'like', '%' . $position . '%')
                ->where('employment_status', 'active')
                ->get();
            
            foreach ($currentHolders as $holder) {
                $potentialSuccessors = Employee::where('department', $holder->department)
                    ->where('employment_status', 'active')
                    ->where('id', '!=', $holder->id)
                    ->whereHas('performanceEvaluations', function($query) {
                        $query->where('overall_rating', '>=', 4.0)
                            ->where('promotion_readiness', true);
                    })
                    ->limit(3)
                    ->get();
                
                $successionData[] = [
                    'position' => $holder->position,
                    'current_holder' => $holder->first_name . ' ' . $holder->last_name,
                    'department' => $holder->department,
                    'potential_successors' => $potentialSuccessors->count(),
                    'succession_readiness' => $potentialSuccessors->count() >= 2 ? 'Good' : ($potentialSuccessors->count() == 1 ? 'Limited' : 'At Risk')
                ];
            }
        }
        
        return $successionData;
    }

    private function getRetirementForecasts()
    {
        $forecasts = [];
        for ($i = 0; $i < 5; $i++) {
            $year = Carbon::now()->addYears($i)->year;
            
            $retirements = Employee::where('employment_status', 'active')
                ->whereNotNull('birth_date')
                ->whereRaw('YEAR(birth_date) + 65 = ?', [$year])
                ->get();
            
            $forecasts[] = [
                'year' => $year,
                'expected_retirements' => $retirements->count(),
                'departments_affected' => $retirements->pluck('department')->unique()->count(),
                'critical_positions' => $retirements->where('position', 'like', '%head%')->count()
            ];
        }
        
        return $forecasts;
    }

    private function getSkillInventory()
    {
        return Employee::where('employment_status', 'active')
            ->whereNotNull('skills')
            ->select('department', 'position', DB::raw('GROUP_CONCAT(skills) as all_skills'), DB::raw('COUNT(*) as employee_count'))
            ->groupBy('department', 'position')
            ->get();
    }

    private function getCapacityAnalysis()
    {
        return Employee::select('department')
            ->selectRaw('COUNT(*) as current_headcount')
            ->selectRaw('SUM(CASE WHEN employment_status = "active" THEN 1 ELSE 0 END) as active_employees')
            ->selectRaw('SUM(CASE WHEN employment_status = "on_leave" THEN 1 ELSE 0 END) as on_leave')
            ->selectRaw('AVG(DATEDIFF(CURDATE(), date_hired) / 365.25) as avg_tenure')
            ->groupBy('department')
            ->get();
    }

    private function getRecruitmentNeeds()
    {
        $retirements = $this->getRetirementForecasts();
        $turnoverPredictions = $this->getPredictedTurnover();
        
        $recruitmentNeeds = [];
        $departments = Employee::where('employment_status', 'active')
            ->distinct('department')
            ->pluck('department');
        
        foreach ($departments as $department) {
            $currentHeadcount = Employee::where('department', $department)
                ->where('employment_status', 'active')
                ->count();
            
            $predictedTurnover = $turnoverPredictions->where('department', $department)->count();
            $upcomingRetirements = Employee::where('department', $department)
                ->whereNotNull('birth_date')
                ->whereRaw('YEAR(birth_date) + 65 = ?', [Carbon::now()->addYear()->year])
                ->count();
            
            $totalNeeded = $predictedTurnover + $upcomingRetirements;
            
            $recruitmentNeeds[] = [
                'department' => $department,
                'current_headcount' => $currentHeadcount,
                'predicted_turnover' => $predictedTurnover,
                'upcoming_retirements' => $upcomingRetirements,
                'recruitment_needed' => $totalNeeded,
                'urgency' => $totalNeeded > ($currentHeadcount * 0.2) ? 'High' : ($totalNeeded > 0 ? 'Medium' : 'Low')
            ];
        }
        
        return $recruitmentNeeds;
    }

    private function getOrganizationalStructure()
    {
        return Employee::where('employment_status', 'active')
            ->select('department', 'position', DB::raw('COUNT(*) as count'))
            ->groupBy('department', 'position')
            ->orderBy('department', 'position')
            ->get();
    }

    private function getLeadershipPipeline()
    {
        return Employee::with(['performanceEvaluations'])
            ->where('employment_status', 'active')
            ->whereHas('performanceEvaluations', function($query) {
                $query->where('overall_rating', '>=', 4.0)
                    ->where('leadership_potential', true);
            })
            ->orderBy('date_hired')
            ->limit(20)
            ->get();
    }

    private function getCriticalPositions()
    {
        $criticalKeywords = ['head', 'chief', 'director', 'manager', 'supervisor', 'officer'];
        
        return Employee::where('employment_status', 'active')
            ->where(function($query) use ($criticalKeywords) {
                foreach ($criticalKeywords as $keyword) {
                    $query->orWhere('position', 'like', '%' . $keyword . '%');
                }
            })
            ->with(['performanceEvaluations'])
            ->get();
    }

    // Cost Analytics Methods
    private function getTotalHRCosts()
    {
        $currentYear = Carbon::now()->year;
        
        return [
            'total_salaries' => Employee::where('employment_status', 'active')->sum('salary') * 12,
            'training_costs' => Training::whereYear('start_date', $currentYear)->sum('cost'),
            'recruitment_costs' => $this->getRecruitmentCosts()['total'],
            'benefit_costs' => $this->getBenefitCosts()['total'],
            'turnover_costs' => $this->getTurnoverCostAnalysis()['total_turnover_cost']
        ];
    }

    private function getCostPerEmployee()
    {
        $totalCosts = $this->getTotalHRCosts();
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        
        $totalHRCost = array_sum($totalCosts);
        
        return [
            'total_hr_cost' => $totalHRCost,
            'total_employees' => $totalEmployees,
            'cost_per_employee' => $totalEmployees > 0 ? $totalHRCost / $totalEmployees : 0
        ];
    }

    private function getTrainingCosts()
    {
        $currentYear = Carbon::now()->year;
        
        return [
            'total' => Training::whereYear('start_date', $currentYear)->sum('cost'),
            'average_per_training' => Training::whereYear('start_date', $currentYear)->avg('cost'),
            'cost_per_participant' => $this->calculateCostPerTrainingParticipant(),
            'by_category' => Training::whereYear('start_date', $currentYear)
                ->select('category', DB::raw('SUM(cost) as total_cost'))
                ->groupBy('category')
                ->get()
        ];
    }

    private function getRecruitmentCosts()
    {
        // Estimated recruitment costs
        $newHires = Employee::whereYear('date_hired', Carbon::now()->year)->count();
        $estimatedCostPerHire = 15000; // Estimated cost
        
        return [
            'total' => $newHires * $estimatedCostPerHire,
            'new_hires' => $newHires,
            'cost_per_hire' => $estimatedCostPerHire
        ];
    }

    private function getBenefitCosts()
    {
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        $averageSalary = Employee::where('employment_status', 'active')->avg('salary') ?? 50000;
        
        // Estimated benefit costs as percentage of salary
        $benefitPercentage = 0.30; // 30% of salary
        
        return [
            'total' => $totalEmployees * $averageSalary * $benefitPercentage * 12,
            'percentage_of_salary' => $benefitPercentage * 100,
            'average_per_employee' => $averageSalary * $benefitPercentage * 12
        ];
    }

    private function getProductivityMetrics()
    {
        $avgPerformanceRating = PerformanceEvaluation::whereYear('evaluation_date', Carbon::now()->year)
            ->avg('overall_rating') ?? 3.0;
        
        $goalAchievementRate = PerformanceEvaluation::whereYear('evaluation_date', Carbon::now()->year)
            ->avg('goal_achievement_percentage') ?? 80;
        
        return [
            'average_performance_rating' => $avgPerformanceRating,
            'goal_achievement_rate' => $goalAchievementRate,
            'productivity_index' => ($avgPerformanceRating / 5) * ($goalAchievementRate / 100) * 100
        ];
    }

    private function getCostTrends()
    {
        $trends = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            
            $monthlySalaryCost = Employee::where('employment_status', 'active')->sum('salary');
            $monthlyTrainingCost = Training::whereYear('start_date', $month->year)
                ->whereMonth('start_date', $month->month)
                ->sum('cost');
            
            $trends[] = [
                'month' => $month->format('M Y'),
                'salary_cost' => $monthlySalaryCost,
                'training_cost' => $monthlyTrainingCost,
                'total_cost' => $monthlySalaryCost + $monthlyTrainingCost
            ];
        }
        
        return $trends;
    }

    // Predictive Analytics Methods
    private function getPerformanceForecasts()
    {
        $trends = $this->getPerformanceTrends();
        
        if (count($trends) < 3) {
            return ['message' => 'Insufficient data for forecasting'];
        }
        
        // Simple linear regression for forecasting
        $years = array_column($trends, 'year');
        $ratings = array_column($trends, 'average_rating');
        
        $n = count($years);
        $sumX = array_sum($years);
        $sumY = array_sum($ratings);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $years[$i] * $ratings[$i];
            $sumX2 += $years[$i] * $years[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        $forecasts = [];
        for ($i = 1; $i <= 3; $i++) {
            $futureYear = Carbon::now()->addYears($i)->year;
            $forecastRating = $slope * $futureYear + $intercept;
            
            $forecasts[] = [
                'year' => $futureYear,
                'predicted_rating' => round($forecastRating, 2),
                'confidence' => 'Medium' // Simplified confidence level
            ];
        }
        
        return $forecasts;
    }

    private function getSuccessionRisks()
    {
        $criticalPositions = $this->getCriticalPositions();
        $risks = [];
        
        foreach ($criticalPositions as $employee) {
            $age = $employee->age ?? 0;
            $tenure = $employee->tenure_years ?? 0;
            
            $riskScore = 0;
            if ($age > 60) $riskScore += 40;
            elseif ($age > 55) $riskScore += 25;
            
            if ($tenure > 25) $riskScore += 30;
            elseif ($tenure > 20) $riskScore += 20;
            
            // Check for potential successors
            $potentialSuccessors = Employee::where('department', $employee->department)
                ->where('employment_status', 'active')
                ->where('id', '!=', $employee->id)
                ->whereHas('performanceEvaluations', function($query) {
                    $query->where('overall_rating', '>=', 4.0);
                })
                ->count();
            
            if ($potentialSuccessors == 0) $riskScore += 30;
            elseif ($potentialSuccessors == 1) $riskScore += 15;
            
            $risks[] = [
                'employee_name' => $employee->first_name . ' ' . $employee->last_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'age' => $age,
                'tenure' => $tenure,
                'potential_successors' => $potentialSuccessors,
                'risk_score' => min($riskScore, 100),
                'risk_level' => $riskScore >= 70 ? 'High' : ($riskScore >= 40 ? 'Medium' : 'Low')
            ];
        }
        
        return collect($risks)->sortByDesc('risk_score');
    }

    private function getBudgetForecasts()
    {
        $currentCosts = $this->getTotalHRCosts();
        $totalCurrent = array_sum($currentCosts);
        
        // Forecast based on inflation and growth
        $forecasts = [];
        $inflationRate = 0.05; // 5% annual inflation
        $growthRate = 0.03; // 3% annual growth
        
        for ($i = 1; $i <= 3; $i++) {
            $year = Carbon::now()->addYears($i)->year;
            $adjustmentFactor = pow(1 + $inflationRate + $growthRate, $i);
            
            $forecasts[] = [
                'year' => $year,
                'predicted_budget' => round($totalCurrent * $adjustmentFactor),
                'growth_rate' => round(($adjustmentFactor - 1) * 100, 1) . '%'
            ];
        }
        
        return $forecasts;
    }

    private function getWorkforceDemandForecast()
    {
        $currentHeadcount = Employee::where('employment_status', 'active')->count();
        $retirementForecasts = $this->getRetirementForecasts();
        $turnoverRate = $this->getAnnualTurnoverRate() / 100;
        
        $forecasts = [];
        for ($i = 1; $i <= 3; $i++) {
            $year = Carbon::now()->addYears($i)->year;
            
            $retirements = collect($retirementForecasts)->where('year', $year)->first()['expected_retirements'] ?? 0;
            $turnoverLoss = round($currentHeadcount * $turnoverRate);
            $totalLoss = $retirements + $turnoverLoss;
            
            $forecasts[] = [
                'year' => $year,
                'current_headcount' => $currentHeadcount,
                'expected_retirements' => $retirements,
                'expected_turnover' => $turnoverLoss,
                'total_loss' => $totalLoss,
                'hiring_needed' => $totalLoss
            ];
        }
        
        return $forecasts;
    }

    private function getSkillShortagePrediction()
    {
        $skillInventory = $this->getSkillInventory();
        $retirements = $this->getRetirementForecasts();
        
        $predictions = [];
        foreach ($skillInventory as $inventory) {
            $upcomingRetirements = Employee::where('department', $inventory->department)
                ->where('position', $inventory->position)
                ->whereNotNull('birth_date')
                ->whereBetween(DB::raw('YEAR(birth_date) + 65'), [
                    Carbon::now()->year,
                    Carbon::now()->addYears(3)->year
                ])
                ->count();
            
            if ($upcomingRetirements > 0) {
                $predictions[] = [
                    'department' => $inventory->department,
                    'position' => $inventory->position,
                    'current_employees' => $inventory->employee_count,
                    'upcoming_retirements' => $upcomingRetirements,
                    'skills_at_risk' => $inventory->all_skills,
                    'shortage_risk' => $upcomingRetirements / $inventory->employee_count > 0.5 ? 'High' : 'Medium'
                ];
            }
        }
        
        return $predictions;
    }

    private function getComplianceRiskPrediction()
    {
        $currentCompliance = $this->getComplianceAnalytics();
        
        $risks = [];
        
        // CSC Compliance Risk
        $cscRate = $currentCompliance['csc_compliance_rates']['compliance_rate'];
        if ($cscRate < 90) {
            $risks[] = [
                'area' => 'CSC Reporting',
                'current_rate' => $cscRate,
                'risk_level' => $cscRate < 70 ? 'High' : 'Medium',
                'predicted_impact' => 'Potential CSC sanctions and audit issues'
            ];
        }
        
        // Training Compliance Risk
        $trainingRate = $currentCompliance['training_compliance']['compliance_rate'];
        if ($trainingRate < 80) {
            $risks[] = [
                'area' => 'Training Compliance',
                'current_rate' => $trainingRate,
                'risk_level' => $trainingRate < 60 ? 'High' : 'Medium',
                'predicted_impact' => 'Skill gaps and performance degradation'
            ];
        }
        
        return $risks;
    }

    // Helper methods for compliance calculations
    private function getAttendancePolicyAdherence()
    {
        // Simplified attendance policy adherence
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        $compliantEmployees = $totalEmployees * 0.85; // Estimated 85% compliance
        
        return [
            'total_employees' => $totalEmployees,
            'compliant_employees' => round($compliantEmployees),
            'adherence_rate' => 85
        ];
    }

    private function getLeavePolicyAdherence()
    {
        $excessiveLeaveUsers = LeaveApplication::select('employee_id')
            ->where('start_date', '>=', Carbon::now()->subYear())
            ->where('status', 'approved')
            ->groupBy('employee_id')
            ->havingRaw('SUM(DATEDIFF(end_date, start_date) + 1) > 30')
            ->count();
        
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        
        return [
            'total_employees' => $totalEmployees,
            'excessive_leave_users' => $excessiveLeaveUsers,
            'adherence_rate' => $totalEmployees > 0 ? (($totalEmployees - $excessiveLeaveUsers) / $totalEmployees) * 100 : 100
        ];
    }

    private function getPerformancePolicyAdherence()
    {
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        $evaluatedEmployees = PerformanceEvaluation::whereYear('evaluation_date', Carbon::now()->year)
            ->distinct('employee_id')
            ->count();
        
        return [
            'total_employees' => $totalEmployees,
            'evaluated_employees' => $evaluatedEmployees,
            'adherence_rate' => $totalEmployees > 0 ? ($evaluatedEmployees / $totalEmployees) * 100 : 0
        ];
    }

    private function getCodeOfConductAdherence()
    {
        // Simplified - based on disciplinary actions
        $totalEmployees = Employee::where('employment_status', 'active')->count();
        $disciplinaryActions = 5; // Estimated number of actions this year
        
        return [
            'total_employees' => $totalEmployees,
            'disciplinary_actions' => $disciplinaryActions,
            'adherence_rate' => $totalEmployees > 0 ? (($totalEmployees - $disciplinaryActions) / $totalEmployees) * 100 : 100
        ];
    }

    // Helper methods for audit readiness
    private function getComplete201FilesCount()
    {
        return Employee::where('employment_status', 'active')
            ->whereHas('documents', function($query) {
                $query->whereIn('document_type', ['pds', 'medical_certificate', 'eligibility_certificate']);
            })
            ->count();
    }

    private function getUpdatedPerformanceEvaluationsCount()
    {
        return Employee::where('employment_status', 'active')
            ->whereHas('performanceEvaluations', function($query) {
                $query->whereYear('evaluation_date', Carbon::now()->year);
            })
            ->count();
    }

    private function getCompliantLeaveRecordsCount()
    {
        return Employee::where('employment_status', 'active')
            ->whereHas('leaveApplications', function($query) {
                $query->where('status', 'approved')
                    ->where('start_date', '>=', Carbon::now()->subYear());
            })
            ->count();
    }

    private function getProperDocumentationCount()
    {
        return Employee::where('employment_status', 'active')
            ->whereHas('documents')
            ->count();
    }

    // Helper methods for monthly compliance tracking
    private function getMonthlyCSCCompliance($month)
    {
        $reports = Report::whereYear('created_at', $month->year)
            ->whereMonth('created_at', $month->month)
            ->count();
        
        return $reports >= 1 ? 100 : 0; // 100% if at least one report submitted
    }

    private function getMonthlyDocumentCompliance($month)
    {
        $documentsUploaded = EmployeeDocument::whereYear('uploaded_at', $month->year)
            ->whereMonth('uploaded_at', $month->month)
            ->count();
        
        return min($documentsUploaded * 10, 100); // Scale to percentage
    }

    private function getMonthlyTrainingCompliance($month)
    {
        $trainingsCompleted = DB::table('training_participants as tp')
            ->join('trainings as t', 'tp.training_id', '=', 't.id')
            ->whereYear('t.start_date', $month->year)
            ->whereMonth('t.start_date', $month->month)
            ->where('tp.completion_status', 'completed')
            ->count();
        
        return min($trainingsCompleted * 5, 100); // Scale to percentage
    }

    // Helper methods for non-compliance risks
    private function getEmployeesMissingDocuments()
    {
        return Employee::where('employment_status', 'active')
            ->whereDoesntHave('documents', function($query) {
                $query->whereIn('document_type', ['pds', 'medical_certificate']);
            })
            ->count();
    }

    private function getOverduePerformanceEvaluations()
    {
        return Employee::where('employment_status', 'active')
            ->whereDoesntHave('performanceEvaluations', function($query) {
                $query->whereYear('evaluation_date', Carbon::now()->year);
            })
            ->count();
    }

    private function getPendingCSCReports()
    {
        return Report::where('submission_status', 'pending')
            ->whereYear('created_at', Carbon::now()->year)
            ->count();
    }

    private function getIncompleteTrainingRecords()
    {
        return DB::table('training_participants')
            ->where('completion_status', 'incomplete')
            ->count();
    }

    private function calculateCostPerTrainingParticipant()
    {
        $totalCost = Training::whereYear('start_date', Carbon::now()->year)->sum('cost');
        $totalParticipants = DB::table('training_participants as tp')
            ->join('trainings as t', 'tp.training_id', '=', 't.id')
            ->whereYear('t.start_date', Carbon::now()->year)
            ->count();
        
        return $totalParticipants > 0 ? $totalCost / $totalParticipants : 0;
    }

    /**
     * Clear analytics cache
     */
    public function clearCache()
    {
        $cacheKeys = [
            'hr_analytics_workforce',
            'hr_analytics_turnover',
            'hr_analytics_performance',
            'hr_analytics_training',
            'hr_analytics_compliance',
            'hr_analytics_workforce_planning',
            'hr_analytics_cost',
            'hr_analytics_predictive'
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        return true;
    }
}