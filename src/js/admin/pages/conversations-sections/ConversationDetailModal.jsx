import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Modal } from '@wordpress/components';

const ConversationDetailModal = ({ isOpen, onClose, conversation }) => {
	if (!isOpen || !conversation) {
		return null;
	}

	return (
		<Modal
			title={__('Conversation Details', 'aria')}
			onRequestClose={onClose}
			className="aria-conversations__modal-wrapper"
		>
			<div className="aria-conversations__modal">
				<header className="aria-conversations__modal-header">
					<div>
						<h4 className="aria-conversations__modal-title">
							{conversation.visitor_name ||
								__('Anonymous Visitor', 'aria')}
						</h4>
						{conversation.visitor_email && (
							<p className="aria-conversations__modal-subtitle">
								{conversation.visitor_email}
							</p>
						)}
					</div>
					<span
						className={`aria-conversations__modal-status is-${conversation.status}`}
					>
						{conversation.status.charAt(0).toUpperCase() +
							conversation.status.slice(1)}
					</span>
				</header>

				<div className="aria-conversations__modal-meta">
					<span>
						{__('Started:', 'aria')} {conversation.created_at}
					</span>
					<span>
						{__('Messages:', 'aria')} {conversation.message_count}
					</span>
					<span>
						{__('Source:', 'aria')} {conversation.source}
					</span>
				</div>

				<div className="aria-conversations__modal-messages">
					{conversation.messages.map((message, index) => (
						<div
							key={`${message.timestamp}-${index}`}
							className={`aria-conversations__message aria-conversations__message--${message.sender}`}
						>
							<div className="aria-conversations__message-bubble">
								<p>{message.content}</p>
								<span className="aria-conversations__message-meta">
									{message.timestamp}
								</span>
							</div>
						</div>
					))}
				</div>
			</div>
		</Modal>
	);
};

ConversationDetailModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	conversation: PropTypes.shape({
		visitor_name: PropTypes.string,
		visitor_email: PropTypes.string,
		status: PropTypes.string.isRequired,
		source: PropTypes.string.isRequired,
		created_at: PropTypes.string.isRequired,
		message_count: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
			.isRequired,
		messages: PropTypes.arrayOf(
			PropTypes.shape({
				sender: PropTypes.string.isRequired,
				content: PropTypes.string.isRequired,
				timestamp: PropTypes.string.isRequired,
			})
		).isRequired,
	}).isRequired,
};

export default ConversationDetailModal;
