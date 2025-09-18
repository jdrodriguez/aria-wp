import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { SectionCard, TextControl } from '../../components';

const DesignBrandingSection = ({ settings, onChange, onUploadIcon, onUploadAvatar }) => (
	<SectionCard
		title={__('Brand Messaging & Assets', 'aria')}
		description={__(
			'Control the copy and visuals that introduce Aria to your visitors.',
			'aria'
		)}
	>
		<div className="aria-design__form-grid">
			<TextControl
				label={__('Widget Title', 'aria')}
				value={settings.title}
				onChange={(value) => onChange('title', value)}
				help={__(
					'Shown at the top of the chat window.',
					'aria'
				)}
			/>

			<TextControl
				label={__('Welcome Message', 'aria')}
				value={settings.welcomeMessage}
				onChange={(value) => onChange('welcomeMessage', value)}
				help={__(
					'First message visitors see when the chat opens.',
					'aria'
				)}
			/>
		</div>

		<div className="aria-design__upload-group">
			<h4 className="aria-design__upload-title">{__('Custom Assets', 'aria')}</h4>
			<div className="aria-design__upload-actions">
				<Button variant="secondary" onClick={onUploadIcon}>
					{__('Upload Custom Icon', 'aria')}
				</Button>
				<Button variant="secondary" onClick={onUploadAvatar}>
					{__('Upload Avatar', 'aria')}
				</Button>
			</div>
			<p className="aria-design__upload-note">
				{__(
					'Upload PNG or SVG assets to personalize Aria. We recommend square images at least 128px.',
					'aria'
				)}
			</p>
		</div>
	</SectionCard>
);

DesignBrandingSection.propTypes = {
	settings: PropTypes.shape({
		title: PropTypes.string.isRequired,
		welcomeMessage: PropTypes.string.isRequired,
	}).isRequired,
	onChange: PropTypes.func.isRequired,
	onUploadIcon: PropTypes.func.isRequired,
	onUploadAvatar: PropTypes.func.isRequired,
};

export default DesignBrandingSection;
