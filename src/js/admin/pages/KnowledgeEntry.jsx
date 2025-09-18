import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageShell } from '../components';
import { fetchKnowledgeData, saveKnowledgeEntry } from '../utils/api';
import {
	HeaderNotice,
	AiGenerationWizard,
	ManualEditor,
	ActionFooter,
	KnowledgeEntryLoading,
} from './knowledge-entry-sections';
import { useKnowledgeGenerator } from './knowledge-entry/hooks';

const createDefaultForm = () => ({
	title: '',
	content: '',
	category: 'general',
	tags: '',
	context: '',
	response_instructions: '',
	language: 'en',
	isActive: true,
});

const formatEntryForForm = (entry) => ({
	title: entry.title || '',
	content: entry.content || '',
	category: entry.category || 'general',
	tags: Array.isArray(entry.tags) ? entry.tags.join(', ') : entry.tags || '',
	context: entry.context || '',
	response_instructions: entry.response_instructions || '',
	language: entry.language || 'en',
	isActive: entry.isActive !== false,
});

const KnowledgeEntry = () => {
	const rootElement = document.getElementById('aria-knowledge-entry-root');
	const entryConfig = useMemo(() => {
		const dataset = rootElement ? { ...rootElement.dataset } : {};
		const fallback = window.ariaKnowledgeEntry || {};

		const resolve = (key, defaultValue = '') => {
			if (dataset && typeof dataset[key] !== 'undefined') {
				return dataset[key];
			}
			if (typeof fallback[key] !== 'undefined') {
				return fallback[key];
			}
			return defaultValue;
		};

		const resolvedAction = resolve('action', 'add');
		const resolvedEntryId = parseInt(resolve('entryId', '0'), 10) || 0;
		const ajaxUrl =
			resolve('ajaxUrl') || window.ariaAdmin?.ajaxUrl || '/wp-admin/admin-ajax.php';
		const returnUrl =
			resolve('returnUrl') || `${window.ariaAdmin?.adminUrl || '/wp-admin/'}admin.php?page=aria-knowledge`;
		const generateNonce = resolve('generateNonce');

		return {
			action: resolvedAction,
			entryId: resolvedEntryId,
			ajaxUrl,
			returnUrl,
			generateNonce,
		};
	}, [rootElement]);

	const { action, entryId, ajaxUrl, returnUrl, generateNonce } = entryConfig;
	const isEdit = action === 'edit';

	const [formData, setFormData] = useState(createDefaultForm());
	const [loading, setLoading] = useState(isEdit && entryId > 0);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const categories = useMemo(
		() => [
			{ label: __('General Information', 'aria'), value: 'general' },
			{ label: __('Products & Services', 'aria'), value: 'products' },
			{
				label: __('Support & Troubleshooting', 'aria'),
				value: 'support',
			},
			{ label: __('Company Information', 'aria'), value: 'company' },
			{ label: __('Policies & Terms', 'aria'), value: 'policies' },
		],
		[]
	);

	const {
		generationStep,
		setGenerationStep,
		rawContent,
		setRawContent,
		generating,
		startGeneration,
		switchToManual,
		startOver,
	} = useKnowledgeGenerator({
		action,
		ajaxUrl,
		generateNonce,
		onPopulateForm: (generated) => {
			setFormData((prev) => ({
				...prev,
				title: generated.title || prev.title,
				content: generated.content || prev.content,
				category: generated.category || prev.category,
				tags: Array.isArray(generated.tags)
					? generated.tags.join(', ')
					: generated.tags || prev.tags,
				context: generated.context || prev.context,
				response_instructions:
					generated.response_instructions ||
					prev.response_instructions,
				language: generated.language || prev.language,
			}));
		},
		onResetForm: () => setFormData(createDefaultForm()),
		setNotice,
	});

	useEffect(() => {
		if (isEdit && entryId > 0) {
			loadEntryData();
		} else {
			setGenerationStep('input');
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [isEdit, entryId]);

	const loadEntryData = async () => {
		setLoading(true);
		try {
			const data = await fetchKnowledgeData();
			const entry = data.entries?.find((item) => item.id === entryId);

			if (!entry) {
				setNotice({
					type: 'error',
					message: __('Knowledge entry not found.', 'aria'),
				});
				setLoading(false);
				return;
			}

			setFormData(formatEntryForForm(entry));
			setGenerationStep('manual');
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Failed to load entry:', error);
			setNotice({
				type: 'error',
				message: __('Failed to load knowledge entry.', 'aria'),
			});
		} finally {
			setLoading(false);
		}
	};

	const handleFieldChange = (key, value) => {
		setFormData((prev) => ({ ...prev, [key]: value }));
		setNotice(null);
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
			const payload = {
				...formData,
				tags:
					formData.tags && typeof formData.tags === 'string'
						? formData.tags
								.split(',')
								.map((tag) => tag.trim())
								.filter((tag) => tag.length > 0)
						: [],
			};

			await saveKnowledgeEntry(payload, isEdit ? entryId : null);

			const successMessage = isEdit
				? __('Knowledge entry updated successfully!', 'aria')
				: __('Knowledge entry created successfully!', 'aria');
			const redirectUrl = `${returnUrl}&message=${encodeURIComponent(successMessage)}`;
			window.location.href = redirectUrl;
		} catch (error) {
			// eslint-disable-next-line no-console
			console.error('Save error:', error);
			setNotice({
				type: 'error',
				message:
					error.message ||
					__('Failed to save knowledge entry.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const handleCancel = () => {
		window.location.href = returnUrl;
	};

	const handleSwitchToManual = () => {
		switchToManual();
		setNotice(null);
	};

	const handleUseAiAssistant = () => {
		startOver();
	};

	if (loading) {
		return <KnowledgeEntryLoading />;
	}

	const showManualEditor =
		generationStep === 'manual' || generationStep === 'review';
	const showActionFooter =
		generationStep === 'manual' || generationStep === 'review';
	const disableSave =
		saving || !formData.title.trim() || !formData.content.trim();

	return (
		<PageShell
			className="aria-knowledge-entry aria-knowledge-entry-react"
			width="wide"
		>
			<HeaderNotice
				action={action}
				notice={notice}
				onRemove={() => setNotice(null)}
			/>

			<div className="aria-stack-lg">
				<AiGenerationWizard
					step={generationStep}
					rawContent={rawContent}
					onRawContentChange={setRawContent}
					onGenerate={startGeneration}
					onCancel={handleCancel}
					onSwitchToManual={handleSwitchToManual}
					generating={generating}
				/>

				<ManualEditor
					visible={showManualEditor}
					formData={formData}
					onFieldChange={handleFieldChange}
					categories={categories}
					generationStep={generationStep}
				/>
			</div>

			<ActionFooter
				visible={showActionFooter}
				generationStep={generationStep}
				action={action}
				onCancel={handleCancel}
				onStartOver={startOver}
				onUseAi={handleUseAiAssistant}
				onSave={handleSave}
				saving={saving}
				disableSave={disableSave}
			/>
		</PageShell>
	);
};

export default KnowledgeEntry;
