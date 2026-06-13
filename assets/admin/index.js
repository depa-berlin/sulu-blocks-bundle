import {initializer} from 'sulu-admin-bundle/services';
import {viewRegistry} from 'sulu-admin-bundle/containers';
import BlocksDashboard from './views/BlocksDashboard';

initializer.addUpdateConfigHook('sulu_blocks', (config, initialized) => {
    if (initialized) {
        return;
    }

    viewRegistry.add('sulu_blocks.dashboard', BlocksDashboard);
});
