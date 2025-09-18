import PropTypes from 'prop-types';
import { __ } from '@wordpress/i18n';
import { Button, Modal, ProgressBar } from '@wordpress/components';

const ContentIndexingProgressModal = ({ isOpen, onClose, progress }) => {
	if (!isOpen) {
		return null;
	}

	return (
		<Modal
			title={__('Content Indexing Progress', 'aria')}
			onRequestClose={onClose}
			className="aria-content-indexing__modal-wrapper"
		>
			<div className="aria-content-indexing__modal">
				<div className="aria-content-indexing__progress">
					<p className="aria-content-indexing__progress-label">
						{__('Indexing your content for AI reference…', 'aria')}
					</p>
					<ProgressBar value={progress.percentage} />
					<div className="aria-content-indexing__progress-meta">
						<span>
							{progress.processed} / {progress.total}{' '}
							{__('items processed', 'aria')}
						</span>
						<span>{progress.percentage}%</span>
					</div>
				</div>

				<div className="aria-content-indexing__progress-current">
					<h4>{__('Current item', 'aria')}</h4>
					<p>{progress.currentItem}</p>
				</div>

				<div className="aria-content-indexing__progress-actions">
					<Button variant="secondary" onClick={onClose}>
						{__('Run in background', 'aria')}
					</Button>
				</div>
			</div>
		</Modal>
	);
};

ContentIndexingProgressModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	progress: PropTypes.shape({
		percentage: PropTypes.number.isRequired,
		processed: PropTypes.number.isRequired,
		total: PropTypes.number.isRequired,
		currentItem: PropTypes.string.isRequired,
	}).isRequired,
};

export default ContentIndexingProgressModal;
