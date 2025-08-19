@php
	$overall = $healthStatus['overall'] ?? ($healthStatus['status'] ?? 'unknown');
	$timestamp = $healthStatus['timestamp'] ?? now()->toISOString();
	$checks = $healthStatus['checks'] ?? [];
	if (empty($checks) && isset($healthStatus['issues'])) {
		// Backward compatibility if payload used 'issues' instead of 'checks'.
		$checks = $healthStatus['issues'];
	}
@endphp

<div style="font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', 'Apple Color Emoji', 'Segoe UI Emoji'; line-height: 1.5; color: #111827;">
	<h1 style="font-size: 20px; margin: 0 0 12px;">Health Alert</h1>

	<p style="margin: 0 0 12px;">A health alert has been triggered in your 1000proxy application.</p>

	<p style="margin: 0 0 8px;"><strong>Status:</strong> {{ strtoupper((string) $overall) }}</p>
	<p style="margin: 0 0 16px;"><strong>Time:</strong> {{ $timestamp }}</p>

	@if(!empty($checks))
		<div style="background: #F9FAFB; border: 1px solid #E5E7EB; border-radius: 6px; padding: 12px; margin: 0 0 16px;">
			<p style="margin: 0 0 8px;"><strong>Detected Issues:</strong></p>
			<ul style="margin: 0; padding-left: 18px;">
				@foreach($checks as $area => $issues)
					@php $areaIssues = is_array($issues) ? $issues : [$issues]; @endphp
					<li style="margin: 0 0 6px;">
						<strong>{{ ucfirst(str_replace('_', ' ', (string) $area)) }}:</strong>
						<ul style="margin: 6px 0 0; padding-left: 18px;">
							@foreach($areaIssues as $issue)
								<li>{{ is_string($issue) ? $issue : json_encode($issue) }}</li>
							@endforeach
						</ul>
					</li>
				@endforeach
			</ul>
		</div>
	@endif

	@isset($alertMessage)
		<p style="margin: 0 0 12px;"><strong>Message:</strong> {{ is_string($alertMessage) ? $alertMessage : '' }}</p>
	@endisset

	@isset($details)
		<div style="margin: 0 0 12px; white-space: pre-wrap;">{{ is_string($details) ? $details : '' }}</div>
	@endisset

	<p style="margin: 16px 0 0;">Thanks,<br>{{ config('app.name') }}</p>
</div>
