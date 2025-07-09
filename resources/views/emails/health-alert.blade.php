<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>1000proxy System Health Alert</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status-critical {
            background: #dc3545;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }
        .status-warning {
            background: #ffc107;
            color: #212529;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            font-weight: bold;
        }
        .check-item {
            background: #f8f9fa;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .check-item.critical {
            border-left-color: #dc3545;
        }
        .check-item.warning {
            border-left-color: #ffc107;
        }
        .issues-list {
            margin: 5px 0;
            padding-left: 20px;
        }
        .footer {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚨 1000proxy System Health Alert</h1>
        <p><strong>Timestamp:</strong> {{ $healthStatus['timestamp'] }}</p>
        <p><strong>Overall Status:</strong> 
            @if($healthStatus['overall'] === 'critical')
                <span class="status-critical">CRITICAL</span>
            @elseif($healthStatus['overall'] === 'warning')
                <span class="status-warning">WARNING</span>
            @else
                <span style="color: #28a745; font-weight: bold;">HEALTHY</span>
            @endif
        </p>
    </div>

    <div class="content">
        <h2>System Health Checks</h2>
        
        @foreach($healthStatus['checks'] as $checkName => $check)
            <div class="check-item {{ $check['status'] === 'critical' ? 'critical' : ($check['status'] === 'warning' ? 'warning' : '') }}">
                <h3>{{ ucwords(str_replace('_', ' ', $checkName)) }}</h3>
                <p><strong>Status:</strong> {{ strtoupper($check['status']) }}</p>
                
                @if(!empty($check['issues']))
                    <p><strong>Issues:</strong></p>
                    <ul class="issues-list">
                        @foreach($check['issues'] as $issue)
                            <li>{{ $issue }}</li>
                        @endforeach
                    </ul>
                @endif
                
                @if(isset($check['response_time']))
                    <p><strong>Response Time:</strong> {{ $check['response_time'] }}</p>
                @endif
                
                @if(isset($check['active_servers']))
                    <p><strong>Active Servers:</strong> {{ $check['active_servers'] }}</p>
                @endif
                
                @if(isset($check['hit_rate']))
                    <p><strong>Cache Hit Rate:</strong> {{ $check['hit_rate'] }}</p>
                @endif
                
                @if(isset($check['memory_usage']))
                    <p><strong>Memory Usage:</strong> {{ $check['memory_usage'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    @if($healthStatus['overall'] === 'critical')
        <div class="status-critical">
            <h3>⚠️ IMMEDIATE ACTION REQUIRED</h3>
            <p>The system is in a critical state and requires immediate attention. Please check the issues listed above and take corrective action.</p>
        </div>
    @elseif($healthStatus['overall'] === 'warning')
        <div class="status-warning">
            <h3>⚠️ ATTENTION NEEDED</h3>
            <p>The system has some issues that need attention. Please review the warnings and address them when possible.</p>
        </div>
    @endif

    <div class="footer">
        <h4>Recommended Actions:</h4>
        <ul>
            <li>Check application logs: <code>tail -f storage/logs/laravel.log</code></li>
            <li>Monitor system resources: <code>htop</code></li>
            <li>Check queue status: <code>php artisan horizon:status</code></li>
            <li>Restart services if needed: <code>sudo systemctl restart nginx php8.2-fpm redis-server</code></li>
            <li>Run health check manually: <code>php artisan system:health-check</code></li>
        </ul>
        
        <p><strong>System URLs:</strong></p>
        <ul>
            <li>Admin Panel: <a href="{{ config('app.url') }}/admin">{{ config('app.url') }}/admin</a></li>
            <li>Queue Monitoring: <a href="{{ config('app.url') }}/admin/horizon">{{ config('app.url') }}/admin/horizon</a></li>
        </ul>
        
        <p><em>This is an automated alert from the 1000proxy monitoring system.</em></p>
    </div>
</body>
</html>
