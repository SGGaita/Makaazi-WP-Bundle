// Playwright end-to-end tests. Base URL: WP_BASE_URL (default: wp-env on http://localhost:8888).
const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: __dirname,
	testMatch: '*.spec.js',
	timeout: 60000,
	retries: process.env.CI ? 1 : 0,
	workers: 1,
	reporter: process.env.CI ? [ [ 'github' ], [ 'html', { open: 'never', outputFolder: 'playwright-report' } ] ] : 'list',
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{ name: 'desktop', use: { ...devices[ 'Desktop Chrome' ] } },
		{ name: 'phone', use: { ...devices[ 'Pixel 7' ] }, testMatch: [ 'search.spec.js', 'property.spec.js' ] },
	],
} );
