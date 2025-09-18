/**
 * API utilities for Aria Admin
 */

/**
 * Get WordPress AJAX configuration
 * @return {Object} AJAX configuration
 */
export const getAjaxConfig = () => {
	// Try to get data from React root element data attributes as backup
	const rootElement =
		document.getElementById('aria-dashboard-root') ||
		document.getElementById('aria-settings-root') ||
		document.getElementById('aria-design-root') ||
		document.getElementById('aria-personality-root') ||
		document.getElementById('aria-knowledge-root') ||
		document.getElementById('aria-ai-config-root') ||
		document.getElementById('aria-conversations-root') ||
		document.getElementById('aria-content-indexing-root');

	return {
		ajaxUrl:
			window.ariaAdmin?.ajaxUrl ||
			rootElement?.getAttribute('data-ajax-url') ||
			'/wp-admin/admin-ajax.php',
		nonce:
			window.ariaAdmin?.nonce ||
			rootElement?.getAttribute('data-nonce') ||
			'',
		adminUrl:
			window.ariaAdmin?.adminUrl ||
			rootElement?.getAttribute('data-admin-url') ||
			'/wp-admin/',
	};
};

/**
 * Make WordPress AJAX request
 * @param {string} action - WordPress AJAX action
 * @param {Object} data   - Additional data to send
 * @return {Promise} Fetch promise
 */
export const makeAjaxRequest = async (action, data = {}) => {
	const config = getAjaxConfig();

	if (!config.ajaxUrl || !config.nonce) {
		throw new Error('WordPress AJAX configuration not available');
	}

	const formData = new FormData();
	formData.append('action', action);
	formData.append('nonce', config.nonce);

	// Append additional data
	Object.keys(data).forEach((key) => {
		formData.append(key, data[key]);
	});

	const response = await fetch(config.ajaxUrl, {
		method: 'POST',
		body: formData,
	});

	if (!response.ok) {
		throw new Error(`HTTP error! status: ${response.status}`);
	}

	const result = await response.json();

	if (!result.success) {
		throw new Error(result.data?.message || 'AJAX request failed');
	}

	return result.data;
};

/**
 * Fetch dashboard data
 * @return {Promise} Dashboard data
 */
export const fetchDashboardData = () => {
	return makeAjaxRequest('aria_get_dashboard_data');
};

/**
 * Save personality settings
 * @param {Object} settings - Personality settings
 * @return {Promise} Save result
 */
export const savePersonalitySettings = (settings) => {
	return makeAjaxRequest('aria_save_personality', settings);
};

/**
 * Save design settings
 * @param {Object} settings - Design settings
 * @return {Promise} Save result
 */
export const saveDesignSettings = (settings) => {
	return makeAjaxRequest('aria_save_design', settings);
};

/**
 * Test API connection
 * @return {Promise} Test result
 */
export const testApiConnection = () => {
	return makeAjaxRequest('aria_test_api');
};

/**
 * Fetch knowledge base data
 * @return {Promise} Knowledge data with entries and statistics
 */
export const fetchKnowledgeData = () => {
	return makeAjaxRequest('aria_get_knowledge_data');
};

/**
 * Save knowledge entry
 * @param {Object} entryData - Knowledge entry data
 * @param {number|null} entryId - Entry ID for updates, null for new entries
 * @return {Promise} Save result
 */
export const saveKnowledgeEntry = (entryData, entryId = null) => {
	const data = {
		...entryData,
		entry_id: entryId || '',
		tags: Array.isArray(entryData.tags) ? entryData.tags.join(',') : entryData.tags,
		context: entryData.context || '',
		response_instructions: entryData.response_instructions || '',
		language: entryData.language || 'en'
	};
	return makeAjaxRequest('aria_save_knowledge', data);
};

/**
 * Delete knowledge entry
 * @param {number} entryId - Entry ID to delete
 * @return {Promise} Delete result
 */
export const deleteKnowledgeEntry = (entryId) => {
	return makeAjaxRequest('aria_delete_knowledge_entry', { entry_id: entryId });
};

/**
 * Fetch advanced admin settings.
 * @return {Promise}
 */
export const fetchAdvancedSettings = () => {
	return makeAjaxRequest('aria_get_advanced_settings');
};

/**
 * Save advanced admin settings.
 * @param {Object} settings Settings payload.
 * @return {Promise}
 */
export const saveAdvancedSettings = (settings) => {
	return makeAjaxRequest('aria_save_advanced_settings', settings);
};
