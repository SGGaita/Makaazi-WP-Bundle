/**
 * Makaazi Help Center: switch articles without reloading, and search.
 */
( function () {
	var root = document.querySelector( '[data-mk-help]' );
	if ( ! root ) {
		return;
	}
	var input = root.querySelector( '[data-mk-help-search]' );
	var items = root.querySelectorAll( '[data-mk-help-item]' );
	var groups = root.querySelectorAll( '[data-mk-help-group]' );
	var arts = root.querySelectorAll( '[data-mk-help-article]' );
	var count = root.querySelector( '[data-mk-help-count]' );
	var empty = root.querySelector( '[data-mk-help-empty]' );

	function show( id, push ) {
		var found = false;
		arts.forEach( function ( a ) {
			var on = a.getAttribute( 'data-mk-help-article' ) === id;
			a.hidden = ! on;
			found = found || on;
		} );
		if ( ! found ) {
			return false;
		}
		items.forEach( function ( li ) {
			var a = li.querySelector( 'a' );
			if ( li.getAttribute( 'data-mk-help-item' ) === id ) {
				a.setAttribute( 'aria-current', 'page' );
			} else {
				a.removeAttribute( 'aria-current' );
			}
		} );
		if ( push ) {
			var url = new URL( window.location.href );
			url.searchParams.set( 'article', id );
			window.history.pushState( { article: id }, '', url );
		}
		var main = root.querySelector( '.mk-help__main' );
		if ( main && main.getBoundingClientRect().top < 0 ) {
			main.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
		return true;
	}

	root.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( 'a' );
		if ( ! a || e.metaKey || e.ctrlKey || e.shiftKey ) {
			return;
		}
		var id = a.getAttribute( 'data-mk-help-link' );
		if ( ! id ) {
			var li = a.closest( '[data-mk-help-item]' );
			id = li ? li.getAttribute( 'data-mk-help-item' ) : '';
		}
		if ( id && show( id, true ) ) {
			e.preventDefault();
		}
	} );

	window.addEventListener( 'popstate', function () {
		var id = new URL( window.location.href ).searchParams.get( 'article' );
		if ( id ) {
			show( id, false );
		}
	} );

	if ( input ) {
		input.addEventListener( 'input', function () {
			var words = input.value.toLowerCase().trim().split( /\s+/ ).filter( Boolean );
			var n = 0;
			var first = '';
			items.forEach( function ( li ) {
				var text = li.getAttribute( 'data-search' ) || '';
				var hit = words.every( function ( w ) {
					return text.indexOf( w ) !== -1;
				} );
				li.hidden = ! hit;
				if ( hit ) {
					n++;
					first = first || li.getAttribute( 'data-mk-help-item' );
				}
			} );
			groups.forEach( function ( g ) {
				g.hidden = ! g.querySelector( '[data-mk-help-item]:not([hidden])' );
			} );
			count.hidden = ! words.length;
			count.textContent = n === 1 ? '1 guide' : n + ' guides';
			empty.hidden = n > 0;
			if ( words.length && first ) {
				show( first, false );
			}
		} );
		input.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				input.value = '';
				input.dispatchEvent( new Event( 'input' ) );
			}
		} );
	}
} )();
