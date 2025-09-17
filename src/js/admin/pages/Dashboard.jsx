import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Flex,
	Button,
} from '@wordpress/components';
import { QuickActionButton, PageHeader } from '../components';
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
				console.log('Dashboard data received:', data);
				
				// Ensure data has expected structure with fallbacks
				const safeData = {
					conversationsToday: data?.conversationsToday || 0,
					totalConversations: data?.totalConversations || 0,
					knowledgeCount: data?.knowledgeCount || 0,
					licenseStatus: data?.licenseStatus || { status: 'trial', days_remaining: 30 },
					recentConversations: Array.isArray(data?.recentConversations) ? data.recentConversations : [],
					setupSteps: Array.isArray(data?.setupSteps) ? data.setupSteps : [],
				};
				
				setDashboardData(safeData);
			} catch (error) {
				console.error('Dashboard data loading error:', error);
				// Keep default initial state on error - no need to change anything
			} finally {
				setLoading(false);
			}
		};

		loadDashboardData();
	}, []);

	if (loading) {
		return (
			<div className="aria-dashboard-react">
				<PageHeader
					title={__('Dashboard', 'aria')}
					description={__('Loading dashboard data…', 'aria')}
				/>
			</div>
		);
	}

	return (
		<div className="aria-dashboard-react" style={{ paddingRight: '32px' }}>
			<PageHeader
				title={__('Dashboard', 'aria')}
				description={__(
					"Monitor your AI assistant's performance and activity",
					'aria'
				)}
			/>

			{/* Main Metrics Grid */}
			<div
				className="aria-dashboard-metrics"
				style={{
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))',
					gap: '24px',
					marginBottom: '32px',
					alignItems: 'start', // Align cards to top instead of stretching
				}}
			>
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
					value={
						dashboardData.licenseStatus && dashboardData.licenseStatus.status
							? dashboardData.licenseStatus.status
								.charAt(0)
								.toUpperCase() +
							  dashboardData.licenseStatus.status.slice(1)
							: 'Trial'
					}
					subtitle={__('Current Status', 'aria')}
					theme="warning"
				/>
			</div>

			<div style={{ marginTop: '64px' }} />

			{/* Setup Steps */}
			{dashboardData.setupSteps.some((step) => !step.completed) && (
				<>
					<Card>
						<CardHeader>
							<h3
								style={{
									fontSize: '18px',
									fontWeight: '600',
									margin: 0,
								}}
							>
								{__('Quick Setup', 'aria')}
							</h3>
						</CardHeader>
						<CardBody>
							<Flex direction="row" gap={4} wrap={true}>
								{dashboardData.setupSteps.map((step, index) => (
									<div key={index}>
										<div
											className={`setup-step ${step.completed ? 'completed' : ''}`}
										>
											<div className="step-icon">
												{step.completed ? '✅' : '⏳'}
											</div>
											<span
												style={{
													fontWeight: '500',
												}}
											>
												{step.title}
											</span>
											{!step.completed && (
												<Button
													variant="link"
													href={step.link}
													size="small"
												>
													{__('Configure', 'aria')}
												</Button>
											)}
										</div>
									</div>
								))}
							</Flex>
						</CardBody>
					</Card>
					<div style={{ marginTop: '24px' }} />
				</>
			)}

			{/* Recent Conversations */}
			<div style={{ marginTop: '48px' }} />
			<Card size="large" style={{ padding: '24px' }}>
				<CardHeader style={{ paddingBottom: '20px' }}>
					<Flex justify="space-between" align="center">
						<h2
							style={{
								fontSize: '20px',
								fontWeight: '600',
								color: '#1e1e1e',
								margin: 0,
							}}
						>
							{__('Recent Conversations', 'aria')}
						</h2>
						<Button
							variant="secondary"
							onClick={() =>
								(window.location.href = getAdminUrl(
									'admin.php?page=aria-conversations'
								))
							}
						>
							{__('View All', 'aria')}
						</Button>
					</Flex>
				</CardHeader>
				<CardBody style={{ padding: '0' }}>
					{dashboardData.recentConversations.length > 0 ? (
						<div
							style={{
								display: 'flex',
								flexDirection: 'column',
								gap: '0',
							}}
						>
							{dashboardData.recentConversations.map(
								(conversation, index) => (
									<button
										key={conversation.id}
										type="button"
										onClick={() =>
											(window.location.href = getAdminUrl(
												`admin.php?page=aria-conversations&conversation_id=${conversation.id}`
											))
										}
										style={{
											padding: '16px 20px',
											border: 'none',
											background: 'transparent',
											width: '100%',
											textAlign: 'left',
											borderBottom:
												index <
												dashboardData
													.recentConversations
													.length -
													1
													? '1px solid #e5e5e5'
													: 'none',
											cursor: 'pointer',
											transition:
												'background-color 0.2s ease',
										}}
										onMouseEnter={(e) =>
											(e.target.style.backgroundColor =
												'#f8f9fa')
										}
										onMouseLeave={(e) =>
											(e.target.style.backgroundColor =
												'transparent')
										}
									>
										<Flex
											gap={3}
											align="flex-start"
											justify="space-between"
										>
											<Flex
												gap={3}
												align="flex-start"
												style={{ flex: 1 }}
											>
												<div
													style={{
														width: '40px',
														height: '40px',
														borderRadius: '50%',
														backgroundColor:
															'#2271b1',
														color: 'white',
														display: 'flex',
														alignItems: 'center',
														justifyContent:
															'center',
														fontSize: '16px',
														fontWeight: '600',
														flexShrink: 0,
													}}
												>
													{conversation.guest_name
														? conversation.guest_name
																.charAt(0)
																.toUpperCase()
														: 'A'}
												</div>
												<div
													style={{
														flex: 1,
														minWidth: 0,
													}}
												>
													<div
														style={{
															fontSize: '15px',
															fontWeight: '600',
															color: '#1e1e1e',
															marginBottom: '4px',
														}}
													>
														{conversation.guest_name ||
															__(
																'Anonymous',
																'aria'
															)}
													</div>
													<div
														style={{
															fontSize: '14px',
															color: '#757575',
															marginBottom: '6px',
															overflow: 'hidden',
															textOverflow:
																'ellipsis',
															whiteSpace:
																'nowrap',
														}}
													>
														{
															conversation.initial_question
														}
													</div>
													<div
														style={{
															fontSize: '13px',
															color: '#949494',
														}}
													>
														{conversation.created_at
															? formatTimeAgo(
																	conversation.created_at
																)
															: __(
																	'Unknown time',
																	'aria'
																)}
													</div>
												</div>
											</Flex>
										</Flex>
									</button>
								)
							)}
						</div>
					) : (
						<div
							style={{
								textAlign: 'center',
								padding: '40px 20px',
							}}
						>
							<div
								style={{
									fontSize: '48px',
									marginBottom: '16px',
								}}
							>
								💬
							</div>
							<div
								style={{
									fontSize: '16px',
									color: '#757575',
									marginBottom: '20px',
								}}
							>
								{__(
									'No conversations yet. Aria is ready to start chatting with your visitors!',
									'aria'
								)}
							</div>
							<Button
								variant="primary"
								onClick={() =>
									window.open(
										window.location.origin,
										'_blank'
									)
								}
							>
								{__('Test Aria', 'aria')}
							</Button>
						</div>
					)}
				</CardBody>
			</Card>

			<div style={{ marginTop: '32px' }} />

			{/* Quick Actions */}
			<Card size="large" style={{ padding: '24px' }}>
				<CardHeader style={{ paddingBottom: '20px' }}>
					<h2
						style={{
							fontSize: '20px',
							fontWeight: '600',
							color: '#1e1e1e',
							margin: 0,
						}}
					>
						{__('Quick Actions', 'aria')}
					</h2>
				</CardHeader>
				<CardBody>
					<div
						style={{
							display: 'grid',
							gridTemplateColumns:
								'repeat(auto-fit, minmax(220px, 1fr))',
							gap: '20px',
						}}
					>
						<QuickActionButton
							onClick={() =>
								(window.location.href = getAdminUrl(
									'admin.php?page=aria-knowledge&action=new'
								))
							}
							icon="➕"
							label={__('Add Knowledge', 'aria')}
							gradient="primary"
						/>
						<QuickActionButton
							onClick={() =>
								(window.location.href = getAdminUrl(
									'admin.php?page=aria-personality'
								))
							}
							icon="🎭"
							label={__('Adjust Personality', 'aria')}
							gradient="purple"
							hoverColor="#667eea"
						/>
						<QuickActionButton
							onClick={() =>
								window.open(window.location.origin, '_blank')
							}
							icon="🔗"
							label={__('Test Aria', 'aria')}
							gradient="green"
							hoverColor="#28a745"
						/>
						<QuickActionButton
							onClick={() =>
								(window.location.href = getAdminUrl(
									'admin.php?page=aria-design'
								))
							}
							icon="🎨"
							label={__('Customize Design', 'aria')}
							gradient="orange"
							hoverColor="#fd7e14"
						/>
					</div>
				</CardBody>
			</Card>
		</div>
	);
};

export default Dashboard;
