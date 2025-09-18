import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import {
	KnowledgeMetrics,
	KnowledgeSearchControls,
	KnowledgeEntriesSection,
	KnowledgeNotice,
	KnowledgeLoading,
} from './knowledge-sections';
import { fetchKnowledgeData, deleteKnowledgeEntry } from '../utils/api';
import { getAdminUrl } from '../utils/helpers';

const KNOWLEDGE_CATEGORIES = [
	{ label: __('General Information', 'aria'), value: 'general' },
	{ label: __('Products & Services', 'aria'), value: 'products' },
	{ label: __('Support & Troubleshooting', 'aria'), value: 'support' },
	{ label: __('Company Information', 'aria'), value: 'company' },
	{ label: __('Policies & Terms', 'aria'), value: 'policies' },
];

const getCategoryLabel = (categoryValue) => {
	const category = KNOWLEDGE_CATEGORIES.find(
		(cat) => cat.value === categoryValue
	);
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
			const data = await fetchKnowledgeData();
			const knowledgeEntries = data.entries || [];
			const processedEntries = knowledgeEntries.map((entry) => ({
				id: entry.id,
				title: entry.title,
				content: entry.content,
				category: entry.category,
				categoryLabel: getCategoryLabel(entry.category),
				tags: entry.tags
					? entry.tags.split(',').map((tag) => tag.trim())
					: [],
				updated_at: entry.updated_at,
			}));

			setEntries(processedEntries);
			setKnowledgeData({
				totalEntries: data.totalEntries || processedEntries.length,
				categories: data.categories || KNOWLEDGE_CATEGORIES.length,
				lastUpdated: data.lastUpdated || __('Today', 'aria'),
				usageStats: data.usageStats || 0,
			});
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Failed to load knowledge data:', error);
			setEntries([]);
			setKnowledgeData({
				totalEntries: 0,
				categories: 0,
				lastUpdated: __('Never', 'aria'),
				usageStats: 0,
			});
		} finally {
			setLoading(false);
		}
	};

	useEffect(() => {
		const urlParams = new URLSearchParams(window.location.search);
		const message = urlParams.get('message');
		if (message) {
			setNotice({
				type: 'success',
				message: decodeURIComponent(message),
			});
			setTimeout(() => setNotice(null), 5000);
			const newUrl = `${window.location.pathname}?page=aria-knowledge`;
			window.history.replaceState({}, '', newUrl);
		}
	}, []);

	const handleAddEntry = () => {
		window.location.href = getAdminUrl(
			'admin.php?page=aria-knowledge-entry'
		);
	};

	const handleEditEntry = (entry) => {
		window.location.href = getAdminUrl(
			`admin.php?page=aria-knowledge-entry&action=edit&id=${entry.id}`
		);
	};

	const handleDeleteEntry = async (entryId) => {
		if (
			// eslint-disable-next-line no-alert
			!window.confirm(
				__('Are you sure you want to delete this entry?', 'aria')
			)
		) {
			return;
		}

		try {
			await deleteKnowledgeEntry(entryId);
			setEntries((prev) => prev.filter((entry) => entry.id !== entryId));
			setNotice({
				type: 'success',
				message: __('Knowledge entry deleted successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
			loadKnowledgeData();
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Delete error:', error);
			setNotice({
				type: 'error',
				message:
					error.message ||
					__('Failed to delete knowledge entry.', 'aria'),
			});
		}
	};

	const filteredEntries = entries.filter((entry) => {
		const term = searchTerm.toLowerCase();
		const matchesSearch =
			!term ||
			entry.title.toLowerCase().includes(term) ||
			entry.content.toLowerCase().includes(term) ||
			entry.tags.some((tag) => tag.toLowerCase().includes(term));
		const matchesCategory =
			selectedCategory === 'all' || entry.category === selectedCategory;
		return matchesSearch && matchesCategory;
	});

	if (loading) {
		return <KnowledgeLoading />;
	}

	const metrics = {
		totalEntries: {
			title: __('Total Entries', 'aria'),
			value: knowledgeData.totalEntries,
			subtitle: __('Knowledge Entries', 'aria'),
		},
		categories: {
			title: __('Categories', 'aria'),
			value: knowledgeData.categories,
			subtitle: __('Knowledge Categories', 'aria'),
		},
		lastUpdated: {
			title: __('Last Updated', 'aria'),
			value: knowledgeData.lastUpdated,
			subtitle: __('Most Recent Change', 'aria'),
		},
		usageStats: {
			title: __('Usage Stats', 'aria'),
			value: knowledgeData.usageStats,
			subtitle: __('Times Referenced', 'aria'),
		},
	};

	return (
		<PageShell className="aria-knowledge aria-knowledge-react" width="wide">
			<PageHeader
				title={__('Knowledge Base', 'aria')}
				description={__(
					"Manage your AI assistant's knowledge and responses",
					'aria'
				)}
			/>

			<KnowledgeNotice notice={notice} onRemove={() => setNotice(null)} />

			<div className="aria-stack-lg">
				<KnowledgeMetrics metrics={metrics} />
				<KnowledgeSearchControls
					searchValue={searchTerm}
					onSearchChange={setSearchTerm}
					filterValue={selectedCategory}
					onFilterChange={setSelectedCategory}
					categories={KNOWLEDGE_CATEGORIES}
				/>
				<KnowledgeEntriesSection
					entries={filteredEntries}
					onAddEntry={handleAddEntry}
					onEditEntry={handleEditEntry}
					onDeleteEntry={handleDeleteEntry}
				/>
			</div>
		</PageShell>
	);
};

export default Knowledge;
