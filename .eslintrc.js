module.exports = {
	extends: [ 'plugin:@wordpress/eslint-plugin/recommended' ],
	env: {
		browser: true,
		es6: true,
	},
	globals: {
		wp: 'readonly',
		jQuery: 'readonly',
		ariaAjax: 'readonly',
		ariaPublic: 'readonly',
		ariaAdmin: 'readonly',
	},
	rules: {
		'no-console': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
		'no-debugger': process.env.NODE_ENV === 'production' ? 'error' : 'warn',
		'no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
		'comma-dangle': ['error', {
			arrays: 'always-multiline',
			objects: 'always-multiline',
			imports: 'always-multiline',
			exports: 'always-multiline',
			functions: 'never',
		}],
		'import/no-extraneous-dependencies': ['error', {
			devDependencies: false,
			optionalDependencies: false,
			peerDependencies: false,
			packageDir: './',
		}],
	},
};