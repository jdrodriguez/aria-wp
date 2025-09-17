import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Button,
	TextControl,
	SelectControl,
	SearchControl,
	Modal,
	Notice,
	Flex,
	__experimentalText as Text,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { PageHeader, MetricCard } from '../components';

const CONVERSATION_STATUSES = [
	{ label: __('All Statuses', 'aria'), value: 'all' },
	{ label: __('Active', 'aria'), value: 'active' },
	{ label: __('Resolved', 'aria'), value: 'resolved' },
	{ label: __('Pending', 'aria'), value: 'pending' },
	{ label: __('Archived', 'aria'), value: 'archived' },
];

const CONVERSATION_SOURCES = [
	{ label: __('All Sources', 'aria'), value: 'all' },
	{ label: __('Website Widget', 'aria'), value: 'widget' },
	{ label: __('Admin Panel', 'aria'), value: 'admin' },
	{ label: __('Email', 'aria'), value: 'email' },
];

const ConversationDetailModal = ({ isOpen, onClose, conversation }) => {
	if (!isOpen || !conversation) return null;

	return (
		<Modal
			title={__('Conversation Details', 'aria')}
			onRequestClose={onClose}
			style={{ maxWidth: '800px' }}
		>
			<div style={{ padding: '16px 0' }}>
				{/* Conversation Header */}
				<div
					style={{
						padding: '16px',
						backgroundColor: '#f8f9fa',
						borderRadius: '8px',
						marginBottom: '20px',
					}}
				>
					<div style={{ display: 'grid', gap: '12px' }}>
						<div style={{ display: 'flex', justifyContent: 'space-between' }}>
							<div>
								<Text style={{ fontSize: '14px', fontWeight: '600' }}>
									{conversation.visitor_name || __('Anonymous Visitor', 'aria')}
								</Text>
								{conversation.visitor_email && (
									<Text style={{ fontSize: '13px', color: '#757575' }}>
										{conversation.visitor_email}
									</Text>
								)}
							</div>
							<div style={{ textAlign: 'right' }}>
								<div
									style={{
										display: 'inline-block',
										padding: '4px 12px',
										borderRadius: '12px',
										fontSize: '12px',
										fontWeight: '600',
										backgroundColor:
											conversation.status === 'active'
												? '#d4edda'
												: conversation.status === 'resolved'
												? '#cce7ff'
												: '#fff3cd',
										color:
											conversation.status === 'active'
												? '#155724'
												: conversation.status === 'resolved'
												? '#004085'
												: '#856404',
									}}
								>
									{conversation.status.charAt(0).toUpperCase() +
										conversation.status.slice(1)}
								</div>
							</div>
						</div>
						<div style={{ fontSize: '13px', color: '#757575' }}>
							{__('Started:', 'aria')} {conversation.created_at} •{' '}
							{__('Messages:', 'aria')} {conversation.message_count}
						</div>
					</div>
				</div>

				{/* Messages */}
				<div style={{ maxHeight: '400px', overflowY: 'auto', marginBottom: '20px' }}>
					{conversation.messages.map((message, index) => (
						<div
							key={index}
							style={{
								display: 'flex',
								marginBottom: '16px',
								flexDirection: message.sender === 'visitor' ? 'row' : 'row-reverse',
							}}
						>
							<div
								style={{
									maxWidth: '70%',
									padding: '12px 16px',
									borderRadius: '16px',
									backgroundColor:
										message.sender === 'visitor' ? '#f1f3f4' : '#2271b1',
									color: message.sender === 'visitor' ? '#1e1e1e' : '#ffffff',
								}}
							>
								<div style={{ fontSize: '14px', marginBottom: '4px' }}>
									{message.content}
								</div>
								<div
									style={{
										fontSize: '12px',
										opacity: 0.7,
									}}
								>
									{message.timestamp}
								</div>
							</div>
						</div>
					))}
				</div>

				{/* Actions */}
				<div
					style={{
						display: 'flex',
						justifyContent: 'flex-end',
						gap: '12px',
						paddingTop: '16px',
						borderTop: '1px solid #e1e4e8',
					}}
				>
					<Button variant="secondary" onClick={onClose}>
						{__('Close', 'aria')}
					</Button>
					<Button variant="primary">
						{__('Reply', 'aria')}
					</Button>
				</div>
			</div>
		</Modal>
	);
};

ConversationDetailModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	conversation: PropTypes.object,
};

