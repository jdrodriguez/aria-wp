import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import {
	Card,
	CardHeader,
	CardBody,
	Button,
	TextControl,
	SelectControl,
	ColorPicker,
	Notice,
} from '@wordpress/components';
import { PageHeader } from '../components';
import {
	WIDGET_POSITIONS,
	WIDGET_SIZES,
	WIDGET_THEMES,
} from '../utils/constants';
import { saveDesignSettings } from '../utils/api';

const ColorSection = ({ title, description, color, onChange }) => (
	<div>
		<h4 style={{ fontSize: '16px', fontWeight: '600', marginBottom: '8px' }}>
			{title}
		</h4>
		<p style={{ fontSize: '13px', color: '#757575', marginBottom: '12px' }}>
			{description}
		</p>
		<ColorPicker color={color} onChange={onChange} enableAlpha={false} />
	</div>
);

const Design = () => {
	const [settings, setSettings] = useState({
		position: 'bottom-right',
		size: 'medium',
		theme: 'light',
		primaryColor: '#2271b1',
		backgroundColor: '#ffffff',
		textColor: '#1e1e1e',
		title: 'Chat with us',
		welcomeMessage: 'Hi! How can I help you today?',
	});

	const [saving, setSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	const updateSetting = (key, value) => {
		setSettings((prev) => ({ ...prev, [key]: value }));
	};

	const handleSave = async () => {
		setSaving(true);
		try {
			await saveDesignSettings(settings);
			setNotice({
				type: 'success',
				message: __('Design settings saved successfully!', 'aria'),
			});
			setTimeout(() => setNotice(null), 5000);
		} catch (error) {
			setNotice({
				type: 'error',
				message: __('Failed to save settings. Please try again.', 'aria'),
			});
		} finally {
			setSaving(false);
		}
	};

	const uploadIcon = () => {
		// TODO: Implement file upload
		console.log('Upload icon functionality to be implemented');
	};

	const uploadAvatar = () => {
		// TODO: Implement file upload
		console.log('Upload avatar functionality to be implemented');
	};

	return (
		<div className="aria-design-react" style={{ paddingRight: '32px' }}>
			<PageHeader
				title={__('Design', 'aria')}
				description={__(
					'Customize the appearance and behavior of your chat widget',
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

			{/* Widget Appearance */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Widget Appearance', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Configure the basic appearance and positioning of your chat widget',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gridTemplateColumns:
								'repeat(auto-fit, minmax(200px, 1fr))',
							gap: '20px',
						}}
					>
						<SelectControl
							label={__('Widget Position', 'aria')}
							value={settings.position}
							options={WIDGET_POSITIONS}
							help={__(
								'Choose where the chat widget appears on your site',
								'aria'
							)}
							onChange={(value) => updateSetting('position', value)}
						/>

						<SelectControl
							label={__('Widget Size', 'aria')}
							value={settings.size}
							options={WIDGET_SIZES}
							help={__(
								'Set the size of the chat widget window',
								'aria'
							)}
							onChange={(value) => updateSetting('size', value)}
						/>

						<SelectControl
							label={__('Widget Theme', 'aria')}
							value={settings.theme}
							options={WIDGET_THEMES}
							help={__(
								'Choose between light and dark theme',
								'aria'
							)}
							onChange={(value) => updateSetting('theme', value)}
						/>
					</div>
				</CardBody>
			</Card>

			{/* Colors */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Color Scheme', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Customize colors to match your brand and website design',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gridTemplateColumns:
								'repeat(auto-fit, minmax(250px, 1fr))',
							gap: '32px',
						}}
					>
						<ColorSection
							title={__('Primary Color', 'aria')}
							description={__(
								'Main color for buttons and highlights',
								'aria'
							)}
							color={settings.primaryColor}
							onChange={(color) =>
								updateSetting('primaryColor', color)
							}
						/>

						<ColorSection
							title={__('Background Color', 'aria')}
							description={__(
								'Background color for the chat widget',
								'aria'
							)}
							color={settings.backgroundColor}
							onChange={(color) =>
								updateSetting('backgroundColor', color)
							}
						/>

						<ColorSection
							title={__('Text Color', 'aria')}
							description={__(
								'Color for text in the chat widget',
								'aria'
							)}
							color={settings.textColor}
							onChange={(color) => updateSetting('textColor', color)}
						/>
					</div>
				</CardBody>
			</Card>

			{/* Branding */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Branding & Messages', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'Customize text, icons, and branding elements',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							display: 'grid',
							gap: '20px',
						}}
					>
						<div
							style={{
								display: 'grid',
								gridTemplateColumns:
									'repeat(auto-fit, minmax(300px, 1fr))',
								gap: '20px',
							}}
						>
							<TextControl
								label={__('Widget Title', 'aria')}
								value={settings.title}
								help={__(
									'Title shown at the top of the chat widget',
									'aria'
								)}
								onChange={(value) => updateSetting('title', value)}
							/>

							<TextControl
								label={__('Welcome Message', 'aria')}
								value={settings.welcomeMessage}
								help={__(
									'First message shown to visitors',
									'aria'
								)}
								onChange={(value) =>
									updateSetting('welcomeMessage', value)
								}
							/>
						</div>

						<div style={{ marginTop: '16px' }}>
							<h4
								style={{
									fontSize: '16px',
									fontWeight: '600',
									marginBottom: '12px',
								}}
							>
								{__('Custom Assets', 'aria')}
							</h4>
							<div
								style={{
									display: 'flex',
									gap: '12px',
									flexWrap: 'wrap',
								}}
							>
								<Button variant="secondary" onClick={uploadIcon}>
									{__('Upload Custom Icon', 'aria')}
								</Button>
								<Button variant="secondary" onClick={uploadAvatar}>
									{__('Upload Avatar', 'aria')}
								</Button>
							</div>
							<p
								style={{
									fontSize: '13px',
									color: '#757575',
									marginTop: '8px',
								}}
							>
								{__(
									'Upload custom icons and avatars to personalize your chat widget',
									'aria'
								)}
							</p>
						</div>
					</div>
				</CardBody>
			</Card>

			{/* Live Preview */}
			<Card size="large" style={{ padding: '24px', marginBottom: '24px' }}>
				<CardHeader style={{ paddingBottom: '16px' }}>
					<h3
						style={{
							fontSize: '18px',
							fontWeight: '600',
							marginBottom: '8px',
							margin: 0,
						}}
					>
						{__('Live Preview', 'aria')}
					</h3>
					<p style={{ fontSize: '14px', color: '#757575', margin: 0 }}>
						{__(
							'See how your chat widget will appear to visitors',
							'aria'
						)}
					</p>
				</CardHeader>
				<CardBody style={{ paddingTop: '24px' }}>
					<div
						style={{
							padding: '32px',
							border: '2px dashed #e1e4e8',
							borderRadius: '12px',
							backgroundColor: '#f8f9fa',
							textAlign: 'center',
							minHeight: '200px',
							display: 'flex',
							flexDirection: 'column',
							alignItems: 'center',
							justifyContent: 'center',
						}}
					>
						<div
							style={{
								width: '280px',
								height: '120px',
								backgroundColor: settings.backgroundColor,
								border: `2px solid ${settings.primaryColor}`,
								borderRadius: '12px',
								display: 'flex',
								flexDirection: 'column',
								padding: '16px',
								boxShadow: '0 4px 16px rgba(0, 0, 0, 0.15)',
								marginBottom: '16px',
							}}
						>
							<div
								style={{
									fontSize: '14px',
									fontWeight: '600',
									color: settings.textColor,
									marginBottom: '8px',
								}}
							>
								{settings.title || __('Chat with us', 'aria')}
							</div>
							<div
								style={{
									fontSize: '13px',
									color: settings.textColor,
									opacity: 0.8,
								}}
							>
								{settings.welcomeMessage ||
									__('Hi! How can I help you today?', 'aria')}
							</div>
							<div
								style={{
									marginTop: 'auto',
									display: 'flex',
									justifyContent: 'flex-end',
								}}
							>
								<div
									style={{
										backgroundColor: settings.primaryColor,
										color: '#ffffff',
										padding: '4px 8px',
										borderRadius: '4px',
										fontSize: '12px',
									}}
								>
									{__('Send', 'aria')}
								</div>
							</div>
						</div>
						<p
							style={{
								fontSize: '14px',
								color: '#757575',
								margin: 0,
							}}
						>
							{__(
								'This is a simplified preview of your chat widget',
								'aria'
							)}
						</p>
					</div>
				</CardBody>
			</Card>

			{/* Save Settings */}
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
							: __('Save Design Settings', 'aria')}
					</Button>
				</CardBody>
			</Card>
		</div>
	);
};

export default Design;