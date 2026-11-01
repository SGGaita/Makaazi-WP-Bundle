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

## Automated tests

| Suite | Where | What it covers |
|---|---|---|
| PHPUnit (`tests/phpunit`, 66 tests) | WordPress test library | formatting, statuses, privacy offset, search filters with and without the index table, index sync and rebuild, REST endpoints (enquiries, rate limit, honeypot, saved lists), roles and switches, Schema.org and Yoast graph, shortcodes, related listings, Help content |
| Playwright (`tests/e2e`, 28 tests on desktop and phone) | a running site (wp-env in CI) | hero search, Short Stay prices, AJAX filters and Back, map view, lightbox by keyboard, unit switch, enquiry form, favourites and compare, owner dashboard and wp-admin block, Help Center search, axe-core WCAG 2.1 AA checks on 7 pages |
| PHPCS (`.phpcs.xml.dist`) | | WordPress-Core, security, i18n text domains, prefixes, PHP 7.4+ compatibility |

### Run PHPUnit

```bash
composer install
# MySQL:
bash tests/bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 7.0
# …or no database server at all (SQLite drop-in):
bash tests/bin/install-wp-tests.sh --sqlite 7.0
WP_TESTS_DIR=/tmp/wordpress-tests-lib vendor/bin/phpunit
```

The script fetches WordPress and the test library with git (no Subversion). The second argument picks the version: `6.6`, `7.0` or `trunk`.

### Run the end-to-end tests

```bash
npm install
npm run env:start          # wp-env (Docker): WordPress on :8888, theme active, demo imported
npx playwright install chromium
npm run test:e2e
```

Against another site: `WP_BASE_URL=http://localhost/makaazi WP_ADMIN_USER=… WP_ADMIN_PASS=… npm run test:e2e`. The site needs the demo content and an owner account `e2e-owner` / `e2e-owner-pass` (run `tests/e2e/setup.php` with `wp eval-file`).

### Lint

```bash
composer lint              # PHPCS
npm run lint:js            # JavaScript syntax
```

## Continuous integration

`.github/workflows/ci.yml` runs on every push and pull request:

1. **Lint**: PHP syntax on 7.4 and 8.3, PHPCS, JS syntax.
2. **PHPUnit**: PHP 7.4, 8.1 and 8.3 × WordPress 6.6 and 7.0, on MySQL 8.
3. **End-to-end**: wp-env, demo import, Playwright; the HTML report is uploaded when a test fails.

`.github/workflows/release.yml` runs when you push a tag such as `v1.0.0`: it checks the three version headers match the tag, builds `makaazi-core.zip`, `makaazi-hero-slider.zip` and `makaazi-theme.zip` (`tests/bin/build-zips.sh`) and attaches them to a GitHub release.

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
4. Commit, then tag and push: `git tag v1.0.0 && git push origin v1.0.0`. The Release workflow builds the zips and publishes them. To build locally: `bash tests/bin/build-zips.sh` (output in `dist/`).
5. Install the zips on a staging copy and run the manual list before production.
