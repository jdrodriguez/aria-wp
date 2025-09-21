import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard, QuickActionButton } from '../../components';
import { brush, external, group, plus } from '@wordpress/icons';

const DashboardQuickActionsSection = ({ onNavigate, onTestChat }) => (
	<SectionCard
		title={__('Quick Actions', 'aria')}
		description={__(
			"Jump into common workflows to keep Aria's knowledge and experience fresh.",
			'aria'
		)}
	>
		<div className="aria-dashboard__quick-actions">
			<QuickActionButton
				onClick={() => onNavigate('admin.php?page=aria-knowledge&action=new')}
				icon={plus}
				label={__('Add Knowledge', 'aria')}
				gradient="primary"
			/>
			<QuickActionButton
				onClick={() => onNavigate('admin.php?page=aria-personality')}
				icon={group}
				label={__('Adjust Personality', 'aria')}
				gradient="purple"
			/>
			<QuickActionButton
				onClick={onTestChat}
				icon={external}
				label={__('Test Aria', 'aria')}
				gradient="green"
			/>
			<QuickActionButton
				onClick={() => onNavigate('admin.php?page=aria-design')}
				icon={brush}
				label={__('Customize Design', 'aria')}
				gradient="orange"
			/>
		</div>
	</SectionCard>
);

DashboardQuickActionsSection.propTypes = {
	onNavigate: PropTypes.func.isRequired,
	onTestChat: PropTypes.func.isRequired,
};

export default DashboardQuickActionsSection;
