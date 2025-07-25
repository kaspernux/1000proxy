@extends('layouts.admin')

@section('title', 'Analytics Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    @livewire('admin.analytics-dashboard')
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.css">
<style>
    .analytics-dashboard {
        animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .kpi-card {
        transition: all 0.3s ease;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .insight-card {
        border-left: 4px solid;
    }

    .insight-card.high-impact {
        border-left-color: #EF4444;
    }

    .insight-card.medium-impact {
        border-left-color: #F59E0B;
    }

    .insight-card.low-impact {
        border-left-color: #10B981;
    }

    .loading-overlay {
        backdrop-filter: blur(2px);
    }

    .export-menu {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .metric-selector, .date-range-selector {
        transition: all 0.2s ease;
    }

    .metric-selector:focus, .date-range-selector:focus {
        transform: scale(1.02);
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
    // Chart.js defaults
    Chart.defaults.color = document.documentElement.classList.contains('dark') ? '#E5E7EB' : '#374151';
    Chart.defaults.borderColor = document.documentElement.classList.contains('dark') ? '#374151' : '#E5E7EB';

    // Global chart options
    Chart.defaults.plugins.legend.display = true;
    Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
    Chart.defaults.plugins.tooltip.titleColor = '#FFFFFF';
    Chart.defaults.plugins.tooltip.bodyColor = '#FFFFFF';
    Chart.defaults.plugins.tooltip.cornerRadius = 8;
    Chart.defaults.plugins.tooltip.displayColors = false;

    // Auto-refresh functionality
    let autoRefreshInterval;

    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltips = document.querySelectorAll('[data-tooltip]');
        tooltips.forEach(tooltip => {
            tooltip.addEventListener('mouseenter', function() {
                // Add tooltip implementation here
            });
        });

        // Initialize keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'r':
                        e.preventDefault();
                        @this.call('refreshData');
                        break;
                    case 'e':
                        e.preventDefault();
                        document.querySelector('[x-data] button').click();
                        break;
                }
            }
        });
    });

    // Dark mode support for charts
    function updateChartsForTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        Chart.defaults.color = isDark ? '#E5E7EB' : '#374151';
        Chart.defaults.borderColor = isDark ? '#374151' : '#E5E7EB';

        // Update existing charts
        Chart.instances.forEach(chart => {
            chart.options.plugins.legend.labels.color = Chart.defaults.color;
            chart.update();
        });
    }

    // Listen for theme changes
    const observer = new MutationObserver(updateChartsForTheme);
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['class']
    });

    // Custom chart animations
    const animationOptions = {
        animation: {
            duration: 1000,
            easing: 'easeInOutQuart'
        },
        hover: {
            animationDuration: 200
        },
        responsiveAnimationDuration: 0
    };

    // Merge animation options with chart configs
    window.chartDefaults = {
        ...animationOptions,
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        }
    };
</script>
@endpush
