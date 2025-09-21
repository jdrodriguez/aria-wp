import PropTypes from 'prop-types';
import { isValidElement } from '@wordpress/element';

/**
 * SVG Icons for metrics
 */
const Icons = {
	activity: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<polyline points="22,12 18,12 15,21 9,3 6,12 2,12"></polyline>
		</svg>
	),
	chat: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<path d="M21 15a2 2 0 0 1-2 2H8l-4 4V5a2 2 0 0 1 2-2h13a2 2 0 0 1 2 2z"></path>
		</svg>
	),
	stack: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<polygon points="12 2 3 7 12 12 21 7 12 2"></polygon>
			<polyline points="3 12 12 17 21 12"></polyline>
			<polyline points="3 17 12 22 21 17"></polyline>
		</svg>
	),
	check: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<path d="M20 6L9 17l-5-5"></path>
			<circle cx="12" cy="12" r="10"></circle>
		</svg>
	),
	clock: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<circle cx="12" cy="12" r="10"></circle>
			<polyline points="12 6 12 12 16 14"></polyline>
		</svg>
	),
	storage: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
			<path d="M3 5v6c0 1.657 4.03 3 9 3s9-1.343 9-3V5"></path>
			<path d="M3 11v6c0 1.657 4.03 3 9 3s9-1.343 9-3v-6"></path>
		</svg>
	),
	users: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
			<circle cx="9" cy="7" r="4"></circle>
			<path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
			<path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
		</svg>
	),
	knowledge: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
			<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
		</svg>
	),
	license: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
			<circle cx="12" cy="16" r="1"></circle>
			<path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
		</svg>
	),
	smile: (
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
			<circle cx="12" cy="12" r="10"></circle>
			<path d="M8 14s1.5 2 4 2 4-2 4-2"></path>
			<line x1="9" y1="9" x2="9" y2="9"></line>
			<line x1="15" y1="9" x2="15" y2="9"></line>
		</svg>
	)
};

const SUPPORTED_ICON_KEYS = Object.keys(Icons);

/**
 * Modern metric card component with gradients and professional styling
 *
 * @param {Object} props               - Component props
 * @param {string} props.icon          - Icon type (activity, users, knowledge, license)
 * @param {string} props.title         - Card title
 * @param {string|number} props.value  - Main metric value
 * @param {string} props.subtitle      - Subtitle text
 * @param {string} props.theme         - Color theme (primary, success, warning, info)
 * @return {JSX.Element} ModernMetricCard component
 */
const ModernMetricCard = ({ icon, title, value, subtitle, theme = 'primary' }) => {
	const THEME_CLASS_MAP = {
		primary: 'aria-modern-metric-card--primary',
		success: 'aria-modern-metric-card--success',
		warning: 'aria-modern-metric-card--warning',
		info: 'aria-modern-metric-card--info',
	};

	const themeKey = THEME_CLASS_MAP[theme] ? theme : 'primary';
	const className = [
		'aria-modern-metric-card',
		THEME_CLASS_MAP[themeKey],
	]
		.filter(Boolean)
		.join(' ');

	const iconComponent = (() => {
		if (isValidElement(icon)) {
			return icon;
		}

		if (typeof icon === 'string' && Icons[icon]) {
			return Icons[icon];
		}

		return Icons.activity;
	})();

	return (
		<div className={className}>
			<div className="aria-modern-metric-card__icon" aria-hidden="true">
				{iconComponent}
			</div>
			<div className="aria-modern-metric-card__body">
				<p className="aria-modern-metric-card__title">{title}</p>
				<p className="aria-modern-metric-card__value">{value}</p>
				<p className="aria-modern-metric-card__subtitle">{subtitle}</p>
			</div>
		</div>
	);
};

ModernMetricCard.propTypes = {
	icon: PropTypes.oneOfType([
		PropTypes.oneOf(SUPPORTED_ICON_KEYS),
		PropTypes.node,
	]).isRequired,
	title: PropTypes.string.isRequired,
	value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
	subtitle: PropTypes.string.isRequired,
	theme: PropTypes.oneOf(['primary', 'success', 'warning', 'info']),
};

export default ModernMetricCard;
