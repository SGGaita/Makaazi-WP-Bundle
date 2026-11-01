/**
 * Makaazi × Elementor: initialise sliders, carousels, maps and listings when Elementor
 * renders a widget (on page load and every time a setting changes in the editor).
 */
( function ( $ ) {
	function init( $scope ) {
		var el = $scope && $scope[ 0 ];
		if ( ! el ) {
			return;
		}
		if ( window.MakaaziSliderInit ) {
			window.MakaaziSliderInit( el );
		}
		if ( window.MakaaziInit ) {
			window.MakaaziInit( el );
		}
	}
	$( window ).on( 'elementor/frontend/init', function () {
		if ( ! window.elementorFrontend || ! window.elementorFrontend.hooks ) {
			return;
		}
		window.elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', function ( $scope ) {
			var type = ( $scope.data( 'widget_type' ) || '' ).toString();
			if ( type.indexOf( 'makaazi' ) === 0 ) {
				init( $scope );
			}
		} );
	} );
}( jQuery ) );
