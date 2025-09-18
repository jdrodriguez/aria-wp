import PropTypes from 'prop-types';
import { __, sprintf } from '@wordpress/i18n';
import { SectionCard } from '../../components';
import ConversationCard from './ConversationCard.jsx';

const ConversationList = ({
	conversations,
	statusOptions,
	onView,
	onUpdateStatus,
	count,
	hasFiltersApplied,
}) => {
	const description = __(
		'Review recent visitor conversations, update their status, or dive deeper for context.',
		'aria'
	);
	const formattedDescription = sprintf(
		/* translators: 1: Base description text, 2: conversation count */
		__('%1$s (%2$s)', 'aria'),
		description,
		count
	);

	return (
		<SectionCard
			title={__('Conversations', 'aria')}
			description={formattedDescription}
		>
			{conversations.length > 0 ? (
				<div className="aria-conversations__list">
					{conversations.map((conversation) => (
						<ConversationCard
							key={conversation.id}
							conversation={conversation}
							statusOptions={statusOptions}
							onView={onView}
							onUpdateStatus={onUpdateStatus}
						/>
					))}
				</div>
			) : (
				<div className="aria-conversations__empty">
					<span
						role="img"
						aria-hidden="true"
						className="aria-conversations__empty-icon"
					>
						💬
					</span>
					<p>
						{hasFiltersApplied
							? __(
									'No conversations match your filters. Try adjusting your search or status selections.',
									'aria'
								)
							: __(
									'No conversations yet. Conversations will appear here once visitors start chatting.',
									'aria'
								)}
					</p>
				</div>
			)}
		</SectionCard>
	);
};

ConversationList.propTypes = {
	conversations: PropTypes.arrayOf(PropTypes.object).isRequired,
	statusOptions: PropTypes.arrayOf(PropTypes.object).isRequired,
	onView: PropTypes.func.isRequired,
	onUpdateStatus: PropTypes.func.isRequired,
	count: PropTypes.number.isRequired,
	hasFiltersApplied: PropTypes.bool.isRequired,
};

export default ConversationList;
