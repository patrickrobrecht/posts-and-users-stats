import globals from 'globals';
import wordpress from '@wordpress/eslint-plugin';

export default [
	...wordpress.configs.recommended,
	{
		languageOptions: {
			globals: {
				...globals.browser,
				...globals.jquery,
				Chartist: 'readonly',
				moment: 'readonly',
			},
		},
		rules: {
			camelcase: 'off',
		},
	},
];
