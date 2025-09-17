import { Card, CardBody, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Reusable page header component with Card-based design
 * @param {Object} props               - Component props
 * @param {string} props.title         - The page title
 * @param {string} [props.description] - Optional description text
 * @param {*}      [props.children]    - Optional children elements
 * @param {string} [props.className]   - Optional CSS class name
 * @return {JSX.Element} PageHeader component
 */
const PageHeader = ({ title, description, children, className = '' }) => {
	return (
		<Card 
			size="large" 
			className={`aria-page-header-card ${className}`}
			style={{ 
				marginBottom: '24px',
				background: 'linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%)',
				border: '1px solid #e1e4e8'
			}}
		>
			<CardBody style={{ padding: '32px' }}>
				<Flex direction="column" gap={2}>
					<div>
						<h1 style={{
							fontSize: '28px',
							fontWeight: '700',
							color: '#1e1e1e',
							margin: 0,
							marginBottom: description ? '8px' : 0,
							lineHeight: '1.2'
						}}>
							{title}
						</h1>
						{description && (
							<p style={{
								fontSize: '16px',
								color: '#6c757d',
								margin: 0,
								lineHeight: '1.5',
								fontWeight: '400'
							}}>
								{description}
							</p>
						)}
					</div>
					{children && (
						<div style={{ marginTop: '16px' }}>
							{children}
						</div>
					)}
				</Flex>
			</CardBody>
		</Card>
	);
};

PageHeader.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string,
	children: PropTypes.node,
	className: PropTypes.string,
};

export default PageHeader;
