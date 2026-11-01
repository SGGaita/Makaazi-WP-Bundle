const { test, expect } = require( '@playwright/test' );
const { login } = require( './helpers' );

test.describe( 'Owner dashboard', () => {
	test( 'owner sees the dashboard, the five-step form and Help', async ( { page } ) => {
		await login( page, process.env.E2E_OWNER_USER || 'e2e-owner', process.env.E2E_OWNER_PASS || 'e2e-owner-pass' );
		await page.goto( '/my-account/' );
		await expect( page.locator( '.mk-dash__tabs' ) ).toContainText( 'Add property' );
		await page.goto( '/my-account/?tab=submit' );
		await expect( page.locator( '.mk-steps li' ) ).toHaveCount( 5 );
		await page.locator( 'label:has([data-mk-status][value=stay])' ).click();
		await expect( page.locator( '[data-mk-period]' ) ).toHaveValue( 'night' );
		await page.goto( '/my-account/?tab=help' );
		await expect( page.locator( '.mk-dhelp__item' ).first() ).toBeVisible();
	} );

	test( 'owners are kept out of wp-admin', async ( { page } ) => {
		await login( page, process.env.E2E_OWNER_USER || 'e2e-owner', process.env.E2E_OWNER_PASS || 'e2e-owner-pass' );
		await page.goto( '/wp-admin/' );
		await expect( page ).toHaveURL( /my-account/ );
	} );
} );

test.describe( 'Admin', () => {
	test( 'Help Center search finds a guide', async ( { page } ) => {
		await login( page );
		await page.goto( '/wp-admin/edit.php?post_type=property&page=makaazi-help' );
		await page.fill( '[data-mk-help-search]', 'smtp' );
		await expect( page.locator( '[data-mk-help-article]:not([hidden]) h2' ) ).toHaveText( 'Emails are not arriving' );
	} );
} );
