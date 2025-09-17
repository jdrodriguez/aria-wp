import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Button,
	TextControl,
	TextareaControl,
	SelectControl,
	ToggleControl,
	SearchControl,
	Modal,
	Notice,
	Flex,
	ProgressBar,
	__experimentalText as Text,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { PageHeader, MetricCard } from '../components';

const CONTENT_TYPES = [
	{ label: __('All Types', 'aria'), value: 'all' },
	{ label: __('Pages', 'aria'), value: 'page' },
	{ label: __('Posts', 'aria'), value: 'post' },
	{ label: __('Products', 'aria'), value: 'product' },
	{ label: __('Documents', 'aria'), value: 'document' },
];

const INDEXING_STATUS = [
	{ label: __('All Status', 'aria'), value: 'all' },
	{ label: __('Indexed', 'aria'), value: 'indexed' },
	{ label: __('Pending', 'aria'), value: 'pending' },
	{ label: __('Failed', 'aria'), value: 'failed' },
	{ label: __('Excluded', 'aria'), value: 'excluded' },
];

const IndexingProgressModal = ({ isOpen, onClose, progress }) => {
	if (!isOpen) return null;

	return (
		<Modal
			title={__('Content Indexing Progress', 'aria')}
			onRequestClose={onClose}
			style={{ maxWidth: '500px' }}
		>
			<div style={{ padding: '16px 0' }}>
				<div style={{ marginBottom: '20px' }}>
					<Text style={{ fontSize: '14px', color: '#757575', marginBottom: '12px' }}>
						{__('Indexing your content for AI reference...', 'aria')}
					</Text>
					<ProgressBar value={progress.percentage} />
					<div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '8px' }}>
						<Text style={{ fontSize: '13px', color: '#757575' }}>
							{progress.processed} / {progress.total} {__('items processed', 'aria')}
						</Text>
						<Text style={{ fontSize: '13px', color: '#757575' }}>
							{progress.percentage}%
						</Text>
					</div>
				</div>

				<div
					style={{
						padding: '16px',
						backgroundColor: '#f8f9fa',
						borderRadius: '8px',
						marginBottom: '20px',
					}}
				>
					<Text style={{ fontSize: '14px', fontWeight: '600', marginBottom: '8px' }}>
						{__('Current Item:', 'aria')}
					</Text>
					<Text style={{ fontSize: '13px', color: '#1e1e1e' }}>
						{progress.currentItem}
					</Text>
				</div>

				<div style={{ display: 'flex', justifyContent: 'flex-end' }}>
					<Button variant="secondary" onClick={onClose}>
						{__('Run in Background', 'aria')}
					</Button>
				</div>
			</div>
		</Modal>
	);
};

IndexingProgressModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	progress: PropTypes.object.isRequired,
};

