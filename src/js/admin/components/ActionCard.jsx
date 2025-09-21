import { Card, CardBody, Button, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

const ActionCard = ({
	title,
	description,
	icon,
	actions = [],
	onClick,
	theme = 'primary',
	disabled = false,
	isLoading = false,
}) => {
	const isInteractive = Boolean(onClick) && !disabled && !isLoading;

	const cardClasses = [
		'aria-action-card',
		`aria-action-card--${theme}`,
		isInteractive ? 'aria-action-card--interactive' : '',
		disabled || isLoading ? 'aria-action-card--disabled' : '',
	]
		.filter(Boolean)
		.join(' ');

	const triggerAction = () => {
		if (onClick) {
			onClick();
		}
	};

	const handleKeyPress = (event) => {
		if (isInteractive && (event.key === 'Enter' || event.key === ' ')) {
			event.preventDefault();
			triggerAction();
		}
	};

	return (
		<Card
			className={cardClasses}
			onClick={isInteractive ? triggerAction : undefined}
			onKeyPress={isInteractive ? handleKeyPress : undefined}
			tabIndex={isInteractive ? 0 : undefined}
			role={onClick ? 'button' : undefined}
			aria-disabled={disabled || isLoading}
		>
			<CardBody className="aria-action-card__body">
				<Flex direction="column" gap={3} className="aria-action-card__layout">
					<Flex align="flex-start" gap={3} className="aria-action-card__header">
						{icon && (
							<div className="aria-action-card__icon" aria-hidden="true">
								{icon}
							</div>
						)}
						<div className="aria-action-card__content">
							<h4 className="aria-action-card__title">{title}</h4>
							{description && <p className="aria-action-card__description">{description}</p>}
						</div>
					</Flex>

					{actions.length > 0 && (
						<Flex gap={2} wrap className="aria-action-card__actions">
							{actions.map((action, index) => {
								const buttonClasses = [
									'aria-action-card__button',
									action.variant ? `aria-action-card__button--${action.variant}` : '',
								]
									.filter(Boolean)
									.join(' ');

									return (
										<Button
											key={`${action.label}-${index}`}
											variant={action.variant || 'secondary'}
											onClick={(event) => {
												event.stopPropagation();
												if (action.onClick) {
													action.onClick(event);
												}
											}}
											className={buttonClasses}
											disabled={disabled || action.disabled || isLoading}
										>
											{action.label}
										</Button>
								);
							})}
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
	icon: PropTypes.oneOfType([PropTypes.string, PropTypes.node]),
	actions: PropTypes.arrayOf(
		PropTypes.shape({
			label: PropTypes.string.isRequired,
			onClick: PropTypes.func.isRequired,
			variant: PropTypes.oneOf(['primary', 'secondary', 'tertiary']),
			disabled: PropTypes.bool,
		}),
	),
	onClick: PropTypes.func,
	theme: PropTypes.oneOf(['primary', 'success', 'warning', 'info']),
	disabled: PropTypes.bool,
	isLoading: PropTypes.bool,
};

export default ActionCard;
