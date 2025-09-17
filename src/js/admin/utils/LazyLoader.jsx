import { lazy, Suspense } from '@wordpress/element';

/**
 * Utility for lazy loading React components with loading fallback
 */

const LoadingFallback = () => (
	<div style={{
		display: 'flex',
		justifyContent: 'center',
		alignItems: 'center',
		height: '200px',
		fontSize: '14px',
		color: '#757575'
	}}>
		Loading...
	</div>
);

/**
 * Higher-order component for lazy loading with suspense
 * @param {Function} importFn - Dynamic import function
 * @param {Object} fallback - Custom loading component
 * @return {Function} Lazy loaded component
 */
export const lazyLoad = (importFn, fallback = <LoadingFallback />) => {
	const LazyComponent = lazy(importFn);
	
	return (props) => (
		<Suspense fallback={fallback}>
			<LazyComponent {...props} />
		</Suspense>
	);
};

export default lazyLoad;