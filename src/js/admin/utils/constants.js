/**
 * Constants for Aria Admin
 */

export const BUSINESS_TYPES = [
	{
		value: 'general',
		label: 'General Business',
		description: 'Standard business communication',
	},
	{
		value: 'healthcare',
		label: 'Healthcare',
		description: 'Medical and health services',
	},
	{
		value: 'retail',
		label: 'Retail',
		description: 'Sales and customer service',
	},
	{
		value: 'technology',
		label: 'Technology',
		description: 'Tech support and services',
	},
	{
		value: 'education',
		label: 'Education',
		description: 'Schools and learning',
	},
	{
		value: 'finance',
		label: 'Finance',
		description: 'Banking and financial services',
	},
];

export const TONE_SETTINGS = [
	{
		value: 'professional',
		label: 'Professional',
		description: 'Formal and business-like',
	},
	{
		value: 'friendly',
		label: 'Friendly',
		description: 'Warm and approachable',
	},
	{
		value: 'casual',
		label: 'Casual',
		description: 'Relaxed and informal',
	},
	{
		value: 'formal',
		label: 'Formal',
		description: 'Very professional and structured',
	},
];

export const PERSONALITY_TRAITS = [
	{ value: 'helpful', label: 'Helpful & Supportive' },
	{ value: 'knowledgeable', label: 'Knowledgeable' },
	{ value: 'empathetic', label: 'Empathetic' },
	{ value: 'efficient', label: 'Efficient' },
	{ value: 'patient', label: 'Patient' },
	{ value: 'proactive', label: 'Proactive' },
];

export const WIDGET_POSITIONS = [
	{ label: 'Bottom Right', value: 'bottom-right' },
	{ label: 'Bottom Left', value: 'bottom-left' },
	{ label: 'Top Right', value: 'top-right' },
	{ label: 'Top Left', value: 'top-left' },
];

export const WIDGET_SIZES = [
	{ label: 'Small', value: 'small' },
	{ label: 'Medium', value: 'medium' },
	{ label: 'Large', value: 'large' },
];

export const WIDGET_THEMES = [
	{ label: 'Light', value: 'light' },
	{ label: 'Dark', value: 'dark' },
	{ label: 'Auto', value: 'auto' },
];

export const DISPLAY_OPTIONS = [
	{ label: 'All pages', value: 'all' },
	{ label: 'Homepage only', value: 'home' },
	{ label: 'Blog posts', value: 'posts' },
	{ label: 'Static pages', value: 'pages' },
];
