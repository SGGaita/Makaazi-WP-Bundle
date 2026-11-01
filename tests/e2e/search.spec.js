const { test, expect } = require( '@playwright/test' );

test.describe( 'Search and filters', () => {
	test( 'hero search opens filtered results', async ( { page } ) => {
		await page.goto( '/' );
		const form = page.locator( '[data-mk-search]' ).first();
		await expect( form ).toBeVisible();
		await form.locator( '.mk-search__tab', { hasText: 'Rent' } ).click();
		await form.locator( '.mk-search__go' ).click();
		await page.waitForURL( /status=rent/ );
		await expect( page.locator( '.mk-results__title' ) ).toContainText( 'to rent' );
		await expect( page.locator( '.mk-card' ).first() ).toBeVisible();
	} );

	test( 'short stay tab uses nightly prices', async ( { page } ) => {
		await page.goto( '/' );
		const form = page.locator( '[data-mk-search]' ).first();
		await form.locator( '.mk-search__tab', { hasText: 'Short Stay' } ).click();
		await expect( form.locator( '[data-mk-price="stay"] select[name=min_price] option' ).nth( 1 ) ).toContainText( '/night' );
		await form.locator( '.mk-search__go' ).click();
		await page.waitForURL( /status=stay/ );
		await expect( page.locator( '.mk-card__price' ).first() ).toContainText( '/night' );
	} );

	test( 'filters update results and the address without reloading', async ( { page }, info ) => {
		test.skip( info.project.name === 'phone', 'Filters sit in a drawer on phones.' );
		await page.goto( '/properties/' );
		const before = await page.locator( '.mk-results__title' ).innerText();
		await page.locator( '.mk-seg label', { hasText: 'Buy' } ).first().click();
		await page.waitForURL( /status=sale/ );
		await expect( page.locator( '.mk-results__title' ) ).not.toHaveText( before );
		await expect( page.locator( '.mk-results__title' ) ).toContainText( 'for sale' );
		await page.goBack();
		await expect( page ).not.toHaveURL( /status=sale/ );
	} );

	test( 'map view shows a map', async ( { page }, info ) => {
		test.skip( info.project.name === 'phone' );
		await page.goto( '/properties/?view=map' );
		await expect( page.locator( '[data-mk-map-results]' ) ).toBeVisible();
	} );
} );
