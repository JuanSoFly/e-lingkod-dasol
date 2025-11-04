import Chart from 'chart.js/auto';

const complianceData = window.OPCR_COMPLIANCE_DATA || {};
const complianceFilters = window.OPCR_COMPLIANCE_FILTERS || {};

document.addEventListener('DOMContentLoaded', () => {
    initializeComplianceCharts();
    registerComplianceActions();
});

function initializeComplianceCharts() {
    renderComplianceTrendChart();
    renderDeadlineAdherenceChart();
}

function renderComplianceTrendChart() {
    const canvas = document.getElementById('complianceTrendChart');
    const emptyState = document.getElementById('complianceTrendEmptyState');

    if (!canvas) {
        return;
    }

    const officeData = complianceData.office_compliance || [];

    if (!officeData.length) {
        toggleChartState(canvas, emptyState, false);
        return;
    }

    const labels = officeData.map((office) => office.name || 'Office');
    const complianceRates = officeData.map((office) => Number(office.compliance_rate || 0));
    const onTimeRates = officeData.map((office) => Number(office.on_time_rate || 0));
    const completenessRates = officeData.map((office) => Number(office.completeness_rate || 0));

    new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: 'Compliance Rate',
                    data: complianceRates,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.15)',
                    tension: 0.35,
                    borderWidth: 2,
                    fill: true,
                },
                {
                    label: 'On-Time Submission %',
                    data: onTimeRates,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    tension: 0.35,
                    borderWidth: 2,
                    fill: true,
                },
                {
                    label: 'Documentation Completeness %',
                    data: completenessRates,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.35,
                    borderWidth: 2,
                    borderDash: [6, 4],
                    fill: false,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${context.parsed.y}%`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                    grid: {
                        color: '#e5e7eb',
                    },
                },
                x: {
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });

    toggleChartState(canvas, emptyState, true);
}

function renderDeadlineAdherenceChart() {
    const canvas = document.getElementById('deadlineAdherenceChart');
    const emptyState = document.getElementById('deadlineAdherenceEmptyState');

    if (!canvas) {
        return;
    }

    const officeData = complianceData.office_compliance || [];

    if (!officeData.length) {
        toggleChartState(canvas, emptyState, false);
        return;
    }

    const labels = officeData.map((office) => office.name || 'Office');
    const onTimeRates = officeData.map((office) => Number(office.on_time_rate || 0));
    const overdueCounts = officeData.map((office) => Number(office.overdue_count || 0));

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'On-Time Submission %',
                    data: onTimeRates,
                    backgroundColor: 'rgba(16, 185, 129, 0.75)',
                    borderRadius: 6,
                    maxBarThickness: 32,
                    yAxisID: 'y',
                },
                {
                    label: 'Overdue Workflows',
                    data: overdueCounts,
                    backgroundColor: 'rgba(248, 113, 113, 0.85)',
                    borderRadius: 6,
                    maxBarThickness: 32,
                    yAxisID: 'y1',
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                    },
                },
                tooltip: {
                    callbacks: {
                        label: (context) => {
                            const value = context.parsed.y;
                            const label = context.dataset.label;
                            return label.includes('%') ? `${label}: ${value}%` : `${label}: ${value}`;
                        },
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    position: 'left',
                    grid: {
                        color: '#e5e7eb',
                    },
                    ticks: {
                        callback: (value) => `${value}%`,
                    },
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    grid: {
                        drawOnChartArea: false,
                    },
                },
                x: {
                    stacked: false,
                    grid: {
                        display: false,
                    },
                },
            },
        },
    });

    toggleChartState(canvas, emptyState, true);
}

function registerComplianceActions() {
    const exportButton = document.getElementById('complianceExportButton');
    const reminderButton = document.getElementById('complianceReminderButton');

    if (exportButton) {
        exportButton.addEventListener('click', () => handleExportClick(exportButton));
    }

    if (reminderButton) {
        reminderButton.addEventListener('click', () => handleReminderClick(reminderButton));
    }
}

function handleExportClick(button) {
    const exportUrl = button.dataset.exportUrl;

    if (!exportUrl) {
        showToast('Export endpoint not configured.', 'error');
        return;
    }

    const params = new URLSearchParams({
        format: 'excel',
    });

    if (complianceFilters.period_id) {
        params.append('period_id', complianceFilters.period_id);
    }

    if (complianceFilters.office_id) {
        params.append('office_id', complianceFilters.office_id);
    }

    const downloadUrl = `${exportUrl}?${params.toString()}`;
    window.open(downloadUrl, '_blank');
    showToast('Export request submitted. Your download will begin shortly.', 'success');
}

async function handleReminderClick(button) {
    const reminderUrl = button.dataset.reminderUrl;

    if (!reminderUrl) {
        showToast('Reminder endpoint not configured.', 'error');
        return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!token) {
        showToast('Missing CSRF token. Unable to send reminders.', 'error');
        return;
    }

    button.disabled = true;
    button.classList.add('opacity-60', 'cursor-not-allowed');

    try {
        const response = await fetch(reminderUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                period_id: complianceFilters.period_id || null,
                office_id: complianceFilters.office_id || null,
            }),
        });

        const payload = await response.json();

        if (!response.ok || !payload.success) {
            const errorMessage = payload.message || 'No workflows require reminders at this time.';
            showToast(errorMessage, 'warning');
            return;
        }

        showToast(payload.message ?? 'Reminders queued successfully.', 'success');
    } catch (error) {
        showToast('Failed to queue reminders. Please try again.', 'error');
        console.error('Compliance reminder error', error);
    } finally {
        button.disabled = false;
        button.classList.remove('opacity-60', 'cursor-not-allowed');
    }
}

function toggleChartState(canvas, emptyState, hasData) {
    if (hasData) {
        canvas.classList.remove('hidden');
        if (emptyState) {
            emptyState.classList.add('hidden');
        }
    } else {
        canvas.classList.add('hidden');
        if (emptyState) {
            emptyState.classList.remove('hidden');
        }
    }
}

function showToast(message, variant = 'success') {
    const container = document.createElement('div');

    const variantStyles = {
        success: 'bg-green-600 text-white',
        warning: 'bg-amber-500 text-white',
        error: 'bg-red-600 text-white',
    };

    container.className = `fixed bottom-6 right-6 px-4 py-3 rounded-lg shadow-lg text-sm transition duration-300 opacity-0 translate-y-2 ${variantStyles[variant] || variantStyles.success}`;
    container.textContent = message;

    document.body.appendChild(container);

    requestAnimationFrame(() => {
        container.classList.remove('opacity-0', 'translate-y-2');
    });

    setTimeout(() => {
        container.classList.add('opacity-0', 'translate-y-2');
        container.addEventListener('transitionend', () => container.remove(), { once: true });
    }, 3200);
}
