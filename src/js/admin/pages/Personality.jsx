import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { PageHeader, PageShell } from '../components';
import {
	PersonalityNotice,
	PersonalityRadioSection,
	PersonalityTraitsSection,
	PersonalityMessagesSection,
	PersonalitySaveSection,
} from './personality-sections';
import {
	BUSINESS_TYPES,
	TONE_SETTINGS,
	PERSONALITY_TRAITS,
} from '../utils/constants';
import { savePersonalitySettings } from '../utils/api';

const Personality = () => {
	const [personalityData, setPersonalityData] = useState({
		businessType: 'general',
		toneSetting: 'professional',
		personalityTraits: [],
		greetingMessage: '',
		farewellMessage: '',
	});
	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const updateSetting = (key, value) => {
		setPersonalityData((prev) => ({ ...prev, [key]: value }));
	};

	const toggleTrait = (trait) => {
		setPersonalityData((prev) => {
			const hasTrait = prev.personalityTraits.includes(trait);
			return {
				...prev,
				personalityTraits: hasTrait
					? prev.personalityTraits.filter((value) => value !== trait)
					: [...prev.personalityTraits, trait],
			};
		});
	};

	const handleSave = async () => {
		setSaving(true);
		try {
			await savePersonalitySettings(personalityData);
			setNotice({
				type: 'success',
				message: __('Personality settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __(
					'Failed to save settings. Please try again.',
					'aria'
				),
			});
		} finally {
			setSaving(false);
		}
	};

	return (
		<PageShell
			className="aria-personality aria-personality-react"
			width="wide"
		>
			<PageHeader
				title={__('Personality & Voice', 'aria')}
				description={__(
					'Define how Aria communicates and interacts with your website visitors',
					'aria'
				)}
			/>

			<PersonalityNotice
				notice={notice}
				onRemove={() => setNotice(null)}
			/>

			<div className="aria-stack-lg">
				<PersonalityRadioSection
					title={__('Business Type', 'aria')}
					description={__(
						'Select your business type to help Aria understand your context.',
						'aria'
					)}
					options={BUSINESS_TYPES}
					value={personalityData.businessType}
					onChange={(value) => updateSetting('businessType', value)}
					name="businessType"
				/>

				<PersonalityRadioSection
					title={__('Conversation Style', 'aria')}
					description={__(
						'Choose the tone that best fits your brand.',
						'aria'
					)}
					options={TONE_SETTINGS}
					value={personalityData.toneSetting}
					onChange={(value) => updateSetting('toneSetting', value)}
					name="toneSetting"
				/>

				<PersonalityTraitsSection
					traits={PERSONALITY_TRAITS}
					selectedTraits={personalityData.personalityTraits}
					onToggleTrait={toggleTrait}
				/>

				<PersonalityMessagesSection
					greetingMessage={personalityData.greetingMessage}
					onGreetingChange={(value) =>
						updateSetting('greetingMessage', value)
					}
					farewellMessage={personalityData.farewellMessage}
					onFarewellChange={(value) =>
						updateSetting('farewellMessage', value)
					}
				/>

				<PersonalitySaveSection saving={saving} onSave={handleSave} />
			</div>
		</PageShell>
	);
};

export default Personality;
