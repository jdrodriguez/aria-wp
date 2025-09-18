import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { MagicWandIcon } from './icons.jsx';

const ActionFooter = ({
	visible,
	generationStep,
	action,
	onCancel,
	onStartOver,
	onUseAi,
	onSave,
	saving,
	disableSave,
}) => {
	if (!visible) {
		return null;
	}

	const isEdit = action === 'edit';
	let primaryLabel = __('Create entry', 'aria');
	if (saving) {
		primaryLabel = __('Saving…', 'aria');
	} else if (isEdit) {
		primaryLabel = __('Update entry', 'aria');
	}

	return (
		<div className="aria-knowledge-entry__sticky-footer">
			<div className="aria-knowledge-entry__footer-actions">
				<div className="aria-knowledge-entry__footer-buttons">
					<Button
						variant="tertiary"
						onClick={onCancel}
						disabled={saving}
					>
						{__('Cancel', 'aria')}
					</Button>
					{generationStep === 'review' && (
						<Button
							variant="tertiary"
							onClick={onStartOver}
							disabled={saving}
						>
							{__('Start over', 'aria')}
						</Button>
					)}
				</div>

				<div className="aria-knowledge-entry__footer-buttons">
					{generationStep === 'manual' && action === 'add' && (
						<Button
							variant="secondary"
							onClick={onUseAi}
							disabled={saving}
						>
							<MagicWandIcon />
							{__('Use AI assistant', 'aria')}
						</Button>
					)}
					<Button
						variant="primary"
						onClick={onSave}
						disabled={disableSave}
						isBusy={saving}
					>
						{primaryLabel}
					</Button>
				</div>
			</div>
		</div>
	);
};

ActionFooter.propTypes = {
	visible: PropTypes.bool.isRequired,
	generationStep: PropTypes.string.isRequired,
	action: PropTypes.string.isRequired,
	onCancel: PropTypes.func.isRequired,
	onStartOver: PropTypes.func.isRequired,
	onUseAi: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
	saving: PropTypes.bool.isRequired,
	disableSave: PropTypes.bool.isRequired,
};

export default ActionFooter;
