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
const CustomRadioGroup = ({
	options,
	value,
	onChange,
	name,
	theme = 'blue',
	columns = 'auto-fit',
	minWidth = '280px',
}) => {
	const getThemeColors = () => {
		switch (theme) {
			case 'purple':
				return {
					borderColor: '#667eea',
					background:
						'linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.02) 100%)',
					shadow: '0 4px 16px rgba(102, 126, 234, 0.15)',
					checkBg:
						'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
				};
			case 'green':
				return {
					borderColor: '#28a745',
					background:
						'linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(32, 201, 151, 0.02) 100%)',
					shadow: '0 4px 16px rgba(40, 167, 69, 0.15)',
					checkBg:
						'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
				};
			default:
				return {
					borderColor: '#2271b1',
					background:
						'linear-gradient(135deg, rgba(34, 113, 177, 0.05) 0%, rgba(34, 113, 177, 0.02) 100%)',
					shadow: '0 4px 16px rgba(34, 113, 177, 0.15)',
					checkBg: '#2271b1',
				};
		}
	};

	const colors = getThemeColors();

	return (
		<div
			style={{
				display: 'grid',
				gridTemplateColumns: `repeat(${columns}, minmax(${minWidth}, 1fr))`,
				gap: '16px',
			}}
		>
			{options.map((option) => (
				<div key={option.value} style={{ position: 'relative' }}>
					<label
						htmlFor={`${name}-${option.value}`}
						style={{
							display: 'flex',
							alignItems: 'flex-start',
							padding: '20px',
							border:
								value === option.value
									? `2px solid ${colors.borderColor}`
									: '2px solid #e1e4e8',
							borderRadius: '12px',
							cursor: 'pointer',
							transition: 'all 0.2s ease',
							background:
								value === option.value
									? colors.background
									: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
							boxShadow:
								value === option.value
									? colors.shadow
									: '0 2px 8px rgba(0, 0, 0, 0.08)',
						}}
					>
						<input
							type="radio"
							id={`${name}-${option.value}`}
							name={name}
							value={option.value}
							checked={value === option.value}
							onChange={() => onChange(option.value)}
							style={{ display: 'none' }}
						/>
						<div style={{ flex: 1 }}>
							<div
								style={{
									fontSize: '16px',
									fontWeight: '600',
									color: '#1e1e1e',
									marginBottom: '6px',
								}}
							>
								{option.label}
							</div>
							<div
								style={{
									fontSize: '14px',
									color: '#757575',
									lineHeight: '1.4',
								}}
							>
								{option.description}
							</div>
						</div>
						{value === option.value && (
							<div
								style={{
									width: '24px',
									height: '24px',
									borderRadius: '50%',
									background: colors.checkBg,
									color: 'white',
									display: 'flex',
									alignItems: 'center',
									justifyContent: 'center',
									fontSize: '14px',
									fontWeight: '600',
									marginLeft: '12px',
									flexShrink: 0,
								}}
							>
								✓
							</div>
						)}
					</label>
				</div>
			))}
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
