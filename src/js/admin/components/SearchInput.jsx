import { TextControl, Flex, Button, Icon } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';
import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { search as searchIcon, closeSmall } from '@wordpress/icons';

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
const SIZE_KEYS = ['small', 'medium', 'large'];
const SIZE_CLASS_MAP = {
	small: 'aria-search-input--small',
	medium: 'aria-search-input--medium',
	large: 'aria-search-input--large',
};

const SearchInput = ({
	value = '',
	onChange,
	placeholder = 'Search...',
	debounceMs = 300,
	showClearBtn = true,
	size = 'medium',
	disabled = false,
	onClear,
	onSubmit,
	className,
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

	// Clean up timer on unmount
	useEffect(() => {
		return () => {
			if (debounceTimer) {
				clearTimeout(debounceTimer);
			}
		};
	}, [debounceTimer]);

	const sizeKey = SIZE_KEYS.includes(size) ? size : 'medium';

	const rootClasses = [
		'aria-search-input',
		SIZE_CLASS_MAP[sizeKey],
		showClearBtn && inputValue ? 'aria-search-input--has-clear' : '',
		disabled ? 'aria-search-input--disabled' : '',
		className,
	]
		.filter(Boolean)
		.join(' ');

	const submitClasses = [
		'aria-search-input__submit',
		sizeKey === 'small' ? 'aria-search-input__submit--small' : '',
		sizeKey === 'large' ? 'aria-search-input__submit--large' : '',
	]
		.filter(Boolean)
		.join(' ');

	const controlClasses = [
		'aria-search-input__control',
		`aria-search-input__control--${sizeKey}`,
	]
		.filter(Boolean)
		.join(' ');

	return (
		<div className={rootClasses}>
			<Flex gap={2} align="center">
				<div className="aria-search-input__control-wrapper">
					<span className="aria-search-input__icon">
						<Icon icon={searchIcon} size={14} />
					</span>
					<TextControl
						value={inputValue}
						onChange={handleInputChange}
						onKeyPress={handleKeyPress}
						placeholder={placeholder}
						disabled={disabled}
						className={controlClasses}
						__nextHasNoMarginBottom
					/>

					{showClearBtn && inputValue && !disabled && (
						<button
							type="button"
							onClick={handleClear}
							className="aria-search-input__clear"
							title={__('Clear search input', 'aria')}
							aria-label={__('Clear search input', 'aria')}
						>
							<Icon icon={closeSmall} size={14} />
						</button>
					)}
				</div>

				{onSubmit && (
					<Button
						variant="primary"
						onClick={() => onSubmit(inputValue)}
						disabled={disabled || !inputValue.trim()}
						className={submitClasses}
					>
						{__('Search', 'aria')}
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
 	className: PropTypes.string,
};

export default SearchInput;
