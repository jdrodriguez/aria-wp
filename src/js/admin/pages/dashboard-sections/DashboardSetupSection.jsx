import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';

const DashboardSetupSection = ({ steps, onNavigate }) => {
	const pendingSteps = steps.filter((step) => !step.completed);

	if (!pendingSteps.length) {
		return null;
	}

	return (
		<SectionCard
			title={__('Quick Setup', 'aria')}
			description={__(
				'Complete these steps to finish configuring Aria for your site.',
				'aria'
			)}
		>
			<div className="aria-dashboard__setup-list">
				{pendingSteps.map((step, index) => (
					<div
						className={`aria-dashboard__setup-item${step.completed ? ' is-complete' : ''}`}
						key={`${step.title}-${index}`}
					>
						<div className="aria-dashboard__setup-content">
							<div className="aria-dashboard__setup-title">{step.title}</div>
							{step.description && (
								<p className="aria-dashboard__setup-description">{step.description}</p>
							)}
						</div>
						<div className="aria-dashboard__setup-status">
							<span aria-hidden="true">{step.completed ? '✅' : '⏳'}</span>
							<span>
								{step.completed ? __('Completed', 'aria') : __('Pending', 'aria')}
							</span>
							{!step.completed && step.link && (
								<Button
									variant="secondary"
									size="small"
									onClick={() => onNavigate(step.link)}
								>
									{__('Configure', 'aria')}
								</Button>
							)}
						</div>
					</div>
				))}
			</div>
		</SectionCard>
	);
};

DashboardSetupSection.propTypes = {
	steps: PropTypes.arrayOf(
		PropTypes.shape({
			title: PropTypes.string.isRequired,
			description: PropTypes.string,
			link: PropTypes.string,
			completed: PropTypes.bool,
		})
	).isRequired,
	onNavigate: PropTypes.func.isRequired,
};

export default DashboardSetupSection;
