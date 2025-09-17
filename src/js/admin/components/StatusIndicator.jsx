import { Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Status indicator component for displaying connection/service status
 *
 * @param {Object} props            - Component props
 * @param {string} props.status     - Status type (connected, disconnected, pending, error)
 * @param {string} [props.label]    - Status label text
 * @param {string} [props.size]     - Size (small, medium, large)
 * @param {boolean} [props.animate] - Whether to animate pending status
 * @return {JSX.Element} StatusIndicator component
 */
const StatusIndicator = ({ 
	status, 
	label, 
	size = 'medium',
	animate = true 
}) => {
	const getStatusConfig = () => {
		switch (status) {
			case 'connected':
				return {
					color: '#28a745',
					bgColor: '#d4edda',
					icon: '●',
					text: label || 'Connected'
				};
			case 'disconnected':
				return {
					color: '#dc3545',
					bgColor: '#f8d7da',
					icon: '●',
					text: label || 'Disconnected'
				};
			case 'pending':
				return {
					color: '#fd7e14',
					bgColor: '#fff3cd',
					icon: '●',
					text: label || 'Connecting...'
				};
			case 'error':
				return {
					color: '#dc3545',
					bgColor: '#f8d7da',
					icon: '⚠',
					text: label || 'Error'
				};
			default:
				return {
					color: '#6c757d',
					bgColor: '#e2e3e5',
					icon: '●',
					text: label || 'Unknown'
				};
		}
	};

	const getSizeConfig = () => {
		switch (size) {
			case 'small':
				return {
					fontSize: '12px',
					padding: '4px 8px',
					iconSize: '8px'
				};
			case 'large':
				return {
					fontSize: '16px',
					padding: '8px 16px',
					iconSize: '12px'
				};
			default: // medium
				return {
					fontSize: '14px',
					padding: '6px 12px',
					iconSize: '10px'
				};
		}
	};

	const statusConfig = getStatusConfig();
	const sizeConfig = getSizeConfig();

	return (
		<Flex 
			align="center" 
			gap={2}
			style={{
				display: 'inline-flex',
				backgroundColor: statusConfig.bgColor,
				color: statusConfig.color,
				fontSize: sizeConfig.fontSize,
				fontWeight: '500',
				padding: sizeConfig.padding,
				borderRadius: '16px',
				border: `1px solid ${statusConfig.color}20`
			}}
		>
			<span 
				style={{
					fontSize: sizeConfig.iconSize,
					color: statusConfig.color,
					animation: (status === 'pending' && animate) ? 'pulse 1.5s infinite' : 'none'
				}}
			>
				{statusConfig.icon}
			</span>
			<span>{statusConfig.text}</span>
			<style jsx>{`
				@keyframes pulse {
					0%, 100% { opacity: 1; }
					50% { opacity: 0.5; }
				}
			`}</style>
		</Flex>
	);
};

StatusIndicator.propTypes = {
	status: PropTypes.oneOf(['connected', 'disconnected', 'pending', 'error']).isRequired,
	label: PropTypes.string,
	size: PropTypes.oneOf(['small', 'medium', 'large']),
	animate: PropTypes.bool,
};

export default StatusIndicator;