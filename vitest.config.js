import { defineConfig } from 'vitest/config';

export default defineConfig( {
	test: {
		globals: true,
		environment: 'jsdom',
		include: [ 'tests/js/**/*.test.{js,jsx}' ],
		passWithNoTests: true,
		coverage: {
			include: [ 'assets/js/**/*.js' ],
		},
	},
} );
