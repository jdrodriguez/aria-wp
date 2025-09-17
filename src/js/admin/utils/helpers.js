import { __ } from '@wordpress/i18n';

/**
 * Helper functions for Aria Admin
 */

/**
 * Format time ago for display
 * @param {string} dateString - Date string to format
 * @return {string} Formatted time ago string
 */
export const formatTimeAgo = (dateString) => {
	if (!dateString) return __('Unknown time', 'aria');

	const date = new Date(dateString);
	const now = new Date();
	const diffInSeconds = Math.floor((now - date) / 1000);

	if (diffInSeconds < 60) {
		return __('Just now', 'aria');
	}
	if (diffInSeconds < 3600) {
		const minutes = Math.floor(diffInSeconds / 60);
		return minutes === 1
			? __('1 minute ago', 'aria')
			: `${minutes} ${__('minutes ago', 'aria')}`;
	}
	if (diffInSeconds < 86400) {
		const hours = Math.floor(diffInSeconds / 3600);
		return hours === 1
			? __('1 hour ago', 'aria')
			: `${hours} ${__('hours ago', 'aria')}`;
	}
	if (diffInSeconds < 604800) {
		const days = Math.floor(diffInSeconds / 86400);
		return days === 1
			? __('1 day ago', 'aria')
			: `${days} ${__('days ago', 'aria')}`;
	}
	// For older dates, show the actual date
	return date.toLocaleDateString();
};

/**
 * Get admin URL with fallback
 * @param {string} page - Admin page path
 * @return {string} Full admin URL
 */
export const getAdminUrl = (page = '') => {
	const baseUrl = window.ariaAdmin?.adminUrl || '/wp-admin/';
	return baseUrl + page;
};

/**
 * Capitalize first letter of string
 * @param {string} str - String to capitalize
 * @return {string} Capitalized string
 */
export const capitalize = (str) => {
	if (!str) return '';
	return str.charAt(0).toUpperCase() + str.slice(1);
};

/**
 * Truncate text to specified length
 * @param {string} text - Text to truncate
 * @param {number} length - Maximum length
 * @return {string} Truncated text
 */
export const truncateText = (text, length = 50) => {
	if (!text || text.length <= length) return text;
	return text.substring(0, length) + '...';
};