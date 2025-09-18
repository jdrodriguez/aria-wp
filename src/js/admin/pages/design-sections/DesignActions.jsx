import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';

const DesignActions = ({ onSave, isSaving }) => (
	<SectionCard
		title={__('Save Design Settings', 'aria')}
		description={__(
			'Apply your visual updates so visitors immediately experience the new design.',
			'aria'
		)}
		footer={
			<Button
				variant="primary"
				onClick={onSave}
				isBusy={isSaving}
				disabled={isSaving}
			>
				{isSaving ? __('Saving…', 'aria') : __('Save Design Settings', 'aria')}
			</Button>
		}
	>
		<p className="aria-design__actions-note">
			{__(
				'Styling updates go live as soon as you save. Consider previewing in a private window after publishing.',
				'aria'
			)}
		</p>
	</SectionCard>
);

DesignActions.propTypes = {
	onSave: PropTypes.func.isRequired,
	isSaving: PropTypes.bool.isRequired,
};

export default DesignActions;
