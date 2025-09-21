import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import ModernMetricCard from '../../components/ModernMetricCard';

const DashboardMetricsSection = ({ conversationsToday, totalConversations, knowledgeCount, licenseLabel }) => (
	<div className="aria-dashboard__metrics aria-grid-metrics">
		<ModernMetricCard
			icon="activity"
			title={__("Today's Activity", 'aria')}
			value={conversationsToday}
			subtitle={__('Conversations Today', 'aria')}
			theme="primary"
		/>
		<ModernMetricCard
			icon="users"
			title={__('Total Activity', 'aria')}
			value={totalConversations}
			subtitle={__('Total Conversations', 'aria')}
			theme="info"
		/>
		<ModernMetricCard
			icon="knowledge"
			title={__('Knowledge Base', 'aria')}
			value={knowledgeCount}
			subtitle={__('Knowledge Entries', 'aria')}
			theme="success"
		/>
		<ModernMetricCard
			icon="license"
			title={__('License Status', 'aria')}
			value={licenseLabel}
			subtitle={__('Current Status', 'aria')}
			theme="warning"
		/>
	</div>
);

DashboardMetricsSection.propTypes = {
	conversationsToday: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
	totalConversations: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
	knowledgeCount: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
	licenseLabel: PropTypes.string.isRequired,
};

export default DashboardMetricsSection;
