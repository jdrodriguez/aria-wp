import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { PageShell, SectionCard } from '../../components';

const KnowledgeEntryLoading = () => (
	<PageShell
		className="aria-knowledge-entry aria-knowledge-entry-react"
		width="wide"
	>
		<SectionCard>
			<div className="aria-knowledge-entry__loading">
				<Spinner className="aria-knowledge-entry__loading-spinner" />
				<p>{__('Loading knowledge entry…', 'aria')}</p>
			</div>
		</SectionCard>
	</PageShell>
);

export default KnowledgeEntryLoading;
