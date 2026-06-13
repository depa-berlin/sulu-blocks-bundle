// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Requester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import styles from './BlocksDashboard.scss';

const API_URL = '/admin/api/sulu-blocks/info';

@observer
class BlocksDashboard extends React.Component<*> {
    @observable loading: boolean = true;
    @observable error: ?string = null;
    @observable installedBundles: Array<Object> = [];
    @observable totalBlocks: number = 0;
    @observable connections: Array<Object> = [];

    componentDidMount() {
        this.loadData();
    }

    @action
    loadData() {
        this.loading = true;
        this.error = null;

        Requester.get(API_URL)
            .then(action((data) => {
                this.installedBundles = data.installedBundles || [];
                this.totalBlocks = data.totalBlocks || 0;
                this.connections = data.connections || [];
                this.loading = false;
            }))
            .catch(action((err) => {
                this.error = err.message || translate('sulu_blocks.dashboard.retry');
                this.loading = false;
            }));
    }

    renderLoading() {
        return (
            <div className={styles.stateContainer}>
                <div className={styles.spinner} />
            </div>
        );
    }

    renderError() {
        return (
            <div className={styles.stateContainer}>
                <div className={styles.errorIcon}>!</div>
                <p className={styles.stateText}>{this.error}</p>
                <button className={styles.retryButton} onClick={() => this.loadData()}>
                    {translate('sulu_blocks.dashboard.retry')}
                </button>
            </div>
        );
    }

    render() {
        if (this.loading) return this.renderLoading();
        if (this.error) return this.renderError();

        return (
            <div className={styles.dashboard}>

                {/* Summary */}
                <div className={styles.summary}>
                    <div className={styles.summaryCard}>
                        <div className={styles.summaryNumber}>{this.installedBundles.length}</div>
                        <div className={styles.summaryLabel}>{translate('sulu_blocks.dashboard.installed_bundles_label')}</div>
                    </div>
                    <div className={styles.summaryCard}>
                        <div className={styles.summaryNumber}>{this.totalBlocks}</div>
                        <div className={styles.summaryLabel}>{translate('sulu_blocks.dashboard.available_types_label')}</div>
                    </div>
                    <div className={styles.summaryCard}>
                        <div className={styles.summaryNumber}>{this.connections.length}</div>
                        <div className={styles.summaryLabel}>{translate('sulu_blocks.dashboard.connections_label')}</div>
                    </div>
                </div>

                {/* Installed Bundles */}
                <section className={styles.section}>
                    <h2 className={styles.sectionTitle}>{translate('sulu_blocks.dashboard.installed_bundles')}</h2>
                    <div className={styles.bundleGrid}>
                        {this.installedBundles.map((bundle) => (
                            <div key={bundle.name} className={styles.bundleCard}>
                                <div className={styles.bundleCardHead}>
                                    <span className={styles.bundleName}>{bundle.name}</span>
                                    <span className={styles.bundleBadge}>{bundle.blockCount} Blocks</span>
                                </div>
                                <div className={styles.bundlePackage}>{bundle.package}</div>
                                <div className={styles.blockListWrap}>
                                    <ul className={styles.blockList}>
                                        {(bundle.blocks || []).map((block) => {
                                            const childBlocks = (bundle.children || {})[block] || [];
                                            return (
                                                <li key={block} className={styles.blockListItem}>
                                                    <code>{block}</code>
                                                    {childBlocks.length > 0 && (
                                                        <ul className={styles.childList}>
                                                            {childBlocks.map((child) => (
                                                                <li key={child} className={styles.childListItem}>
                                                                    <code>{child}</code>
                                                                </li>
                                                            ))}
                                                        </ul>
                                                    )}
                                                </li>
                                            );
                                        })}
                                    </ul>
                                    {(bundle.blocks || []).length > 8 && (
                                        <div className={styles.scrollHint}>↓ scroll</div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                </section>

                {/* Block Types grouped by Bundle */}
                <section className={styles.section}>
                    <h2 className={styles.sectionTitle}>
                        {translate('sulu_blocks.dashboard.available_types')}
                        <span className={styles.titleCount}>{this.totalBlocks}</span>
                    </h2>
                    {this.installedBundles.map((bundle) => (
                        <div key={bundle.name} className={styles.blockTypeGroup}>
                            <div className={styles.blockTypeGroupLabel}>
                                {bundle.name}
                                <span className={styles.blockTypeGroupCount}>{bundle.blockCount}</span>
                            </div>
                            <div className={styles.blockTypeGrid}>
                                {(bundle.blocks || []).map((block) => (
                                    <code key={block} className={styles.blockType}>{block}</code>
                                ))}
                            </div>
                        </div>
                    ))}
                </section>

                {/* Cross-Bundle Connections */}
                <section className={styles.section}>
                    <h2 className={styles.sectionTitle}>
                        {translate('sulu_blocks.dashboard.connections')}
                        <span className={styles.titleCount}>{this.connections.length}</span>
                    </h2>
                    {this.connections.length === 0 ? (
                        <div className={styles.emptyState}>
                            <p>{translate('sulu_blocks.dashboard.no_connections')}</p>
                            <p className={styles.emptyHint}>
                                {translate('sulu_blocks.dashboard.no_connections_hint')}
                            </p>
                        </div>
                    ) : (
                        <div className={styles.connectionList}>
                            {this.connections.map((conn) => (
                                <div key={(conn.requires || []).join('+')} className={styles.connectionCard}>
                                    <div className={styles.connectionHeader}>
                                        {(conn.requires || []).map((req, j) => (
                                            <React.Fragment key={req}>
                                                <span className={styles.connectionBundle}>{req}</span>
                                                {j < (conn.requires || []).length - 1 && (
                                                    <span className={styles.connectionPlus}>+</span>
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </div>
                                    {conn.description && (
                                        <p className={styles.connectionDesc}>{conn.description}</p>
                                    )}
                                    <div className={styles.connectionBlocks}>
                                        {(conn.blocks || []).map((b) => (
                                            <code key={b} className={styles.blockType}>{b}</code>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>

            </div>
        );
    }
}

export default BlocksDashboard;
