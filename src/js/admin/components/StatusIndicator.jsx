import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

const STATUS_PRESETS = {
	connected: {
		icon: '●',
		label: __('Connected', 'aria'),
		className: 'aria-status-indicator--connected',
	},
	disconnected: {
		icon: '●',
		label: __('Disconnected', 'aria'),
		className: 'aria-status-indicator--disconnected',
	},
	pending: {
		icon: '●',
		label: __('Connecting…', 'aria'),
		className: 'aria-status-indicator--pending',
	},
	error: {
		icon: '⚠',
		label: __('Error', 'aria'),
		className: 'aria-status-indicator--error',
	},
};

const SIZE_PRESETS = {
	small: 'aria-status-indicator--small',
	medium: 'aria-status-indicator--medium',
	large: 'aria-status-indicator--large',
};

const StatusIndicator = ({ status, label, size = 'medium', animate = true }) => {
	const preset = STATUS_PRESETS[status] || STATUS_PRESETS.disconnected;
	const sizeClass = SIZE_PRESETS[size] || SIZE_PRESETS.medium;

	const indicatorClassNames = ['aria-status-indicator', preset.className, sizeClass]
		.filter(Boolean)
		.join(' ');

	const iconClassNames = ['aria-status-indicator__icon', animate && status === 'pending' ? 'is-animated' : '']
		.filter(Boolean)
		.join(' ');

	return (
		<span className={indicatorClassNames}>
			<span className={iconClassNames} aria-hidden="true">
				{preset.icon}
			</span>
			<span className="aria-status-indicator__label">{label || preset.label}</span>
		</span>
	);
};

StatusIndicator.propTypes = {
	status: PropTypes.oneOf(['connected', 'disconnected', 'pending', 'error']).isRequired,
	label: PropTypes.string,
	size: PropTypes.oneOf(['small', 'medium', 'large']),
	animate: PropTypes.bool,
};

export default StatusIndicator;
