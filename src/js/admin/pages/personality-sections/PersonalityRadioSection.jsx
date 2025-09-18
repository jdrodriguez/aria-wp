import PropTypes from 'prop-types';
import { SectionCard, CustomRadioGroup } from '../../components';

const PersonalityRadioSection = ({
	title,
	description,
	options,
	value,
	onChange,
	name,
}) => (
	<SectionCard title={title} description={description}>
		<CustomRadioGroup
			options={options}
			value={value}
			onChange={onChange}
			name={name}
		/>
	</SectionCard>
);

PersonalityRadioSection.propTypes = {
	title: PropTypes.string.isRequired,
	description: PropTypes.string.isRequired,
	options: PropTypes.arrayOf(PropTypes.object).isRequired,
	value: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	name: PropTypes.string.isRequired,
};

export default PersonalityRadioSection;
