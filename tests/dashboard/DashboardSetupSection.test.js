import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DashboardSetupSection from '../../src/js/admin/pages/dashboard-sections/DashboardSetupSection.jsx';

jest.mock('@wordpress/i18n', () => ({
	__: (text) => text,
}));

const steps = [
	{ title: 'Connect account', description: 'Authorize API access', completed: false, link: 'admin.php?page=aria-settings' },
	{ title: 'Add knowledge', description: 'Import your docs', completed: true },
];

describe('DashboardSetupSection', () => {
	it('renders pending steps with actions', async () => {
		const onNavigate = jest.fn();
		render(<DashboardSetupSection steps={steps} onNavigate={onNavigate} />);

		expect(screen.getByText('Connect account')).toBeInTheDocument();
		expect(screen.getByText('Authorize API access')).toBeInTheDocument();

		await userEvent.click(screen.getByRole('button', { name: 'Configure' }));
		expect(onNavigate).toHaveBeenCalledWith('admin.php?page=aria-settings');
	});

	it('returns null when all steps complete', () => {
		const { container } = render(
			<DashboardSetupSection
				steps={steps.map((step) => ({ ...step, completed: true }))}
				onNavigate={jest.fn()}
			/>
		);

		expect(container).toBeEmptyDOMElement();
	});
});