const ConversationCard = ({ conversation, onView, onUpdateStatus }) => {
	const getStatusColor = (status) => {
		switch (status) {
			case 'active':
				return { bg: '#d4edda', color: '#155724' };
			case 'resolved':
				return { bg: '#cce7ff', color: '#004085' };
			case 'pending':
				return { bg: '#fff3cd', color: '#856404' };
			case 'archived':
				return { bg: '#f8d7da', color: '#721c24' };
			default:
				return { bg: '#e2e3e5', color: '#383d41' };
		}
	};

	const statusColors = getStatusColor(conversation.status);

	return (
		<Card style={{ marginBottom: '16px' }}>
			<CardBody style={{ padding: '20px' }}>
				<Flex justify="space-between" align="flex-start">
					<div style={{ flex: 1, minWidth: 0 }}>
						{/* Header */}
						<div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '12px' }}>
							<h4
								style={{
									fontSize: '16px',
									fontWeight: '600',
									margin: 0,
								}}
							>
								{conversation.visitor_name || __('Anonymous Visitor', 'aria')}
							</h4>
							<div
								style={{
									padding: '2px 8px',
									borderRadius: '12px',
									fontSize: '12px',
									fontWeight: '600',
									backgroundColor: statusColors.bg,
									color: statusColors.color,
								}}
							>
								{conversation.status.charAt(0).toUpperCase() + conversation.status.slice(1)}
							</div>
						</div>

						{/* Visitor Info */}
						<div style={{ marginBottom: '12px' }}>
							{conversation.visitor_email && (
								<div style={{ fontSize: '13px', color: '#757575', marginBottom: '4px' }}>
									📧 {conversation.visitor_email}
								</div>
							)}
							<div style={{ fontSize: '13px', color: '#757575' }}>
								🌐 {conversation.source} • 
								📅 {conversation.created_at} • 
								💬 {conversation.message_count} {__('messages', 'aria')}
							</div>
						</div>

						{/* Last Message Preview */}
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
							<strong>{__('Last message:', 'aria')}</strong> {conversation.last_message}
						</div>

						{/* Tags */}
						{conversation.tags && conversation.tags.length > 0 && (
							<div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
								{conversation.tags.map((tag, index) => (
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
							onClick={() => onView(conversation)}
						>
							{__('View', 'aria')}
						</Button>
						<SelectControl
							label=""
							value={conversation.status}
							options={CONVERSATION_STATUSES.filter(status => status.value !== 'all')}
							onChange={(value) => onUpdateStatus(conversation.id, value)}
							style={{ minWidth: '120px' }}
						/>
					</div>
				</Flex>
			</CardBody>
		</Card>
	);
};

ConversationCard.propTypes = {
	conversation: PropTypes.object.isRequired,
	onView: PropTypes.func.isRequired,
	onUpdateStatus: PropTypes.func.isRequired,
};

const Conversations = () => {
	const [conversationsData, setConversationsData] = useState({
		totalConversations: 0,
		activeConversations: 0,
		avgResponseTime: '0m',
		satisfactionRate: '0%',
	});
	const [conversations, setConversations] = useState([]);
	const [loading, setLoading] = useState(true);
	const [searchTerm, setSearchTerm] = useState('');
	const [selectedStatus, setSelectedStatus] = useState('all');
	const [selectedSource, setSelectedSource] = useState('all');
	const [selectedConversation, setSelectedConversation] = useState(null);
	const [isDetailModalOpen, setIsDetailModalOpen] = useState(false);
	const [notice, setNotice] = useState(null);

	useEffect(() => {
		loadConversations();
	}, []);

	const loadConversations = async () => {
		setLoading(true);
		try {
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 1000));

			// Mock data
			const mockConversations = [
				{
					id: 1,
					visitor_name: 'John Smith',
					visitor_email: 'john@example.com',
					status: 'active',
					source: 'Website Widget',
					created_at: '2024-01-15 10:30 AM',
					message_count: 8,
					last_message: 'Thank you for your help! I have one more question about the pricing...',
					tags: ['pricing', 'support'],
					messages: [
						{
							sender: 'visitor',
							content: 'Hi, I need help with pricing information.',
							timestamp: '10:30 AM',
						},
						{
							sender: 'aria',
							content: 'I\'d be happy to help you with pricing information! What specific details would you like to know?',
							timestamp: '10:31 AM',
						},
						{
							sender: 'visitor',
							content: 'What are the different plans available?',
							timestamp: '10:32 AM',
						},
					],
				},
				{
					id: 2,
					visitor_name: 'Sarah Johnson',
					visitor_email: 'sarah@company.com',
					status: 'resolved',
					source: 'Website Widget',
					created_at: '2024-01-14 3:45 PM',
					message_count: 5,
					last_message: 'Perfect, that solved my issue. Thank you!',
					tags: ['technical', 'resolved'],
					messages: [
						{
							sender: 'visitor',
							content: 'I\'m having trouble with the installation process.',
							timestamp: '3:45 PM',
						},
						{
							sender: 'aria',
							content: 'I can help you with that! What step are you currently on?',
							timestamp: '3:46 PM',
						},
					],
				},
				{
					id: 3,
					visitor_name: null,
					visitor_email: null,
					status: 'pending',
					source: 'Website Widget',
					created_at: '2024-01-14 1:20 PM',
					message_count: 2,
					last_message: 'Hello, is anyone there?',
					tags: ['pending'],
					messages: [
						{
							sender: 'visitor',
							content: 'Hello, is anyone there?',
							timestamp: '1:20 PM',
						},
					],
				},
			];

			setConversations(mockConversations);
			setConversationsData({
				totalConversations: mockConversations.length,
				activeConversations: mockConversations.filter(c => c.status === 'active').length,
				avgResponseTime: '2.5m',
				satisfactionRate: '94%',
			});
		} catch (error) {
			console.error('Failed to load conversations:', error);
		} finally {
			setLoading(false);
		}
	};

	const handleViewConversation = (conversation) => {
		setSelectedConversation(conversation);
		setIsDetailModalOpen(true);
	};

	const handleUpdateStatus = async (conversationId, newStatus) => {
		try {
			// Simulate API call
			await new Promise((resolve) => setTimeout(resolve, 500));

			setConversations(prev =>
				prev.map(conv =>
					conv.id === conversationId
						? { ...conv, status: newStatus }
						: conv
				)
			);

			setNotice({
				type: 'success',
				message: __('Conversation status updated successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 3000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to update conversation status.', 'aria'),
			});
		}
	};

	const filteredConversations = conversations.filter((conversation) => {
		const matchesSearch =
			!searchTerm ||
			(conversation.visitor_name &&
				conversation.visitor_name.toLowerCase().includes(searchTerm.toLowerCase())) ||
			(conversation.visitor_email &&
				conversation.visitor_email.toLowerCase().includes(searchTerm.toLowerCase())) ||
			conversation.last_message.toLowerCase().includes(searchTerm.toLowerCase());

		const matchesStatus =
			selectedStatus === 'all' || conversation.status === selectedStatus;

		const matchesSource =
			selectedSource === 'all' || conversation.source.toLowerCase().includes(selectedSource);

		return matchesSearch && matchesStatus && matchesSource;
	});

	if (loading) {
		return (
			<div className="aria-conversations-react" style={{ paddingRight: '32px' }}>
				<PageHeader
					title={__('Conversations', 'aria')}
					description={__('Loading conversations...', 'aria')}
				/>
			</div>
		);
	}

	return (
		<div className="aria-conversations-react" style={{ paddingRight: '32px' }}>
			<PageHeader
				title={__('Conversations', 'aria')}
				description={__('View and manage all conversations with your visitors', 'aria')}
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

			{/* Conversation Metrics */}
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
					icon="💬"
					title={__('Total Conversations', 'aria')}
					value={conversationsData.totalConversations}
					subtitle={__('All Time', 'aria')}
				/>
				<MetricCard
					icon="🟢"
					title={__('Active Conversations', 'aria')}
					value={conversationsData.activeConversations}
					subtitle={__('Ongoing Chats', 'aria')}
					theme="success"
				/>
				<MetricCard
					icon="⏱️"
					title={__('Avg Response Time', 'aria')}
					value={conversationsData.avgResponseTime}
					subtitle={__('AI Response Speed', 'aria')}
					theme="info"
				/>
				<MetricCard
					icon="😊"
					title={__('Satisfaction Rate', 'aria')}
					value={conversationsData.satisfactionRate}
					subtitle={__('Visitor Satisfaction', 'aria')}
					theme="warning"
				/>
			</div>

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
							label={__('Search Conversations', 'aria')}
							value={searchTerm}
							onChange={setSearchTerm}
							placeholder={__('Search by name, email, or message...', 'aria')}
						/>
						<SelectControl
							label={__('Filter by Status', 'aria')}
							value={selectedStatus}
							options={CONVERSATION_STATUSES}
							onChange={setSelectedStatus}
						/>
						<SelectControl
							label={__('Filter by Source', 'aria')}
							value={selectedSource}
							options={CONVERSATION_SOURCES}
							onChange={setSelectedSource}
						/>
					</div>
				</CardBody>
			</Card>

			{/* Conversations List */}
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
						{__('Conversations', 'aria')} ({filteredConversations.length})
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__('Manage and respond to visitor conversations', 'aria')}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					{filteredConversations.length > 0 ? (
						<div>
							{filteredConversations.map((conversation) => (
								<ConversationCard
									key={conversation.id}
									conversation={conversation}
									onView={handleViewConversation}
									onUpdateStatus={handleUpdateStatus}
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
								💬
							</div>
							<div
								style={{
									fontSize: '16px',
									color: '#757575',
									marginBottom: '20px',
								}}
							>
								{searchTerm || selectedStatus !== 'all' || selectedSource !== 'all'
									? __('No conversations match your search criteria.', 'aria')
									: __('No conversations yet. Conversations will appear here when visitors start chatting with Aria.', 'aria')}
							</div>
						</div>
					)}
				</CardBody>
			</Card>

			{/* Conversation Detail Modal */}
			<ConversationDetailModal
				isOpen={isDetailModalOpen}
				onClose={() => {
					setIsDetailModalOpen(false);
					setSelectedConversation(null);
				}}
				conversation={selectedConversation}
			/>
		</div>
	);
};

export default Conversations;