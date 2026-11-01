// Syntax-check every Makaazi script (no build step, plain ES5/ES2015).
import { execFileSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { join } from 'node:path';

const dirs = [ 'plugins/makaazi-core/assets/js', 'plugins/makaazi-hero-slider/assets/js' ];
let failed = 0;
for ( const d of dirs ) {
	for ( const f of readdirSync( d ).filter( ( n ) => n.endsWith( '.js' ) ) ) {
		try {
			execFileSync( process.execPath, [ '--check', join( d, f ) ], { stdio: 'pipe' } );
			console.log( 'ok  ', join( d, f ) );
		} catch ( e ) {
			failed++;
			console.error( 'FAIL', join( d, f ), '\n' + e.stderr.toString() );
		}
	}
}
process.exit( failed ? 1 : 0 );
