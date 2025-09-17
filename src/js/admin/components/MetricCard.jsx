import { Card, CardHeader, CardBody, Flex } from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Reusable metric card component
 *
 * @param {Object} root0          - Component props
 * @param {string} root0.icon     - Icon to display
 * @param {string} root0.title    - Card title
 * @param {string} root0.value    - Main metric value
 * @param {string} root0.subtitle - Subtitle text
 * @param {string} root0.theme    - Color theme
 * @return {Element} MetricCard component
 */
const MetricCard = ({ icon, title, value, subtitle, theme = 'primary' }) => {
	const getThemeColors = () => {
		switch (theme) {
			case 'success':
				return { color: '#28a745' };
			case 'warning':
				return { color: '#fd7e14' };
			case 'info':
				return { color: '#17a2b8' };
			default:
				return { color: '#2271b1' };
		}
	};

	const { color } = getThemeColors();

	return (
		<Card size="large" style={{ padding: '20px', minHeight: '120px', display: 'flex', flexDirection: 'column' }}>
			<CardHeader style={{ paddingBottom: '12px' }}>
				<Flex align="center" gap={3}>
					<div style={{ fontSize: '20px' }}>{icon}</div>
					<h2
						style={{
							fontSize: '16px',
							fontWeight: '600',
							color: '#1e1e1e',
							margin: 0,
						}}
					>
						{title}
					</h2>
				</Flex>
			</CardHeader>
			<CardBody style={{ padding: 0, flex: 1, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
				<div style={{ textAlign: 'center', width: '100%' }}>
					<div
						style={{
							fontSize: '32px',
							fontWeight: '700',
							color,
							lineHeight: '1.2',
							marginBottom: '4px',
						}}
					>
						{value}
					</div>
					<div
						style={{
							fontSize: '13px',
							color: '#757575',
							fontWeight: '500',
						}}
					>
						{subtitle}
					</div>
				</div>
			</CardBody>
		</Card>
	);
};

MetricCard.propTypes = {
	icon: PropTypes.string.isRequired,
	title: PropTypes.string.isRequired,
	value: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
	subtitle: PropTypes.string.isRequired,
	theme: PropTypes.oneOf(['primary', 'success', 'warning', 'info']),
};

export default MetricCard;
