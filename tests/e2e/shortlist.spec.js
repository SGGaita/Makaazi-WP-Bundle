const { test, expect } = require( '@playwright/test' );

test.describe( 'Favourites and compare', () => {
	test( 'save from a card and see it on the favourites page', async ( { page } ) => {
		await page.goto( '/properties/' );
		const card = page.locator( '.mk-card' ).first();
		const title = await card.locator( '.mk-card__title' ).innerText();
		await card.locator( '.mk-fav' ).click();
		await expect( card.locator( '.mk-fav' ) ).toHaveAttribute( 'aria-pressed', 'true' );
		await page.goto( '/favourites/' );
		await expect( page.locator( '.mk-card__title', { hasText: title } ) ).toBeVisible();
	} );

	test( 'compare two properties side by side', async ( { page } ) => {
		await page.goto( '/properties/' );
		await page.locator( '.mk-card .mk-cmp' ).nth( 0 ).click();
		await page.locator( '.mk-card .mk-cmp' ).nth( 1 ).click();
		await page.goto( '/compare/' );
		await expect( page.locator( '.mk-ctable__head' ) ).toHaveCount( 2 );
	} );
} );
