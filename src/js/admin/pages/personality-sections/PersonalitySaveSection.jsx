import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard } from '../../components';

const PersonalitySaveSection = ({ saving, onSave }) => (
	<SectionCard
		title={__('Save Personality Settings', 'aria')}
		description={__(
			'Apply your preferences across all of Aria’s conversations.',
			'aria'
		)}
		footer={
			<div className="aria-personality__actions">
				<Button
					variant="primary"
					onClick={onSave}
					isBusy={saving}
					disabled={saving}
				>
					{saving
						? __('Saving…', 'aria')
						: __('Save Personality Settings', 'aria')}
				</Button>
			</div>
		}
	>
		<p className="aria-personality__save-hint">
			{__(
				'Settings update immediately across the chat widget and conversation summaries.',
				'aria'
			)}
		</p>
	</SectionCard>
);

PersonalitySaveSection.propTypes = {
	saving: PropTypes.bool.isRequired,
	onSave: PropTypes.func.isRequired,
};

export default PersonalitySaveSection;
