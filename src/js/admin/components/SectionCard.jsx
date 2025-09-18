import { Card, CardHeader, CardBody } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Consistent card wrapper for page sections with shared spacing and typography.
 */
const SectionCard = ({
	title,
	description,
	actions,
	children,
	footer,
	size = 'large',
	className = '',
}) => {
	return (
		<Card className={`aria-section-card ${className}`.trim()} size={size}>
			{(title || description || actions) && (
				<CardHeader className="aria-section-card__header">
					<div className="aria-section-card__header-content">
						{title && <h3 className="aria-section-card__title">{title}</h3>}
						{description && (
							<p className="aria-section-card__description">{description}</p>
						)}
					</div>
					{actions && <div className="aria-section-card__actions">{actions}</div>}
				</CardHeader>
			)}
			<CardBody className="aria-section-card__body">{children}</CardBody>
			{footer && <div className="aria-section-card__footer">{footer}</div>}
		</Card>
	);
};

SectionCard.propTypes = {
	title: PropTypes.node,
	description: PropTypes.node,
	actions: PropTypes.node,
	children: PropTypes.node.isRequired,
	footer: PropTypes.node,
	size: PropTypes.oneOf(['small', 'medium', 'large']),
	className: PropTypes.string,
};

export default SectionCard;
