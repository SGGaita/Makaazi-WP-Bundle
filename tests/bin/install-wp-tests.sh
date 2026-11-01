#!/usr/bin/env bash
# Prepare WordPress and the WordPress test library for PHPUnit.
#
# Usage:
#   tests/bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version]
#   tests/bin/install-wp-tests.sh --sqlite [wp-version]      (no database server needed)
#
# wp-version: a WordPress branch or tag such as 7.0, 6.6 or trunk (default: 7.0).
# Downloads with git from the official GitHub mirrors, so no Subversion is needed.
# Writes to $WP_TESTS_DIR (default /tmp/wordpress-tests-lib) and $WP_CORE_DIR (default /tmp/wordpress).

set -euo pipefail

SQLITE=0
if [ "${1:-}" = "--sqlite" ]; then
	SQLITE=1
	WP_VERSION=${2:-7.0}
else
	DB_NAME=${1:?db name}
	DB_USER=${2:?db user}
	DB_PASS=${3-}
	DB_HOST=${4:-localhost}
	WP_VERSION=${5:-7.0}
fi

TMP=${TMPDIR:-/tmp}
TMP=${TMP%/}
WP_TESTS_DIR=${WP_TESTS_DIR:-$TMP/wordpress-tests-lib}
WP_CORE_DIR=${WP_CORE_DIR:-$TMP/wordpress}
WP_CORE_DIR=${WP_CORE_DIR%/}
SRC="$TMP/wordpress-develop-$WP_VERSION"

# Tests come from wordpress-develop; the built core comes from the WordPress/WordPress mirror.
if [ "$WP_VERSION" = "trunk" ]; then BRANCH=trunk; CORE_BRANCH=master; else BRANCH=$WP_VERSION; CORE_BRANCH="$WP_VERSION-branch"; fi

if [ ! -d "$SRC/.git" ]; then
	echo "Fetching wordpress-develop $BRANCH…"
	git clone -q --depth 1 --filter=blob:none --sparse --branch "$BRANCH" https://github.com/WordPress/wordpress-develop.git "$SRC"
	git -C "$SRC" sparse-checkout set tests/phpunit/includes tests/phpunit/data
fi

if [ ! -f "$WP_CORE_DIR/wp-settings.php" ]; then
	echo "Fetching WordPress $CORE_BRANCH…"
	rm -rf "$WP_CORE_DIR"
	git -c advice.detachedHead=false clone -q --depth 1 --branch "$CORE_BRANCH" https://github.com/WordPress/WordPress.git "$WP_CORE_DIR"
	rm -rf "$WP_CORE_DIR/.git"
fi

if [ ! -d "$WP_TESTS_DIR/includes" ]; then
	mkdir -p "$WP_TESTS_DIR"
	cp -R "$SRC/tests/phpunit/includes" "$SRC/tests/phpunit/data" "$WP_TESTS_DIR/"
fi

if [ "$SQLITE" = "1" ] && [ ! -s "$WP_CORE_DIR/wp-content/db.php" ]; then
	rm -rf "$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration" "$TMP/sqlite-database-integration-src"
	echo "Adding the SQLite database drop-in…"
	git -c advice.detachedHead=false clone -q --depth 1 --branch v3.1.2 https://github.com/WordPress/sqlite-database-integration.git "$TMP/sqlite-database-integration-src"
	PKG="$TMP/sqlite-database-integration-src/packages/plugin-sqlite-database-integration"
	[ -d "$PKG" ] || PKG="$TMP/sqlite-database-integration-src"
	mkdir -p "$WP_CORE_DIR/wp-content/plugins"
	cp -R "$PKG" "$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration"
	# Resolve the driver symlink the same way the plugin's build script does.
	if [ -L "$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration/wp-includes/database" ]; then
		rm "$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration/wp-includes/database"
		cp -R "$TMP/sqlite-database-integration-src/packages/mysql-on-sqlite/src" "$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration/wp-includes/database"
	fi
	sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration#" \
		"$WP_CORE_DIR/wp-content/plugins/sqlite-database-integration/db.copy" > "$WP_CORE_DIR/wp-content/db.php"
	mkdir -p "$WP_CORE_DIR/wp-content/database"
fi

CONFIG="$WP_TESTS_DIR/wp-tests-config.php"
cp "$SRC/wp-tests-config-sample.php" "$CONFIG" 2>/dev/null || git -C "$SRC" show HEAD:wp-tests-config-sample.php > "$CONFIG"
sed -i.bak "s:dirname( __FILE__ ) . '/src/':'$WP_CORE_DIR/':" "$CONFIG"
sed -i.bak "s:__DIR__ . '/src/':'$WP_CORE_DIR/':" "$CONFIG"
if [ "$SQLITE" = "1" ]; then
	sed -i.bak "s/youremptytestdbnamehere/wptests/; s/yourusernamehere/x/; s/yourpasswordhere/x/" "$CONFIG"
	{
		echo "define( 'DB_DIR', '$WP_CORE_DIR/wp-content/database/' );"
		echo "define( 'DB_FILE', 'tests.sqlite' );"
	} >> "$CONFIG"
else
	sed -i.bak "s/youremptytestdbnamehere/$DB_NAME/; s/yourusernamehere/$DB_USER/; s/yourpasswordhere/$DB_PASS/; s|localhost|$DB_HOST|" "$CONFIG"
	if command -v mysqladmin >/dev/null 2>&1; then
		mysqladmin create "$DB_NAME" --user="$DB_USER" --password="$DB_PASS" --host="${DB_HOST%%:*}" --protocol=tcp 2>/dev/null || true
	fi
fi
rm -f "$CONFIG.bak"
echo "Ready. Run: WP_TESTS_DIR=$WP_TESTS_DIR vendor/bin/phpunit"
