import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Button,
	Notice,
} from '@wordpress/components';
import { PageHeader, CustomRadioGroup } from '../components';
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

	const handleTraitChange = (traitValue, isChecked) => {
		setPersonalityData((prev) => ({
			...prev,
			personalityTraits: isChecked
				? [...prev.personalityTraits, traitValue]
				: prev.personalityTraits.filter(
						(trait) => trait !== traitValue
					),
		}));
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
		<div
			className="aria-personality-react"
			style={{ paddingRight: '32px' }}
		>
			<PageHeader
				title={__('Personality & Voice', 'aria')}
				description={__(
					'Define how Aria communicates and interacts with your website visitors',
					'aria'
				)}
			/>

			{notice && (
				<div style={{ marginBottom: '24px' }}>
					<Notice
						status={notice.type}
						isDismissible={true}
						onRemove={() => setNotice(null)}
					>
						{notice.message}
					</Notice>
				</div>
			)}

			{/* Business Type Section */}
			<Card
				size="large"
				style={{ padding: '24px', marginBottom: '24px' }}
			>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Business Type', 'aria')}
					</h3>
					<p
						style={{
							fontSize: '14px',
							color: '#757575',
							margin: 0,
						}}
					>
						{__(
							'Select your business type to help Aria understand your context',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<CustomRadioGroup
						options={BUSINESS_TYPES}
						value={personalityData.businessType}
						onChange={(value) =>
							updateSetting('businessType', value)
						}
						name="businessType"
						theme="blue"
					/>
				</CardBody>
			</Card>

			{/* Conversation Style Section */}
			<Card
				size="large"
				style={{ padding: '24px', marginBottom: '24px' }}
			>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Conversation Style', 'aria')}
					</h3>
					<p
						style={{
							fontSize: '14px',
							color: '#757575',
							margin: 0,
						}}
					>
						{__(
							'Choose the tone that best fits your brand',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<CustomRadioGroup
						options={TONE_SETTINGS}
						value={personalityData.toneSetting}
						onChange={(value) =>
							updateSetting('toneSetting', value)
						}
						name="toneSetting"
						theme="purple"
						minWidth="260px"
					/>
				</CardBody>
			</Card>

			{/* Key Characteristics Section */}
			<Card
				size="large"
				style={{ padding: '24px', marginBottom: '24px' }}
			>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Key Characteristics', 'aria')}
					</h3>
					<p
						style={{
							fontSize: '14px',
							color: '#757575',
							margin: 0,
						}}
					>
						{__(
							"Select 2-3 traits that define Aria's approach",
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gridTemplateColumns:
								'repeat(auto-fit, minmax(240px, 1fr))',
							gap: '16px',
						}}
					>
						{PERSONALITY_TRAITS.map((trait) => (
							<div
								key={trait.value}
								style={{ position: 'relative' }}
							>
								<div
									role="button"
									aria-pressed={personalityData.personalityTraits.includes(
										trait.value
									)}
									aria-label={trait.label}
									tabIndex={0}
									style={{
										display: 'flex',
										alignItems: 'center',
										padding: '16px 20px',
										border: personalityData.personalityTraits.includes(
											trait.value
										)
											? '2px solid #28a745'
											: '2px solid #e1e4e8',
										borderRadius: '12px',
										cursor: 'pointer',
										transition: 'all 0.2s ease',
										background:
											personalityData.personalityTraits.includes(
												trait.value
											)
												? 'linear-gradient(135deg, rgba(40, 167, 69, 0.05) 0%, rgba(32, 201, 151, 0.02) 100%)'
												: 'linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%)',
										boxShadow:
											personalityData.personalityTraits.includes(
												trait.value
											)
												? '0 4px 16px rgba(40, 167, 69, 0.15)'
												: '0 2px 8px rgba(0, 0, 0, 0.08)',
									}}
									onClick={() =>
										handleTraitChange(
											trait.value,
											!personalityData.personalityTraits.includes(
												trait.value
											)
										)
									}
									onKeyDown={(e) => {
										if (
											e.key === 'Enter' ||
											e.key === ' '
										) {
											e.preventDefault();
											handleTraitChange(
												trait.value,
												!personalityData.personalityTraits.includes(
													trait.value
												)
											);
										}
									}}
								>
									<input
										type="checkbox"
										value={trait.value}
										checked={personalityData.personalityTraits.includes(
											trait.value
										)}
										onChange={(e) =>
											handleTraitChange(
												trait.value,
												e.target.checked
											)
										}
										style={{ display: 'none' }}
									/>
									<div style={{ flex: 1 }}>
										<div
											style={{
												fontSize: '16px',
												fontWeight: '600',
												color: '#1e1e1e',
											}}
										>
											{trait.label}
										</div>
									</div>
									{personalityData.personalityTraits.includes(
										trait.value
									) && (
										<div
											style={{
												width: '24px',
												height: '24px',
												borderRadius: '50%',
												background:
													'linear-gradient(135deg, #28a745 0%, #20c997 100%)',
												color: 'white',
												display: 'flex',
												alignItems: 'center',
												justifyContent: 'center',
												fontSize: '14px',
												fontWeight: '600',
												marginLeft: '12px',
												flexShrink: 0,
											}}
										>
											✓
										</div>
									)}
								</div>
							</div>
						))}
					</div>
				</CardBody>
			</Card>

			{/* Custom Messages Section */}
			<Card
				size="large"
				style={{ padding: '24px', marginBottom: '32px' }}
			>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Custom Messages', 'aria')}
					</h3>
					<p
						style={{
							fontSize: '14px',
							color: '#757575',
							margin: 0,
						}}
					>
						{__(
							"Customize Aria's greeting and farewell messages",
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gridTemplateColumns:
								'repeat(auto-fit, minmax(300px, 1fr))',
							gap: '24px',
						}}
					>
						<div>
							<label
								htmlFor="greeting-message"
								style={{
									display: 'block',
									fontSize: '14px',
									fontWeight: '600',
									color: '#1e1e1e',
									marginBottom: '8px',
								}}
							>
								{__('Greeting Message', 'aria')}
							</label>
							<textarea
								id="greeting-message"
								rows={4}
								placeholder={__(
									"Hi! I'm Aria. How can I help you today?",
									'aria'
								)}
								value={personalityData.greetingMessage}
								onChange={(e) =>
									updateSetting(
										'greetingMessage',
										e.target.value
									)
								}
								style={{
									width: '100%',
									padding: '12px 16px',
									border: '2px solid #e1e4e8',
									borderRadius: '8px',
									fontSize: '14px',
									lineHeight: '1.5',
									fontFamily: 'inherit',
									resize: 'vertical',
									transition: 'border-color 0.2s ease',
									outline: 'none',
								}}
								onFocus={(e) => {
									e.target.style.borderColor = '#2271b1';
								}}
								onBlur={(e) => {
									e.target.style.borderColor = '#e1e4e8';
								}}
							/>
							<p
								style={{
									fontSize: '12px',
									marginTop: '6px',
									color: '#757575',
									margin: '6px 0 0 0',
								}}
							>
								{__(
									'First message shown to visitors when they start a conversation',
									'aria'
								)}
							</p>
						</div>

						<div>
							<label
								htmlFor="farewell-message"
								style={{
									display: 'block',
									fontSize: '14px',
									fontWeight: '600',
									color: '#1e1e1e',
									marginBottom: '8px',
								}}
							>
								{__('Farewell Message', 'aria')}
							</label>
							<textarea
								id="farewell-message"
								rows={4}
								placeholder={__(
									'Thanks for chatting! Have a great day!',
									'aria'
								)}
								value={personalityData.farewellMessage}
								onChange={(e) =>
									updateSetting(
										'farewellMessage',
										e.target.value
									)
								}
								style={{
									width: '100%',
									padding: '12px 16px',
									border: '2px solid #e1e4e8',
									borderRadius: '8px',
									fontSize: '14px',
									lineHeight: '1.5',
									fontFamily: 'inherit',
									resize: 'vertical',
									transition: 'border-color 0.2s ease',
									outline: 'none',
								}}
								onFocus={(e) => {
									e.target.style.borderColor = '#2271b1';
								}}
								onBlur={(e) => {
									e.target.style.borderColor = '#e1e4e8';
								}}
							/>
							<p
								style={{
									fontSize: '12px',
									marginTop: '6px',
									color: '#757575',
									margin: '6px 0 0 0',
								}}
							>
								{__(
									'Message shown when conversations end or timeout',
									'aria'
								)}
							</p>
						</div>
					</div>
				</CardBody>
			</Card>

			{/* Save Actions */}
			<Card>
				<CardBody>
					<Button
						variant="primary"
						onClick={handleSave}
						isBusy={saving}
						disabled={saving}
					>
						{saving
							? __('Saving…', 'aria')
							: __('Save Personality Settings', 'aria')}
					</Button>
				</CardBody>
			</Card>
		</div>
	);
};

export default Personality;
