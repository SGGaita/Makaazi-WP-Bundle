<?php
/**
 * Help Center articles for site staff (Properties › Help).
 *
 * Bodies are HTML. Tokens replaced at render time:
 *   {url:settings}  {url:settings:TAB}  {url:new}  {url:properties}  {url:pending}  {url:slider}
 *   {url:agents}  {url:enquiries}  {url:users}  {url:shortcodes}  {url:demo}  {url:locations}
 *   {help:ARTICLE_ID}  link to another article.
 *
 * Add or change articles with the `makaazi_help_articles` filter.
 *
 * @package Makaazi
 */

defined( 'ABSPATH' ) || exit;

return array(

	/* ---------------------------------------------------------------- Getting started */
	array(
		'id'       => 'welcome',
		'group'    => __( 'Getting started', 'makaazi' ),
		'title'    => __( 'Welcome to Makaazi', 'makaazi' ),
		'summary'  => __( 'What the theme and the two plugins do, and where everything lives.', 'makaazi' ),
		'screens'  => array( 'edit-property', 'property_page_makaazi-demo' ),
		'keywords' => 'overview start intro plugins theme',
		'body'     => __(
			'<p>Makaazi is a property listing template by <a href="https://ascensiondynamics.co/" target="_blank" rel="noopener">Ascension Dynamics</a>. It is made of three parts that work together:</p>
<ul>
<li><strong>Makaazi theme</strong> – the look of the site: header, footer, page layouts, fonts and colours.</li>
<li><strong>Makaazi Core plugin</strong> – properties, search and filters, property pages, maps, enquiries, agents, owner accounts, favourites and compare.</li>
<li><strong>Makaazi Hero Slider plugin</strong> – the big image slider with the Buy/Rent search bar on the home page.</li>
</ul>
<p>Everything you manage is under the <strong>Properties</strong> menu on the left:</p>
<ul>
<li><a href="{url:properties}">All Properties</a> and <a href="{url:new}">Add New</a> – your listings.</li>
<li>Statuses (Buy / Rent), Uses, Types, Categories, Locations, Features – the lists used in search and filters.</li>
<li><a href="{url:slider}">Hero Sliders</a>, <a href="{url:agents}">Agents</a>, <a href="{url:enquiries}">Enquiries</a>.</li>
<li><a href="{url:settings}">Settings</a>, <a href="{url:shortcodes}">Shortcodes</a>, <a href="{url:demo}">Demo Content</a> and this Help Center.</li>
</ul>
<p>New site? Start with {help:demo}, then {help:add-property}.</p>
<p class="mk-help__tip">Look for the <strong>Help</strong> button at the top right of any Makaazi screen. It shows tips for that screen.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'demo',
		'group'    => __( 'Getting started', 'makaazi' ),
		'title'    => __( 'Import the demo content', 'makaazi' ),
		'summary'  => __( 'Fill an empty site with sample listings, agents, a slider and ready-made pages.', 'makaazi' ),
		'screens'  => array( 'property_page_makaazi-demo' ),
		'keywords' => 'demo import sample remove pages menu',
		'body'     => __(
			'<p>The demo gives you a working site in one click, so you can see how everything fits before adding your own listings.</p>
<ol>
<li>Go to <a href="{url:demo}">Properties › Demo Content</a>.</li>
<li>Click <strong>Import demo content</strong>. It takes about a minute because it copies the photos.</li>
<li>Visit the site. The home page, Properties page, agent pages, Favourites, Compare and My account are all set up.</li>
</ol>
<p>What gets added: 15 properties in Nairobi, Mombasa and Nakuru, 4 agents, the home page slider, the pages above, the main menu and sensible settings.</p>
<p><strong>Removing it:</strong> the same screen has a <strong>Remove demo content</strong> button. It deletes only what the import created; anything you added yourself stays. You can import again at any time.</p>
<p class="mk-help__tip">Tip: replace demo listings with real ones one by one, then remove the demo when you are done.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'setup-checklist',
		'group'    => __( 'Getting started', 'makaazi' ),
		'title'    => __( 'Launch checklist', 'makaazi' ),
		'summary'  => __( 'The settings to check before you share the site.', 'makaazi' ),
		'screens'  => array( 'property_page_makaazi-settings' ),
		'keywords' => 'launch go live checklist logo email phone whatsapp permalinks',
		'body'     => __(
			'<ol>
<li><strong>Logo and site name</strong> – Appearance › Editor › Patterns › Header, or Settings › General for the site title.</li>
<li><strong>Contact details</strong> – <a href="{url:settings:single}">Settings › Property page</a>: contact name, phone, WhatsApp number (format 2547…, no +) and the email that receives enquiries.</li>
<li><strong>Currency and units</strong> – <a href="{url:settings:general}">Settings › General</a>.</li>
<li><strong>Price brackets</strong> – <a href="{url:settings:prices}">Settings › Price brackets</a>, so the price filters match your market.</li>
<li><strong>Features</strong> – <a href="{url:settings:features}">Settings › Features</a>: switch agents and owner submissions on or off.</li>
<li><strong>Permalinks</strong> – Settings › Permalinks should be “Post name”. Click <em>Save</em> there once if a property page shows “Not found”.</li>
<li><strong>Email</strong> – send yourself a test enquiry from a property page. If it does not arrive, see {help:email}.</li>
<li><strong>Remove demo content</strong> if you imported it.</li>
</ol>',
			'makaazi'
		),
	),

	/* ---------------------------------------------------------------- Listings */
	array(
		'id'       => 'add-property',
		'group'    => __( 'Listings', 'makaazi' ),
		'title'    => __( 'Add or edit a property', 'makaazi' ),
		'summary'  => __( 'Every field on the property screen, in the order you fill it.', 'makaazi' ),
		'screens'  => array( 'property', 'edit-property' ),
		'keywords' => 'add new listing property price gallery photos map location reference featured',
		'body'     => __(
			'<ol>
<li><strong>Title</strong> – e.g. “4 Bedroom Townhouse, Lavington”. It becomes the page address.</li>
<li><strong>Description</strong> – the main text box. Short paragraphs read best on phones.</li>
<li><strong>Right-hand column</strong> – choose the <em>Status</em> (For Sale, For Rent or Short Stay), <em>Use</em> (residential or commercial), <em>Type</em>, <em>Category</em>, <em>Location</em> and tick the <em>Features</em>. Pick the most specific location (e.g. Kilimani, not Nairobi); the parents are added automatically.</li>
<li><strong>Property Details box</strong>, tab by tab:
<ul>
<li><em>Listing</em> – Featured (shows first and gets a badge), availability (Available, Under offer, Sold, Let) and the reference number (created on save).</li>
<li><em>Pricing</em> – the amount and, for rentals, per month / year / night. Tick “Price on request” to hide the number.</li>
<li><em>Specs</em> – bedrooms, bathrooms, parking, floor area, land size, year built.</li>
<li><em>Gallery</em> – add photos, drag to reorder. The first photo is the cover on cards.</li>
<li><em>Location</em> – drag the pin to the exact spot, then choose the public privacy radius (see {help:map-privacy}).</li>
<li><em>Attributes</em> – extra rows for the Details table, e.g. “Service charge – KES 8,000 /month”, plus a video or virtual tour link.</li>
</ul></li>
<li><strong>Agent</strong> box – who handles this listing (or “Site contact”). Enquiries go to that agent.</li>
<li>Click <strong>Publish</strong>.</li>
</ol>
<p class="mk-help__tip">Sold and Let listings stay online with a ribbon but drop out of search and grids. Use this instead of deleting, so links keep working.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'photos',
		'group'    => __( 'Listings', 'makaazi' ),
		'title'    => __( 'Photo guide', 'makaazi' ),
		'summary'  => __( 'Sizes and order that make listings look their best.', 'makaazi' ),
		'screens'  => array( 'property' ),
		'keywords' => 'photos images gallery size cover lightbox',
		'body'     => __(
			'<ul>
<li>Landscape photos, at least <strong>1600 px wide</strong>. Phone photos are fine; hold the phone sideways.</li>
<li>Put the best outside or living-room shot first – it is the cover on cards and in search.</li>
<li>6–20 photos per listing. Cards let visitors swipe through the first few (Settings › Cards › Photos per card).</li>
<li>WordPress makes smaller copies automatically, so you do not need to resize.</li>
<li>Photos open in a full-screen lightbox with arrows and swipe on the property page.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'map-privacy',
		'group'    => __( 'Listings', 'makaazi' ),
		'title'    => __( 'Maps and location privacy', 'makaazi' ),
		'summary'  => __( 'Why the map shows a circle, and how to change it.', 'makaazi' ),
		'screens'  => array( 'property' ),
		'keywords' => 'map pin privacy radius circle exact address leaflet openstreetmap',
		'body'     => __(
			'<p>Property pages show the general area as a shaded circle, not the exact house. The exact pin you place is kept private – it is never sent to visitors’ browsers.</p>
<ul>
<li>Set the circle size per listing in <em>Property Details › Location › Public privacy radius</em>, or the default in <a href="{url:settings:single}">Settings › Property page</a>.</li>
<li>Tick <em>Show the exact pin publicly</em> only for places that are already public, such as an office block, shop or development.</li>
<li>Maps use OpenStreetMap, which is free. For a busy site, use a paid tile provider and paste its URL in Settings › Property page › Map tiles URL.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'short-stays',
		'group'    => __( 'Listings', 'makaazi' ),
		'title'    => __( 'Short Stays (nightly rentals)', 'makaazi' ),
		'summary'  => __( 'List furnished homes that guests book by the night.', 'makaazi' ),
		'screens'  => array( 'property', 'edit-property' ),
		'keywords' => 'short stay holiday nightly night booking guest serviced apartment vacation rental',
		'body'     => __(
			'<p>Short Stay is a third listing type next to Buy and Rent, for holiday homes and serviced apartments priced per night.</p>
<ol>
<li>Make sure <em>Short Stays</em> is ticked in <a href="{url:settings:features}">Settings › Features</a>.</li>
<li>Add a property and choose the <em>Short Stay</em> status. The price period switches to <em>Per night</em>.</li>
<li>Use <em>Attributes</em> for stay details: Sleeps, Minimum stay, Check-in time, Cleaning.</li>
</ol>
<p>Visitors get a <strong>Short Stay</strong> tab in the search bar and filters, with nightly price ranges you set in <a href="{url:settings:prices}">Settings › Price brackets</a>. Put a row of stays on any page with <code>[makaazi_properties source="stay" layout="carousel"]</code>.</p>
<p>When a stay is no longer offered, owners click <em>Mark unavailable</em> (you can set Availability to Let). Makaazi shows availability and takes enquiries; it does not take bookings or payments.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'taxonomies',
		'group'    => __( 'Listings', 'makaazi' ),
		'title'    => __( 'Locations, types, categories and features', 'makaazi' ),
		'summary'  => __( 'Manage the lists visitors filter by.', 'makaazi' ),
		'screens'  => array( 'edit-property_location', 'edit-property_type', 'edit-property_category', 'edit-property_feature', 'edit-property_use', 'edit-property_status' ),
		'keywords' => 'location area neighbourhood type category feature amenities taxonomy terms image',
		'body'     => __(
			'<ul>
<li><strong>Locations</strong> are nested: City › Area (e.g. Nairobi › Westlands). Give each area a photo – it is used by the Locations tiles on the home page.</li>
<li><strong>Types</strong> are what the building is (Apartment, Villa, Office…). <strong>Categories</strong> group listings for marketing (Luxury, New development…).</li>
<li><strong>Uses</strong> are Residential or Commercial. <strong>Statuses</strong> are For Sale, For Rent and Short Stay – the Buy / Rent / Short Stay choice in search.</li>
<li><strong>Features</strong> are the ticks on the property page (Borehole, Backup generator, Pool…).</li>
</ul>
<p>Renaming a term updates every listing that uses it. Deleting a term removes it from listings but does not delete the listings.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'enquiries',
		'group'    => __( 'Listings', 'makaazi' ),
		'title'    => __( 'Enquiries', 'makaazi' ),
		'summary'  => __( 'Where messages from visitors go and how to follow them up.', 'makaazi' ),
		'screens'  => array( 'edit-mk_enquiry', 'mk_enquiry' ),
		'keywords' => 'enquiry enquiries leads messages email whatsapp contact form spam',
		'body'     => __(
			'<p>Every message sent from a property page or the contact page is saved under <a href="{url:enquiries}">Properties › Enquiries</a> and emailed.</p>
<ul>
<li>If the listing has an agent, the email goes to the agent. Tick <em>Copy me on agent enquiries</em> in Settings › Features to get a copy.</li>
<li>Otherwise it goes to <em>Send enquiries to</em> in Settings › Property page (or the site admin email).</li>
<li>Owners get an email for enquiries on their own listings and can read them in their dashboard.</li>
<li>Each enquiry shows the property, name, phone and email. Reply by phone, WhatsApp or email.</li>
</ul>
<p>Spam protection is built in: a hidden trap field and a limit of a few messages per visitor per hour.</p>',
			'makaazi'
		),
	),

	/* ---------------------------------------------------------------- Home page & design */
	array(
		'id'       => 'hero-slider',
		'group'    => __( 'Home page & design', 'makaazi' ),
		'title'    => __( 'Hero slider', 'makaazi' ),
		'summary'  => __( 'Slides, timing, the search bar, and placing a slider on a page.', 'makaazi' ),
		'screens'  => array( 'edit-mk_slider', 'mk_slider' ),
		'keywords' => 'slider hero banner slides search bar autoplay ken burns video featured',
		'body'     => __(
			'<ol>
<li>Go to <a href="{url:slider}">Properties › Hero Sliders</a> and open a slider (or add a new one).</li>
<li><strong>Slides</strong> – add a background image (1920 × 1080 or larger), an optional phone image, label, heading, text and up to two buttons. Drag to reorder. <em>Show from / until</em> dates let you schedule a promotion.</li>
<li><strong>Slides from</strong> – your slides, your featured properties, or both.</li>
<li><strong>Slider settings</strong> – effect (fade, slide, slow zoom), autoplay timing, arrows/dots/progress bar and the height on desktop, tablet and phone.</li>
<li><strong>Search bar</strong> – show the Buy/Rent search, choose its position and style, tick the fields and drag to reorder them.</li>
<li>Click <strong>Update</strong>, then copy the shortcode from the box on the right.</li>
</ol>
<p>To show a slider: paste the shortcode, e.g. <code>[makaazi_slider slug="home"]</code>, into a Shortcode block, or use the Hero Slider widget in Elementor.</p>
<p class="mk-help__tip">Visitors who have “reduce motion” turned on never get autoplay or zoom.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'home-page',
		'group'    => __( 'Home page & design', 'makaazi' ),
		'title'    => __( 'Edit the home page and other pages', 'makaazi' ),
		'summary'  => __( 'Change sections, headings and the header and footer.', 'makaazi' ),
		'screens'  => array( 'edit-page', 'page' ),
		'keywords' => 'home page edit sections editor header footer logo menu colours fonts',
		'body'     => __(
			'<p>Pages are built from blocks. Most Makaazi sections are a <strong>Shortcode block</strong> with one line in it, such as <code>[makaazi_properties featured="1" columns="3"]</code>.</p>
<ul>
<li><strong>Home page</strong> – Pages › Home. Click a section to edit its heading, or change the numbers in a shortcode to show more or fewer listings. See {help:shortcodes} for every option.</li>
<li><strong>Header and footer</strong> – Appearance › Editor › Patterns, then Header or Footer. Logo, menu and contact details are there.</li>
<li><strong>Colours and fonts</strong> – Appearance › Editor › Styles.</li>
<li><strong>Menu</strong> – in the Header, click the Navigation block.</li>
</ul>
<p>Prefer drag and drop? Install Elementor and use the Makaazi widgets – see {help:elementor}.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'shortcodes',
		'group'    => __( 'Home page & design', 'makaazi' ),
		'title'    => __( 'Using shortcodes', 'makaazi' ),
		'summary'  => __( 'Put listings, search, maps and forms on any page.', 'makaazi' ),
		'screens'  => array( 'property_page_makaazi-shortcodes' ),
		'keywords' => 'shortcode grid carousel properties search listing agents locations block',
		'body'     => __(
			'<p>A shortcode is a short tag in square brackets that shows a Makaazi section. The full list, with every option and copy buttons, is on <a href="{url:shortcodes}">Properties › Shortcodes</a>.</p>
<ol>
<li>Edit a page and add a <strong>Shortcode</strong> block (type <code>/shortcode</code>).</li>
<li>Paste the shortcode and change the options, e.g. <code>[makaazi_properties status="rent" location="westlands" columns="4" limit="8"]</code>.</li>
<li>Update the page.</li>
</ol>
<p>Most used:</p>
<ul>
<li><code>[makaazi_properties]</code> – a grid, list or carousel of listings (featured, buy, rent, by location…).</li>
<li><code>[makaazi_listing]</code> – the full Properties page with filters, sort and map view.</li>
<li><code>[makaazi_search]</code> – a search bar that sends visitors to the Properties page.</li>
<li><code>[makaazi_locations]</code>, <code>[makaazi_agents]</code>, <code>[makaazi_favourites]</code>, <code>[makaazi_compare]</code>, <code>[makaazi_dashboard]</code>.</li>
</ul>
<p class="mk-help__tip">Options use plain words – use the slug shown on the Locations or Types screen, e.g. <code>location="kilimani"</code>.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'elementor',
		'group'    => __( 'Home page & design', 'makaazi' ),
		'title'    => __( 'Elementor widgets', 'makaazi' ),
		'summary'  => __( 'Drag-and-drop Makaazi sections when Elementor is installed.', 'makaazi' ),
		'screens'  => array(),
		'keywords' => 'elementor page builder widgets drag drop',
		'body'     => __(
			'<p>When the free Elementor plugin is active, a <strong>Makaazi</strong> group appears in the Elementor widget panel:</p>
<ul>
<li>Hero Slider, Property Search, Properties (grid, list or carousel), Property Listing (full page with filters), Locations, Agents.</li>
<li>Property Section – one part of a property page (gallery, facts, details, features, map, contact, similar listings) for building your own property template in Elementor Pro.</li>
<li>Favourites &amp; Compare.</li>
</ul>
<p>Each widget has the same options as its shortcode on the <em>Content</em> tab, and colours, fonts and spacing on the <em>Style</em> tab. Widgets show live previews in the editor.</p>
<p>Elementor is optional. The theme works fully without it.</p>',
			'makaazi'
		),
	),

	/* ---------------------------------------------------------------- People */
	array(
		'id'       => 'agents',
		'group'    => __( 'Agents & owners', 'makaazi' ),
		'title'    => __( 'Agents', 'makaazi' ),
		'summary'  => __( 'Agent profiles, agent sign-in, and assigning listings.', 'makaazi' ),
		'screens'  => array( 'edit-mk_agent', 'mk_agent' ),
		'keywords' => 'agent agents profile staff team login account assign',
		'body'     => __(
			'<p>Agents are switched on in <a href="{url:settings:features}">Settings › Features</a>. Turning them off hides agent pages and the agent box on listings; nothing is deleted.</p>
<p><strong>Add an agent</strong></p>
<ol>
<li><a href="{url:agents}">Properties › Agents</a> › Add New.</li>
<li>Name (the title), bio (the text box), <em>Agent photo</em> (square, at least 400 px) and, in <em>Agent details</em>, job title, office, phone, WhatsApp and email.</li>
<li>Optional: in <em>Sign-in account</em>, pick a user (or click <em>Create a user</em>) so the agent can sign in. The user is given the Agent role.</li>
</ol>
<p><strong>What signed-in agents can do:</strong> add and edit their own listings from the website dashboard or wp-admin, read enquiries for their listings, and update their profile. They cannot see other agents’ listings or change settings.</p>
<p>With <em>Review agent listings</em> on, an agent’s new listings wait in <a href="{url:pending}">Pending</a> for you to approve.</p>
<p><strong>Assign a listing:</strong> edit the property and choose the agent in the Agent box. Their card appears on the property page and enquiries go to them.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'owners',
		'group'    => __( 'Agents & owners', 'makaazi' ),
		'title'    => __( 'Owner sign-ups and listing review', 'makaazi' ),
		'summary'  => __( 'Let property owners list from the website, and approve or reject their listings.', 'makaazi' ),
		'screens'  => array( 'users', 'edit-property' ),
		'keywords' => 'owner landlord submission sign up register approve reject pending moderation activate',
		'body'     => __(
			'<p>Switch on <em>Owner submissions</em> in <a href="{url:settings:features}">Settings › Features</a> and choose how owners get an account:</p>
<ul>
<li><strong>Anyone can sign up</strong> – optionally with <em>Confirm email</em>.</li>
<li><strong>Sign-ups need my approval</strong> – new owners appear under <a href="{url:users}">Users</a> as “Pending”. Hover and click <strong>Activate</strong>.</li>
<li><strong>Invite only</strong> – you create users with the role <em>Property owner</em>.</li>
</ul>
<p>Owners use the <strong>My account</strong> page (it contains <code>[makaazi_dashboard]</code>). They never see wp-admin.</p>
<p><strong>Reviewing a listing</strong></p>
<ol>
<li>A number on the Properties menu shows listings waiting. Open <a href="{url:pending}">Pending</a>.</li>
<li>Open the listing to check photos, price and text. You can fix small things yourself.</li>
<li>Back in the list, hover the row: <strong>Approve</strong> publishes it and emails the owner. <strong>Reject</strong> asks for a reason, moves it back to draft and emails the owner the reason.</li>
</ol>
<p>With <em>Review edits to live listings</em> on, an owner’s changes to a live listing go back to Pending.</p>',
			'makaazi'
		),
	),
	array(
		'id'       => 'favourites',
		'group'    => __( 'Agents & owners', 'makaazi' ),
		'title'    => __( 'Favourites and compare', 'makaazi' ),
		'summary'  => __( 'How visitors save and compare listings.', 'makaazi' ),
		'screens'  => array(),
		'keywords' => 'favourites saved heart compare shortlist share',
		'body'     => __(
			'<ul>
<li>Visitors tap the <strong>heart</strong> on a card or <strong>Save</strong> on a property page. Saved listings are kept in the browser, and in their account when signed in, so they follow them to other devices.</li>
<li><strong>Compare</strong> holds up to 4 listings and shows them side by side, with differences highlighted.</li>
<li>Both pages have a <em>Share</em> link that opens the same list for someone else.</li>
</ul>
<p>Switch each on or off and choose the pages in <a href="{url:settings:shortlist}">Settings › Favourites &amp; compare</a>.</p>',
			'makaazi'
		),
	),

	/* ---------------------------------------------------------------- Settings */
	array(
		'id'       => 'settings',
		'group'    => __( 'Settings', 'makaazi' ),
		'title'    => __( 'Settings explained', 'makaazi' ),
		'summary'  => __( 'What each Settings tab controls.', 'makaazi' ),
		'screens'  => array( 'property_page_makaazi-settings' ),
		'keywords' => 'settings options currency units cards filters features switches',
		'body'     => __(
			'<ul>
<li><a href="{url:settings:features}"><strong>Features</strong></a> – switch Short Stays, agents and owner submissions on or off, and how they work. Switching off hides things; nothing is deleted.</li>
<li><a href="{url:settings:general}"><strong>General</strong></a> – currency, short prices (KES 85M), m² or ft², the unit switch and the reference prefix.</li>
<li><a href="{url:settings:cards}"><strong>Cards</strong></a> – button text, how many photos a card can scroll, photo shape, what each card shows and the “New” badge.</li>
<li><a href="{url:settings:listing}"><strong>Properties page</strong></a> – listings per page, default sort and which filters to offer.</li>
<li><a href="{url:settings:shortlist}"><strong>Favourites &amp; compare</strong></a> – switches and pages.</li>
<li><a href="{url:settings:single}"><strong>Property page</strong></a> – contact details, where enquiries go, map privacy, similar listings and map tiles.</li>
<li><a href="{url:settings:prices}"><strong>Price brackets</strong></a> – amounts offered in the price filters for Buy, Rent (per month) and Short Stay (per night).</li>
</ul>',
			'makaazi'
		),
	),

	array(
		'id'       => 'seo',
		'group'    => __( 'Settings', 'makaazi' ),
		'title'    => __( 'Google, sharing and Yoast SEO', 'makaazi' ),
		'summary'  => __( 'How listings appear in Google and when shared on WhatsApp or Facebook.', 'makaazi' ),
		'screens'  => array( 'property', 'edit-property' ),
		'keywords' => 'seo google yoast rank math schema share whatsapp facebook preview description sitemap',
		'body'     => __(
			'<p>Makaazi tells search engines what each listing is (price, rooms, size, area, photos) using Schema.org data, so Google can show richer results. Nothing to set up.</p>
<ul>
<li><strong>Without an SEO plugin</strong>, Makaazi adds a description, the share preview (title, price, area and cover photo for WhatsApp, Facebook and X) and sensible titles such as “Properties to rent”.</li>
<li><strong>With Yoast SEO or Rank Math</strong>, the plugin takes over titles and share tags and Makaazi feeds it: the listing data joins its schema, listings without a written meta description get an automatic one, gallery photos go into the XML sitemap, and Yoast’s content analysis also reads the Property Details box.</li>
<li>In Yoast you can use these variables in title and description templates (Yoast SEO › Settings › Content types › Properties): <code>%%mk_price%%</code>, <code>%%mk_location%%</code>, <code>%%mk_status%%</code>, <code>%%mk_type%%</code>, <code>%%mk_beds%%</code>, <code>%%mk_ref%%</code>. Example title: <code>%%title%% – %%mk_price%% %%sep%% %%sitename%%</code>.</li>
<li>Search pages with several filters are marked “noindex” so Google indexes your clean pages (Properties, Properties to rent, each location) instead of thousands of combinations.</li>
<li>Exact map pins are never shared with Google either.</li>
</ul>
<p class="mk-help__tip">Tip: write a short, specific first sentence in each description. It is what Google and WhatsApp show.</p>',
			'makaazi'
		),
	),

	/* ---------------------------------------------------------------- Troubleshooting */
	array(
		'id'       => 'email',
		'group'    => __( 'Troubleshooting', 'makaazi' ),
		'title'    => __( 'Emails are not arriving', 'makaazi' ),
		'summary'  => __( 'Enquiry, approval and sign-up emails need a working mail setup.', 'makaazi' ),
		'screens'  => array( 'edit-mk_enquiry', 'users' ),
		'keywords' => 'email mail smtp not received xampp localhost spam',
		'body'     => __(
			'<ul>
<li><strong>On your own computer (XAMPP, Local…)</strong> email is not sent by default. Enquiries are still saved under Properties › Enquiries, and you can activate owners under Users.</li>
<li><strong>On a live host</strong>, install an SMTP plugin (e.g. WP Mail SMTP) and connect it to your email provider. This also stops emails landing in spam.</li>
<li>Check the address in Settings › Property page › Send enquiries to.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'not-found',
		'group'    => __( 'Troubleshooting', 'makaazi' ),
		'title'    => __( 'A page says “Not found” or a shortcode shows as text', 'makaazi' ),
		'summary'  => __( 'Fixes for the most common display problems.', 'makaazi' ),
		'screens'  => array(),
		'keywords' => 'not found 404 permalinks shortcode text broken layout',
		'body'     => __(
			'<ul>
<li><strong>Property or agent page “Not found”</strong> – go to Settings › Permalinks and click <em>Save Changes</em> (no need to change anything).</li>
<li><strong>Shortcode shows as plain text</strong> – check Makaazi Core is active and the shortcode is spelled correctly. Put it in a Shortcode block, not a Paragraph.</li>
<li><strong>Agent pages missing</strong> – agents are switched off in Settings › Features.</li>
<li><strong>Listing missing from search</strong> – it is Sold/Let, a draft, or waiting in Pending.</li>
<li><strong>Map is grey</strong> – the listing has no pin. Edit it and drag the pin in Property Details › Location.</li>
</ul>',
			'makaazi'
		),
	),
	array(
		'id'       => 'faq',
		'group'    => __( 'Troubleshooting', 'makaazi' ),
		'title'    => __( 'Frequently asked questions', 'makaazi' ),
		'summary'  => __( 'Short answers to common questions.', 'makaazi' ),
		'screens'  => array(),
		'keywords' => 'faq questions currency language translation backup update',
		'body'     => __(
			'<p><strong>Can I use another currency?</strong> Yes – Settings › General › Currency code, then update the price brackets.</p>
<p><strong>Can I hide the price?</strong> Tick “Price on request” in Property Details › Pricing.</p>
<p><strong>Can I translate the site?</strong> Yes. All text uses the <code>makaazi</code> text domain, so tools like Loco Translate work.</p>
<p><strong>Will updates overwrite my changes?</strong> Changes made in the editor, settings and listings are kept. Edits to theme or plugin files are lost – ask your developer to use a child theme.</p>
<p><strong>How do I back up?</strong> Use your host’s backup or a plugin such as UpdraftPlus. Back up both the database and the uploads folder.</p>
<p><strong>I need a developer feature.</strong> Your developer can find the separate developer documentation in the Makaazi repository under <code>docs/developer</code>.</p>',
			'makaazi'
		),
	),
);
