import { useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import {
	ConversationNotice,
	ConversationMetrics,
	ConversationFilters,
	ConversationList,
	ConversationDetailModal,
	ConversationLoading,
} from './conversations-sections';

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

const Conversations = () => {
	const [metrics, setMetrics] = useState({
		totalConversations: {
			icon: '💬',
			title: __('Total Conversations', 'aria'),
			value: 0,
			subtitle: __('All Time', 'aria'),
			theme: 'primary',
		},
		activeConversations: {
			icon: '🟢',
			title: __('Active Conversations', 'aria'),
			value: 0,
			subtitle: __('Currently Active', 'aria'),
			theme: 'success',
		},
		avgResponseTime: {
			icon: '⏱️',
			title: __('Avg Response Time', 'aria'),
			value: '0m',
			subtitle: __('AI Response Speed', 'aria'),
			theme: 'info',
		},
		satisfactionRate: {
			icon: '😊',
			title: __('Satisfaction Rate', 'aria'),
			value: '0%',
			subtitle: __('Visitor Satisfaction', 'aria'),
			theme: 'warning',
		},
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
			await new Promise((resolve) => setTimeout(resolve, 1000));

			const mockConversations = [
				{
					id: 1,
					visitor_name: 'John Smith',
					visitor_email: 'john@example.com',
					status: 'active',
					source: 'Website Widget',
					created_at: '2024-01-15 10:30 AM',
					message_count: 8,
					last_message:
						'Thank you for your help! I have one more question about the pricing…',
					tags: ['pricing', 'support'],
					messages: [
						{
							sender: 'visitor',
							content:
								'Hi, I need help with pricing information.',
							timestamp: '10:30 AM',
						},
						{
							sender: 'aria',
							content:
								"I'd be happy to help you with pricing information! What specific details would you like to know?",
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
							content:
								"I'm having trouble with the installation process.",
							timestamp: '3:45 PM',
						},
						{
							sender: 'aria',
							content:
								'I can help you with that! What step are you currently on?',
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
			setMetrics({
				totalConversations: {
					icon: '💬',
					title: __('Total Conversations', 'aria'),
					value: mockConversations.length,
					subtitle: __('All Time', 'aria'),
					theme: 'primary',
				},
				activeConversations: {
					icon: '🟢',
					title: __('Active Conversations', 'aria'),
					value: mockConversations.filter(
						(c) => c.status === 'active'
					).length,
					subtitle: __('Currently Active', 'aria'),
					theme: 'success',
				},
				avgResponseTime: {
					icon: '⏱️',
					title: __('Avg Response Time', 'aria'),
					value: '2.5m',
					subtitle: __('AI Response Speed', 'aria'),
					theme: 'info',
				},
				satisfactionRate: {
					icon: '😊',
					title: __('Satisfaction Rate', 'aria'),
					value: '94%',
					subtitle: __('Visitor Satisfaction', 'aria'),
					theme: 'warning',
				},
			});
		} catch (error) {
			// eslint-disable-next-line no-console
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
			await new Promise((resolve) => setTimeout(resolve, 500));

			setConversations((prev) =>
				prev.map((conversation) =>
					conversation.id === conversationId
						? { ...conversation, status: newStatus }
						: conversation
				)
			);

			setNotice({
				type: 'success',
				message: __(
					'Conversation status updated successfully!',
					'aria'
				),
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
		const normalizedSearch = searchTerm.toLowerCase();
		const matchesSearch =
			!normalizedSearch ||
			(conversation.visitor_name &&
				conversation.visitor_name
					.toLowerCase()
					.includes(normalizedSearch)) ||
			(conversation.visitor_email &&
				conversation.visitor_email
					.toLowerCase()
					.includes(normalizedSearch)) ||
			conversation.last_message.toLowerCase().includes(normalizedSearch);

		const matchesStatus =
			selectedStatus === 'all' || conversation.status === selectedStatus;

		const matchesSource =
			selectedSource === 'all' ||
			conversation.source.toLowerCase().includes(selectedSource);

		return matchesSearch && matchesStatus && matchesSource;
	});

	if (loading) {
		return <ConversationLoading />;
	}

	const hasFiltersApplied =
		Boolean(searchTerm.trim()) ||
		selectedStatus !== 'all' ||
		selectedSource !== 'all';

	const statusOptionsForCards = CONVERSATION_STATUSES.filter(
		(option) => option.value !== 'all'
	);

	return (
		<PageShell
			className="aria-conversations aria-conversations-react"
			width="wide"
		>
			<PageHeader
				title={__('Conversations', 'aria')}
				description={__(
					'Monitor visitor interactions, follow up on active threads, and keep statuses current.',
					'aria'
				)}
			/>

			<ConversationNotice
				notice={notice}
				onRemove={() => setNotice(null)}
			/>

			<div className="aria-stack-lg">
				<ConversationMetrics metrics={metrics} />
				<ConversationFilters
					searchValue={searchTerm}
					onSearchChange={setSearchTerm}
					statusValue={selectedStatus}
					onStatusChange={setSelectedStatus}
					statusOptions={CONVERSATION_STATUSES}
					sourceValue={selectedSource}
					onSourceChange={setSelectedSource}
					sourceOptions={CONVERSATION_SOURCES}
				/>
				<ConversationList
					conversations={filteredConversations}
					statusOptions={statusOptionsForCards}
					onView={handleViewConversation}
					onUpdateStatus={handleUpdateStatus}
					count={filteredConversations.length}
					hasFiltersApplied={hasFiltersApplied}
				/>
			</div>

			<ConversationDetailModal
				isOpen={isDetailModalOpen}
				onClose={() => {
					setIsDetailModalOpen(false);
					setSelectedConversation(null);
				}}
				conversation={selectedConversation}
			/>
		</PageShell>
	);
};

export default Conversations;
