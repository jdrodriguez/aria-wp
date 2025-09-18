import PropTypes from 'prop-types';
import { MetricCard } from '../../components';

const ConversationMetrics = ({ metrics }) => (
	<div className="aria-grid-metrics">
		<MetricCard
			icon={metrics.totalConversations.icon}
			title={metrics.totalConversations.title}
			value={metrics.totalConversations.value}
			subtitle={metrics.totalConversations.subtitle}
			theme={metrics.totalConversations.theme}
		/>
		<MetricCard
			icon={metrics.activeConversations.icon}
			title={metrics.activeConversations.title}
			value={metrics.activeConversations.value}
			subtitle={metrics.activeConversations.subtitle}
			theme={metrics.activeConversations.theme}
		/>
		<MetricCard
			icon={metrics.avgResponseTime.icon}
			title={metrics.avgResponseTime.title}
			value={metrics.avgResponseTime.value}
			subtitle={metrics.avgResponseTime.subtitle}
			theme={metrics.avgResponseTime.theme}
		/>
		<MetricCard
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
			icon: PropTypes.string.isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
		activeConversations: PropTypes.shape({
			icon: PropTypes.string.isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
		avgResponseTime: PropTypes.shape({
			icon: PropTypes.string.isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
		satisfactionRate: PropTypes.shape({
			icon: PropTypes.string.isRequired,
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			theme: PropTypes.string.isRequired,
		}).isRequired,
	}).isRequired,
};

export default ConversationMetrics;
