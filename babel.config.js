module.exports = {
	presets: [
		[
			'@babel/preset-env',
			{
				targets: { node: 'current' },
			},
		],
		[
			'@babel/preset-react',
			{
				runtime: 'classic',
				pragma: 'wp.element.createElement',
				pragmaFrag: 'wp.element.Fragment',
			},
		],
	],
};
