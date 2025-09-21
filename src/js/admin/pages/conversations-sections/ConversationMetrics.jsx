import PropTypes from 'prop-types';
import ModernMetricCard from '../../components/ModernMetricCard.jsx';

const ConversationMetrics = ({ metrics }) => (
	<div className="aria-grid-metrics">
		<ModernMetricCard
			icon={metrics.totalConversations.icon}
			title={metrics.totalConversations.title}
			value={metrics.totalConversations.value}
			subtitle={metrics.totalConversations.subtitle}
			theme={metrics.totalConversations.theme}
		/>
		<ModernMetricCard
			icon={metrics.activeConversations.icon}
			title={metrics.activeConversations.title}
			value={metrics.activeConversations.value}
			subtitle={metrics.activeConversations.subtitle}
			theme={metrics.activeConversations.theme}
		/>
		<ModernMetricCard
			icon={metrics.avgResponseTime.icon}
			title={metrics.avgResponseTime.title}
			value={metrics.avgResponseTime.value}
			subtitle={metrics.avgResponseTime.subtitle}
			theme={metrics.avgResponseTime.theme}
		/>
		<ModernMetricCard
			icon={metrics.satisfactionRate.icon}
			title={metrics.satisfactionRate.title}
			value={metrics.satisfactionRate.value}
			subtitle={metrics.satisfactionRate.subtitle}
			theme={metrics.satisfactionRate.theme}
		/>
	</div>
);

ConversationMetrics.propTypes = {
	metrics: PropTypes.shape({
		totalConversations: PropTypes.shape({
			icon: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
		activeConversations: PropTypes.shape({
			icon: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
		avgResponseTime: PropTypes.shape({
			icon: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
		satisfactionRate: PropTypes.shape({
			icon: PropTypes.oneOfType([PropTypes.string, PropTypes.node]).isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
	}).isRequired,
};

export default ConversationMetrics;
