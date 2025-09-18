import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button, Spinner } from '@wordpress/components';
import {
	PageHeader,
	PageShell,
	SectionCard,
	QuickActionButton,
} from '../components';
import ModernMetricCard from '../components/ModernMetricCard';
import { fetchDashboardData } from '../utils/api';
import { formatTimeAgo, getAdminUrl } from '../utils/helpers';

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

	useEffect(() => {
		const loadDashboardData = async () => {
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
			} catch (error) {
				// eslint-disable-next-line no-console
				console.error('Dashboard data loading error:', error);
			} finally {
				setLoading(false);
			}
		};

		loadDashboardData();
	}, []);

	const handleNavigate = (path) => {
		window.location.href = getAdminUrl(path);
	};

	const conversationAvatar = (conversation) => {
		const letter = conversation.guest_name
			? conversation.guest_name.charAt(0).toUpperCase()
			: 'A';
		return letter;
	};

	const renderSetupSteps = () => {
		const pendingSteps = dashboardData.setupSteps.filter((step) => !step.completed);

		if (!pendingSteps.length) {
			return null;
		}

		return (
			<SectionCard
				title={__('Quick Setup', 'aria')}
				description={__(
					'Complete these steps to finish configuring Aria for your site.',
					'aria'
				)}
			>
				<div className="aria-dashboard__setup-list">
					{pendingSteps.map((step, index) => (
						<div
							className={`aria-dashboard__setup-item${
								step.completed ? ' is-complete' : ''
							}`}
							key={`${step.title}-${index}`}
						>
							<div className="aria-dashboard__setup-content">
								<div className="aria-dashboard__setup-title">{step.title}</div>
								{step.description && (
									<p className="aria-dashboard__setup-description">{step.description}</p>
								)}
							</div>
							<div className="aria-dashboard__setup-status">
								<span aria-hidden="true">{step.completed ? '✅' : '⏳'}</span>
								<span>
									{step.completed
										? __('Completed', 'aria')
										: __('Pending', 'aria')}
								</span>
								{!step.completed && step.link && (
									<Button
										variant="secondary"
										onClick={() => handleNavigate(step.link)}
										size="small"
									>
										{__('Configure', 'aria')}
									</Button>
								)}
							</div>
						</div>
					))}
				</div>
			</SectionCard>
		);
	};

	const renderRecentConversations = () => {
		const conversations = dashboardData.recentConversations;

		if (!conversations.length) {
			return (
				<div className="aria-dashboard__empty">
					<span role="img" aria-hidden="true" style={{ fontSize: '2.5rem' }}>
						💬
					</span>
					<p>{__('No conversations yet. Aria is ready to start chatting with your visitors!', 'aria')}</p>
					<Button
						variant="primary"
						onClick={() => window.open(window.location.origin, '_blank')}
					>
						{__('Test Aria', 'aria')}
					</Button>
				</div>
			);
		}

		return (
			<ul className="aria-dashboard__conversation-list">
				{conversations.map((conversation) => (
					<li key={conversation.id}>
						<button
							type="button"
							className="aria-dashboard__conversation-item"
							onClick={() =>
								handleNavigate(
									`admin.php?page=aria-conversations&conversation_id=${conversation.id}`
								)
							}
						>
							<div className="aria-dashboard__conversation-main">
								<div className="aria-dashboard__conversation-header">
									<span className="aria-dashboard__conversation-avatar">
										{conversationAvatar(conversation)}
									</span>
									<div className="aria-dashboard__conversation-text">
										<h3 className="aria-dashboard__conversation-title">
											{conversation.guest_name || __('Anonymous', 'aria')}
										</h3>
										<p className="aria-dashboard__conversation-snippet">
											{conversation.initial_question}
										</p>
									</div>
								</div>
								<p className="aria-dashboard__conversation-meta">
									{conversation.created_at
										? formatTimeAgo(conversation.created_at)
										: __('Unknown time', 'aria')}
								</p>
							</div>
						</button>
					</li>
				))}
			</ul>
		);
	};

	const renderQuickActions = () => (
		<div className="aria-dashboard__quick-actions">
			<QuickActionButton
				onClick={() => handleNavigate('admin.php?page=aria-knowledge&action=new')}
				icon="➕"
				label={__('Add Knowledge', 'aria')}
				gradient="primary"
			/>
			<QuickActionButton
				onClick={() => handleNavigate('admin.php?page=aria-personality')}
				icon="🎭"
				label={__('Adjust Personality', 'aria')}
				gradient="purple"
			/>
			<QuickActionButton
				onClick={() => window.open(window.location.origin, '_blank')}
				icon="🔗"
				label={__('Test Aria', 'aria')}
				gradient="green"
			/>
			<QuickActionButton
				onClick={() => handleNavigate('admin.php?page=aria-design')}
				icon="🎨"
				label={__('Customize Design', 'aria')}
				gradient="orange"
			/>
		</div>
	);

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

	const licenseStatus = dashboardData.licenseStatus?.status
		? dashboardData.licenseStatus.status.charAt(0).toUpperCase() +
		  dashboardData.licenseStatus.status.slice(1)
		: __('Trial', 'aria');

	return (
		<PageShell className="aria-dashboard aria-dashboard-react" width="wide">
			<PageHeader
				title={__('Dashboard', 'aria')}
				description={__("Monitor your AI assistant's performance and activity", 'aria')}
			/>

			<div className="aria-stack-lg">
				<div className="aria-dashboard__metrics aria-grid-metrics">
					<ModernMetricCard
						icon="activity"
						title={__("Today's Activity", 'aria')}
						value={dashboardData.conversationsToday || 0}
						subtitle={__('Conversations Today', 'aria')}
						theme="primary"
					/>
					<ModernMetricCard
						icon="users"
						title={__('Total Activity', 'aria')}
						value={dashboardData.totalConversations || 0}
						subtitle={__('Total Conversations', 'aria')}
						theme="info"
					/>
					<ModernMetricCard
						icon="knowledge"
						title={__('Knowledge Base', 'aria')}
						value={dashboardData.knowledgeCount || 0}
						subtitle={__('Knowledge Entries', 'aria')}
						theme="success"
					/>
					<ModernMetricCard
						icon="license"
						title={__('License Status', 'aria')}
						value={licenseStatus}
						subtitle={__('Current Status', 'aria')}
						theme="warning"
					/>
				</div>

				{renderSetupSteps()}

				<SectionCard
					title={__('Recent Conversations', 'aria')}
					actions={
						<Button
							variant="secondary"
							onClick={() => handleNavigate('admin.php?page=aria-conversations')}
						>
							{__('View All', 'aria')}
						</Button>
					}
				>
					{renderRecentConversations()}
				</SectionCard>

				<SectionCard
					title={__('Quick Actions', 'aria')}
					description={__(
						"Jump into common workflows to keep Aria's knowledge and experience fresh.",
						'aria'
					)}
				>
					{renderQuickActions()}
				</SectionCard>
			</div>
		</PageShell>
	);
};

export default Dashboard;
