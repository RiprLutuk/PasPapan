import { error } from '@sveltejs/kit';
import { isLegacyRoute } from '../../lib/legacy-features';
import { legacyDestination } from '../../lib/legacy-destinations';
export const load = ({ url }: { url: URL }) => {
 if (!isLegacyRoute(url.pathname) && !legacyDestination(url.pathname)) error(404, 'Not found');
 return {};
};
