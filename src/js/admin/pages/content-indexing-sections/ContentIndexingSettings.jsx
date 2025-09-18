import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	SectionCard,
	ToggleControl,
	SelectControl,
	TextareaControl,
	TextControl,
} from '../../components';

const ContentIndexingSettings = ({ settings, onChange, onSave, saving }) => (
	<SectionCard
		title={__('Indexing Settings', 'aria')}
		description={__(
			'Configure how content is automatically indexed and refreshed.',
			'aria'
		)}
		footer={
			<div className="aria-content-indexing__settings-actions">
				<Button
					variant="primary"
					onClick={onSave}
					isBusy={saving}
					disabled={saving}
				>
					{saving
						? __('Saving…', 'aria')
						: __('Save Settings', 'aria')}
				</Button>
			</div>
		}
	>
		<div className="aria-content-indexing__settings-grid">
			<ToggleControl
				label={__('Auto-index new content', 'aria')}
				help={__(
					'Automatically index content when it is published.',
					'aria'
				)}
				checked={settings.autoIndex}
				onChange={(value) => onChange('autoIndex', value)}
			/>

			<SelectControl
				label={__('Index frequency', 'aria')}
				value={settings.indexFrequency}
				onChange={(value) => onChange('indexFrequency', value)}
				options={[
					{ label: __('Hourly', 'aria'), value: 'hourly' },
					{ label: __('Daily', 'aria'), value: 'daily' },
					{ label: __('Weekly', 'aria'), value: 'weekly' },
					{ label: __('Manual only', 'aria'), value: 'manual' },
				]}
			/>

			<TextControl
				label={__('Max file size (MB)', 'aria')}
				type="number"
				value={settings.maxFileSize}
				onChange={(value) => onChange('maxFileSize', value)}
				help={__(
					'Skip files larger than this size (0 for no limit).',
					'aria'
				)}
			/>

			<TextareaControl
				label={__('Exclude patterns', 'aria')}
				value={settings.excludePatterns}
				onChange={(value) => onChange('excludePatterns', value)}
				placeholder="/wp-admin/*\n/wp-includes/*\n*.pdf"
				help={__(
					'Provide URL patterns to omit from indexing (one per line).',
					'aria'
				)}
				rows={4}
			/>
		</div>
	</SectionCard>
);

ContentIndexingSettings.propTypes = {
	settings: PropTypes.shape({
		autoIndex: PropTypes.bool.isRequired,
		indexFrequency: PropTypes.string.isRequired,
		excludePatterns: PropTypes.string.isRequired,
		maxFileSize: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
			.isRequired,
	}).isRequired,
	onChange: PropTypes.func.isRequired,
	onSave: PropTypes.func.isRequired,
	saving: PropTypes.bool.isRequired,
};

export default ContentIndexingSettings;
