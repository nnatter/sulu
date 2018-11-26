// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import type {IObservableValue} from 'mobx';
import {action, autorun, observable, toJS} from 'mobx';
import SingleItemSelection from 'sulu-admin-bundle/components/SingleItemSelection';
import {translate} from 'sulu-admin-bundle/utils/Translator';
import SingleMediaSelectionStore from '../../stores/SingleMediaSelectionStore';
import MediaSelectionItem from '../../components/MediaSelectionItem';
import SingleMediaSelectionOverlay from '../SingleMediaSelectionOverlay';
import type {Value} from './types';

type Props = {|
    disabled: boolean,
    locale: IObservableValue<string>,
    onChange: (selectedIds: Value) => void,
    value: Value,
|}

const THUMBNAIL_SIZE = 'sulu-25x25';

@observer
export default class SingleMediaSelection extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        value: {id: undefined},
    };

    singleMediaSelectionStore: SingleMediaSelectionStore;
    changeDisposer: () => void;
    changeAutorunInitialized: boolean = false;

    @observable overlayOpen: boolean = false;

    constructor(props: Props) {
        super(props);

        const {onChange, locale, value} = this.props;

        this.singleMediaSelectionStore = new SingleMediaSelectionStore(value.id, locale);
        this.changeDisposer = autorun(() => {
            const {value} = this.props;
            const loadedMediaId = this.singleMediaSelectionStore.selectedMediaId;

            if (!this.changeAutorunInitialized) {
                this.changeAutorunInitialized = true;
                return;
            }

            if (value.id === loadedMediaId) {
                return;
            }

            onChange({id: loadedMediaId});
        });
    }

    componentDidUpdate() {
        const {
            locale,
            value,
        } = this.props;

        const newSelectedId = toJS(value.id);
        const loadedSelectedId = toJS(this.singleMediaSelectionStore.selectedMediaId);

        if (loadedSelectedId !== newSelectedId) {
            this.singleMediaSelectionStore.loadSelectedMedia(newSelectedId, locale);
        }
    }

    componentWillUnmount() {
        this.changeDisposer();
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    handleRemove = () => {
        this.singleMediaSelectionStore.clear();
    };

    handleOverlayOpen = () => {
        this.openOverlay();
    };

    handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = (selectedMedia: Object) => {
        this.singleMediaSelectionStore.set(selectedMedia);
        this.closeOverlay();
    };

    render() {
        const {
            disabled,
            locale,
        } = this.props;
        const {
            selectedMedia,
            selectedMediaId,
        } = this.singleMediaSelectionStore;

        return (
            <Fragment>
                <SingleItemSelection
                    disabled={disabled}
                    emptyText={translate('sulu_media.select_media_singular')}
                    leftButton={{
                        icon: 'su-image',
                        onClick: this.handleOverlayOpen,
                    }}
                    onRemove={this.singleMediaSelectionStore.selectedMedia ? this.handleRemove : undefined}
                >
                    {selectedMedia &&
                        <MediaSelectionItem
                            mimeType={selectedMedia.mimeType}
                            thumbnail={selectedMedia.thumbnails[THUMBNAIL_SIZE]}
                        >
                            {selectedMedia.title}
                        </MediaSelectionItem>
                    }
                </SingleItemSelection>
                <SingleMediaSelectionOverlay
                    excludedIds={selectedMediaId ? [selectedMediaId] : []}
                    locale={locale}
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                />
            </Fragment>
        );
    }
}