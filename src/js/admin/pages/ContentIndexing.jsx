import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import { makeAjaxRequest } from '../utils/api';
import {
	ContentIndexingNotice,
	ContentIndexingLoading,
	ContentIndexingMetrics,
	ContentIndexingActions,
	ContentIndexingSettings,
	ContentIndexingFilters,
	ContentIndexingList,
	ContentIndexingProgressModal,
} from './content-indexing-sections';

const CONTENT_TYPES = [
	{ label: __('All types', 'aria'), value: 'all' },
	{ label: __('Pages', 'aria'), value: 'page' },
	{ label: __('Posts', 'aria'), value: 'post' },
	{ label: __('Products', 'aria'), value: 'product' },
	{ label: __('Documents', 'aria'), value: 'document' },
];

const INDEXING_STATUS = [
	{ label: __('All status', 'aria'), value: 'all' },
	{ label: __('Indexed', 'aria'), value: 'indexed' },
	{ label: __('Pending', 'aria'), value: 'pending' },
	{ label: __('Failed', 'aria'), value: 'failed' },
	{ label: __('Excluded', 'aria'), value: 'excluded' },
];

const ContentIndexing = () => {
	const [metrics, setMetrics] = useState([
		{
			icon: 'stack',
			title: __('Total items', 'aria'),
			value: 0,
			subtitle: __('Tracked content', 'aria'),
			theme: 'primary',
		},
		{
			icon: 'check',
			title: __('Indexed items', 'aria'),
			value: 0,
			subtitle: __('Ready for AI', 'aria'),
			theme: 'success',
		},
		{
			icon: 'clock',
			title: __('Last indexed', 'aria'),
			value: __('Never', 'aria'),
			subtitle: __('Most recent run', 'aria'),
			theme: 'info',
		},
		{
			icon: 'storage',
			title: __('Storage used', 'aria'),
			value: '0 MB',
			subtitle: __('Vector store footprint', 'aria'),
			theme: 'warning',
		},
	]);
	const [contentItems, setContentItems] = useState([]);
	const [loading, setLoading] = useState(true);
	const [searchTerm, setSearchTerm] = useState('');
	const [selectedType, setSelectedType] = useState('all');
	const [selectedStatus, setSelectedStatus] = useState('all');
    const [typeOptions, setTypeOptions] = useState(CONTENT_TYPES);
    const [settings, setSettings] = useState({
		autoIndex: true,
		indexFrequency: 'daily',
		excludePatterns: '',
		maxFileSize: '10',
	});
	const [isIndexing, setIsIndexing] = useState(false);
	const [indexingProgress, setIndexingProgress] = useState({
		percentage: 0,
		processed: 0,
		total: 0,
		currentItem: '',
	});
	const [isProgressModalOpen, setIsProgressModalOpen] = useState(false);
	const [notice, setNotice] = useState(null);
	const [saving, setSaving] = useState(false);

	useEffect(() => {
		loadContentData();
	}, []);

	const loadContentData = async () => {
		setLoading(true);
		try {
			const data = await makeAjaxRequest('aria_get_content_indexing_data');

			if (Array.isArray(data?.metrics) && data.metrics.length) {
				setMetrics(data.metrics);
			}

			const incomingItems = Array.isArray(data?.items)
				? data.items.map((item) => ({
					...item,
					typeLabel: item.type_label || item.type,
					tags: Array.isArray(item.tags) ? item.tags : [],
				}))
				: [];
			setContentItems(incomingItems);

			if (data?.settings) {
				setSettings({
					autoIndex: Boolean(data.settings.autoIndex),
					indexFrequency: data.settings.indexFrequency || 'daily',
					excludePatterns: data.settings.excludePatterns || '',
					maxFileSize: data.settings.maxFileSize || '0',
				});
			}

			if (Array.isArray(data?.availableTypes) && data.availableTypes.length) {
				setTypeOptions(data.availableTypes);
			}

		} catch (error) {
			setNotice({
				type: 'error',
				message: error?.message || __('Failed to load content indexing data.', 'aria'),
			});
		} finally {
			setLoading(false);
		}
	};

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleSaveSettings = async () => {
		setSaving(true);
		try {
			await makeAjaxRequest('aria_save_content_indexing_settings', {
				auto_index: settings.autoIndex ? '1' : '0',
				index_frequency: settings.indexFrequency,
				exclude_patterns: settings.excludePatterns,
				max_file_size: settings.maxFileSize,
			});

			setNotice({
				type: 'success',
				message: __('Content indexing settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message || __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const handleStartIndexing = async () => {
		setIsIndexing(true);
		try {
			const response = await makeAjaxRequest('aria_reindex_all_content');

			setNotice({
				type: 'success',
				message:
					response?.message || __('Content indexing started successfully.', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
			await loadContentData();
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message || __('Content indexing failed. Please try again.', 'aria'),
			});
		} finally {
			setIsIndexing(false);
			setIsProgressModalOpen(false);
		}
	};

	const handleToggleIndex = async (itemId) => {
		const targetItem = contentItems.find((item) => item.id === itemId);
		const shouldIndex = targetItem
			? targetItem.status === 'excluded' || targetItem.status === 'pending'
			: true;

		try {
			const response = await makeAjaxRequest('aria_toggle_content_indexing', {
				content_id: itemId,
				should_index: shouldIndex ? '1' : '0',
			});

			setContentItems((prev) =>
				prev.map((item) =>
					item.id === itemId
						? {
							...item,
							status:
								response?.status || (shouldIndex ? 'indexed' : 'excluded'),
						}
						: item
				)
			);

			setNotice({
				type: 'success',
				message:
					response?.message || __('Content indexing status updated!', 'aria'),
			});
			setTimeout(() => setNotice(null), 3000);
			await loadContentData();
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message || __('Failed to update content status.', 'aria'),
			});
		}
	};

	const handleViewContent = (item) => {
		window.open(item.url, '_blank');
	};

	const filteredItems = contentItems.filter((item) => {
		const normalizedSearch = searchTerm.toLowerCase();
		const tagList = Array.isArray(item.tags) ? item.tags : [];
		const matchesSearch =
			!normalizedSearch ||
			item.title.toLowerCase().includes(normalizedSearch) ||
			item.excerpt.toLowerCase().includes(normalizedSearch) ||
			tagList.some((tag) => tag.toLowerCase().includes(normalizedSearch));

		const matchesType =
			selectedType === 'all' || item.type === selectedType;
		const matchesStatus =
			selectedStatus === 'all' || item.status === selectedStatus;

		return matchesSearch && matchesType && matchesStatus;
	});

	if (loading) {
		return <ContentIndexingLoading />;
	}

	const hasFiltersApplied =
		Boolean(searchTerm.trim()) ||
		selectedType !== 'all' ||
		selectedStatus !== 'all';

	return (
		<PageShell
			className="aria-content-indexing aria-content-indexing-react"
			width="wide"
		>
			<PageHeader
				title={__('Content Indexing', 'aria')}
				description={__(
					'Manage automatic content indexing for the AI knowledge base.',
					'aria'
				)}
			/>

			<ContentIndexingNotice
				notice={notice}
				onRemove={() => setNotice(null)}
			/>

			<div className="aria-stack-lg">
				<ContentIndexingMetrics metrics={metrics} />
				<ContentIndexingActions
					onStart={handleStartIndexing}
					isIndexing={isIndexing}
				/>
				<ContentIndexingSettings
					settings={settings}
					onChange={updateSetting}
					onSave={handleSaveSettings}
					saving={saving}
				/>
					<ContentIndexingFilters
						searchValue={searchTerm}
						onSearchChange={setSearchTerm}
						typeValue={selectedType}
						onTypeChange={setSelectedType}
						typeOptions={typeOptions}
						statusValue={selectedStatus}
						onStatusChange={setSelectedStatus}
						statusOptions={INDEXING_STATUS}
					/>
				<ContentIndexingList
					items={filteredItems}
					onToggleIndex={handleToggleIndex}
					onViewContent={handleViewContent}
					totalCount={contentItems.length}
					filteredCount={filteredItems.length}
					hasFiltersApplied={hasFiltersApplied}
					onStartIndexing={handleStartIndexing}
					isIndexing={isIndexing}
				/>
			</div>

			<ContentIndexingProgressModal
				isOpen={isProgressModalOpen}
				onClose={() => setIsProgressModalOpen(false)}
				progress={indexingProgress}
			/>
		</PageShell>
	);
};

export default ContentIndexing;
