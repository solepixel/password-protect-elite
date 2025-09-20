import defaultConfig from '@wordpress/scripts/config/webpack.config.js';
import { fileURLToPath } from 'url';
import { dirname } from 'path';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

export default {
	...defaultConfig,
	entry: {
		'password-entry': './src/blocks/password-entry/index.js',
		'protected-content': './src/blocks/protected-content/index.js',
	},
	output: {
		...defaultConfig.output,
		path: __dirname + '/build',
	},
};
