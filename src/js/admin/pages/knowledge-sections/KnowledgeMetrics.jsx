import PropTypes from 'prop-types';
import ModernMetricCard from '../../components/ModernMetricCard.jsx';

const KnowledgeMetrics = ({ metrics }) => (
	<div className="aria-grid-metrics">
		<ModernMetricCard
			icon="knowledge"
			title={metrics.totalEntries.title}
			value={metrics.totalEntries.value}
			subtitle={metrics.totalEntries.subtitle}
			theme="primary"
		/>
		<ModernMetricCard
			icon="license"
			title={metrics.categories.title}
			value={metrics.categories.value}
			subtitle={metrics.categories.subtitle}
			theme="info"
		/>
		<ModernMetricCard
			icon="activity"
			title={metrics.lastUpdated.title}
			value={metrics.lastUpdated.value}
			subtitle={metrics.lastUpdated.subtitle}
			theme="warning"
		/>
		<ModernMetricCard
			icon="users"
			title={metrics.usageStats.title}
			value={metrics.usageStats.value}
			subtitle={metrics.usageStats.subtitle}
			theme="success"
		/>
	</div>
);

KnowledgeMetrics.propTypes = {
	metrics: PropTypes.shape({
		totalEntries: PropTypes.shape({
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
		}).isRequired,
		categories: PropTypes.shape({
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
		}).isRequired,
		lastUpdated: PropTypes.shape({
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
		}).isRequired,
		usageStats: PropTypes.shape({
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
		}).isRequired,
	}).isRequired,
};

export default KnowledgeMetrics;
