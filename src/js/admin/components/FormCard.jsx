import { Card, CardHeader, CardBody, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Reusable form card component for consistent form sections
 *
 * @param {Object} props               - Component props
 * @param {string} props.title         - Card title
 * @param {string} [props.description] - Optional description text
 * @param {string} [props.icon]        - Optional icon to display
 * @param {*}      props.children      - Form content
 * @param {string} [props.size]        - Card size (small, medium, large)
 * @param {boolean} [props.isLoading]  - Loading state
 * @return {JSX.Element} FormCard component
 */
const FormCard = ({ 
	title, 
	description, 
	icon, 
	children, 
	size = 'large',
	isLoading = false 
}) => {
	return (
		<Card 
			size={size} 
			style={{ 
				padding: '24px', 
				marginBottom: '24px',
				opacity: isLoading ? 0.6 : 1,
				transition: 'opacity 0.2s ease'
			}}
		>
			<CardHeader style={{ paddingBottom: '16px' }}>
				<Flex align="center" gap={3}>
					{icon && (
						<div style={{ 
							fontSize: '20px', 
							color: '#2271b1',
							minWidth: '20px'
						}}>
							{icon}
						</div>
					)}
					<div style={{ flex: 1 }}>
						<h3 style={{
							fontSize: '18px',
							fontWeight: '600',
							color: '#1e1e1e',
							margin: 0,
							marginBottom: description ? '4px' : 0
						}}>
							{title}
						</h3>
						{description && (
							<p style={{
								fontSize: '14px',
								color: '#757575',
								margin: 0,
								lineHeight: '1.4'
							}}>
								{description}
							</p>
						)}
					</div>
				</Flex>
			</CardHeader>
			<CardBody style={{ padding: isLoading ? '20px 0' : '0' }}>
				{isLoading ? (
					<div style={{
						textAlign: 'center',
						color: '#757575',
						fontSize: '14px'
					}}>
						Loading...
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
	icon: PropTypes.string,
	children: PropTypes.node.isRequired,
	size: PropTypes.oneOf(['small', 'medium', 'large']),
	isLoading: PropTypes.bool,
};

export default FormCard;