module.exports = {
	// Basic formatting
	semi: true,
	singleQuote: true,
	quoteProps: 'as-needed',
	trailingComma: 'es5',
	tabWidth: 2,
	useTabs: true,
	printWidth: 100,
	bracketSpacing: true,
	bracketSameLine: false,
	arrowParens: 'avoid',

	// File-specific overrides
	overrides: [
		{
			files: '*.php',
			options: {
				parser: 'php',
				tabWidth: 4,
				useTabs: true,
				printWidth: 120,
			},
		},
		{
			files: '*.{js,jsx}',
			options: {
				parser: 'babel',
				tabWidth: 2,
				useTabs: true,
				printWidth: 100,
			},
		},
		{
			files: '*.{css,scss,sass}',
			options: {
				parser: 'css',
				tabWidth: 2,
				useTabs: true,
				printWidth: 100,
			},
		},
		{
			files: '*.json',
			options: {
				parser: 'json',
				tabWidth: 2,
				useTabs: false,
				printWidth: 100,
			},
		},
		{
			files: '*.{yml,yaml}',
			options: {
				parser: 'yaml',
				tabWidth: 2,
				useTabs: false,
				printWidth: 100,
			},
		},
		{
			files: '*.md',
			options: {
				parser: 'markdown',
				tabWidth: 2,
				useTabs: false,
				printWidth: 80,
				proseWrap: 'always',
			},
		},
	],

	// Ignore patterns
	ignorePath: '.prettierignore',

	// Plugin configuration
	plugins: [
		'@prettier/plugin-php',
		'prettier-plugin-tailwindcss',
	],

	// Tailwind CSS plugin options
	tailwindConfig: './tailwind.config.js',
	tailwindFunctions: ['clsx', 'cn'],

	// PHP plugin options
	phpVersion: '8.0',
};
