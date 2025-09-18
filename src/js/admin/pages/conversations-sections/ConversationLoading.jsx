import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { PageHeader, PageShell, SectionCard } from '../../components';

const ConversationLoading = () => (
	<PageShell
		className="aria-conversations aria-conversations-react"
		width="wide"
	>
		<PageHeader
			title={__('Conversations', 'aria')}
			description={__('Loading conversations…', 'aria')}
		/>
		<SectionCard>
			<div className="aria-conversations__empty">
				<Spinner />
				<p>{__('Fetching recent conversations…', 'aria')}</p>
			</div>
		</SectionCard>
	</PageShell>
);

export default ConversationLoading;
