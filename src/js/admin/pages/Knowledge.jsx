import { useState, useEffect, useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import {
	KnowledgeMetrics,
	KnowledgeSearchControls,
	KnowledgeCategoryPills,
	KnowledgeEntriesSection,
	KnowledgeNotice,
	KnowledgeLoading,
} from './knowledge-sections';
import { fetchKnowledgeData, deleteKnowledgeEntry } from '../utils/api';
import { getAdminUrl } from '../utils/helpers';

const DEFAULT_KNOWLEDGE_CATEGORIES = [
	{ label: __('General Information', 'aria'), value: 'general' },
	{ label: __('Products & Services', 'aria'), value: 'products' },
	{ label: __('Support & Troubleshooting', 'aria'), value: 'support' },
	{ label: __('Company Information', 'aria'), value: 'company' },
	{ label: __('Policies & Terms', 'aria'), value: 'policies' },
];

const formatCategoryLabel = (slug = '') => {
	if (!slug) {
		return '';
	}

	return slug
		.replace(/-/g, ' ')
		.replace(/_/g, ' ')
		.replace(/\b\w/g, (char) => char.toUpperCase());
};

const getCategoryLabel = (categoryValue, options) => {
	const category = options.find((cat) => cat.value === categoryValue);
	return category ? category.label : formatCategoryLabel(categoryValue);
};

const defaultCategoryValues = new Set(
	DEFAULT_KNOWLEDGE_CATEGORIES.map((category) => category.value)
);

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
	const [availableCategories, setAvailableCategories] = useState(
		DEFAULT_KNOWLEDGE_CATEGORIES
	);

	const categoryCounts = useMemo(() => {
		const counts = entries.reduce((acc, entry) => {
			const key = entry.category || 'uncategorized';
			acc[key] = (acc[key] || 0) + 1;
			return acc;
		}, {});
		return counts;
	}, [entries]);

	const categoryPillOptions = useMemo(() => {
		const dedupe = new Map();
		const baseOptions = [
			{
				label: __('All', 'aria'),
				value: 'all',
				count: entries.length,
			},
			...availableCategories.map((category) => ({
				label: category.label,
				value: category.value,
				count: categoryCounts[category.value] || 0,
			})),
		];

		baseOptions.forEach((option) => {
			if (!dedupe.has(option.value)) {
				dedupe.set(option.value, option);
			} else {
				const existing = dedupe.get(option.value);
				dedupe.set(option.value, {
					...existing,
					count: option.count,
				});
			}
		});

		const filtered = Array.from(dedupe.values()).filter((option) => {
			if (option.value === 'all') {
				return true;
			}
			if (defaultCategoryValues.has(option.value)) {
				return true;
			}
			if (option.value === 'uncategorized') {
				return option.count > 0;
			}
			return option.count > 1;
		});

		return filtered;
	}, [availableCategories, categoryCounts, entries.length]);

	useEffect(() => {
		loadKnowledgeData();
	}, []);

	const loadKnowledgeData = async () => {
		setLoading(true);
		setNotice(null);
		try {
			const data = await fetchKnowledgeData();
			const knowledgeEntries = data.entries || [];

			const derivedCategories = Array.isArray(data.categoriesList)
				? data.categoriesList
						.filter(Boolean)
						.map((slug) => ({
							value: slug,
							label: formatCategoryLabel(slug),
						}))
				: [];

			const mergedCategoriesMap = new Map();
			[...DEFAULT_KNOWLEDGE_CATEGORIES, ...derivedCategories].forEach((cat) => {
				if (!mergedCategoriesMap.has(cat.value)) {
					mergedCategoriesMap.set(cat.value, cat);
				}
			});
			setAvailableCategories(Array.from(mergedCategoriesMap.values()));

			const processedEntries = knowledgeEntries.map((entry) => {
				const tagsList = Array.isArray(entry.tags_array)
					? entry.tags_array
					: entry.tags
					? entry.tags.split(',').map((tag) => tag.trim())
					: [];

				return {
					id: entry.id,
					title: entry.title,
					content: entry.content_preview || entry.content || '',
					fullContent: entry.content || '',
					category: entry.category,
					categoryLabel: getCategoryLabel(entry.category, Array.from(mergedCategoriesMap.values())),
					tags: tagsList,
					updated_at: entry.updated_at,
					status: entry.status,
					totalChunks: entry.total_chunks || 0,
					language: entry.language || 'en',
				};
			});

			setEntries(processedEntries);
			setKnowledgeData({
				totalEntries: data.totalEntries || processedEntries.length,
				categories:
					data.categories || mergedCategoriesMap.size || DEFAULT_KNOWLEDGE_CATEGORIES.length,
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
			setNotice({
				type: 'error',
				message: error?.message || __('Failed to load knowledge entries.', 'aria'),
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
			entry.fullContent?.toLowerCase().includes(term) ||
			entry.tags.some((tag) => tag.toLowerCase().includes(term));
		const matchesCategory =
			selectedCategory === 'all' || entry.category === selectedCategory;
		return matchesSearch && matchesCategory;
	});

	const hasFiltersApplied =
		Boolean(searchTerm.trim()) || selectedCategory !== 'all';

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
					categories={availableCategories}
				/>
				<KnowledgeCategoryPills
					categories={categoryPillOptions}
					activeValue={selectedCategory}
					onSelect={setSelectedCategory}
				/>
				<KnowledgeEntriesSection
					entries={filteredEntries}
					totalCount={entries.length}
					filteredCount={filteredEntries.length}
					isFiltered={hasFiltersApplied}
					onAddEntry={handleAddEntry}
					onEditEntry={handleEditEntry}
					onDeleteEntry={handleDeleteEntry}
				/>
			</div>
		</PageShell>
	);
};

export default Knowledge;
