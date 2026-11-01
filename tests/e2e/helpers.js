const fs = require( 'fs' );

const ADMIN_USER = process.env.WP_ADMIN_USER || 'admin';
const ADMIN_PASS = process.env.WP_ADMIN_PASS || 'password';

/**
 * Sign in through wp-login.php.
 */
async function login( page, user = ADMIN_USER, pass = ADMIN_PASS ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', user );
	await page.fill( '#user_pass', pass );
	await Promise.all( [ page.waitForURL( ( url ) => ! url.pathname.endsWith( 'wp-login.php' ) ), page.click( '#wp-submit' ) ] );
}

/**
 * Run axe-core and return serious or critical violations, ignoring WordPress core's admin bar.
 */
async function axe( page ) {
	await page.addScriptTag( { content: fs.readFileSync( require.resolve( 'axe-core/axe.min.js' ), 'utf8' ) } );
	const v = await page.evaluate( () =>
		window.axe.run( document, { exclude: [ [ '#wpadminbar' ], [ '#wp-skip-link' ] ], runOnly: { type: 'tag', values: [ 'wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa' ] } } )
			.then( ( r ) => r.violations.filter( ( x ) => x.impact === 'serious' || x.impact === 'critical' ).map( ( x ) => `${ x.id }: ${ x.nodes.map( ( n ) => n.target.join( ' ' ) ).slice( 0, 3 ).join( ' | ' ) }` ) )
	);
	return v;
}

/**
 * First property URL on the Properties page.
 */
async function firstProperty( page ) {
	await page.goto( '/properties/' );
	return page.locator( '.mk-card__title a' ).first().getAttribute( 'href' );
}

module.exports = { login, axe, firstProperty };