const ContentItemCard = ({ item, onToggleIndex, onViewContent }) => {
	const getStatusColor = (status) => {
		switch (status) {
			case 'indexed':
				return { bg: '#d4edda', color: '#155724' };
			case 'pending':
				return { bg: '#fff3cd', color: '#856404' };
			case 'failed':
				return { bg: '#f8d7da', color: '#721c24' };
			case 'excluded':
				return { bg: '#e2e3e5', color: '#383d41' };
			default:
				return { bg: '#e2e3e5', color: '#383d41' };
		}
	};

	const statusColors = getStatusColor(item.status);

	return (
		<Card style={{ marginBottom: '16px' }}>
			<CardBody style={{ padding: '20px' }}>
				<Flex justify="space-between" align="flex-start">
					<div style={{ flex: 1, minWidth: 0 }}>
						{/* Header */}
						<div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '8px' }}>
							<h4
								style={{
									fontSize: '16px',
									fontWeight: '600',
									margin: 0,
									overflow: 'hidden',
									textOverflow: 'ellipsis',
									whiteSpace: 'nowrap',
								}}
							>
								{item.title}
							</h4>
							<div
								style={{
									padding: '2px 8px',
									borderRadius: '12px',
									fontSize: '12px',
									fontWeight: '600',
									backgroundColor: statusColors.bg,
									color: statusColors.color,
									whiteSpace: 'nowrap',
								}}
							>
								{item.status.charAt(0).toUpperCase() + item.status.slice(1)}
							</div>
						</div>

						{/* Meta Info */}
						<div style={{ marginBottom: '12px' }}>
							<div style={{ fontSize: '13px', color: '#757575', marginBottom: '4px' }}>
								📄 {item.type.charAt(0).toUpperCase() + item.type.slice(1)} • 
								📅 {item.updated_at} • 
								📊 {item.word_count} {__('words', 'aria')}
							</div>
							<div style={{ fontSize: '13px', color: '#757575' }}>
								🔗 {item.url}
							</div>
						</div>

						{/* Content Preview */}
						<div
							style={{
								fontSize: '14px',
								color: '#1e1e1e',
								overflow: 'hidden',
								textOverflow: 'ellipsis',
								display: '-webkit-box',
								WebkitLineClamp: 2,
								WebkitBoxOrient: 'vertical',
								marginBottom: '12px',
							}}
						>
							{item.excerpt}
						</div>

						{/* Tags */}
						{item.tags && item.tags.length > 0 && (
							<div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
								{item.tags.map((tag, index) => (
									<span
										key={index}
										style={{
											fontSize: '12px',
											padding: '2px 8px',
											backgroundColor: '#f0f6fc',
											color: '#0969da',
											borderRadius: '12px',
											border: '1px solid #d1d9e0',
										}}
									>
										{tag}
									</span>
								))}
							</div>
						)}
					</div>

					{/* Actions */}
					<div style={{ display: 'flex', flexDirection: 'column', gap: '8px', marginLeft: '16px' }}>
						<Button 
							variant="secondary" 
							size="small" 
							onClick={() => onViewContent(item)}
						>
							{__('View', 'aria')}
						</Button>
						<ToggleControl
							label={item.status === 'indexed' ? __('Indexed', 'aria') : __('Include', 'aria')}
							checked={item.status === 'indexed'}
							onChange={() => onToggleIndex(item.id)}
						/>
					</div>
				</Flex>
			</CardBody>
		</Card>
	);
};

ContentItemCard.propTypes = {
	item: PropTypes.object.isRequired,
	onToggleIndex: PropTypes.func.isRequired,
	onViewContent: PropTypes.func.isRequired,
};

