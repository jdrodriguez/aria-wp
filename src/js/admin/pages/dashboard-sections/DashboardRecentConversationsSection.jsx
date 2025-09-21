import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Icon } from '@wordpress/components';
import { SectionCard } from '../../components';
import { formatTimeAgo } from '../../utils/helpers';
import { comment } from '@wordpress/icons';

const getAvatarLetter = (conversation) => {
	if (!conversation?.guest_name) {
		return 'A';
	}

	return conversation.guest_name.charAt(0).toUpperCase();
};

const DashboardRecentConversationsSection = ({ conversations, onSelectConversation, onViewAll, onTestChat }) => {
	if (!conversations.length) {
		return (
			<SectionCard title={__('Recent Conversations', 'aria')}>
					<div className="aria-dashboard__empty">
						<div aria-hidden="true" className="aria-dashboard__empty-icon">
							<Icon icon={comment} size={28} />
						</div>
					<p>{__('No conversations yet. Aria is ready to start chatting with your visitors!', 'aria')}</p>
					<Button variant="primary" onClick={onTestChat}>
						{__('Test Aria', 'aria')}
					</Button>
				</div>
			</SectionCard>
		);
	}

	return (
		<SectionCard
			title={__('Recent Conversations', 'aria')}
			actions={
				<Button variant="secondary" onClick={onViewAll}>
					{__('View All', 'aria')}
				</Button>
			}
		>
			<ul className="aria-dashboard__conversation-list">
				{conversations.map((conversation) => (
					<li key={conversation.id}>
						<button
							type="button"
							className="aria-dashboard__conversation-item"
							onClick={() => onSelectConversation(conversation.id)}
						>
							<div className="aria-dashboard__conversation-main">
								<div className="aria-dashboard__conversation-header">
									<span className="aria-dashboard__conversation-avatar">
										{getAvatarLetter(conversation)}
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
		</SectionCard>
	);
};

DashboardRecentConversationsSection.propTypes = {
	conversations: PropTypes.arrayOf(
		PropTypes.shape({
			id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
			guest_name: PropTypes.string,
			initial_question: PropTypes.string,
			created_at: PropTypes.string,
		})
	).isRequired,
	onSelectConversation: PropTypes.func.isRequired,
	onViewAll: PropTypes.func.isRequired,
	onTestChat: PropTypes.func.isRequired,
};

export default DashboardRecentConversationsSection;
