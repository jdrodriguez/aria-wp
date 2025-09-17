import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Button,
	TextControl,
	TextareaControl,
	SelectControl,
	Notice,
	Flex,
	FlexItem,
	Panel,
	PanelBody,
	PanelHeader,
	Spinner,
} from '@wordpress/components';
import PropTypes from 'prop-types';
import { fetchKnowledgeData, saveKnowledgeEntry } from '../utils/api';

/**
 * SVG Magic Wand Icon for AI Generation
 */
const MagicWandIcon = () => (
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<path d="M15 4V2m0 16v-2m8-6h-2M1 10h2m12.12-6.12L19 2m-7.88 19.88L13 20M6.12 6.12L5 5m13.88 13.88L20 20"/>
		<circle cx="12" cy="12" r="3"/>
	</svg>
);

/**
 * SVG Sparkles Icon
 */
const SparklesIcon = () => (
	<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
		<path d="M6 9l6-6 6 6M12 3v18"/>
		<path d="M6 15l6 6 6-6"/>
	</svg>
);

/**
 * Knowledge Entry Add/Edit Page Component
 */
const KnowledgeEntry = () => {
	// Get data from DOM attributes with null safety
	const rootElement = document.getElementById('aria-knowledge-entry-root');
	const action = rootElement?.getAttribute('data-action') || 'add';
	const entryIdString = rootElement?.getAttribute('data-entry-id') || '0';
	const entryId = entryIdString ? parseInt(entryIdString, 10) : 0;
	const returnUrl = rootElement?.getAttribute('data-return-url') || 'admin.php?page=aria-knowledge';

	// Form state
	const [formData, setFormData] = useState({
		title: '',
		content: '',
		category: 'general',
		tags: '',
		context: '',
		response_instructions: '',
		language: 'en',
		isActive: true,
	});

	// UI state
	const [loading, setLoading] = useState(action === 'edit' && entryId > 0);
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);
	const [generationStep, setGenerationStep] = useState('input'); // 'input', 'generating', 'review', 'manual'
	const [generating, setGenerating] = useState(false);
	const [rawContent, setRawContent] = useState('');

	// Knowledge categories
	const KNOWLEDGE_CATEGORIES = [
		{ label: __('General Information', 'aria'), value: 'general' },
		{ label: __('Products & Services', 'aria'), value: 'products' },
		{ label: __('Support & Troubleshooting', 'aria'), value: 'support' },
		{ label: __('Company Information', 'aria'), value: 'company' },
		{ label: __('Policies & Terms', 'aria'), value: 'policies' },
	];

	// Load entry data for editing
	useEffect(() => {
		if (action === 'edit' && entryId > 0) {
			loadEntryData();
			setGenerationStep('manual'); // Skip AI workflow for editing
		} else {
			// For new entries, start with AI input step (unless user prefers manual)
			setGenerationStep('input');
		}
	}, [action, entryId]);

	const loadEntryData = async () => {
		setLoading(true);
		try {
			const data = await fetchKnowledgeData();
			const entry = data.entries?.find(e => e.id === entryId);
			
			if (entry) {
				setFormData({
					title: entry.title || '',
					content: entry.content || '',
					category: entry.category || 'general',
					tags: entry.tags && typeof entry.tags === 'string' 
						? entry.tags.split(',').map(tag => tag.trim()).join(', ') 
						: '',
					context: entry.context || '',
					response_instructions: entry.response_instructions || '',
					language: entry.language || 'en',
					isActive: entry.isActive !== false,
				});
			} else {
				setNotice({
					type: 'error',
					message: __('Knowledge entry not found.', 'aria'),
				});
			}
		} catch (error) {
			console.error('Failed to load entry:', error);
			setNotice({
				type: 'error',
				message: __('Failed to load knowledge entry.', 'aria'),
			});
		} finally {
			setLoading(false);
		}
	};

	// Update form field
	const updateField = (key, value) => {
		setFormData((prev) => ({ ...prev, [key]: value }));
		// Clear notice when user starts editing
		if (notice) {
			setNotice(null);
		}
	};

	// Handle AI generation with multi-step workflow
	const handleAIGeneration = async () => {
		if (!rawContent.trim()) {
			setNotice({
				type: 'error',
				message: __('Please enter some content to generate from.', 'aria'),
			});
			return;
		}

		setGenerating(true);
		setGenerationStep('generating');
		setNotice(null);

		try {
			const generateNonce = rootElement?.getAttribute('data-generate-nonce') || '';
			const ajaxUrl = rootElement?.getAttribute('data-ajax-url') || '';

			if (!generateNonce) {
				throw new Error(__('Generation nonce not available', 'aria'));
			}

			const response = await fetch(ajaxUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded',
				},
				body: new URLSearchParams({
					action: 'aria_generate_knowledge_entry',
					content: rawContent,
					nonce: generateNonce,
				}),
			});

			const result = await response.json();

			if (result.success && result.data) {
				// Populate form with generated data
				setFormData(prev => ({
					...prev,
					title: result.data.title || prev.title,
					content: result.data.content || prev.content,
					category: result.data.category || prev.category,
					tags: result.data.tags || prev.tags,
					context: result.data.context || prev.context,
					response_instructions: result.data.response_instructions || prev.response_instructions,
					language: result.data.language || prev.language,
				}));
				
				// Move to review step
				setGenerationStep('review');
				setNotice({
					type: 'success',
					message: __('Content generated successfully! Review and edit as needed.', 'aria'),
				});
			} else {
				throw new Error(result.data?.message || __('Generation failed', 'aria'));
			}
		} catch (error) {
			console.error('AI Generation Error:', error);
			setNotice({
				type: 'error',
				message: error.message || __('Failed to generate content. Please try again.', 'aria'),
			});
			setGenerationStep('input');
		} finally {
			setGenerating(false);
		}
	};

	// Switch to manual entry mode
	const switchToManualEntry = () => {
		setGenerationStep('manual');
		setNotice(null);
	};

	// Start over with AI generation
	const startOver = () => {
		setGenerationStep('input');
		setRawContent('');
		setFormData({
			title: '',
			content: '',
			category: 'general',
			tags: '',
			context: '',
			response_instructions: '',
			language: 'en',
			isActive: true,
		});
		setNotice(null);
	};

	// Save the knowledge entry
	const handleSave = async () => {
		// Validate required fields
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
				tags: formData.tags && typeof formData.tags === 'string'
					? formData.tags
						.split(',')
						.map((tag) => tag.trim())
						.filter((tag) => tag.length > 0)
					: [],
			};

			await saveKnowledgeEntry(entryData, action === 'edit' ? entryId : null);
			
			// Redirect back to knowledge page with success message
			const successMessage = action === 'edit' 
				? __('Knowledge entry updated successfully!', 'aria')
				: __('Knowledge entry created successfully!', 'aria');
			
			const redirectUrl = returnUrl + '&message=' + encodeURIComponent(successMessage);
			window.location.href = redirectUrl;
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

	// Cancel and return to knowledge page
	const handleCancel = () => {
		window.location.href = returnUrl;
	};

	if (loading) {
		return (
			<div style={{ padding: '40px', textAlign: 'center' }}>
				<Spinner style={{ width: '40px', height: '40px' }} />
				<p style={{ marginTop: '16px' }}>
					{__('Loading knowledge entry...', 'aria')}
				</p>
			</div>
		);
	}

	return (
		<div className="aria-knowledge-entry-react" style={{ maxWidth: '800px' }}>
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

			{/* Step 1: Raw Content Input */}
			{generationStep === 'input' && (
				<>
					<Card>
						<CardHeader>
							<Flex align="center" gap="12px">
								<SparklesIcon />
								<h3 style={{ margin: 0 }}>
									{__('AI-Powered Knowledge Generation', 'aria')}
								</h3>
							</Flex>
						</CardHeader>
						<CardBody>
							<p style={{ marginBottom: '20px', color: '#666' }}>
								{__(
									'Paste any raw content (emails, documents, notes, FAQs) and let Aria\'s AI structure it into a comprehensive knowledge entry.',
									'aria'
								)}
							</p>
							
							<TextareaControl
								label={__('Raw Content', 'aria')}
								value={rawContent}
								onChange={setRawContent}
								rows={12}
								placeholder={__(
									'Paste your raw content here... For example:\n\n- Customer support emails\n- Product documentation\n- FAQ responses\n- Meeting notes\n- Policy documents\n\nThe AI will analyze this content and create a structured knowledge entry with appropriate categories, tags, and formatting.',
									'aria'
								)}
								help={__(
									'The more context you provide, the better the AI can structure your knowledge entry.',
									'aria'
								)}
							/>
						</CardBody>
					</Card>

					<div style={{ 
						height: '1px', 
						background: '#e2e8f0', 
						margin: '24px 0' 
					}} />

					<Flex justify="space-between" align="center">
						<Button variant="tertiary" onClick={switchToManualEntry}>
							{__('Create Manually Instead', 'aria')}
						</Button>
						
						<Flex gap="12px">
							<Button variant="secondary" onClick={handleCancel}>
								{__('Cancel', 'aria')}
							</Button>
							<Button
								variant="primary"
								onClick={handleAIGeneration}
								disabled={!rawContent.trim() || generating}
								isBusy={generating}
								style={{ background: 'linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%)' }}
							>
								{generating ? (
									<>
										<Spinner style={{ marginRight: '8px' }} />
										{__('Generating...', 'aria')}
									</>
								) : (
									<>
										<MagicWandIcon />
										{__('Generate with AI', 'aria')}
									</>
								)}
							</Button>
						</Flex>
					</Flex>
				</>
			)}

			{/* Step 2: Generating (Loading State) */}
			{generationStep === 'generating' && (
				<div style={{ textAlign: 'center', padding: '40px 20px' }}>
					<Spinner style={{ width: '40px', height: '40px', marginBottom: '20px' }} />
					<h3>{__('AI is analyzing your content...', 'aria')}</h3>
					<p style={{ color: '#666', maxWidth: '400px', margin: '0 auto' }}>
						{__(
							'Our AI is reading through your content and structuring it into a comprehensive knowledge entry. This may take a few moments.',
							'aria'
						)}
					</p>
				</div>
			)}

			{/* Step 3: Review Generated Content or Manual Entry */}
			{(generationStep === 'review' || generationStep === 'manual') && (
				<>
					{generationStep === 'review' && (
						<Card style={{ marginBottom: '24px', backgroundColor: '#f0f9ff', border: '1px solid #0ea5e9' }}>
							<CardBody>
								<Flex align="center" gap="12px" style={{ marginBottom: '12px' }}>
									<SparklesIcon />
									<strong>{__('AI Generation Complete!', 'aria')}</strong>
								</Flex>
								<p style={{ margin: 0, fontSize: '14px' }}>
									{__(
										'Review the generated fields below and make any adjustments needed. The AI has structured your content with appropriate categories, tags, and formatting.',
										'aria'
									)}
								</p>
							</CardBody>
						</Card>
					)}

					<div style={{ display: 'grid', gap: '24px' }}>
						{/* Primary Information Panel */}
						<Panel>
							<PanelHeader>{__('Primary Information', 'aria')}</PanelHeader>
							<PanelBody opened={true}>
								<div style={{ display: 'grid', gap: '20px' }}>
									<TextControl
										label={__('Title', 'aria')}
										value={formData.title}
										onChange={(value) => updateField('title', value)}
										placeholder={__('Enter a clear, descriptive title', 'aria')}
										help={__('This will be used to identify the knowledge entry', 'aria')}
									/>

									<SelectControl
										label={__('Category', 'aria')}
										value={formData.category}
										options={KNOWLEDGE_CATEGORIES}
										onChange={(value) => updateField('category', value)}
										help={__('Choose the most appropriate category', 'aria')}
									/>

									<TextareaControl
										label={__('Content', 'aria')}
										value={formData.content}
										onChange={(value) => updateField('content', value)}
										rows={8}
										placeholder={__('Enter the main knowledge content...', 'aria')}
										help={__('The primary information that Aria should know about this topic', 'aria')}
									/>
								</div>
							</PanelBody>
						</Panel>

						{/* AI Context Panel */}
						<Panel>
							<PanelHeader>{__('AI Behavior & Context', 'aria')}</PanelHeader>
							<PanelBody opened={true}>
								<div style={{ display: 'grid', gap: '20px' }}>
									<TextareaControl
										label={__('Context', 'aria')}
										value={formData.context}
										onChange={(value) => updateField('context', value)}
										rows={3}
										placeholder={__(
											'When should Aria use this information? Describe situations or customer questions...',
											'aria'
										)}
										help={__(
											'Help Aria understand when to reference this knowledge',
											'aria'
										)}
									/>

									<TextareaControl
										label={__('Response Instructions', 'aria')}
										value={formData.response_instructions}
										onChange={(value) => updateField('response_instructions', value)}
										rows={3}
										placeholder={__(
											'How should Aria communicate this information? Include tone and special instructions...',
											'aria'
										)}
										help={__(
											'Guide how Aria should present this information to customers',
											'aria'
										)}
									/>
								</div>
							</PanelBody>
						</Panel>

						{/* Organization Panel */}
						<Panel>
							<PanelHeader>{__('Organization & Discovery', 'aria')}</PanelHeader>
							<PanelBody opened={true}>
								<div style={{ display: 'grid', gap: '20px' }}>
									<TextControl
										label={__('Tags', 'aria')}
										value={formData.tags}
										onChange={(value) => updateField('tags', value)}
										placeholder={__('Enter keywords separated by commas', 'aria')}
										help={__('Tags help Aria find and match this knowledge to customer questions', 'aria')}
									/>

									<SelectControl
										label={__('Language', 'aria')}
										value={formData.language}
										options={[
											{ label: __('English', 'aria'), value: 'en' },
											{ label: __('Spanish', 'aria'), value: 'es' },
											{ label: __('French', 'aria'), value: 'fr' },
											{ label: __('German', 'aria'), value: 'de' },
											{ label: __('Italian', 'aria'), value: 'it' },
										]}
										onChange={(value) => updateField('language', value)}
									/>
								</div>
							</PanelBody>
						</Panel>
					</div>

					{/* Action Buttons */}
					<div style={{ marginTop: '24px', padding: '24px', background: '#fff', borderTop: '1px solid #e0e0e0', position: 'sticky', bottom: 0 }}>
						<Flex justify="space-between" align="center">
							<div>
								<Button variant="tertiary" onClick={handleCancel} disabled={saving}>
									{__('Cancel', 'aria')}
								</Button>
								{generationStep === 'review' && (
									<Button variant="tertiary" onClick={startOver} disabled={saving} style={{ marginLeft: '12px' }}>
										{__('Start Over', 'aria')}
									</Button>
								)}
							</div>
							
							<Flex gap="12px">
								{generationStep === 'manual' && action === 'add' && (
									<Button
										variant="secondary"
										onClick={() => setGenerationStep('input')}
										disabled={saving}
									>
										<MagicWandIcon />
										{__('Use AI Assistant', 'aria')}
									</Button>
								)}
								<Button
									variant="primary"
									onClick={handleSave}
									disabled={saving || !formData.title.trim() || !formData.content.trim()}
									isBusy={saving}
								>
									{saving
										? __('Saving…', 'aria')
										: action === 'edit'
										? __('Update Entry', 'aria')
										: __('Create Entry', 'aria')}
								</Button>
							</Flex>
						</Flex>
					</div>
				</>
			)}
		</div>
	);
};

export default KnowledgeEntry;