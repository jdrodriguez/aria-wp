import { useEffect, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
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

const getProcessingLabel = (processed, total) =>
	sprintf(
		/* translators: 1: processed item count, 2: total item count */
		__('Processing item %1$s/%2$s…', 'aria'),
		processed,
		total
	);

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
			await new Promise((resolve) => setTimeout(resolve, 1000));

			const mockItems = [
				{
					id: 1,
					title: 'About Our Company',
					type: 'page',
					status: 'indexed',
					url: '/about',
					updated_at: '2024-01-15',
					word_count: 547,
					excerpt:
						'We are a leading provider of innovative solutions for businesses of all sizes…',
					tags: ['company', 'about'],
				},
				{
					id: 2,
					title: 'Product Features Guide',
					type: 'post',
					status: 'indexed',
					url: '/blog/product-features',
					updated_at: '2024-01-14',
					word_count: 1205,
					excerpt:
						'Discover all the powerful features our product offers to help streamline your workflow…',
					tags: ['features', 'guide', 'product'],
				},
				{
					id: 3,
					title: 'Pricing Plans',
					type: 'page',
					status: 'pending',
					url: '/pricing',
					updated_at: '2024-01-13',
					word_count: 892,
					excerpt:
						'Choose the perfect plan for your needs with our flexible pricing options…',
					tags: ['pricing', 'plans'],
				},
				{
					id: 4,
					title: 'Installation Documentation',
					type: 'document',
					status: 'failed',
					url: '/docs/installation.pdf',
					updated_at: '2024-01-12',
					word_count: 2341,
					excerpt:
						'Complete step-by-step guide for installing and configuring the software…',
					tags: ['documentation', 'installation'],
				},
			];

			setContentItems(mockItems);
			setMetrics([
				{
					icon: '📚',
					title: __('Total items', 'aria'),
					value: mockItems.length,
					subtitle: __('Tracked content', 'aria'),
					theme: 'primary',
				},
				{
					icon: '✅',
					title: __('Indexed items', 'aria'),
					value: mockItems.filter((item) => item.status === 'indexed')
						.length,
					subtitle: __('Ready for AI', 'aria'),
					theme: 'success',
				},
				{
					icon: '🕒',
					title: __('Last indexed', 'aria'),
					value: __('Today, 3:45 PM', 'aria'),
					subtitle: __('Most recent run', 'aria'),
					theme: 'info',
				},
				{
					icon: '💾',
					title: __('Storage used', 'aria'),
					value: '2.4 MB',
					subtitle: __('Vector store footprint', 'aria'),
					theme: 'warning',
				},
			]);
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Failed to load content data:', error);
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
			await new Promise((resolve) => setTimeout(resolve, 1000));
			setNotice({
				type: 'success',
				message: __(
					'Content indexing settings saved successfully!',
					'aria'
				),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __(
					'Failed to save settings. Please try again.',
					'aria'
				),
			});
		} finally {
			setSaving(false);
		}
	};

	const handleStartIndexing = async () => {
		setIsIndexing(true);
		setIsProgressModalOpen(true);
		setIndexingProgress({
			percentage: 0,
			processed: 0,
			total: 100,
			currentItem: __('Initializing…', 'aria'),
		});

		try {
			for (let i = 0; i <= 100; i += 10) {
				// eslint-disable-next-line no-await-in-loop
				await new Promise((resolve) => setTimeout(resolve, 500));
				const currentItemLabel = getProcessingLabel(i, 100);
				setIndexingProgress({
					percentage: i,
					processed: i,
					total: 100,
					currentItem: currentItemLabel,
				});
			}

			setNotice({
				type: 'success',
				message: __('Content indexing completed successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
			loadContentData();
		} catch (error) {
			setNotice({
				type: 'error',
				message: __(
					'Content indexing failed. Please try again.',
					'aria'
				),
			});
		} finally {
			setIsIndexing(false);
		}
	};

	const handleToggleIndex = async (itemId) => {
		try {
			await new Promise((resolve) => setTimeout(resolve, 500));

			setContentItems((prev) =>
				prev.map((item) =>
					item.id === itemId
						? {
								...item,
								status:
									item.status === 'indexed'
										? 'excluded'
										: 'indexed',
							}
						: item
				)
			);

			setNotice({
				type: 'success',
				message: __('Content indexing status updated!', 'aria'),
			});
			setTimeout(() => setNotice(null), 3000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to update content status.', 'aria'),
			});
		}
	};

	const handleViewContent = (item) => {
		window.open(item.url, '_blank');
	};

	const filteredItems = contentItems.filter((item) => {
		const normalizedSearch = searchTerm.toLowerCase();
		const matchesSearch =
			!normalizedSearch ||
			item.title.toLowerCase().includes(normalizedSearch) ||
			item.excerpt.toLowerCase().includes(normalizedSearch) ||
			item.tags.some((tag) =>
				tag.toLowerCase().includes(normalizedSearch)
			);

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
					typeOptions={CONTENT_TYPES}
					statusValue={selectedStatus}
					onStatusChange={setSelectedStatus}
					statusOptions={INDEXING_STATUS}
				/>
				<ContentIndexingList
					items={filteredItems}
					onToggleIndex={handleToggleIndex}
					onViewContent={handleViewContent}
					count={filteredItems.length}
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
