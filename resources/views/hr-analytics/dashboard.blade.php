@extends('layouts.app')

@section('title', 'HR Analytics Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">HR Analytics Dashboard</h1>
                    <p class="text-muted">Comprehensive insights into workforce performance and trends</p>
                </div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" id="refreshData">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <button class="btn btn-outline-success" id="exportData">
                        <i class="fas fa-download"></i> Export
                    </button>
                    <button class="btn btn-outline-info" id="clearCache">
                        <i class="fas fa-trash"></i> Clear Cache
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Stats -->
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Annual Turnover Rate</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="turnoverRate">
                                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Avg Performance Rating</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avgPerformance">
                                <div class="spinner-border spinner-border-sm text-info" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">CSC Compliance</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="cscCompliance">
                                <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Analytics Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Analytics Categories</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.workforce') }}" class="btn btn-outline-primary btn-block">
                                <i class="fas fa-users mb-2"></i><br>
                                Workforce Analytics
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.turnover') }}" class="btn btn-outline-success btn-block">
                                <i class="fas fa-exchange-alt mb-2"></i><br>
                                Turnover Analysis
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.performance') }}" class="btn btn-outline-info btn-block">
                                <i class="fas fa-chart-line mb-2"></i><br>
                                Performance Analytics
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.training') }}" class="btn btn-outline-warning btn-block">
                                <i class="fas fa-graduation-cap mb-2"></i><br>
                                Training Analytics
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.compliance') }}" class="btn btn-outline-danger btn-block">
                                <i class="fas fa-clipboard-check mb-2"></i><br>
                                Compliance Monitoring
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.workforce-planning') }}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-sitemap mb-2"></i><br>
                                Workforce Planning
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.costs') }}" class="btn btn-outline-dark btn-block">
                                <i class="fas fa-dollar-sign mb-2"></i><br>
                                Cost Analysis
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('hr-analytics.predictive') }}" class="btn btn-outline-purple btn-block">
                                <i class="fas fa-crystal-ball mb-2"></i><br>
                                Predictive Analytics
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Insights -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Workforce Overview</h6>
                </div>
                <div class="card-body">
                    <canvas id="workforceOverviewChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Key Insights</h6>
                </div>
                <div class="card-body">
                    <div id="keyInsights">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading insights...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Department Performance -->
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
                    <h6 class="m-0 font-weight-bold text-primary">Performance Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="performanceChart" width="400" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Trends and Forecasts -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Turnover Trends (Last 12 Months)</h6>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary active" data-period="monthly">Monthly</button>
                        <button class="btn btn-outline-primary" data-period="quarterly">Quarterly</button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="turnoverTrendChart" width="400" height="150"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Items -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger">High Priority Actions</h6>
                </div>
                <div class="card-body">
                    <div id="highPriorityActions">
                        <div class="text-center">
                            <div class="spinner-border text-danger" role="status">
                                <span class="sr-only">Loading actions...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-warning">Upcoming Retirements (Next 2 Years)</h6>
                </div>
                <div class="card-body">
                    <div id="upcomingRetirements">
                        <div class="text-center">
                            <div class="spinner-border text-warning" role="status">
                                <span class="sr-only">Loading retirements...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Export Modal -->