const ContentIndexing = () => {
	const [indexingData, setIndexingData] = useState({
		totalItems: 0,
		indexedItems: 0,
		lastIndexed: 'Never',
		storageUsed: '0 MB',
	});
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
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 1000));

			// Mock data
			const mockItems = [
				{
					id: 1,
					title: 'About Our Company',
					type: 'page',
					status: 'indexed',
					url: '/about',
					updated_at: '2024-01-15',
					word_count: 547,
					excerpt: 'We are a leading provider of innovative solutions for businesses of all sizes...',
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
					excerpt: 'Discover all the powerful features our product offers to help streamline your workflow...',
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
					excerpt: 'Choose the perfect plan for your needs with our flexible pricing options...',
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
					excerpt: 'Complete step-by-step guide for installing and configuring the software...',
					tags: ['documentation', 'installation'],
				},
			];

			setContentItems(mockItems);
			setIndexingData({
				totalItems: mockItems.length,
				indexedItems: mockItems.filter(item => item.status === 'indexed').length,
				lastIndexed: 'Today, 3:45 PM',
				storageUsed: '2.4 MB',
			});
		} catch (error) {
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
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 1000));
			setNotice({
				type: 'success',
				message: __('Content indexing settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const handleStartIndexing = async () => {
		setIsIndexing(true);
		setIsProgressModalOpen(true);
		setIndexingProgress({ percentage: 0, processed: 0, total: 100, currentItem: 'Initializing...' });

		try {
			// Simulate indexing process
			for (let i = 0; i <= 100; i += 10) {
				await new Promise((resolve) => setTimeout(resolve, 500));
				setIndexingProgress({
					percentage: i,
					processed: i,
					total: 100,
					currentItem: `Processing item ${i}/100...`,
				});
			}

			setNotice({
				type: 'success',
				message: __('Content indexing completed successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
			loadContentData(); // Refresh data
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Content indexing failed. Please try again.', 'aria'),
			});
		} finally {
			setIsIndexing(false);
		}
	};

	const handleToggleIndex = async (itemId) => {
		try {
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 500));

			setContentItems(prev =>
				prev.map(item =>
					item.id === itemId
						? { 
							...item, 
							status: item.status === 'indexed' ? 'excluded' : 'indexed' 
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
		// Open item URL in new tab
		window.open(item.url, '_blank');
	};

	const filteredItems = contentItems.filter((item) => {
		const matchesSearch =
			!searchTerm ||
			item.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
			item.excerpt.toLowerCase().includes(searchTerm.toLowerCase()) ||
			item.tags.some(tag => tag.toLowerCase().includes(searchTerm.toLowerCase()));

		const matchesType = selectedType === 'all' || item.type === selectedType;
		const matchesStatus = selectedStatus === 'all' || item.status === selectedStatus;

		return matchesSearch && matchesType && matchesStatus;
	});

	if (loading) {
		return (
			<div className="aria-content-indexing-react" style={{ paddingRight: '32px' }}>
				<PageHeader
					title={__('Content Indexing', 'aria')}
					description={__('Loading content indexing data...', 'aria')}
				/>
			</div>
		);
	}

	return (
		<div className="aria-content-indexing-react" style={{ paddingRight: '32px' }}>
			<PageHeader
				title={__('Content Indexing', 'aria')}
				description={__('Manage automatic content indexing for AI knowledge base', 'aria')}
			/>

			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			{/* Indexing Metrics */}
			<div
				className="aria-metrics-grid"
				style={{
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
					gap: '24px',
					marginBottom: '32px',
				}}
			>
				<MetricCard
					icon="📄"
					title={__('Total Items', 'aria')}
					value={indexingData.totalItems}
					subtitle={__('Content Items', 'aria')}
				/>
				<MetricCard
					icon="✅"
					title={__('Indexed Items', 'aria')}
					value={indexingData.indexedItems}
					subtitle={__('Available to AI', 'aria')}
					theme="success"
				/>
				<MetricCard
					icon="🔄"
					title={__('Last Indexed', 'aria')}
					value={indexingData.lastIndexed}
					subtitle={__('Most Recent Update', 'aria')}
					theme="info"
				/>
				<MetricCard
					icon="💾"
					title={__('Storage Used', 'aria')}
					value={indexingData.storageUsed}
					subtitle={__('Index Storage', 'aria')}
					theme="warning"
				/>
			</div>

			{/* Indexing Controls */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<Flex justify="space-between" align="center">
						<div>
							<h3
								style={{
									fontSize: '18px',
									fontWeight: '600',
									marginBottom: '8px',
									margin: 0,
								}}
							>
								{__('Indexing Controls', 'aria')}
							</h3>
							<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
								{__('Start manual indexing or configure automatic updates', 'aria')}
							</p>
						</div>
						<Button
							variant="primary"
							onClick={handleStartIndexing}
							isBusy={isIndexing}
							disabled={isIndexing}
						>
							{isIndexing ? __('Indexing...', 'aria') : __('Start Indexing', 'aria')}
						</Button>
					</Flex>
				</CardHeader>
			</Card>

			{/* Indexing Settings */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Indexing Settings', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__('Configure how content is automatically indexed and updated', 'aria')}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div style={{ display: 'grid', gap: '20px' }}>
						<div
							style={{
								display: 'grid',
								gridTemplateColumns: 'repeat(auto-fit, minmax(300px, 1fr))',
								gap: '20px',
							}}
						>
							<ToggleControl
								label={__('Auto-Index New Content', 'aria')}
								help={__('Automatically index new content when it\'s published', 'aria')}
								checked={settings.autoIndex}
								onChange={(value) => updateSetting('autoIndex', value)}
							/>

							<SelectControl
								label={__('Index Frequency', 'aria')}
								value={settings.indexFrequency}
								options={[
									{ label: __('Hourly', 'aria'), value: 'hourly' },
									{ label: __('Daily', 'aria'), value: 'daily' },
									{ label: __('Weekly', 'aria'), value: 'weekly' },
									{ label: __('Manual Only', 'aria'), value: 'manual' },
								]}
								help={__('How often to check for content updates', 'aria')}
								onChange={(value) => updateSetting('indexFrequency', value)}
							/>

							<TextControl
								label={__('Max File Size (MB)', 'aria')}
								type="number"
								value={settings.maxFileSize}
								help={__('Maximum file size to index (0 for no limit)', 'aria')}
								onChange={(value) => updateSetting('maxFileSize', value)}
							/>
						</div>

						<TextareaControl
							label={__('Exclude Patterns', 'aria')}
							value={settings.excludePatterns}
							onChange={(value) => updateSetting('excludePatterns', value)}
							placeholder="/wp-admin/*, /wp-includes/*, *.pdf"
							help={__('URL patterns to exclude from indexing (one per line)', 'aria')}
							rows={4}
						/>

						<div style={{ marginTop: '16px' }}>
							<Button
								variant="primary"
								onClick={handleSaveSettings}
								isBusy={saving}
								disabled={saving}
							>
								{saving ? __('Saving...', 'aria') : __('Save Settings', 'aria')}
							</Button>
						</div>
					</div>
				</CardBody>
			</Card>

			{/* Search and Filter */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Search & Filter', 'aria')}
					</h3>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gridTemplateColumns: 'repeat(auto-fit, minmax(250px, 1fr))',
							gap: '16px',
						}}
					>
						<SearchControl
							label={__('Search Content', 'aria')}
							value={searchTerm}
							onChange={setSearchTerm}
							placeholder={__('Search titles, content, or tags...', 'aria')}
						/>
						<SelectControl
							label={__('Filter by Type', 'aria')}
							value={selectedType}
							options={CONTENT_TYPES}
							onChange={setSelectedType}
						/>
						<SelectControl
							label={__('Filter by Status', 'aria')}
							value={selectedStatus}
							options={INDEXING_STATUS}
							onChange={setSelectedStatus}
						/>
					</div>
				</CardBody>
			</Card>

			{/* Content Items List */}
			<Card size="large" style={{ padding: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Content Items', 'aria')} ({filteredItems.length})
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__('Manage which content is available to the AI assistant', 'aria')}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					{filteredItems.length > 0 ? (
						<div>
							{filteredItems.map((item) => (
								<ContentItemCard
									key={item.id}
									item={item}
									onToggleIndex={handleToggleIndex}
									onViewContent={handleViewContent}
								/>
							))}
						</div>
					) : (
						<div
							style={{
								textAlign: 'center',
								padding: '40px 20px',
							}}
						>
							<div style={{ fontSize: '48px', marginBottom: '16px' }}>
								📄
							</div>
							<div
								style={{
									fontSize: '16px',
									color: '#757575',
									marginBottom: '20px',
								}}
							>
								{searchTerm || selectedType !== 'all' || selectedStatus !== 'all'
									? __('No content items match your search criteria.', 'aria')
									: __('No content items found. Start indexing to populate this list.', 'aria')}
							</div>
							{(!searchTerm && selectedType === 'all' && selectedStatus === 'all') && (
								<Button
									variant="primary"
									onClick={handleStartIndexing}
									disabled={isIndexing}
								>
									{__('Start Indexing', 'aria')}
								</Button>
							)}
						</div>
					)}
				</CardBody>
			</Card>

			{/* Indexing Progress Modal */}
			<IndexingProgressModal
				isOpen={isProgressModalOpen}
				onClose={() => setIsProgressModalOpen(false)}
				progress={indexingProgress}
			/>
		</div>
	);
};

export default ContentIndexing;