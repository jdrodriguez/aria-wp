import { Button } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Reusable quick action button component
 *
 * @param {Object}   props            - Component props
 * @param {Function} props.onClick    - Click handler function
 * @param {string}   props.icon       - Icon to display
 * @param {string}   props.label      - Button label text
 * @param {string}   props.gradient   - Gradient theme (primary, purple, green, orange)
 * @param {string}   props.hoverColor - Hover color
 * @return {JSX.Element} Quick action button component
 */
const QuickActionButton = ({
	onClick,
	icon,
	label,
	gradient = 'primary',
	hoverColor = '#2271b1',
}) => {
	const getGradient = () => {
		switch (gradient) {
			case 'purple':
				return 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
			case 'green':
				return 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
			case 'orange':
				return 'linear-gradient(135deg, #fd7e14 0%, #f093fb 100%)';
			default:
				return 'linear-gradient(135deg, #2271b1 0%, #1a5d8a 100%)';
		}
	};

	return (
		<Button
			variant="secondary"
			onClick={onClick}
			style={{
				height: '72px',
				fontSize: '15px',
				fontWeight: '600',
				display: 'flex',
				alignItems: 'center',
				justifyContent: 'center',
				gap: '12px',
				background: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
				border: '2px solid #e1e4e8',
				borderRadius: '12px',
				color: '#1e1e1e',
				boxShadow: '0 2px 8px rgba(0, 0, 0, 0.08)',
				transition: 'all 0.3s ease',
				cursor: 'pointer',
			}}
			onMouseEnter={(e) => {
				e.target.style.transform = 'translateY(-2px)';
				e.target.style.borderColor = hoverColor;
				e.target.style.boxShadow = `0 4px 16px ${hoverColor}15`;
			}}
			onMouseLeave={(e) => {
				e.target.style.transform = 'translateY(0)';
				e.target.style.borderColor = '#e1e4e8';
				e.target.style.boxShadow = '0 2px 8px rgba(0, 0, 0, 0.08)';
			}}
		>
			<span
				style={{
					fontSize: '20px',
					background: getGradient(),
					WebkitBackgroundClip: 'text',
					WebkitTextFillColor: 'transparent',
					backgroundClip: 'text',
					padding: '6px',
					display: 'flex',
					alignItems: 'center',
					justifyContent: 'center',
				}}
			>
				{icon}
			</span>
			{label}
		</Button>
	);
};

QuickActionButton.propTypes = {
	onClick: PropTypes.func.isRequired,
	icon: PropTypes.string.isRequired,
	label: PropTypes.string.isRequired,
	gradient: PropTypes.oneOf(['primary', 'purple', 'green', 'orange']),
	hoverColor: PropTypes.string,
};

export default QuickActionButton;
