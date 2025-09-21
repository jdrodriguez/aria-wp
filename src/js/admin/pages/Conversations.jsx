import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import { makeAjaxRequest } from '../utils/api';
import {
	ConversationNotice,
	ConversationMetrics,
	ConversationFilters,
	ConversationStatusPills,
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
			icon: 'chat',
			title: __('Total Conversations', 'aria'),
			value: 0,
			subtitle: __('All Time', 'aria'),
			theme: 'primary',
		},
		activeConversations: {
			icon: 'activity',
			title: __('Active Conversations', 'aria'),
			value: 0,
			subtitle: __('Currently Active', 'aria'),
			theme: 'success',
		},
		avgResponseTime: {
			icon: 'clock',
			title: __('Avg Response Time', 'aria'),
			value: '—',
			subtitle: __('AI Response Speed', 'aria'),
			theme: 'info',
		},
		satisfactionRate: {
			icon: 'smile',
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
			const data = await makeAjaxRequest('aria_get_conversations_data', {
				limit: 50,
			});

			const metricsPayload = data?.metrics ?? {};
			setMetrics((prev) => ({
				totalConversations: {
					...prev.totalConversations,
					value: metricsPayload.totalConversations ?? 0,
				},
				activeConversations: {
					...prev.activeConversations,
					value: metricsPayload.activeConversations ?? 0,
				},
				avgResponseTime: {
					...prev.avgResponseTime,
					value: metricsPayload.avgResponseTime || '—',
				},
				satisfactionRate: {
					...prev.satisfactionRate,
					value:
						typeof metricsPayload.satisfactionRate === 'number'
							? `${metricsPayload.satisfactionRate}%`
						: metricsPayload.satisfactionRate || '0%',
				},
			}));

			const conversationList = Array.isArray(data?.conversations)
				? data.conversations.map((conversation) => ({
						...conversation,
						source: conversation.source || __('Website Widget', 'aria'),
						status: (conversation.status || 'pending').toLowerCase(),
						tags: Array.isArray(conversation.tags) ? conversation.tags : [],
						messages: Array.isArray(conversation.messages) ? conversation.messages : [],
				  }))
				: [];

			setConversations(conversationList);
		} catch (error) {
			setNotice({
				type: 'error',
				message: error?.message || __('Failed to load conversations.', 'aria'),
			});
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
			const response = await makeAjaxRequest(
				'aria_update_conversation_status',
				{
					conversation_id: conversationId,
					status: newStatus,
				}
			);

			setConversations((prev) =>
				prev.map((conversation) =>
					conversation.id === conversationId
						? { ...conversation, status: newStatus }
						: conversation
				)
			);

			setSelectedConversation((prev) =>
				prev && prev.id === conversationId
					? { ...prev, status: newStatus }
					: prev
			);

			setNotice({
				type: 'success',
				message:
					response?.message ||
					__('Conversation status updated successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 3000);
		} catch (error) {
			setNotice({
				type: 'error',
				message:
					error?.message ||
					__('Failed to update conversation status.', 'aria'),
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

		const sourceValue = (conversation.source || '').toLowerCase();
		const matchesSource =
			selectedSource === 'all' || sourceValue.includes(selectedSource);

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

	const statusPillOptions = useMemo(() => {
		const counts = conversations.reduce((acc, conversation) => {
			const statusKey = conversation.status || 'pending';
			acc[statusKey] = (acc[statusKey] || 0) + 1;
			return acc;
		}, {});

		return CONVERSATION_STATUSES.map((status) => ({
			...status,
			count:
				status.value === 'all'
					? conversations.length
					: counts[status.value] || 0,
		})).filter((status) => status.value === 'all' || status.count > 0);
	}, [conversations]);

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
				<ConversationStatusPills
					statuses={statusPillOptions}
					activeValue={selectedStatus}
					onSelect={setSelectedStatus}
				/>
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
					totalCount={conversations.length}
					filteredCount={filteredConversations.length}
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
