import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard } from '../../components';
import ModernMetricCard from '../../components/ModernMetricCard.jsx';

const AIConfigUsageSection = ({ usageStats, provider }) => {
	const hasActivity = Array.isArray(usageStats.recent_activity) && usageStats.recent_activity.length > 0;
	const formattedMonthlyUsage = Number(usageStats.monthly_usage || 0).toLocaleString();
	const formattedCost = Number(usageStats.estimated_cost || 0).toFixed(2);

	return (
		<SectionCard
			title={__('Usage Insights', 'aria')}
			description={__(
				'Monitor token consumption and review recent API calls to stay within budget.',
				'aria'
			)}
		>
			<div className="aria-ai-config__usage-grid">
				<ModernMetricCard
					icon="stack"
					title={__('Tokens This Month', 'aria')}
					value={formattedMonthlyUsage}
					subtitle={__('Usage Volume', 'aria')}
					theme="primary"
				/>

				{provider === 'openai' && usageStats.estimated_cost > 0 && (
					<ModernMetricCard
						icon="storage"
						title={__('Estimated Cost', 'aria')}
						value={`$${formattedCost}`}
						subtitle={__('This billing cycle', 'aria')}
						theme="warning"
					/>
				)}
			</div>

			<div className="aria-ai-config__activity">
				<h4 className="aria-ai-config__activity-title">
					{__('Recent Activity', 'aria')}
				</h4>

				{hasActivity ? (
					<ul className="aria-ai-config__activity-list">
						{usageStats.recent_activity.slice(0, 5).map((activity, index) => (
							<li key={`${activity.timestamp}-${index}`} className="aria-ai-config__activity-item">
								<span className="aria-ai-config__activity-time">
									{new Date(activity.timestamp).toLocaleString()}
								</span>
								<span className="aria-ai-config__activity-tokens">
									{activity.tokens_used?.toLocaleString() || 0}{' '}
									{__('tokens', 'aria')}
								</span>
							</li>
						))}
					</ul>
				) : (
					<p className="aria-ai-config__empty-activity">
						{__('No recent usage data available yet.', 'aria')}
					</p>
				)}
			</div>
		</SectionCard>
	);
};

AIConfigUsageSection.propTypes = {
	usageStats: PropTypes.shape({
		monthly_usage: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		estimated_cost: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
		recent_activity: PropTypes.arrayOf(
			PropTypes.shape({
				timestamp: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
				tokens_used: PropTypes.number,
			})
		),
	}).isRequired,
	provider: PropTypes.oneOf(['openai', 'gemini']).isRequired,
};

export default AIConfigUsageSection;
