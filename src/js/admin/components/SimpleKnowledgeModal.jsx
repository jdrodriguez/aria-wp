import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Modal,
	Button,
	TextControl,
	TextareaControl,
	SelectControl,
	Notice,
	Flex,
} from '@wordpress/components';
import PropTypes from 'prop-types';

/**
 * Simple Knowledge Entry Modal - Lightweight replacement for AIKnowledgeGenerator
 */
const SimpleKnowledgeModal = ({ isOpen, onClose, entry = null, onSave }) => {
	const [formData, setFormData] = useState({
		title: entry?.title || '',
		content: entry?.content || '',
		category: entry?.category || 'general',
		tags: entry?.tags ? entry.tags.join(', ') : '',
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const KNOWLEDGE_CATEGORIES = [
		{ label: __('General Information', 'aria'), value: 'general' },
		{ label: __('Products & Services', 'aria'), value: 'products' },
		{ label: __('Support & Troubleshooting', 'aria'), value: 'support' },
		{ label: __('Company Information', 'aria'), value: 'company' },
		{ label: __('Policies & Terms', 'aria'), value: 'policies' },
	];

	const updateField = (key, value) => {
		setFormData((prev) => ({ ...prev, [key]: value }));
	};

	const handleSave = async () => {
		if (!formData.title.trim() || !formData.content.trim()) {
			setNotice({
				type: 'error',
				message: __('Title and content are required.', 'aria'),
			});
			return;
		}

		setSaving(true);
		setNotice(null);

		try {
			const entryData = {
				...formData,
				tags: formData.tags
					.split(',')
					.map((tag) => tag.trim())
					.filter((tag) => tag.length > 0),
			};

			await onSave(entryData);
			onClose();
		} catch (error) {
			console.error('Save error:', error);
			setNotice({
				type: 'error',
				message: error.message || __('Failed to save knowledge entry.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	if (!isOpen) return null;

	return (
		<Modal
			title={entry ? __('Edit Knowledge Entry', 'aria') : __('Add Knowledge Entry', 'aria')}
			onRequestClose={onClose}
			style={{ maxWidth: '600px', width: '90vw' }}
		>
			<div style={{ padding: '16px 0' }}>
				{notice && (
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
						style={{ marginBottom: '24px' }}
					>
						{notice.message}
					</Notice>
				)}

				<div style={{ display: 'grid', gap: '20px' }}>
					<TextControl
						label={__('Title', 'aria')}
						value={formData.title}
						onChange={(value) => updateField('title', value)}
						placeholder={__('Enter a descriptive title', 'aria')}
					/>

					<SelectControl
						label={__('Category', 'aria')}
						value={formData.category}
						options={KNOWLEDGE_CATEGORIES}
						onChange={(value) => updateField('category', value)}
					/>

					<TextareaControl
						label={__('Content', 'aria')}
						value={formData.content}
						onChange={(value) => updateField('content', value)}
						rows={8}
						placeholder={__('Enter the knowledge content...', 'aria')}
					/>

					<TextControl
						label={__('Tags', 'aria')}
						value={formData.tags}
						onChange={(value) => updateField('tags', value)}
						placeholder={__('Enter keywords separated by commas', 'aria')}
						help={__('Tags help organize and find knowledge entries', 'aria')}
					/>
				</div>

				<div style={{ marginTop: '24px' }}>
					<Flex justify="flex-end" gap="12px">
						<Button variant="secondary" onClick={onClose}>
							{__('Cancel', 'aria')}
						</Button>
						<Button
							variant="primary"
							onClick={handleSave}
							disabled={saving || !formData.title.trim() || !formData.content.trim()}
							isBusy={saving}
						>
							{saving
								? __('Saving…', 'aria')
								: entry
								? __('Update Entry', 'aria')
								: __('Save Entry', 'aria')}
						</Button>
					</Flex>
				</div>
			</div>
		</Modal>
	);
};

SimpleKnowledgeModal.propTypes = {
	isOpen: PropTypes.bool.isRequired,
	onClose: PropTypes.func.isRequired,
	entry: PropTypes.object,
	onSave: PropTypes.func.isRequired,
};

export default SimpleKnowledgeModal;