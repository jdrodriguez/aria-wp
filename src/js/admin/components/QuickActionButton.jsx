import { Button, Icon } from '@wordpress/components';
import { isValidElement } from 'react';
import PropTypes from 'prop-types';

const HOVER_FALLBACKS = {
	primary: '#1a5d8a',
	purple: '#6650e6',
	green: '#1d9f6f',
	orange: '#f06b24',
};

const getShadowFromHex = (hex) => {
	if (typeof hex !== 'string' || hex[0] !== '#' || (hex.length !== 7 && hex.length !== 4)) {
		return undefined;
	}

	let normalized = hex.slice(1);
	if (normalized.length === 3) {
		normalized = normalized
			.split('')
			.map((char) => char + char)
			.join('');
	}

	const r = parseInt(normalized.slice(0, 2), 16);
	const g = parseInt(normalized.slice(2, 4), 16);
	const b = parseInt(normalized.slice(4, 6), 16);

	if ([r, g, b].some(Number.isNaN)) {
		return undefined;
	}

	return `rgba(${r}, ${g}, ${b}, 0.18)`;
};

const QuickActionButton = ({ onClick, icon, label, gradient = 'primary', hoverColor }) => {
	const classNames = ['aria-quick-action-button', `aria-quick-action-button--${gradient}`]
		.filter(Boolean)
		.join(' ');

	const resolvedHoverColor = hoverColor || HOVER_FALLBACKS[gradient] || HOVER_FALLBACKS.primary;
	const customShadow = hoverColor ? getShadowFromHex(hoverColor) : undefined;
	const inlineStyle = hoverColor
		? {
			'--aria-qab-hover': resolvedHoverColor,
			...(customShadow ? { '--aria-qab-shadow': customShadow } : {}),
		}
		: undefined;

	const renderIcon = () => {
		if (typeof icon === 'string') {
			return <span aria-hidden="true">{icon}</span>;
		}

		if (isValidElement(icon)) {
			return icon;
		}

		return <Icon icon={icon} size={24} />;
	};

	return (
		<Button
			variant="secondary"
			onClick={onClick}
			className={classNames}
			style={inlineStyle}
		>
			<span className="aria-quick-action-button__icon">{renderIcon()}</span>
			<span className="aria-quick-action-button__label">{label}</span>
		</Button>
	);
};

QuickActionButton.propTypes = {
	onClick: PropTypes.func.isRequired,
	icon: PropTypes.oneOfType([PropTypes.node, PropTypes.object, PropTypes.string]).isRequired,
	label: PropTypes.string.isRequired,
	gradient: PropTypes.oneOf(['primary', 'purple', 'green', 'orange']),
	hoverColor: PropTypes.string,
};

export default QuickActionButton;
