@extends('layouts.app')

@section('title', 'Workforce Analytics')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Workforce Analytics</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('hr-analytics.dashboard') }}">Analytics</a></li>
                            <li class="breadcrumb-item active">Workforce</li>
                        </ol>
                    </nav>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" id="refreshData">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn btn-outline-success" id="exportData">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Employees</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalEmployees">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">New Hires (This Month)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="newHires">
                                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Average Age</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="averageAge">
                                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-birthday-cake fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Average Tenure</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="averageTenure">
                                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Department Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Employment Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="employmentStatusChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Age Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="ageDistributionChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tenure Analysis</h6>
                </div>
                <div class="card-body">
                    <canvas id="tenureChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 3 -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Gender Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="genderChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Education Levels</h6>
                </div>
                <div class="card-body">
                    <canvas id="educationChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Positions Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Positions by Headcount</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="positionsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Position</th>
                                    <th>Employee Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    let charts = {};
    
    // Load data
    loadWorkforceData();
    
    // Refresh data
    $('#refreshData').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        loadWorkforceData().finally(() => {
            $('#refreshData').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
        });
    });
    
    // Export data
    $('#exportData').click(function() {
        // Implementation for export functionality
        window.location.href = '{{ route("hr-analytics.export") }}?type=workforce&format=excel';
    });
    
    function loadWorkforceData() {
        return $.ajax({
            url: '{{ route("hr-analytics.api.workforce") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update metrics
                    updateMetrics(data.employee_demographics);
                    
                    // Update charts
                    updateDepartmentChart(data.department_distribution);
                    updateEmploymentStatusChart(data.employment_status_breakdown);
                    updateAgeDistributionChart(data.age_distribution);
                    updateTenureChart(data.tenure_analysis);
                    updateGenderChart(data.gender_distribution);
                    updateEducationChart(data.education_levels);
                    updatePositionsTable(data.position_analysis);
                }
            },
            error: function() {
                showAlert('danger', 'Error loading workforce data.');
            }
        });
    }
    
    function updateMetrics(demographics) {
        $('#totalEmployees').text(demographics.total_employees.toLocaleString());
        $('#newHires').text(demographics.new_hires_this_month.toLocaleString());
        $('#averageAge').text(Math.round(demographics.average_age || 0) + ' years');
        $('#averageTenure').text((demographics.average_tenure || 0).toFixed(1) + ' years');
    }
    
    function updateDepartmentChart(data) {
        const ctx = document.getElementById('departmentChart').getContext('2d');
        
        if (charts.department) {
            charts.department.destroy();
        }
        
        charts.department = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.department),
                datasets: [{
                    label: 'Employees',
                    data: data.map(item => item.count),
                    backgroundColor: '#4e73df',
                    borderColor: '#4e73df',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    function updateEmploymentStatusChart(data) {
        const ctx = document.getElementById('employmentStatusChart').getContext('2d');
        
        if (charts.employment) {
            charts.employment.destroy();
        }
        
        charts.employment = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(item => item.employment_status.replace('_', ' ').toUpperCase()),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    function updateAgeDistributionChart(data) {
        const ctx = document.getElementById('ageDistributionChart').getContext('2d');
        
        if (charts.age) {
            charts.age.destroy();
        }
        
        charts.age = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.age_group),
                datasets: [{
                    label: 'Employees',
                    data: data.map(item => item.count),
                    backgroundColor: '#1cc88a',
                    borderColor: '#1cc88a',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    function updateTenureChart(data) {
        const ctx = document.getElementById('tenureChart').getContext('2d');
        
        if (charts.tenure) {
            charts.tenure.destroy();
        }
        
        charts.tenure = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.tenure_group),
                datasets: [{
                    label: 'Employees',
                    data: data.map(item => item.count),
                    backgroundColor: '#36b9cc',
                    borderColor: '#36b9cc',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    function updateGenderChart(data) {
        const ctx = document.getElementById('genderChart').getContext('2d');
        
        if (charts.gender) {
            charts.gender.destroy();
        }
        
        charts.gender = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: data.map(item => item.gender || 'Not Specified'),
                datasets: [{
                    data: data.map(item => item.count),
                    backgroundColor: [
                        '#4e73df',
                        '#e74a3b',
                        '#f6c23e'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
    
    function updateEducationChart(data) {
        const ctx = document.getElementById('educationChart').getContext('2d');
        
        if (charts.education) {
            charts.education.destroy();
        }
        
        charts.education = new Chart(ctx, {
            type: 'horizontalBar',
            data: {
                labels: data.map(item => item.education_level || 'Not Specified'),
                datasets: [{
                    label: 'Employees',
                    data: data.map(item => item.count),
                    backgroundColor: '#f6c23e',
                    borderColor: '#f6c23e',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    function updatePositionsTable(data) {
        const tbody = $('#positionsTable tbody');
        tbody.empty();
        
        const totalEmployees = data.reduce((sum, item) => sum + item.count, 0);
        
        if (data.length > 0) {
            data.forEach(function(item) {
                const percentage = ((item.count / totalEmployees) * 100).toFixed(1);
                tbody.append(`
                    <tr>
                        <td>${item.position}</td>
                        <td>${item.count.toLocaleString()}</td>
                        <td>${percentage}%</td>
                    </tr>
                `);
            });
        } else {
            tbody.append(`
                <tr>
                    <td colspan="3" class="text-center text-muted">No data available</td>
                </tr>
            `);
        }
    }
    
    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        $('.container-fluid').prepend(alertHtml);
        
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
});
</script>
@endpush