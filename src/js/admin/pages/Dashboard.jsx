import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import { PageHeader, PageShell, SectionCard } from '../components';
import {
	DashboardMetricsSection,
	DashboardQuickActionsSection,
	DashboardRecentConversationsSection,
	DashboardSetupSection,
} from './dashboard-sections';
import { fetchDashboardData } from '../utils/api';
import { getAdminUrl } from '../utils/helpers';

const Dashboard = () => {
	const [dashboardData, setDashboardData] = useState({
		conversationsToday: 0,
		totalConversations: 0,
		knowledgeCount: 0,
		licenseStatus: { status: 'trial', days_remaining: 30 },
		recentConversations: [],
		setupSteps: [],
	});
	const [loading, setLoading] = useState(true);
	const [error, setError] = useState(null);

	const loadDashboardData = useCallback(async () => {
		setLoading(true);
		setError(null);
		try {
			const data = await fetchDashboardData();
			const safeData = {
				conversationsToday: data?.conversationsToday || 0,
				totalConversations: data?.totalConversations || 0,
				knowledgeCount: data?.knowledgeCount || 0,
				licenseStatus: data?.licenseStatus || { status: 'trial', days_remaining: 30 },
				recentConversations: Array.isArray(data?.recentConversations)
					? data.recentConversations
					: [],
				setupSteps: Array.isArray(data?.setupSteps) ? data.setupSteps : [],
			};

			setDashboardData(safeData);
		} catch (err) {
			// eslint-disable-next-line no-console
			console.error('Dashboard data loading error:', err);
			setError(err?.message || __('We could not load dashboard data. Please try again.', 'aria'));
		} finally {
			setLoading(false);
		}
	}, []);

	useEffect(() => {
		loadDashboardData();
	}, [loadDashboardData]);

	const handleNavigate = (path) => {
		window.location.href = getAdminUrl(path);
	};

	const handleTestChat = () => {
		window.open(window.location.origin, '_blank');
	};

	const handleRetry = () => {
		loadDashboardData();
	};

	if (loading) {
		return (
			<PageShell className="aria-dashboard aria-dashboard-react" width="wide">
				<PageHeader
					title={__('Dashboard', 'aria')}
					description={__('Loading dashboard data…', 'aria')}
				/>
				<SectionCard>
					<div className="aria-dashboard__empty">
						<Spinner />
						<p>{__('Fetching the latest insights…', 'aria')}</p>
					</div>
				</SectionCard>
			</PageShell>
		);
	}

	const licenseLabel = dashboardData.licenseStatus?.status
		? `${dashboardData.licenseStatus.status.charAt(0).toUpperCase()}${dashboardData.licenseStatus.status.slice(1)}`
		: __('Trial', 'aria');

	return (
		<PageShell className="aria-dashboard aria-dashboard-react" width="wide">
			<PageHeader
				title={__('Dashboard', 'aria')}
				description={__("Monitor your AI assistant's performance and activity", 'aria')}
			/>

			<div className="aria-stack-lg">
				{error && (
					<SectionCard
						className="aria-dashboard__notice"
						title={__('We hit a snag fetching live data', 'aria')}
						description={__(
							'Some metrics may be outdated until we reconnect. Retry below to refresh the latest numbers.',
							'aria'
						)}
						actions={
							<Button variant="secondary" onClick={handleRetry}>
								{__('Retry', 'aria')}
							</Button>
						}
					>
						<p className="aria-dashboard__notice-message">{error}</p>
					</SectionCard>
				)}

				<DashboardMetricsSection
					conversationsToday={dashboardData.conversationsToday || 0}
					totalConversations={dashboardData.totalConversations || 0}
					knowledgeCount={dashboardData.knowledgeCount || 0}
					licenseLabel={licenseLabel}
				/>

				<DashboardSetupSection
					steps={dashboardData.setupSteps}
					onNavigate={handleNavigate}
				/>

				<DashboardRecentConversationsSection
					conversations={dashboardData.recentConversations}
					onSelectConversation={(conversationId) =>
						handleNavigate(
							`admin.php?page=aria-conversations&conversation_id=${conversationId}`
						)
					}
					onViewAll={() => handleNavigate('admin.php?page=aria-conversations')}
					onTestChat={handleTestChat}
				/>

				<DashboardQuickActionsSection
					onNavigate={handleNavigate}
					onTestChat={handleTestChat}
				/>
			</div>
		</PageShell>
	);
};

export default Dashboard;
