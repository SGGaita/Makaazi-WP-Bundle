# Help Center content

The in-admin Help is content in PHP arrays, so it ships with the plugin, is translatable, and can be changed with a filter.

| Where people see it | Source | Context |
|---|---|---|
| **Properties › Help** (Help Center) | `includes/help/admin-articles.php` | `admin` |
| The **Help** button (top right) on Makaazi screens | same articles, matched by `screens` | `admin` |
| **Help** tab in `[makaazi_dashboard]` (agents and owners) | `includes/help/account-articles.php` | `account` |

## Article format

```php
array(
	'id'       => 'hero-slider',                 // unique, used in ?article= and {help:…} links
	'group'    => __( 'Home page & design', 'makaazi' ),
	'title'    => __( 'Hero slider', 'makaazi' ),
	'summary'  => __( 'One line shown under the title.', 'makaazi' ),
	'screens'  => array( 'edit-mk_slider', 'mk_slider' ), // admin screen IDs for the Help button (admin only)
	'keywords' => 'slider banner autoplay',      // extra search words
	'body'     => __( '<p>HTML…</p>', 'makaazi' ),
)
```

The body is HTML, run through `wp_kses_post()`. Tokens are replaced when it is shown:

- `{url:settings}`, `{url:settings:features}` (any Settings tab key), `{url:new}`, `{url:properties}`, `{url:pending}`, `{url:slider}`, `{url:agents}`, `{url:enquiries}`, `{url:users}`, `{url:shortcodes}`, `{url:demo}`, `{url:locations}`
- dashboard articles: `{url:submit}`, `{url:listings}`, `{url:enquiries-tab}`, `{url:profile}`
- `{help:article-id}`: a link to another article, with its title as the text

Screen IDs: `edit-property` (list), `property` (edit screen), `edit-property_location` (taxonomy), `property_page_makaazi-settings` (a Properties submenu page), `users`, and so on. `get_current_screen()->id` shows the value on any screen.

## Adding or changing articles

```php
add_filter( 'makaazi_help_articles', function ( $articles, $context ) {
	if ( 'admin' === $context ) {
		$articles[] = array(
			'id'      => 'our-process',
			'group'   => 'Our agency',
			'title'   => 'How we publish listings',
			'summary' => 'Checks before a listing goes live.',
			'screens' => array( 'edit-property' ),
			'body'    => '<ol><li>Call the owner…</li><li>Visit…</li></ol>',
		);
	}
	if ( 'account' === $context ) {
		// Remove the generic FAQ for owners and agents.
		$articles = array_filter( $articles, function ( $a ) {
			return 'acc-faq' !== $a['id'];
		} );
	}
	return $articles;
}, 10, 2 );
```

Up to five matching articles appear as tabs in the Help button; the sidebar links to the Help Center, Shortcodes and (if set with `makaazi_developer_docs_url`) the developer docs.

## Writing style

The articles are written for non-technical staff: short steps, the exact labels used on screen in bold or italics, one tip per article. Keep that tone when you add content, and update the article when you change the screen it describes.
