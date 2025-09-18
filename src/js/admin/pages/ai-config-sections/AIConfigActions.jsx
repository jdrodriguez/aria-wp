import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';

const AIConfigActions = ({ onSave, isSaving }) => (
	<SectionCard
		title={__('Save Configuration', 'aria')}
		description={__(
			'Save your provider credentials and model preferences for Aria conversations.',
			'aria'
		)}
		footer={
			<Button
				variant="primary"
				onClick={onSave}
				isBusy={isSaving}
				disabled={isSaving}
			>
				{isSaving ? __('Saving…', 'aria') : __('Save Configuration', 'aria')}
			</Button>
		}
	>
		<p className="aria-ai-config__actions-note">
			{__(
				'We recommend testing your API connection before saving to avoid downtime for your visitors.',
				'aria'
			)}
		</p>
	</SectionCard>
);

AIConfigActions.propTypes = {
	onSave: PropTypes.func.isRequired,
	isSaving: PropTypes.bool.isRequired,
};

export default AIConfigActions;
