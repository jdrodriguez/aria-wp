import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { SectionCard, ResultsSummary } from '../../components';
import { Icon } from '@wordpress/components';
import { comment } from '@wordpress/icons';
import ConversationCard from './ConversationCard.jsx';

const ConversationList = ({
	conversations,
	statusOptions,
	onView,
	onUpdateStatus,
	totalCount,
	filteredCount,
	hasFiltersApplied,
}) => {
	const description = __(
		'Review recent visitor conversations, update their status, or dive deeper for context.',
		'aria'
	);

	return (
		<SectionCard
			title={__('Conversations', 'aria')}
			description={description}
		>
			{totalCount > 0 && (
				<ResultsSummary
					totalCount={totalCount}
					filteredCount={filteredCount}
					isFiltered={hasFiltersApplied}
					label={__('conversations', 'aria')}
				/>
			)}
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
					<div
						role="presentation"
						aria-hidden="true"
						className="aria-conversations__empty-icon"
					>
						<Icon icon={comment} size={40} />
					</div>
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
	totalCount: PropTypes.number.isRequired,
	filteredCount: PropTypes.number.isRequired,
	hasFiltersApplied: PropTypes.bool.isRequired,
};

export default ConversationList;
