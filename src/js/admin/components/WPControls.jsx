import { forwardRef } from '@wordpress/element';
import {
	SelectControl as CoreSelectControl,
	TextControl as CoreTextControl,
	TextareaControl as CoreTextareaControl,
	ToggleControl as CoreToggleControl,
	SearchControl as CoreSearchControl,
} from '@wordpress/components';

const withNextDefaults = (Component, defaultProps = {}) =>
	forwardRef(({ __nextHasNoMarginBottom = true, ...props }, ref) => (
		<Component
			ref={ref}
			__nextHasNoMarginBottom={__nextHasNoMarginBottom}
			{...defaultProps}
			{...props}
		/>
	));

export const SelectControl = forwardRef(
	({ __nextHasNoMarginBottom = true, __next40pxDefaultSize = true, ...props }, ref) => (
		<CoreSelectControl
			ref={ref}
			__nextHasNoMarginBottom={__nextHasNoMarginBottom}
			__next40pxDefaultSize={__next40pxDefaultSize}
			{...props}
		/>
	)
);

export const TextControl = forwardRef(
	({ __nextHasNoMarginBottom = true, __next40pxDefaultSize = true, ...props }, ref) => (
		<CoreTextControl
			ref={ref}
			__nextHasNoMarginBottom={__nextHasNoMarginBottom}
			__next40pxDefaultSize={__next40pxDefaultSize}
			{...props}
		/>
	)
);

export const TextareaControl = withNextDefaults(CoreTextareaControl);
export const ToggleControl = withNextDefaults(CoreToggleControl);
export const SearchControl = withNextDefaults(CoreSearchControl);

export default {
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
	SearchControl,
};
