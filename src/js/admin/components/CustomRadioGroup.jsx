import PropTypes from 'prop-types';

/**
 * Custom radio group component for better styling
 *
 * @param {Object}   props          - Component props
 * @param {Array}    props.options  - Array of radio options
 * @param {string}   props.value    - Currently selected value
 * @param {Function} props.onChange - Change handler function
 * @param {string}   props.name     - Input name attribute
 * @param {string}   props.theme    - Color theme
 * @param {string}   props.columns  - Grid columns configuration
 * @param {string}   props.minWidth - Minimum width for grid items
 * @return {JSX.Element} Custom radio group component
 */
const THEME_CLASS_MAP = {
	blue: 'aria-radio-option--theme-blue',
	purple: 'aria-radio-option--theme-purple',
	green: 'aria-radio-option--theme-green',
};

const CustomRadioGroup = ({
	options,
	value,
	onChange,
	name,
	theme = 'blue',
	columns = 'auto-fit',
	minWidth = '280px',
}) => {
	const gridStyles = {
		'--aria-radio-grid-columns': columns,
		'--aria-radio-min-width': minWidth,
	};

	const themeClass = THEME_CLASS_MAP[theme] || THEME_CLASS_MAP.blue;

	return (
		<div className="aria-radio-grid" style={gridStyles}>
			{options.map((option) => {
				const isActive = value === option.value;
				const optionClasses = [
					'aria-radio-option',
					themeClass,
					isActive ? 'aria-radio-option--selected' : '',
				]
					.filter(Boolean)
					.join(' ');

				return (
					<div key={option.value} className="aria-radio-grid__item">
						<label className={optionClasses} htmlFor={`${name}-${option.value}`}>
							<input
								type="radio"
								id={`${name}-${option.value}`}
								name={name}
								value={option.value}
								checked={isActive}
								onChange={() => onChange(option.value)}
								className="aria-radio-option__input"
							/>
							<div className="aria-radio-option__body">
								<div className="aria-radio-option__title">{option.label}</div>
								<p className="aria-radio-option__description">{option.description}</p>
							</div>
							{isActive && (
								<div className="aria-radio-option__check" aria-hidden="true">
									✓
								</div>
							)}
						</label>
					</div>
				);
			})}
		</div>
	);
};

CustomRadioGroup.propTypes = {
	options: PropTypes.arrayOf(
		PropTypes.shape({
			value: PropTypes.string.isRequired,
			label: PropTypes.string.isRequired,
			description: PropTypes.string.isRequired,
		})
	).isRequired,
	value: PropTypes.string.isRequired,
	onChange: PropTypes.func.isRequired,
	name: PropTypes.string.isRequired,
	theme: PropTypes.oneOf(['blue', 'purple', 'green']),
	columns: PropTypes.string,
	minWidth: PropTypes.string,
};

export default CustomRadioGroup;
