import { __ } from '@wordpress/i18n';
import { Spinner } from '@wordpress/components';
import { PageHeader, PageShell, SectionCard } from '../../components';

const ContentIndexingLoading = () => (
	<PageShell
		className="aria-content-indexing aria-content-indexing-react"
		width="wide"
	>
		<PageHeader
			title={__('Content Indexing', 'aria')}
			description={__('Loading content indexing data…', 'aria')}
		/>
		<SectionCard>
			<div className="aria-content-indexing__empty">
				<Spinner />
				<p>{__('Preparing the latest indexing status…', 'aria')}</p>
			</div>
		</SectionCard>
	</PageShell>
);

export default ContentIndexingLoading;
