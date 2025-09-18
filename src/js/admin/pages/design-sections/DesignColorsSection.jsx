import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { ColorPicker } from '@wordpress/components';
import { SectionCard } from '../../components';

const ColorControl = ({ label, description, value, onChange }) => (
	<div className="aria-design__color-control">
		<div className="aria-design__color-header">
			<h4 className="aria-design__color-title">{label}</h4>
			<p className="aria-design__color-description">{description}</p>
		</div>
		<ColorPicker color={value} onChange={onChange} enableAlpha={false} />
	</div>
);

ColorControl.propTypes = {
	label: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	value: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
};

const DesignColorsSection = ({ settings, onChange }) => (
	<SectionCard
		title={__('Brand Colors', 'aria')}
		description={__(
			'Align Aria with your brand palette for a seamless customer experience.',
			'aria'
		)}
	>
		<div className="aria-design__color-grid">
			<ColorControl
				label={__('Primary Color', 'aria')}
				description={__(
					'Used for buttons and interactive accents.',
					'aria'
				)}
				value={settings.primaryColor}
				onChange={(color) => onChange('primaryColor', color)}
			/>

			<ColorControl
				label={__('Background Color', 'aria')}
				description={__(
					'Sets the chat panel background.',
					'aria'
				)}
				value={settings.backgroundColor}
				onChange={(color) => onChange('backgroundColor', color)}
			/>

			<ColorControl
				label={__('Text Color', 'aria')}
				description={__(
					'Defines default text color for the widget.',
					'aria'
				)}
				value={settings.textColor}
				onChange={(color) => onChange('textColor', color)}
			/>
		</div>
	</SectionCard>
);

DesignColorsSection.propTypes = {
	settings: PropTypes.shape({
		primaryColor: PropTypes.string.isRequired,
		backgroundColor: PropTypes.string.isRequired,
		textColor: PropTypes.string.isRequired,
	}).isRequired,
	onChange: PropTypes.func.isRequired,
};

export default DesignColorsSection;
