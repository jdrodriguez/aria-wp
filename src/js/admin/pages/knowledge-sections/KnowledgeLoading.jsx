import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { PageHeader, PageShell, SectionCard } from '../../components';

const KnowledgeLoading = () => (
	<PageShell className="aria-knowledge aria-knowledge-react" width="wide">
		<PageHeader
			title={__('Knowledge Base', 'aria')}
			description={__('Loading knowledge base…', 'aria')}
		/>
		<SectionCard>
			<div className="aria-knowledge__empty">
				<Spinner />
				<p>{__('Fetching articles…', 'aria')}</p>
			</div>
		</SectionCard>
	</PageShell>
);

export default KnowledgeLoading;
