<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Security Monitoring Dashboard') }}
            </h2>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Last Updated:</span>
                <span id="last-updated" class="text-sm font-medium text-gray-800">{{ now()->format('M d, Y H:i:s') }}</span>
                <button id="refresh-btn" class="ml-2 px-3 py-1 bg-blue-500 text-white text-xs rounded hover:bg-blue-600">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Status Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <!-- Privacy Protection Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-green-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-shield-alt text-2xl text-green-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Privacy Protection</h3>
                                <p id="privacy-status" class="text-sm text-green-600 font-semibold">OPTIMAL</p>
                                <p class="text-xs text-gray-500">0 violations today</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tests Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-blue-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-2xl text-blue-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Security Tests</h3>
                                <p id="tests-status" class="text-sm text-blue-600 font-semibold">ALL PASSING</p>
                                <p class="text-xs text-gray-500">67/67 tests passed</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-purple-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-gavel text-2xl text-purple-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Compliance</h3>
                                <p id="compliance-status" class="text-sm text-purple-600 font-semibold">COMPLIANT</p>
                                <p class="text-xs text-gray-500">98% compliance score</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Health -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-l-4 border-yellow-500">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-heartbeat text-2xl text-yellow-600"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">System Health</h3>
                                <p id="health-status" class="text-sm text-yellow-600 font-semibold">EXCELLENT</p>
                                <p class="text-xs text-gray-500">99.8% uptime</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Dashboard Content -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Real-Time Security Metrics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-chart-line mr-2"></i>Security Metrics
                        </h3>
                        
                        <!-- Privacy Protection Metrics -->
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-800 mb-2">Privacy Protection</h4>
                            <div class="grid grid-cols-3 gap-4 text-center">
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-2xl font-bold text-green-600" id="violations-today">0</div>
                                    <div class="text-xs text-gray-600">Today</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-2xl font-bold text-green-600" id="violations-week">0</div>
                                    <div class="text-xs text-gray-600">This Week</div>
                                </div>
                                <div class="bg-gray-50 p-3 rounded">
                                    <div class="text-2xl font-bold text-green-600" id="violations-month">0</div>
                                    <div class="text-xs text-gray-600">This Month</div>
                                </div>
                            </div>
                        </div>

                        <!-- Access Control Metrics -->
                        <div class="mb-6">
                            <h4 class="text-md font-medium text-gray-800 mb-2">Access Control</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Active Sessions</span>
                                    <span class="font-medium" id="active-sessions">12</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Failed Logins (24h)</span>
                                    <span class="font-medium" id="failed-logins">3</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Unauthorized Attempts</span>
                                    <span class="font-medium" id="unauthorized-attempts">0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Audit Logging Metrics -->
                        <div>
                            <h4 class="text-md font-medium text-gray-800 mb-2">Audit Logging</h4>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Events Today</span>
                                    <span class="font-medium" id="audit-events-today">1,247</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Storage Usage</span>
                                    <span class="font-medium" id="audit-storage">2.4 GB</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Status</span>
                                    <span class="font-medium text-green-600" id="audit-status">Active</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Execution Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-vial mr-2"></i>Security Test Status
                        </h3>

                        <!-- Last Test Run -->
                        <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-green-800">Last Test Run</span>
                                <span class="text-xs text-green-600" id="last-test-time">30 minutes ago</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-green-700">Duration: <span id="test-duration">8m 42s</span></span>
                                <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">PASSED</span>
                            </div>
                        </div>

                        <!-- Test Suites -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="text-sm font-medium">Privacy Protection</span>
                                    <div class="text-xs text-gray-600">15/15 tests passed</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">✓ PASSED</span>
                                    <div class="text-xs text-gray-600 mt-1">94% coverage</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="text-sm font-medium">Access Control</span>
                                    <div class="text-xs text-gray-600">22/22 tests passed</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">✓ PASSED</span>
                                    <div class="text-xs text-gray-600 mt-1">89% coverage</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="text-sm font-medium">API Security</span>
                                    <div class="text-xs text-gray-600">18/18 tests passed</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">✓ PASSED</span>
                                    <div class="text-xs text-gray-600 mt-1">91% coverage</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded">
                                <div>
                                    <span class="text-sm font-medium">Compliance</span>
                                    <div class="text-xs text-gray-600">12/12 tests passed</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">✓ PASSED</span>
                                    <div class="text-xs text-gray-600 mt-1">100% coverage</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privacy Alerts and Compliance Status -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                <!-- Recent Privacy Alerts -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Recent Privacy Alerts
                        </h3>
                        
                        <div id="privacy-alerts">
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                                <p class="text-sm">No privacy violations detected</p>
                                <p class="text-xs">System operating within normal parameters</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance Status -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">
                            <i class="fas fa-certificate mr-2"></i>Compliance Status
                        </h3>
                        
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 bg-green-50 rounded border border-green-200">
                                <div>
                                    <span class="text-sm font-medium text-green-800">Philippine Data Privacy Act</span>
                                    <div class="text-xs text-green-600">Last assessed 30 days ago</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-green-200 text-green-800 px-2 py-1 rounded">COMPLIANT</span>
                                    <div class="text-xs text-green-600 mt-1">98% score</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-blue-50 rounded border border-blue-200">
                                <div>
                                    <span class="text-sm font-medium text-blue-800">CSC Requirements</span>
                                    <div class="text-xs text-blue-600">Last audit 45 days ago</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-blue-200 text-blue-800 px-2 py-1 rounded">COMPLIANT</span>
                                    <div class="text-xs text-blue-600 mt-1">95% score</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between p-3 bg-purple-50 rounded border border-purple-200">
                                <div>
                                    <span class="text-sm font-medium text-purple-800">Data Subject Rights</span>
                                    <div class="text-xs text-purple-600">Average response: 3.2 days</div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xs bg-purple-200 text-purple-800 px-2 py-1 rounded">100%</span>
                                    <div class="text-xs text-purple-600 mt-1">Response rate</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Security Health Report -->
            <div class="mt-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">
                                <i class="fas fa-clipboard-check mr-2"></i>Security Health Report
                            </h3>
                            <button id="generate-report-btn" class="px-4 py-2 bg-blue-500 text-white text-sm rounded hover:bg-blue-600">
                                <i class="fas fa-download mr-1"></i>Generate Report
                            </button>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">HEALTHY</div>
                                <div class="text-sm text-gray-600">Overall Status</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600 mb-2">LOW</div>
                                <div class="text-sm text-gray-600">Risk Level</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-blue-600 mb-2">0</div>
                                <div class="text-sm text-gray-600">Immediate Actions</div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 bg-gray-50 rounded">
                            <h4 class="text-sm font-medium text-gray-800 mb-2">Recommendations</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• Continue regular security training for all staff</li>
                                <li>• Monitor audit logs daily for unusual patterns</li>
                                <li>• Update security documentation quarterly</li>
                                <li>• Plan for multi-factor authentication implementation</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Real-time Updates -->
    @push('scripts')
    <script>
        // Auto-refresh functionality
        let refreshInterval;
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initial load
            updateDashboard();
            
            // Auto-refresh every 30 seconds
            refreshInterval = setInterval(updateDashboard, 30000);
            
            // Manual refresh button
            document.getElementById('refresh-btn').addEventListener('click', function() {
                updateDashboard();
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh';
                }, 1000);
            });
            
            // Generate report button
            document.getElementById('generate-report-btn').addEventListener('click', generateHealthReport);
        });

        async function updateDashboard() {
            try {
                // Update metrics
                const metricsResponse = await fetch('/security-monitoring/metrics');
                const metricsData = await metricsResponse.json();
                
                if (metricsData.success) {
                    updateMetricsDisplay(metricsData.data);
                }

                // Update test status
                const testResponse = await fetch('/security-monitoring/test-status');
                const testData = await testResponse.json();
                
                if (testData.success) {
                    updateTestStatusDisplay(testData.data);
                }

                // Update alerts
                const alertsResponse = await fetch('/security-monitoring/privacy-alerts');
                const alertsData = await alertsResponse.json();
                
                if (alertsData.success) {
                    updateAlertsDisplay(alertsData.data);
                }

                // Update timestamp
                document.getElementById('last-updated').textContent = new Date().toLocaleString();
                
            } catch (error) {
                console.error('Dashboard update failed:', error);
            }
        }

        function updateMetricsDisplay(metrics) {
            // Privacy protection metrics
            document.getElementById('violations-today').textContent = metrics.privacy_protection.violations_today;
            document.getElementById('violations-week').textContent = metrics.privacy_protection.violations_week;
            document.getElementById('violations-month').textContent = metrics.privacy_protection.violations_month;
            
            // Access control metrics
            document.getElementById('active-sessions').textContent = metrics.access_control.active_sessions;
            document.getElementById('failed-logins').textContent = metrics.access_control.failed_logins_today;
            document.getElementById('unauthorized-attempts').textContent = metrics.access_control.unauthorized_attempts_today;
            
            // Audit logging metrics
            document.getElementById('audit-events-today').textContent = metrics.audit_logging.events_today.toLocaleString();
            document.getElementById('audit-storage').textContent = metrics.audit_logging.storage_usage;
            document.getElementById('audit-status').textContent = metrics.audit_logging.status.charAt(0).toUpperCase() + metrics.audit_logging.status.slice(1);
        }

        function updateTestStatusDisplay(testStatus) {
            const lastRun = testStatus.last_run;
            document.getElementById('last-test-time').textContent = formatTimeAgo(lastRun.timestamp);
            document.getElementById('test-duration').textContent = lastRun.duration;
        }

        function updateAlertsDisplay(alertsData) {
            const alertsContainer = document.getElementById('privacy-alerts');
            
            if (alertsData.alerts.length === 0) {
                alertsContainer.innerHTML = `
                    <div class="text-center py-8 text-gray-500">
                        <i class="fas fa-check-circle text-4xl text-green-500 mb-2"></i>
                        <p class="text-sm">No privacy violations detected</p>
                        <p class="text-xs">System operating within normal parameters</p>
                    </div>
                `;
            } else {
                // Display recent alerts
                alertsContainer.innerHTML = alertsData.alerts.slice(0, 5).map(alert => `
                    <div class="p-3 mb-2 border border-red-200 bg-red-50 rounded">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-sm font-medium text-red-800">${alert.violation_type}</span>
                                <div class="text-xs text-red-600">${alert.user_name} - ${formatTimeAgo(alert.timestamp)}</div>
                            </div>
                            <span class="text-xs bg-red-200 text-red-800 px-2 py-1 rounded">${alert.severity}</span>
                        </div>
                    </div>
                `).join('');
            }
        }

        async function generateHealthReport() {
            const btn = document.getElementById('generate-report-btn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generating...';
            btn.disabled = true;
            
            try {
                const response = await fetch('/security-monitoring/health-report');
                const data = await response.json();
                
                if (data.success) {
                    // Download or display the report
                    const blob = new Blob([JSON.stringify(data.data, null, 2)], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `security-health-report-${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            } catch (error) {
                console.error('Report generation failed:', error);
                alert('Failed to generate report. Please try again.');
            } finally {
                btn.innerHTML = '<i class="fas fa-download mr-1"></i>Generate Report';
                btn.disabled = false;
            }
        }

        function formatTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000);
            
            if (diff < 60) return `${diff}s ago`;
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        }

        // Cleanup interval on page unload
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });
    </script>
    @endpush

    @push('styles')
    <style>
        .refresh-animation {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-healthy { background-color: #10b981; }
        .status-warning { background-color: #f59e0b; }
        .status-critical { background-color: #ef4444; }
    </style>
    @endpush
</x-app-layout>