<style data-filament-custom>
/*
  Shared, SCOPED custom CSS for both Filament panels, keeping native Filament v4 theme.
  Replace this with your previously used custom rules, but scope them to panel roots to avoid bleed.

  IMPORTANT:
  - Only target inside [data-panel-id="admin"] and/or [data-panel-id="customer"].
  - Avoid global selectors that affect non-Filament pages.
  - Prefer low-specificity rules and CSS variables; avoid !important.
*/

/* Example: carry over your existing custom theme tweaks here */

/* Scope to both panels */
[data-panel-id="admin"],
[data-panel-id="customer"] {
  /* Example variable you can reuse in child rules */
  --panel-card-radius: 0.75rem;
  --panel-section-gap: 1.25rem;
  --panel-section-gap-md: 1.5rem;
}

/* Cards / sections rounding */
[data-panel-id="admin"] .fi-card,
[data-panel-id="admin"] .fi-section,
[data-panel-id="admin"] .fi-widget,
[data-panel-id="customer"] .fi-card,
[data-panel-id="customer"] .fi-section,
[data-panel-id="customer"] .fi-widget {
  border-radius: var(--panel-card-radius);
}

/* Keep modals interactive — do not disable pointer-events (prevent prior regression) */
/* If you previously had pointer-events overrides, do NOT reintroduce them. */

/* Dialog and overlay polish */
[data-panel-id="admin"] .fi-modal-overlay,
[data-panel-id="customer"] .fi-modal-overlay {
  backdrop-filter: blur(2px);
}

/* Table hover contrast */
[data-panel-id="admin"] .fi-ta table tbody tr:hover,
[data-panel-id="customer"] .fi-ta table tbody tr:hover {
  background-color: color-mix(in oklab, Canvas, black 3%);
}

/* Top bar tweaks (minimal) */
[data-panel-id="admin"] .fi-topbar,
[data-panel-id="customer"] .fi-topbar {
  --_bg: color-mix(in oklab, var(--gray-1, #fff), black 3%);
  background-color: var(--_bg);
}

/* Respect dark mode — Filament sets data-color-scheme */
@media (prefers-color-scheme: dark) {
  [data-panel-id="admin"][data-color-scheme="dark"] .fi-card,
  [data-panel-id="customer"][data-color-scheme="dark"] .fi-card {
    box-shadow: 0 0 0 1px color-mix(in oklab, white 8%, transparent);
  }
}

/* ------------------------------- */
/* Customer dashboard refinements  */
/* ------------------------------- */
[data-panel-id="customer"] .fi-dashboard-page .fi-section-content-ctn {
  padding-left: 1rem;
  padding-right: 1rem;
}
@media (min-width: 768px) {
  [data-panel-id="customer"] .fi-dashboard-page .fi-section-content-ctn {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

[data-panel-id="customer"] .fi-dashboard-page .fi-section-header {
  margin-bottom: 1rem;
  padding-bottom: .75rem;
  border-bottom: 1px solid color-mix(in oklab, CanvasText 8%, transparent);
}
[data-panel-id="customer"][data-color-scheme="dark"] .fi-dashboard-page .fi-section-header {
  border-bottom-color: color-mix(in oklab, white 10%, transparent);
}

/* Quick action tiles polish */
[data-panel-id="customer"] .fi-dashboard-page .fi-section.shadow-lg {
  border-radius: var(--panel-card-radius);
  box-shadow: 0 12px 24px -8px rgba(0,0,0,.15);
}

/* Ensure section inner padding is comfortable */
[data-panel-id="customer"] .fi-dashboard-page .fi-section .p-4 { padding: 1rem; }
@media (min-width: 768px) {
  [data-panel-id="customer"] .fi-dashboard-page .fi-section .p-4 { padding: 1.125rem; }
}

/* Header buttons spacing */
[data-panel-id="customer"] .fi-dashboard-page .fi-section-header .fi-btn { margin-left: .25rem; }

/* ---------------------------- */
/* Admin dashboard refinements  */
/* ---------------------------- */
[data-panel-id="admin"] .fi-dashboard-page .fi-section-content-ctn {
  padding-left: 1rem;
  padding-right: 1rem;
}
@media (min-width: 768px) {
  [data-panel-id="admin"] .fi-dashboard-page .fi-section-content-ctn {
    padding-left: 1.5rem;
    padding-right: 1.5rem;
  }
}

[data-panel-id="admin"] .fi-dashboard-page .fi-section-header {
  margin-bottom: 1rem;
  padding-bottom: .75rem;
  border-bottom: 1px solid color-mix(in oklab, CanvasText 8%, transparent);
}
[data-panel-id="admin"][data-color-scheme="dark"] .fi-dashboard-page .fi-section-header {
  border-bottom-color: color-mix(in oklab, white 10%, transparent);
}

[data-panel-id="admin"] .fi-dashboard-page .fi-section.shadow-lg {
  border-radius: var(--panel-card-radius);
  box-shadow: 0 12px 24px -8px rgba(0,0,0,.15);
}
@media (min-width: 768px) {
  [data-panel-id="admin"] .fi-dashboard-page .fi-section .p-4 { padding: 1.125rem; }
}
[data-panel-id="admin"] .fi-dashboard-page .fi-section .p-4 { padding: 1rem; }

[data-panel-id="admin"] .fi-dashboard-page .fi-section-header .fi-btn { margin-left: .25rem; }

/* You can paste more of your previous custom rules below, but ensure they are scoped: */
/* [data-panel-id="admin"] .your-class { ... } */
/* [data-panel-id="customer"] .your-class { ... } */

/* Themed accents for StatsOverview tiles via extraAttributes hooks */
[data-panel-id="customer"] .kp-stat,
[data-panel-id="admin"] .kp-stat {
  border-radius: var(--panel-card-radius);
  overflow: hidden;
}

/* Color accents — keep subtle to avoid fighting theme */
[data-panel-id="customer"] .kp-stat--primary,
[data-panel-id="admin"] .kp-stat--primary { box-shadow: inset 0 0 0 999px color-mix(in oklab, var(--color-primary-500, #3b82f6) 6%, transparent); }
[data-panel-id="customer"] .kp-stat--success,
[data-panel-id="admin"] .kp-stat--success { box-shadow: inset 0 0 0 999px color-mix(in oklab, var(--color-success-500, #22c55e) 6%, transparent); }
[data-panel-id="customer"] .kp-stat--info,
[data-panel-id="admin"] .kp-stat--info { box-shadow: inset 0 0 0 999px color-mix(in oklab, #38bdf8 6%, transparent); }
[data-panel-id="customer"] .kp-stat--warning,
[data-panel-id="admin"] .kp-stat--warning { box-shadow: inset 0 0 0 999px color-mix(in oklab, #f59e0b 6%, transparent); }
[data-panel-id="customer"] .kp-stat--danger,
[data-panel-id="admin"] .kp-stat--danger { box-shadow: inset 0 0 0 999px color-mix(in oklab, #ef4444 6%, transparent); }
</style>
