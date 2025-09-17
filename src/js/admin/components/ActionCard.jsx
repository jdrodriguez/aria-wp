import { Card, CardBody, Button, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Action card component for displaying actionable items with quick actions
 *
 * @param {Object} props               - Component props
 * @param {string} props.title         - Card title
 * @param {string} [props.description] - Description text
 * @param {string} [props.icon]        - Icon to display
 * @param {Array} [props.actions]      - Array of action buttons
 * @param {Function} [props.onClick]   - Main click handler for the card
 * @param {string} [props.theme]       - Color theme (primary, success, warning, info)
 * @param {boolean} [props.disabled]   - Disabled state
 * @param {boolean} [props.isLoading]  - Loading state
 * @return {JSX.Element} ActionCard component
 */
const ActionCard = ({
	title,
	description,
	icon,
	actions = [],
	onClick,
	theme = 'primary',
	disabled = false,
	isLoading = false
}) => {
	const getThemeColors = () => {
		switch (theme) {
			case 'success':
				return {
					borderColor: '#28a745',
					iconColor: '#28a745',
					bgHover: '#f8fff9'
				};
			case 'warning':
				return {
					borderColor: '#fd7e14',
					iconColor: '#fd7e14',
					bgHover: '#fffbf5'
				};
			case 'info':
				return {
					borderColor: '#17a2b8',
					iconColor: '#17a2b8',
					bgHover: '#f5fcfd'
				};
			default:
				return {
					borderColor: '#2271b1',
					iconColor: '#2271b1',
					bgHover: '#f6f9fc'
				};
		}
	};

	const themeColors = getThemeColors();

	const handleKeyPress = (event) => {
		if (onClick && !disabled && !isLoading && (event.key === 'Enter' || event.key === ' ')) {
			event.preventDefault();
			onClick();
		}
	};

	return (
		<Card
			style={{
				padding: 0,
				marginBottom: '16px',
				border: `2px solid ${disabled ? '#e2e3e5' : themeColors.borderColor}20`,
				borderRadius: '8px',
				cursor: onClick && !disabled ? 'pointer' : 'default',
				opacity: disabled || isLoading ? 0.6 : 1,
				transition: 'all 0.2s ease',
				':hover': onClick && !disabled ? {
					backgroundColor: themeColors.bgHover,
					borderColor: `${themeColors.borderColor}40`
				} : {},
				':focus-visible': onClick && !disabled ? {
					outline: '2px solid #2271b1',
					outlineOffset: '2px'
				} : {}
			}}
			onClick={onClick && !disabled && !isLoading ? onClick : undefined}
			onKeyPress={onClick && !disabled && !isLoading ? handleKeyPress : undefined}
			tabIndex={onClick && !disabled && !isLoading ? 0 : undefined}
			role={onClick ? 'button' : undefined}
			aria-disabled={disabled || isLoading}
		>
			<CardBody style={{ padding: '20px' }}>
				<Flex direction="column" gap={3}>
					{/* Header */}
					<Flex align="flex-start" gap={3}>
						{icon && (
							<div 
								style={{
									fontSize: '24px',
									color: disabled ? '#6c757d' : themeColors.iconColor,
									minWidth: '24px',
									transition: 'color 0.2s ease'
								}}
								aria-hidden="true"
							>
								{icon}
							</div>
						)}
						<div style={{ flex: 1, minWidth: 0 }}>
							<h4 style={{
								fontSize: '16px',
								fontWeight: '600',
								color: disabled ? '#6c757d' : '#1e1e1e',
								margin: 0,
								marginBottom: description ? '4px' : 0,
								transition: 'color 0.2s ease'
							}}>
								{title}
							</h4>
							{description && (
								<p style={{
									fontSize: '14px',
									color: disabled ? '#9ca3af' : '#757575',
									margin: 0,
									lineHeight: '1.4',
									transition: 'color 0.2s ease'
								}}>
									{description}
								</p>
							)}
						</div>
					</Flex>

					{/* Loading State */}
					{isLoading && (
						<div style={{
							textAlign: 'center',
							color: '#757575',
							fontSize: '14px',
							padding: '10px 0'
						}}>
							Processing...
						</div>
					)}

					{/* Actions */}
					{actions.length > 0 && !isLoading && (
						<Flex gap={2} justify="flex-end">
							{actions.map((action, index) => (
								<Button
									key={index}
									variant={action.variant || 'secondary'}
									size="small"
									onClick={(e) => {
										e.stopPropagation();
										if (action.onClick && !disabled) {
											action.onClick();
										}
									}}
									disabled={disabled || action.disabled}
									style={{
										fontSize: '14px',
										fontWeight: '600',
										padding: '8px 16px',
										borderRadius: '8px',
										...(action.variant === 'primary' && {
											backgroundColor: themeColors.iconColor,
											borderColor: themeColors.iconColor,
											background: `linear-gradient(135deg, ${themeColors.iconColor} 0%, ${themeColors.iconColor}dd 100%)`,
											boxShadow: `0 2px 8px ${themeColors.iconColor}20`,
											transition: 'all 0.2s ease'
										}),
										...(action.variant === 'secondary' && {
											borderColor: '#ddd',
											color: '#555',
											backgroundColor: '#fff',
											transition: 'all 0.2s ease'
										})
									}}
								>
									{action.label}
								</Button>
							))}
						</Flex>
					)}
				</Flex>
			</CardBody>
		</Card>
	);
};

ActionCard.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string,
	icon: PropTypes.string,
	actions: PropTypes.arrayOf(PropTypes.shape({
		label: PropTypes.string.isRequired,
		onClick: PropTypes.func.isRequired,
		variant: PropTypes.oneOf(['primary', 'secondary', 'tertiary']),
		disabled: PropTypes.bool
	})),
	onClick: PropTypes.func,
	theme: PropTypes.oneOf(['primary', 'success', 'warning', 'info']),
	disabled: PropTypes.bool,
	isLoading: PropTypes.bool,
};

export default ActionCard;