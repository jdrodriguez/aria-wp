import React from 'react';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import DashboardQuickActionsSection from '../../src/js/admin/pages/dashboard-sections/DashboardQuickActionsSection.jsx';

jest.mock('@wordpress/i18n', () => ({
	__: (text) => text,
}));

describe('DashboardQuickActionsSection', () => {
	it('invokes handlers when quick actions clicked', async () => {
		const onNavigate = jest.fn();
		const onTestChat = jest.fn();

		render(
			<DashboardQuickActionsSection
				onNavigate={onNavigate}
				onTestChat={onTestChat}
			/>
		);

		await userEvent.click(screen.getByRole('button', { name: 'Add Knowledge' }));
		expect(onNavigate).toHaveBeenCalledWith('admin.php?page=aria-knowledge&action=new');

		await userEvent.click(screen.getByRole('button', { name: 'Test Aria' }));
		expect(onTestChat).toHaveBeenCalled();
	});
});
