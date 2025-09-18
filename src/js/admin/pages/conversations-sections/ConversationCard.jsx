import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, SelectControl } from '@wordpress/components';

const statusClassMap = {
	active: 'is-active',
	resolved: 'is-resolved',
	pending: 'is-pending',
	archived: 'is-archived',
};

const ConversationCard = ({
	conversation,
	statusOptions,
	onView,
	onUpdateStatus,
}) => {
	const statusClass = statusClassMap[conversation.status] || 'is-pending';

	return (
		<div className="aria-conversations__item">
			<div className="aria-conversations__item-main">
				<div className="aria-conversations__item-header">
					<h4 className="aria-conversations__item-title">
						{conversation.visitor_name ||
							__('Anonymous Visitor', 'aria')}
					</h4>
					<span
						className={`aria-conversations__item-status ${statusClass}`}
					>
						{conversation.status.charAt(0).toUpperCase() +
							conversation.status.slice(1)}
					</span>
				</div>
				<div className="aria-conversations__item-meta">
					{conversation.visitor_email && (
						<span className="aria-conversations__item-meta-chip">
							📧 {conversation.visitor_email}
						</span>
					)}
					<span className="aria-conversations__item-meta-chip">
						🌐 {conversation.source}
					</span>
					<span className="aria-conversations__item-meta-chip">
						📅 {conversation.created_at}
					</span>
					<span className="aria-conversations__item-meta-chip">
						💬 {conversation.message_count} {__('messages', 'aria')}
					</span>
				</div>
				<p className="aria-conversations__item-preview">
					<strong>{__('Last message:', 'aria')}</strong>{' '}
					{conversation.last_message}
				</p>
				{conversation.tags && conversation.tags.length > 0 && (
					<div className="aria-conversations__item-tags">
						{conversation.tags.map((tag, index) => (
							<span
								key={`${tag}-${index}`}
								className="aria-conversations__item-tag"
							>
								{tag}
							</span>
						))}
					</div>
				)}
			</div>
			<div className="aria-conversations__item-actions">
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
					onChange={(value) => onUpdateStatus(conversation.id, value)}
					options={statusOptions}
				/>
			</div>
		</div>
	);
};

ConversationCard.propTypes = {
	conversation: PropTypes.shape({
		id: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
			.isRequired,
		visitor_name: PropTypes.string,
		visitor_email: PropTypes.string,
		source: PropTypes.string.isRequired,
		created_at: PropTypes.string.isRequired,
		message_count: PropTypes.oneOfType([PropTypes.string, PropTypes.number])
			.isRequired,
		last_message: PropTypes.string.isRequired,
		status: PropTypes.string.isRequired,
		tags: PropTypes.arrayOf(PropTypes.string),
	}).isRequired,
	statusOptions: PropTypes.arrayOf(PropTypes.object).isRequired,
	onView: PropTypes.func.isRequired,
	onUpdateStatus: PropTypes.func.isRequired,
};

export default ConversationCard;
