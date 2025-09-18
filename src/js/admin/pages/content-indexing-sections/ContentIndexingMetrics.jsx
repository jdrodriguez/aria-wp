import PropTypes from 'prop-types';
import ModernMetricCard from '../../components/ModernMetricCard.jsx';

const ContentIndexingMetrics = ({ metrics }) => (
	<div className="aria-grid-metrics">
		{metrics.map((metric) => (
			<ModernMetricCard
				key={metric.title}
				icon={metric.icon}
				title={metric.title}
				value={metric.value}
				subtitle={metric.subtitle}
				theme={metric.theme}
			/>
		))}
	</div>
);

ContentIndexingMetrics.propTypes = {
	metrics: PropTypes.arrayOf(
		PropTypes.shape({
			title: PropTypes.string.isRequired,
			value: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
				.isRequired,
			subtitle: PropTypes.string.isRequired,
			icon: PropTypes.string.isRequired,
			theme: PropTypes.string,
		})
	).isRequired,
};

export default ContentIndexingMetrics;
