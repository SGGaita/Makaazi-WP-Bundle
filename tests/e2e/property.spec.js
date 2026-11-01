const { test, expect } = require( '@playwright/test' );
const { firstProperty } = require( './helpers' );

test.describe( 'Property page', () => {
	test( 'gallery lightbox works with the keyboard', async ( { page }, info ) => {
		test.skip( info.project.name === 'phone', 'Phones use the swipe strip.' );
		await page.goto( await firstProperty( page ) );
		await page.locator( '.mk-mosaic__item' ).first().focus();
		await page.keyboard.press( 'Enter' );
		const lb = page.locator( '.mk-lb' );
		await expect( lb ).toBeVisible();
		await page.keyboard.press( 'ArrowRight' );
		await page.keyboard.press( 'Escape' );
		await expect( lb ).toBeHidden();
	} );

	test( 'area unit switch converts to square feet', async ( { page } ) => {
		await page.goto( await firstProperty( page ) );
		const area = page.locator( '[data-sqm]' ).first();
		await expect( area ).toContainText( 'm²' );
		await page.locator( '[data-mk-unit-toggle]:visible' ).first().click();
		await expect( area ).toContainText( 'ft²' );
	} );

	test( 'enquiry form validates then sends', async ( { page } ) => {
		await page.goto( await firstProperty( page ) );
		const form = page.locator( 'form[data-mk-enquiry]' ).first();
		await form.scrollIntoViewIfNeeded();
		await form.locator( 'input[name=name]' ).fill( 'E2E Tester' );
		await form.locator( 'input[name=phone]' ).fill( '12' );
		await page.waitForTimeout( 3200 ); // The spam check silently drops forms sent within 3 seconds of loading.
		await form.locator( 'button[type=submit]' ).click();
		await expect( form.locator( '.mk-enquiry__status' ) ).toContainText( '9 digits' );
		await form.locator( 'input[name=phone]' ).fill( '0712 345 678' );
		await form.locator( 'button[type=submit]' ).click();
		await expect( form.locator( '.mk-enquiry__status' ) ).toContainText( 'Thanks' );
	} );

	test( 'map shows an area, never the exact pin', async ( { page } ) => {
		await page.goto( await firstProperty( page ) );
		const html = await page.content();
		const map = page.locator( '[data-mk-map]' ).first();
		if ( await map.count() ) {
			await expect( map ).toHaveAttribute( 'data-exact', '0' );
		}
		expect( html ).toContain( 'RealEstateListing' );
	} );
} );