<div class="modal fade" id="exportModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Export Analytics Data</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="exportForm">
                    <div class="form-group">
                        <label for="exportType">Analytics Type</label>
                        <select class="form-control" id="exportType" name="type" required>
                            <option value="workforce">Workforce Analytics</option>
                            <option value="turnover">Turnover Analysis</option>
                            <option value="performance">Performance Analytics</option>
                            <option value="training">Training Analytics</option>
                            <option value="compliance">Compliance Monitoring</option>
                            <option value="cost">Cost Analysis</option>
                            <option value="predictive">Predictive Analytics</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exportFormat">Format</label>
                        <select class="form-control" id="exportFormat" name="format" required>
                            <option value="excel">Excel (.xlsx)</option>
                            <option value="csv">CSV (.csv)</option>
                            <option value="pdf">PDF (.pdf)</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmExport">Export</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-outline-purple {
    color: #6f42c1;
    border-color: #6f42c1;
}
.btn-outline-purple:hover {
    color: #fff;
    background-color: #6f42c1;
    border-color: #6f42c1;
}
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}
.insight-item {
    display: flex;
    align-items-center;
    padding: 10px 0;
    border-bottom: 1px solid #e3e6f0;
}
.insight-item:last-child {
    border-bottom: none;
}
.insight-icon {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}
.insight-icon.warning {
    background-color: #ffc107;
    color: #fff;
}
.insight-icon.danger {
    background-color: #dc3545;
    color: #fff;
}
.insight-icon.info {
    background-color: #17a2b8;
    color: #fff;
}
.action-item {
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 4px;
    border-left: 4px solid #dc3545;
    background-color: #f8f9fa;
}
.retirement-item {
    padding: 8px 12px;
    margin-bottom: 8px;
    border-radius: 4px;
    border-left: 4px solid #ffc107;
    background-color: #fff3cd;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    let charts = {};
    
    // Load initial data
    loadSummaryData();
    loadInsights();
    loadWorkforceChart();
    loadDepartmentChart();
    loadPerformanceChart();
    loadTurnoverTrend();
    loadActionItems();
    loadRetirements();
    
    // Refresh data
    $('#refreshData').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Refreshing...');
        
        Promise.all([
            loadSummaryData(),
            loadInsights(),
            loadWorkforceChart(),
            loadDepartmentChart(),
            loadPerformanceChart(),
            loadTurnoverTrend(),
            loadActionItems(),
            loadRetirements()
        ]).finally(() => {
            $('#refreshData').prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Refresh');
        });
    });
    
    // Export data
    $('#exportData').click(function() {
        $('#exportModal').modal('show');
    });
    
    $('#confirmExport').click(function() {
        const formData = new FormData($('#exportForm')[0]);
        
        $.ajax({
            url: '{{ route("hr-analytics.export") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Create temporary download link
                    const link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = '';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    $('#exportModal').modal('hide');
                    showAlert('success', 'Export generated successfully!');
                } else {
                    showAlert('danger', 'Export failed: ' + response.message);
                }
            },
            error: function() {
                showAlert('danger', 'Error generating export.');
            }
        });
    });
    
    // Clear cache
    $('#clearCache').click(function() {
        if (confirm('Are you sure you want to clear the analytics cache? This will refresh all data.')) {
            $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Clearing...');
            
            $.ajax({
                url: '{{ route("hr-analytics.clear-cache") }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Cache cleared successfully!');
                        $('#refreshData').click(); // Refresh data
                    } else {
                        showAlert('danger', 'Error clearing cache: ' + response.message);
                    }
                },
                error: function() {
                    showAlert('danger', 'Error clearing cache.');
                },
                complete: function() {
                    $('#clearCache').prop('disabled', false).html('<i class="fas fa-trash"></i> Clear Cache');
                }
            });
        }
    });
    
    // Period toggle for turnover trend
    $('[data-period]').click(function() {
        $('[data-period]').removeClass('active');
        $(this).addClass('active');
        loadTurnoverTrend($(this).data('period'));
    });
    
    function loadSummaryData() {
        return $.ajax({
            url: '{{ route("hr-analytics.summary") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    $('#totalEmployees').text(data.workforce_summary.total_employees.toLocaleString());
                    $('#turnoverRate').text(data.turnover_summary.annual_rate.toFixed(1) + '%');
                    $('#avgPerformance').text((data.performance_summary.avg_rating || 0).toFixed(1) + '/5.0');
                    $('#cscCompliance').text(data.compliance_summary.csc_compliance.toFixed(1) + '%');
                }
            },
            error: function() {
                $('#totalEmployees, #turnoverRate, #avgPerformance, #cscCompliance').text('Error');
            }
        });
    }
    
    function loadInsights() {
        return $.ajax({
            url: '{{ route("hr-analytics.insights") }}',
            method: 'GET',
            success: function(response) {
                if (response.success && response.data.insights.length > 0) {
                    let html = '';
                    response.data.insights.forEach(function(insight) {
                        const iconClass = insight.type === 'danger' ? 'danger' : 
                                        insight.type === 'warning' ? 'warning' : 'info';
                        const icon = insight.type === 'danger' ? 'fas fa-exclamation-triangle' :
                                   insight.type === 'warning' ? 'fas fa-exclamation-circle' : 
                                   'fas fa-info-circle';
                        
                        html += `
                            <div class="insight-item">
                                <div class="insight-icon ${iconClass}">
                                    <i class="${icon} fa-sm"></i>
                                </div>
                                <div>
                                    <small class="text-muted">${insight.category}</small><br>
                                    <span class="text-sm">${insight.message}</span>
                                </div>
                            </div>
                        `;
                    });
                    $('#keyInsights').html(html);
                } else {
                    $('#keyInsights').html('<p class="text-muted text-center">No critical insights at this time.</p>');
                }
            },
            error: function() {
                $('#keyInsights').html('<p class="text-danger text-center">Error loading insights.</p>');
            }
        });
    }
    
    function loadWorkforceChart() {
        return $.ajax({
            url: '{{ route("hr-analytics.workforce") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const ctx = document.getElementById('workforceOverviewChart').getContext('2d');
                    
                    if (charts.workforce) {
                        charts.workforce.destroy();
                    }
                    
                    const data = response.data.employment_status_breakdown;
                    
                    charts.workforce = new Chart(ctx, {
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
                                ],
                                hoverBackgroundColor: [
                                    '#2e59d9',
                                    '#17a673',
                                    '#2c9faf',
                                    '#f4b942',
                                    '#e02424'
                                ]
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }
        });
    }
    
    function loadDepartmentChart() {
        return $.ajax({
            url: '{{ route("hr-analytics.workforce") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const ctx = document.getElementById('departmentChart').getContext('2d');
                    
                    if (charts.department) {
                        charts.department.destroy();
                    }
                    
                    const data = response.data.department_distribution.slice(0, 8); // Top 8 departments
                    
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
            }
        });
    }
    
    function loadPerformanceChart() {
        return $.ajax({
            url: '{{ route("hr-analytics.performance") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const ctx = document.getElementById('performanceChart').getContext('2d');
                    
                    if (charts.performance) {
                        charts.performance.destroy();
                    }
                    
                    const data = response.data.performance_distribution;
                    
                    charts.performance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.map(item => item.performance_category),
                            datasets: [{
                                label: 'Employees',
                                data: data.map(item => item.count),
                                backgroundColor: [
                                    '#1cc88a',
                                    '#36b9cc',
                                    '#f6c23e',
                                    '#fd7e14',
                                    '#e74a3b'
                                ]
                            }]
                        },
                        options: {
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
            }
        });
    }
    
    function loadTurnoverTrend(period = 'monthly') {
        return $.ajax({
            url: '{{ route("hr-analytics.turnover") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const ctx = document.getElementById('turnoverTrendChart').getContext('2d');
                    
                    if (charts.turnover) {
                        charts.turnover.destroy();
                    }
                    
                    const data = response.data.monthly_turnover_rate;
                    
                    charts.turnover = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.map(item => item.month),
                            datasets: [{
                                label: 'Turnover Rate (%)',
                                data: data.map(item => item.turnover_rate),
                                borderColor: '#e74a3b',
                                backgroundColor: 'rgba(231, 74, 59, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.3
                            }]
                        },
                        options: {
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: function(value) {
                                            return value + '%';
                                        }
                                    }
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
            }
        });
    }
    
    function loadActionItems() {
        return $.ajax({
            url: '{{ route("hr-analytics.insights") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const highPriorityInsights = response.data.insights.filter(insight => insight.priority === 'high');
                    
                    if (highPriorityInsights.length > 0) {
                        let html = '';
                        highPriorityInsights.forEach(function(insight) {
                            html += `
                                <div class="action-item">
                                    <strong>${insight.category}:</strong> ${insight.message}
                                </div>
                            `;
                        });
                        $('#highPriorityActions').html(html);
                    } else {
                        $('#highPriorityActions').html('<p class="text-muted text-center">No high priority actions required.</p>');
                    }
                }
            },
            error: function() {
                $('#highPriorityActions').html('<p class="text-danger text-center">Error loading actions.</p>');
            }
        });
    }
    
    function loadRetirements() {
        return $.ajax({
            url: '{{ route("hr-analytics.workforce-planning") }}',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    const forecasts = response.data.retirement_forecasts.slice(0, 2); // Next 2 years
                    
                    if (forecasts.length > 0) {
                        let html = '';
                        forecasts.forEach(function(forecast) {
                            if (forecast.expected_retirements > 0) {
                                html += `
                                    <div class="retirement-item">
                                        <strong>${forecast.year}:</strong> ${forecast.expected_retirements} expected retirements
                                        (${forecast.critical_positions} in critical positions)
                                    </div>
                                `;
                            }
                        });
                        
                        if (html) {
                            $('#upcomingRetirements').html(html);
                        } else {
                            $('#upcomingRetirements').html('<p class="text-muted text-center">No significant retirements expected.</p>');
                        }
                    } else {
                        $('#upcomingRetirements').html('<p class="text-muted text-center">No retirement data available.</p>');
                    }
                }
            },
            error: function() {
                $('#upcomingRetirements').html('<p class="text-danger text-center">Error loading retirement data.</p>');
            }
        });
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