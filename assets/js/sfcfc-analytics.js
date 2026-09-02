( function ( $ ) {
	'use strict';

	function loadAnalytics() {
		var $insights = $( '#sfcfc-insights' );

		if ( ! $insights.length ) {
			return;
		}

		$.post( sfcfc_ajax.ajaxUrl, {
			action: 'sfcfc_get_analytics',
			nonce: sfcfc_ajax.nonce
		} ).done( function ( response ) {
			if ( ! response.success ) {
				return;
			}

			var percentFields = [ 'cached_pct' ];

			$.each( response.data, function ( field, value ) {
				if ( 'hourly' === field ) {
					return;
				}

				$insights.find( '[data-field="' + field + '"]' ).text(
					percentFields.indexOf( field ) > -1 ? value + '%' : value
				);
			} );

			renderRequestsChart( response.data.hourly || [] );
		} );
	}

	var sfcfcRequestsChart = null;

	function renderRequestsChart( hourly ) {
		var $canvas = $( '#sfcfc-requests-chart' );

		if ( ! $canvas.length || 'undefined' === typeof Chart || ! hourly.length ) {
			return;
		}

		if ( sfcfcRequestsChart ) {
			sfcfcRequestsChart.destroy();
		}

		var labels = hourly.map( function ( point ) {
			return new Date( point.time ).toLocaleTimeString( [], { hour: '2-digit' } );
		} );

		var datasets = [
			{ key: 'requests', label: 'Total Requests', border: '#f6821f', background: 'rgba(246, 130, 31, 0.12)' },
			{ key: 'cachedRequests', label: 'Cached Requests', border: '#059669', background: 'rgba(5, 150, 105, 0.08)' }
		].map( function ( def ) {
			return {
				label: def.label,
				data: hourly.map( function ( point ) { return point[ def.key ]; } ),
				borderColor: def.border,
				backgroundColor: def.background,
				pointBackgroundColor: def.border,
				pointRadius: 0,
				pointHoverRadius: 5,
				pointHitRadius: 12,
				borderWidth: 2,
				fill: true,
				tension: 0.3
			};
		} );

		sfcfcRequestsChart = new Chart( $canvas.get( 0 ), {
			type: 'line',
			data: { labels: labels, datasets: datasets },
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				hover: { mode: 'index', intersect: false },
				plugins: {
					legend: {
						position: 'bottom',
						labels: { boxWidth: 12, usePointStyle: true, font: { size: 11 } }
					},
					tooltip: { mode: 'index', intersect: false, backgroundColor: '#171717', padding: 10, cornerRadius: 6, usePointStyle: true }
				},
				scales: {
					x: { grid: { display: false } },
					y: { beginAtZero: true, ticks: { precision: 0 } }
				}
			}
		} );
	}

	$( function () {
		loadAnalytics();
	} );

} )( jQuery );
