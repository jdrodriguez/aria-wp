import { TextControl, Flex, Button } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import PropTypes from 'prop-types';

/**
 * Enhanced search input component with debouncing and clear functionality
 *
 * @param {Object} props                  - Component props
 * @param {string} [props.value]          - Current search value
 * @param {Function} props.onChange       - Change handler
 * @param {string} [props.placeholder]    - Placeholder text
 * @param {number} [props.debounceMs]     - Debounce delay in milliseconds
 * @param {boolean} [props.showClearBtn]  - Show clear button
 * @param {string} [props.size]           - Input size (small, medium, large)
 * @param {boolean} [props.disabled]      - Disabled state
 * @param {Function} [props.onClear]      - Clear button handler
 * @param {Function} [props.onSubmit]     - Submit handler for Enter key
 * @return {JSX.Element} SearchInput component
 */
const SearchInput = ({
	value = '',
	onChange,
	placeholder = 'Search...',
	debounceMs = 300,
	showClearBtn = true,
	size = 'medium',
	disabled = false,
	onClear,
	onSubmit
}) => {
	const [inputValue, setInputValue] = useState(value);
	const [debounceTimer, setDebounceTimer] = useState(null);

	// Update internal state when external value changes
	useEffect(() => {
		setInputValue(value);
	}, [value]);

	// Handle input change with debouncing
	const handleInputChange = (newValue) => {
		setInputValue(newValue);

		// Clear existing timer
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}

		// Set new timer for debounced onChange
		const timer = setTimeout(() => {
			onChange(newValue);
		}, debounceMs);

		setDebounceTimer(timer);
	};

	// Handle clear button click
	const handleClear = () => {
		setInputValue('');
		if (debounceTimer) {
			clearTimeout(debounceTimer);
		}
		onChange('');
		if (onClear) {
			onClear();
		}
	};

	// Handle key press events
	const handleKeyPress = (event) => {
		if (event.key === 'Enter' && onSubmit) {
			event.preventDefault();
			onSubmit(inputValue);
		}
	};

	// Get size-specific styles
	const getSizeStyles = () => {
		switch (size) {
			case 'small':
				return {
					fontSize: '13px',
					height: '32px',
					padding: '4px 8px'
				};
			case 'large':
				return {
					fontSize: '16px',
					height: '48px',
					padding: '12px 16px'
				};
			default: // medium
				return {
					fontSize: '14px',
					height: '40px',
					padding: '8px 12px'
				};
		}
	};

	const sizeStyles = getSizeStyles();

	// Clean up timer on unmount
	useEffect(() => {
		return () => {
			if (debounceTimer) {
				clearTimeout(debounceTimer);
			}
		};
	}, [debounceTimer]);

	return (
		<div style={{ position: 'relative', width: '100%' }}>
			<Flex gap={2} align="center">
				<div style={{ position: 'relative', flex: 1 }}>
					{/* Search Icon */}
					<div style={{
						position: 'absolute',
						left: '12px',
						top: '50%',
						transform: 'translateY(-50%)',
						color: disabled ? '#9ca3af' : '#6c757d',
						fontSize: '14px',
						pointerEvents: 'none',
						zIndex: 1
					}}>
						🔍
					</div>

					<TextControl
						value={inputValue}
						onChange={handleInputChange}
						onKeyPress={handleKeyPress}
						placeholder={placeholder}
						disabled={disabled}
						style={{
							...sizeStyles,
							paddingLeft: '36px', // Make room for search icon
							paddingRight: showClearBtn && inputValue ? '36px' : sizeStyles.padding.split(' ')[1],
							border: '1px solid #ddd',
							borderRadius: '6px',
							width: '100%',
							transition: 'border-color 0.2s ease',
							fontSize: sizeStyles.fontSize,
							':focus': {
								borderColor: '#2271b1',
								boxShadow: '0 0 0 1px #2271b1'
							}
						}}
					/>

					{/* Clear Button */}
					{showClearBtn && inputValue && !disabled && (
						<button
							type="button"
							onClick={handleClear}
							style={{
								position: 'absolute',
								right: '8px',
								top: '50%',
								transform: 'translateY(-50%)',
								background: 'none',
								border: 'none',
								color: '#6c757d',
								cursor: 'pointer',
								padding: '4px',
								borderRadius: '3px',
								fontSize: '12px',
								lineHeight: 1,
								transition: 'color 0.2s ease',
								':hover': {
									color: '#dc3545'
								}
							}}
							title="Clear search"
							aria-label="Clear search input"
							tabIndex={0}
						>
							<span aria-hidden="true">✕</span>
						</button>
					)}
				</div>

				{/* Optional Submit Button */}
				{onSubmit && (
					<Button
						variant="primary"
						size={size === 'small' ? 'small' : 'medium'}
						onClick={() => onSubmit(inputValue)}
						disabled={disabled || !inputValue.trim()}
						style={{
							height: sizeStyles.height,
							minWidth: size === 'small' ? '60px' : '80px'
						}}
					>
						Search
					</Button>
				)}
			</Flex>
		</div>
	);
};

SearchInput.propTypes = {
	value: PropTypes.string,
	onChange: PropTypes.func.isRequired,
	placeholder: PropTypes.string,
	debounceMs: PropTypes.number,
	showClearBtn: PropTypes.bool,
	size: PropTypes.oneOf(['small', 'medium', 'large']),
	disabled: PropTypes.bool,
	onClear: PropTypes.func,
	onSubmit: PropTypes.func,
};

export default SearchInput;