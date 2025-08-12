(function( $ ) {
    'use strict';

    $( function() {
        if ( typeof CM_REPORTS_DATA !== 'undefined' && $( '#cm-revenue-chart' ).length ) {
            var ctx = document.getElementById( 'cm-revenue-chart' ).getContext( '2d' );
            new Chart( ctx, {
                type: 'line',
                data: {
                    labels: CM_REPORTS_DATA.labels,
                    datasets: [ {
                        label: 'Revenue',
                        data: CM_REPORTS_DATA.data,
                        borderColor: 'rgba(75,192,192,1)',
                        backgroundColor: 'rgba(75,192,192,0.2)',
                    } ]
                },
            } );
        }
    } );
})( jQuery );