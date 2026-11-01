const { test, expect } = require( '@playwright/test' );
const { axe, firstProperty } = require( './helpers' );

const pages = [ '/', '/properties/', '/agents/', '/favourites/', '/compare/', '/my-account/' ];

test.describe( 'Accessibility (axe, WCAG 2.1 AA)', () => {
	for ( const path of pages ) {
		test( `no serious issues on ${ path }`, async ( { page } ) => {
			await page.goto( path );
			expect( await axe( page ) ).toEqual( [] );
		} );
	}
	test( 'no serious issues on a property page', async ( { page } ) => {
		await page.goto( await firstProperty( page ) );
		expect( await axe( page ) ).toEqual( [] );
	} );
} );
