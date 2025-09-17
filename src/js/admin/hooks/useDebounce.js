import { useState, useEffect } from '@wordpress/element';

/**
 * Custom hook for debouncing values
 * @param {any} value - The value to debounce
 * @param {number} delay - Delay in milliseconds
 * @return {any} Debounced value
 */
export const useDebounce = (value, delay) => {
	const [debouncedValue, setDebouncedValue] = useState(value);

	useEffect(() => {
		const handler = setTimeout(() => {
			setDebouncedValue(value);
		}, delay);

		return () => {
			clearTimeout(handler);
		};
	}, [value, delay]);

	return debouncedValue;
};

/**
 * Custom hook for debounced callback functions
 * @param {Function} callback - The callback function to debounce
 * @param {number} delay - Delay in milliseconds
 * @param {Array} deps - Dependencies array
 * @return {Function} Debounced callback
 */
export const useDebouncedCallback = (callback, delay, deps = []) => {
	const [timeoutId, setTimeoutId] = useState(null);

	const debouncedCallback = (...args) => {
		if (timeoutId) {
			clearTimeout(timeoutId);
		}

		const newTimeoutId = setTimeout(() => {
			callback(...args);
		}, delay);

		setTimeoutId(newTimeoutId);
	};

	// Cleanup on unmount
	useEffect(() => {
		return () => {
			if (timeoutId) {
				clearTimeout(timeoutId);
			}
		};
	}, [timeoutId]);

	return debouncedCallback;
};