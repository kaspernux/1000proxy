@push('styles')
<style data-customer-panel>
/*
  SCOPED customer panel tweaks (keeps native Filament v4 theme):
  - Only target within the customer panel root to avoid bleeding into admin or public pages.
  - Use low-specificity utility-ish rules; avoid overriding core variables unless necessary.
*/

/* Limit scope to this panel's root. Filament wraps panels with [data-theme] + [data-panel-id] */
[data-panel-id="customer"] {
  /* Subtle card elevation & rounding adjustments */
  --customer-card-radius: 0.75rem;
}

[data-panel-id="customer"] .fi-section,
[data-panel-id="customer"] .fi-widget,
[data-panel-id="customer"] .fi-card {
  border-radius: var(--customer-card-radius);
}

/* Topbar quick actions polish */
[data-panel-id="customer"] .hs-transition-opacity { transition: opacity .2s ease, background-color .2s ease; }
[data-panel-id="customer"] .hs-transition-opacity:hover { opacity: .95; }

/* Improve table row hover contrast slightly, without fighting Tailwind classes */
[data-panel-id="customer"] .fi-ta table tbody tr:hover { background-color: color-mix(in oklab, Canvas, black 3%); }

/* Make notifications panel a touch wider on md+ to avoid cramped content */
@media (min-width: 768px) {
  [data-panel-id="customer"] .fi-notifications-panel { width: min(34rem, 92vw); }
}

/* Respect dark mode via [data-color-scheme] attribute set by Filament */
[data-panel-id="customer"][data-color-scheme="dark"] .fi-card {
  box-shadow: 0 0 0 1px color-mix(in oklab, white 5%, transparent);
}
</style>
@endpush
