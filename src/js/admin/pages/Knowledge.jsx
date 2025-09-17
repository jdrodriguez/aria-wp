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
	Modal,
	Notice,
	Flex,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { PageHeader } from '../components';
import ModernMetricCard from '../components/ModernMetricCard';
import ModernKnowledgeEntryCard from '../components/ModernKnowledgeEntryCard';
import ModernSearchFilter from '../components/ModernSearchFilter';

import { fetchKnowledgeData, deleteKnowledgeEntry } from '../utils/api';

const KNOWLEDGE_CATEGORIES = [
	{ label: __('General Information', 'aria'), value: 'general' },
	{ label: __('Products & Services', 'aria'), value: 'products' },
	{ label: __('Support & Troubleshooting', 'aria'), value: 'support' },
	{ label: __('Company Information', 'aria'), value: 'company' },
	{ label: __('Policies & Terms', 'aria'), value: 'policies' },
];

// Note: Replaced KnowledgeEntryModal with AIKnowledgeGenerator component

const KnowledgeEntryCard = ({ entry, onEdit, onDelete }) => {
	const formatDate = (dateString) => {
		return new Date(dateString).toLocaleDateString();
	};

	return (
		<Card style={{ marginBottom: '16px' }}>
			<CardBody style={{ padding: '20px' }}>
				<Flex justify="space-between" align="flex-start">
					<div style={{ flex: 1, minWidth: 0 }}>
						<h4
							style={{
								fontSize: '16px',
								fontWeight: '600',
								margin: '0 0 8px 0',
							}}
						>
							{entry.title}
						</h4>
						<p
							style={{
								fontSize: '13px',
								color: '#757575',
								margin: '0 0 12px 0',
							}}
						>
							{__('Category:', 'aria')}{' '}
							<strong>{entry.categoryLabel}</strong> •{' '}
							{__('Updated:', 'aria')}{' '}
							{formatDate(entry.updated_at)}
						</p>
						<p
							style={{
								fontSize: '14px',
								color: '#1e1e1e',
								margin: '0 0 12px 0',
								overflow: 'hidden',
								textOverflow: 'ellipsis',
								display: '-webkit-box',
								WebkitLineClamp: 2,
								WebkitBoxOrient: 'vertical',
							}}
						>
							{entry.content}
						</p>
						{entry.tags && entry.tags.length > 0 && (
							<div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
								{entry.tags.map((tag, index) => (
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
					<div style={{ display: 'flex', gap: '8px', marginLeft: '16px' }}>
						<Button variant="secondary" size="small" onClick={() => onEdit(entry)}>
							{__('Edit', 'aria')}
						</Button>
						<Button
							variant="secondary"
							size="small"
							onClick={() => onDelete(entry.id)}
							style={{ color: '#dc3545' }}
						>
							{__('Delete', 'aria')}
						</Button>
					</div>
				</Flex>
			</CardBody>
		</Card>
	);
};

KnowledgeEntryCard.propTypes = {
	entry: PropTypes.object.isRequired,
	onEdit: PropTypes.func.isRequired,
	onDelete: PropTypes.func.isRequired,
};

// Helper function to get category label
const getCategoryLabel = (categoryValue) => {
	const category = KNOWLEDGE_CATEGORIES.find(cat => cat.value === categoryValue);
	return category ? category.label : categoryValue;
};

const Knowledge = () => {
	
	const [knowledgeData, setKnowledgeData] = useState({
		totalEntries: 0,
		categories: 0,
		lastUpdated: 'Never',
		usageStats: 0,
	});
	const [entries, setEntries] = useState([]);
	const [loading, setLoading] = useState(true);
	const [searchTerm, setSearchTerm] = useState('');
	const [selectedCategory, setSelectedCategory] = useState('all');
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		loadKnowledgeData();
	}, []);

	const loadKnowledgeData = async () => {
		setLoading(true);
		try {
			// Fetch real data using API utility
			const data = await fetchKnowledgeData();
			
			const knowledgeEntries = data.entries || [];
			
			// Process entries to match component format
			const processedEntries = knowledgeEntries.map(entry => ({
				id: entry.id,
				title: entry.title,
				content: entry.content,
				category: entry.category,
				categoryLabel: getCategoryLabel(entry.category),
				tags: entry.tags ? entry.tags.split(',').map(tag => tag.trim()) : [],
				updated_at: entry.updated_at
			}));

			setEntries(processedEntries);
			setKnowledgeData({
				totalEntries: data.totalEntries || processedEntries.length,
				categories: data.categories || 5,
				lastUpdated: data.lastUpdated || 'Today',
				usageStats: data.usageStats || 0,
			});
		} catch (error) {
			console.error('Failed to load knowledge data:', error);
			// Set empty state on error
			setEntries([]);
			setKnowledgeData({
				totalEntries: 0,
				categories: 0,
				lastUpdated: 'Never',
				usageStats: 0,
			});
		} finally {
			setLoading(false);
		}
	};

	// Handle success messages from redirects
	useEffect(() => {
		const urlParams = new URLSearchParams(window.location.search);
		const message = urlParams.get('message');
		if (message) {
			setNotice({
				type: 'success',
				message: decodeURIComponent(message),
			});
			setTimeout(() => setNotice(null), 5000);
			
			// Clean up URL by removing the message parameter
			const newUrl = window.location.pathname + '?page=aria-knowledge';
			window.history.replaceState({}, '', newUrl);
		}
	}, []);

	const handleEditEntry = (entry) => {
		// Create edit URL with proper parameters
		const editUrl = `admin.php?page=aria-knowledge-entry&action=edit&id=${entry.id}`;
		window.location.href = editUrl;
	};

	const handleDeleteEntry = async (entryId) => {
		if (!confirm(__('Are you sure you want to delete this entry?', 'aria'))) {
			return;
		}

		try {
			// Delete using API utility
			await deleteKnowledgeEntry(entryId);
			
			// Remove from state
			setEntries((prev) => prev.filter((entry) => entry.id !== entryId));
			setNotice({
				type: 'success',
				message: __('Knowledge entry deleted successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
			
			// Reload data to get updated stats
			loadKnowledgeData();
		} catch (error) {
			console.error('Delete error:', error);
			setNotice({
				type: 'error',
				message: error.message || __('Failed to delete knowledge entry.', 'aria'),
			});
		}
	};

	const filteredEntries = entries.filter((entry) => {
		const matchesSearch =
			!searchTerm ||
			entry.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
			entry.content.toLowerCase().includes(searchTerm.toLowerCase()) ||
			entry.tags.some((tag) =>
				tag.toLowerCase().includes(searchTerm.toLowerCase())
			);

		const matchesCategory =
			selectedCategory === 'all' || entry.category === selectedCategory;

		return matchesSearch && matchesCategory;
	});

	if (loading) {
		return (
			<div className="aria-knowledge-react" style={{ paddingRight: '32px' }}>
				<PageHeader
					title={__('Knowledge Base', 'aria')}
					description={__('Loading knowledge base...', 'aria')}
				/>
			</div>
		);
	}

	return (
		<div className="aria-knowledge-react" style={{ paddingRight: '32px' }}>
			<PageHeader
				title={__('Knowledge Base', 'aria')}
				description={__(
					"Manage your AI assistant's knowledge and responses",
					'aria'
				)}
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

			{/* Knowledge Metrics */}
			<div
				className="aria-metrics-grid"
				style={{
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
					gap: '24px',
					marginBottom: '32px',
					alignItems: 'stretch', // Make all cards equal height
				}}
			>
				<ModernMetricCard
					icon="knowledge"
					title={__('Total Entries', 'aria')}
					value={knowledgeData.totalEntries}
					subtitle={__('Knowledge Entries', 'aria')}
					theme="primary"
				/>
				<ModernMetricCard
					icon="license"
					title={__('Categories', 'aria')}
					value={knowledgeData.categories}
					subtitle={__('Knowledge Categories', 'aria')}
					theme="info"
				/>
				<ModernMetricCard
					icon="activity"
					title={__('Last Updated', 'aria')}
					value={knowledgeData.lastUpdated}
					subtitle={__('Most Recent Change', 'aria')}
					theme="warning"
				/>
				<ModernMetricCard
					icon="users"
					title={__('Usage Stats', 'aria')}
					value={knowledgeData.usageStats}
					subtitle={__('Times Referenced', 'aria')}
					theme="success"
				/>
			</div>


			{/* Search and Filter */}
			<ModernSearchFilter
				searchValue={searchTerm}
				onSearchChange={setSearchTerm}
				searchPlaceholder={__('Search titles, content, or tags...', 'aria')}
				filterValue={selectedCategory}
				onFilterChange={setSelectedCategory}
				filterOptions={[
					{ label: __('All Categories', 'aria'), value: 'all' },
					...KNOWLEDGE_CATEGORIES,
				]}
				filterLabel={__('Filter by Category', 'aria')}
				title={__('Search & Filter', 'aria')}
				description={__('Find specific knowledge entries', 'aria')}
			/>

			{/* Knowledge Entries List */}
			<Card size="large" style={{ padding: '24px' }}>
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
								{__('Knowledge Entries', 'aria')} ({filteredEntries.length})
							</h3>
							<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
								{__(
									'Manage your existing knowledge base entries',
									'aria'
								)}
							</p>
						</div>
						<Button
							variant="primary"
							onClick={() => {
								window.location.href = 'admin.php?page=aria-knowledge-entry';
							}}
						>
							{__('Add New Entry', 'aria')}
						</Button>
					</Flex>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					{filteredEntries.length > 0 ? (
						<div>
							{filteredEntries.map((entry) => (
								<ModernKnowledgeEntryCard
									key={entry.id}
									entry={entry}
									onEdit={handleEditEntry}
									onDelete={handleDeleteEntry}
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
								📚
							</div>
							<div
								style={{
									fontSize: '16px',
									color: '#757575',
									marginBottom: '20px',
								}}
							>
								{searchTerm || selectedCategory !== 'all'
									? __(
											'No knowledge entries match your search criteria.',
											'aria'
									  )
									: __(
											'No knowledge entries yet. Add your first entry to get started!',
											'aria'
									  )}
							</div>
							{(!searchTerm && selectedCategory === 'all') && (
								<Button
									variant="primary"
									onClick={() => {
										window.location.href = 'admin.php?page=aria-knowledge-entry';
									}}
								>
									{__('Add Your First Entry', 'aria')}
								</Button>
							)}
						</div>
					)}
				</CardBody>
			</Card>

		</div>
	);
};

export default Knowledge;