// Chart.js plugin to persist dataset visibility per chart (by widget heading) in localStorage
// Enhanced: user-scoped key + defensive guards
( function ()
{
    if ( typeof window === 'undefined' || typeof window.filamentChartJsPlugins === 'undefined' )
    {
        window.filamentChartJsPlugins = window.filamentChartJsPlugins || [];
    }
    // Try to derive a user id from common Filament body attributes or meta tags
    let userId = null;
    try
    {
        const body = document.body;
        userId = body?.getAttribute( 'data-user-id' ) ||
            document.querySelector( 'meta[name="current-user-id"]' )?.getAttribute( 'content' ) || null;
    } catch ( e ) { }
    const STORAGE_KEY = 'filament.chart.dataset.visibility.v1' + ( userId ? ( '.u.' + userId ) : '' );

    function loadState ()
    {
        try { return JSON.parse( localStorage.getItem( STORAGE_KEY ) || '{}' ); } catch ( e ) { return {}; }
    }
    function saveState ( state )
    {
        try { localStorage.setItem( STORAGE_KEY, JSON.stringify( state ) ); } catch ( e ) { }
    }

    const state = loadState();

    const plugin = {
        id: 'datasetVisibilityPersistence',
        beforeInit ( chart )
        {
            try
            {
                const heading = chart?.canvas?.closest( '[data-chart-heading]' )?.getAttribute( 'data-chart-heading' );
                if ( !heading ) return;
                chart.$headingKey = heading;
                const saved = state[ heading ];
                if ( saved )
                {
                    const baseGen = ( Chart.overrides?.[ chart.config.type ]?.plugins?.legend?.labels?.generateLabels ) || Chart.defaults.plugins.legend.labels.generateLabels;
                    if ( typeof baseGen === 'function' )
                    {
                        chart.options.plugins.legend.labels.generateLabels = ( ( orig ) => function ( ch )
                        {
                            let labels = [];
                            try { labels = orig( ch ); } catch ( e ) { return orig( ch ); }
                            labels.forEach( ( l, i ) => { if ( saved[ i ] === false ) { try { ch.hide( i ); } catch ( e ) { } } } );
                            return labels;
                        } )( baseGen );
                    }
                }
            } catch ( e ) { /* swallow */ }
        },
        afterEvent ( chart, args )
        {
            try
            {
                if ( !args || !args.event ) return;
                const event = args.event;
                if ( event.type === 'click' )
                {
                    setTimeout( () =>
                    {
                        if ( !chart.$headingKey ) return;
                        const vis = [];
                        chart.data.datasets.forEach( ( ds, idx ) =>
                        {
                            try
                            {
                                vis[ idx ] = !chart.isDatasetVisible || chart.isDatasetVisible( idx );
                            } catch ( e ) { vis[ idx ] = true; }
                        } );
                        state[ chart.$headingKey ] = vis;
                        saveState( state );
                    }, 0 );
                }
            } catch ( e ) { /* swallow */ }
        }
    };

    if ( !window.filamentChartJsPlugins.find( p => p.id === 'datasetVisibilityPersistence' ) )
    {
        window.filamentChartJsPlugins.push( plugin );
    }
} )();
