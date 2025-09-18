import { useCallback, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const useKnowledgeGenerator = ({
	action,
	ajaxUrl,
	generateNonce,
	onPopulateForm,
	onResetForm,
	setNotice,
}) => {
	const defaultStep = action === 'edit' ? 'manual' : 'input';
	const [generationStep, setGenerationStep] = useState(defaultStep);
	const [rawContent, setRawContent] = useState('');
	const [generating, setGenerating] = useState(false);

	const ajaxConfig = useMemo(
		() => ({
			nonce: generateNonce || '',
			ajaxUrl: ajaxUrl || '',
		}),
		[ajaxUrl, generateNonce]
	);

	const startGeneration = useCallback(async () => {
		if (!rawContent.trim()) {
			setNotice?.({
				type: 'error',
				message: __(
					'Please enter some content to generate from.',
					'aria'
				),
			});
			return;
		}

		if (!ajaxConfig.nonce || !ajaxConfig.ajaxUrl) {
			setNotice?.({
				type: 'error',
				message: __(
					'AI generation is not available. Please refresh and try again.',
					'aria'
				),
			});
			return;
		}

		setGenerating(true);
		setGenerationStep('generating');
		setNotice?.(null);

		try {
			const formData = new FormData();
			formData.append('action', 'aria_generate_knowledge_entry');
			formData.append('nonce', ajaxConfig.nonce);
			formData.append('content', rawContent);

			const response = await fetch(ajaxConfig.ajaxUrl, {
				method: 'POST',
				body: formData,
			});

			if (!response.ok) {
				throw new Error(`HTTP error! status: ${response.status}`);
			}

			const result = await response.json();

			if (result.success && result.data) {
				onPopulateForm(result.data);
				setGenerationStep('review');
				setNotice?.({
					type: 'success',
					message: __(
						'Content generated successfully! Review and edit as needed.',
						'aria'
					),
				});
			} else {
				throw new Error(
					result.data?.message || __('Generation failed', 'aria')
				);
			}
		} catch (error) {
			setGenerationStep('input');
			setNotice?.({
				type: 'error',
				message:
					error.message ||
					__('Failed to generate content. Please try again.', 'aria'),
			});
		} finally {
			setGenerating(false);
		}
	}, [ajaxConfig, onPopulateForm, rawContent, setNotice]);

	const switchToManual = useCallback(() => {
		setGenerationStep('manual');
		setNotice?.(null);
	}, [setNotice]);

	const startOver = useCallback(() => {
		onResetForm();
		setRawContent('');
		setGenerationStep('input');
		setNotice?.(null);
	}, [onResetForm, setNotice]);

	return {
		generationStep,
		setGenerationStep,
		rawContent,
		setRawContent,
		generating,
		startGeneration,
		switchToManual,
		startOver,
	};
};

export default useKnowledgeGenerator;
