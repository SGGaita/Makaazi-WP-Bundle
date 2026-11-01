/**
 * Makaazi × Yoast SEO: include the Property Details (price, rooms, area, location, features,
 * extra details) in Yoast's content analysis, so its readability and keyphrase checks see them.
 */
( function () {
	function val( name ) {
		var el = document.querySelector( '[name="mk[' + name + ']"]' );
		return el && el.value ? el.value.trim() : '';
	}
	function checkedLabels( box ) {
		return Array.prototype.map.call( document.querySelectorAll( box + ' input:checked' ), function ( i ) {
			return ( i.parentElement.textContent || '' ).trim();
		} ).filter( Boolean );
	}
	function details() {
		var out = [];
		var status = checkedLabels( '#property_statusdiv' );
		var type = checkedLabels( '#property_typediv' );
		var loc = checkedLabels( '#property_locationdiv' );
		if ( status.length || type.length ) {
			out.push( type.concat( status ).join( ' ' ) );
		}
		if ( loc.length || val( 'location_label' ) ) {
			out.push( loc.concat( val( 'location_label' ) ? [ val( 'location_label' ) ] : [] ).join( ', ' ) );
		}
		if ( val( 'price' ) ) {
			out.push( 'Price ' + val( 'price' ) );
		}
		[ [ 'bedrooms', 'bedrooms' ], [ 'bathrooms', 'bathrooms' ], [ 'parking', 'parking spaces' ], [ 'area', 'square metres' ] ].forEach( function ( p ) {
			if ( val( p[ 0 ] ) ) {
				out.push( val( p[ 0 ] ) + ' ' + p[ 1 ] );
			}
		} );
		var feats = checkedLabels( '#property_featurediv' );
		if ( feats.length ) {
			out.push( 'Features: ' + feats.join( ', ' ) );
		}
		var ks = document.querySelectorAll( '[name="mk[attr_k][]"]' );
		var vs = document.querySelectorAll( '[name="mk[attr_v][]"]' );
		Array.prototype.forEach.call( ks, function ( k, i ) {
			if ( k.value && vs[ i ] && vs[ i ].value ) {
				out.push( k.value + ': ' + vs[ i ].value );
			}
		} );
		return out.length ? '<p>' + out.join( '. ' ).replace( /</g, '&lt;' ) + '.</p>' : '';
	}
	function init() {
		var app = window.YoastSEO && window.YoastSEO.app;
		if ( ! app || ! app.registerPlugin || init.done ) {
			return;
		}
		init.done = true;
		app.registerPlugin( 'makaazi', { status: 'ready' } );
		app.registerModification( 'content', function ( content ) {
			return content + details();
		}, 'makaazi', 5 );
		var t;
		document.addEventListener( 'input', function ( e ) {
			if ( e.target.closest && e.target.closest( '#mk_details, #property_statusdiv, #property_typediv, #property_locationdiv, #property_featurediv' ) ) {
				clearTimeout( t );
				t = setTimeout( function () {
					app.pluginReloaded( 'makaazi' );
				}, 600 );
			}
		} );
		document.addEventListener( 'change', function ( e ) {
			if ( e.target.closest && e.target.closest( '#mk_details, #property_statusdiv, #property_typediv, #property_locationdiv, #property_featurediv' ) ) {
				app.pluginReloaded( 'makaazi' );
			}
		} );
	}
	if ( window.YoastSEO && window.YoastSEO.app ) {
		init();
	} else {
		window.addEventListener( 'YoastSEO:ready', init );
		document.addEventListener( 'DOMContentLoaded', function () {
			setTimeout( init, 1500 );
		} );
	}
}() );
