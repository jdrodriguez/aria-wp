import { createRoot } from '@wordpress/element';
import { Dashboard, Personality } from './pages';
import { lazyLoad } from './utils/LazyLoader.jsx';

// Lazy load heavier components to improve initial bundle size
const Settings = lazyLoad(() => import('./pages/Settings.jsx').then(m => ({ default: m.default })));
const Design = lazyLoad(() => import('./pages/Design.jsx').then(m => ({ default: m.default })));
const Knowledge = lazyLoad(() => import('./pages/Knowledge.jsx').then(m => ({ default: m.default })));
const KnowledgeEntry = lazyLoad(() => import('./pages/KnowledgeEntry.jsx').then(m => ({ default: m.default })));
const AIConfig = lazyLoad(() => import('./pages/AIConfig.jsx').then(m => ({ default: m.AIConfig })));
const Conversations = lazyLoad(() => import('./pages/Conversations.jsx').then(m => ({ default: m.default })));
const ContentIndexing = lazyLoad(() => import('./pages/ContentIndexing.jsx').then(m => ({ default: m.default })));

/**
 * Mount React components to their respective DOM containers
 */
const mountComponents = () => {
	// Mount Dashboard Page
	const dashboardRoot = document.getElementById('aria-dashboard-root');
	if (dashboardRoot) {
		const root = createRoot(dashboardRoot);
		root.render(<Dashboard />);
	}

	// Mount Settings Page
	const settingsRoot = document.getElementById('aria-settings-root');
	if (settingsRoot) {
		const root = createRoot(settingsRoot);
		root.render(<Settings />);
	}

	// Mount Design Page
	const designRoot = document.getElementById('aria-design-root');
	if (designRoot) {
		const root = createRoot(designRoot);
		root.render(<Design />);
	}

	// Mount Personality Page
	const personalityRoot = document.getElementById('aria-personality-root');
	if (personalityRoot) {
		const root = createRoot(personalityRoot);
		root.render(<Personality />);
	}

	// Mount Knowledge Page
	const knowledgeRoot = document.getElementById('aria-knowledge-root');
	if (knowledgeRoot) {
		const root = createRoot(knowledgeRoot);
		root.render(<Knowledge />);
	}

	// Mount Knowledge Entry Page
	const knowledgeEntryRoot = document.getElementById('aria-knowledge-entry-root');
	if (knowledgeEntryRoot) {
		const root = createRoot(knowledgeEntryRoot);
		root.render(<KnowledgeEntry />);
	}

	// Mount AI Config Page
	const aiConfigRoot = document.getElementById('aria-ai-config-root');
	if (aiConfigRoot) {
		const root = createRoot(aiConfigRoot);
		root.render(<AIConfig />);
	}

	// Mount Conversations Page
	const conversationsRoot = document.getElementById('aria-conversations-root');
	if (conversationsRoot) {
		const root = createRoot(conversationsRoot);
		root.render(<Conversations />);
	}

	// Mount Content Indexing Page
	const contentIndexingRoot = document.getElementById('aria-content-indexing-root');
	if (contentIndexingRoot) {
		const root = createRoot(contentIndexingRoot);
		root.render(<ContentIndexing />);
	}
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', mountComponents);