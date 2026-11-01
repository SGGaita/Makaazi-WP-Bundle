# Build, test and release

There is no build step: edit the PHP, CSS and JS directly.

## Local setup

Any local WordPress works (XAMPP, Local, wp-env, Docker).

1. Clone the repository **into `wp-content/`** (the `.gitignore` there tracks only Makaazi files):
   ```bash
   cd path/to/site/wp-content
   git init && git remote add origin <repo-url> && git fetch && git checkout -t origin/main
   ```
2. Activate **Makaazi Core**, then **Makaazi Hero Slider**, then the **Makaazi** theme.
3. **Properties › Demo Content › Import demo content** for sample data.
4. Settings › Permalinks › Post name.
5. In `wp-config.php` while developing:
   ```php
   define( 'WP_DEBUG', true );
   define( 'WP_DEBUG_LOG', true );
   define( 'WP_DEBUG_DISPLAY', false );
   ```

Email: local stacks usually cannot send mail. Enquiries are still saved under Properties › Enquiries; activate test owners from Users. Use an SMTP catcher such as Mailpit if you need to see emails.

## Checks before a commit

```bash
# PHP syntax (PHP 7.4+ compatible code; run with the oldest PHP you support if you can)
find plugins/makaazi-* themes/makaazi-theme -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v 'No syntax errors'

# JS syntax
for f in plugins/makaazi-core/assets/js/*.js plugins/makaazi-hero-slider/assets/js/*.js; do node --check "$f"; done

# Coding standards (optional)
composer global require wp-coding-standards/wpcs
phpcs --standard=WordPress plugins/makaazi-core plugins/makaazi-hero-slider themes/makaazi-theme
```

Then keep `wp-content/debug.log` empty while you click through.

## Manual test list

Run these on a site with the demo content (desktop and phone width):

- **Home**: slider autoplay and arrows, search bar submits to the Properties page with filters, featured grid, rent carousel, location tiles.
- **Properties page**: each filter, chips remove filters, sort, grid/list/map, pagination, the address updates and Back works, and it still works with JavaScript off.
- **Property page**: gallery and lightbox (keys, swipe), unit toggle, map circle (no exact pin in page source), enquiry form (validation, success, 6th message in an hour refused), similar properties.
- **Favourites and compare**: as a guest, after sign-in (lists merge), shared links, compare limit of 4.
- **Agents**: agent pages, agent box on a listing, agent sign-in, own listings only in wp-admin, the Agents switch off hides everything and on restores it.
- **Owners**: sign-up in each registration mode, verify email, submit with photos, reject with a reason, resubmit, approve, mark sold/let, delete.
- **Help**: Help Center search, the Help button on Makaazi screens, the dashboard Help tab.
- **Elementor** (if used): every widget in the editor, change settings and confirm the preview updates; the published page matches.

## Releasing

1. Bump `Version:` in `makaazi-core.php` (and `MAKAAZI_VERSION`), `makaazi-hero-slider.php` (`MKS_VERSION`) and `themes/makaazi-theme/style.css`. Asset URLs include the file modification time as well, so browsers fetch new CSS and JS.
2. If roles changed, bump `Makaazi_Roles::VER`.
3. Update the root `README.md` and these docs.
4. Zip each package from `wp-content` so the folder name stays the same:
   ```bash
   cd wp-content/plugins && zip -r makaazi-core.zip makaazi-core -x '*.DS_Store'
   zip -r makaazi-hero-slider.zip makaazi-hero-slider
   cd ../themes && zip -r makaazi-theme.zip makaazi-theme
   ```
5. Install the zips on a staging copy and run the test list before production.
