import { Card, CardHeader, CardBody, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';

const FormCard = ({
	title,
	description,
	icon,
	children,
	size = 'large',
	isLoading = false,
}) => {
	const cardClasses = [
		'aria-form-card',
		isLoading ? 'aria-form-card--loading' : '',
	]
		.filter(Boolean)
		.join(' ');

	return (
		<Card size={size} className={cardClasses}>
			<CardHeader className="aria-form-card__header">
				<Flex align="center" gap={3}>
					{icon && (
						<span className="aria-form-card__icon" aria-hidden="true">
							{icon}
						</span>
					)}
					<div className="aria-form-card__header-text">
						<h3 className="aria-form-card__title">{title}</h3>
						{description && <p className="aria-form-card__description">{description}</p>}
					</div>
				</Flex>
			</CardHeader>
			<CardBody className="aria-form-card__body">
				{isLoading ? (
					<div className="aria-form-card__loading" role="status">
						{__('Loading…', 'aria')}
					</div>
				) : (
					children
				)}
			</CardBody>
		</Card>
	);
};

FormCard.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string,
	icon: PropTypes.oneOfType([PropTypes.string, PropTypes.node]),
	children: PropTypes.node.isRequired,
	size: PropTypes.oneOf(['small', 'medium', 'large']),
	isLoading: PropTypes.bool,
};

export default FormCard;
