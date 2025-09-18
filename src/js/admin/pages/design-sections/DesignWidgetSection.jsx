import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard, SelectControl } from '../../components';

const DesignWidgetSection = ({
	position,
	size,
	theme,
	onChange,
	positionOptions,
	sizeOptions,
	themeOptions,
}) => (
	<SectionCard
		title={__('Widget Appearance', 'aria')}
		description={__(
			'Configure the default placement, dimensions, and theme for your chat widget.',
			'aria'
		)}
	>
		<div className="aria-design__grid">
			<SelectControl
				label={__('Widget Position', 'aria')}
				value={position}
				onChange={(value) => onChange('position', value)}
				options={positionOptions}
				help={__(
					'Choose where the chat widget appears on your site.',
					'aria'
				)}
			/>

			<SelectControl
				label={__('Widget Size', 'aria')}
				value={size}
				onChange={(value) => onChange('size', value)}
				options={sizeOptions}
				help={__(
					'Set the default chat widget dimensions.',
					'aria'
				)}
			/>

			<SelectControl
				label={__('Widget Theme', 'aria')}
				value={theme}
				onChange={(value) => onChange('theme', value)}
				options={themeOptions}
				help={__(
					'Choose between light and dark themes.',
					'aria'
				)}
			/>
		</div>
	</SectionCard>
);

DesignWidgetSection.propTypes = {
	position: PropTypes.string.isRequired,
	size: PropTypes.string.isRequired,
	theme: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	positionOptions: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		})
	).isRequired,
	sizeOptions: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		})
	).isRequired,
	themeOptions: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
		})
	).isRequired,
};

export default DesignWidgetSection;
