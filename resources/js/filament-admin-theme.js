// Admin runtime theming and UI polish
// - Chart.js color palette sync with dark mode
// - Minor density tweaks via CSS classes if needed

( function ()
{
    const applyChartTheme = () =>
    {
        if ( !window.Chart ) return;
        const dark = document.documentElement.classList.contains( 'dark' );
        const text = dark ? '#e5e7eb' : '#111827';
        const grid = dark ? 'rgba(255,255,255,0.08)' : 'rgba(17,24,39,0.08)';
        const border = dark ? 'rgba(255,255,255,0.15)' : 'rgba(17,24,39,0.15)';

        // Set sane defaults
        window.Chart.defaults.color = text;
        window.Chart.defaults.borderColor = border;

        const scales = window.Chart.defaults.scales || {};
        [ 'x', 'y' ].forEach( ( axis ) =>
        {
            scales[ axis ] = scales[ axis ] || {};
            scales[ axis ].grid = scales[ axis ].grid || {};
            scales[ axis ].grid.color = grid;
            scales[ axis ].ticks = scales[ axis ].ticks || {};
            scales[ axis ].ticks.color = text;
        } );
        window.Chart.defaults.scales = scales;
    };

    const onReady = () =>
    {
        applyChartTheme();

        // Observe dark mode toggles and re-apply chart theme
        const observer = new MutationObserver( () => applyChartTheme() );
        observer.observe( document.documentElement, { attributes: true, attributeFilter: [ 'class' ] } );

        // re-apply when Filament swaps pages
        document.addEventListener( 'livewire:navigated', () => setTimeout( applyChartTheme, 0 ) );
    };

    if ( document.readyState === 'loading' )
    {
        document.addEventListener( 'DOMContentLoaded', onReady );
    } else
    {
        onReady();
    }
} )();
