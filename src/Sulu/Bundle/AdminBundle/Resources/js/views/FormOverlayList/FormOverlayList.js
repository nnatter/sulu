// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable, toJS} from 'mobx';
import type {ElementRef} from 'react';
import type {ViewProps} from '../../containers/ViewRenderer';
import Overlay from '../../components/Overlay';
import {translate} from '../../utils/Translator';
import Form from '../../containers/Form';
import ResourceStore from '../../stores/ResourceStore';
import List from '../List';
import ResourceFormStore from '../../containers/Form/stores/ResourceFormStore';
import formOverlayListStyles from './formOverlayList.scss';
import ErrorSnackbar from './ErrorSnackbar';

@observer
export default class FormOverlayList extends React.Component<ViewProps> {
    static getDerivedRouteAttributes = List.getDerivedRouteAttributes;

    listRef: ?ElementRef<typeof List>;
    formRef: ?ElementRef<typeof Form>;

    @observable formStore: ?ResourceFormStore;
    @observable formErrors = [];

    handleItemAdd = () => {
        this.createFormOverlay(undefined);
    };

    handleItemClick = (itemId: string | number) => {
        this.createFormOverlay(itemId);
    };

    handleFormOverlayConfirm = () => {
        if (!this.formRef) {
            throw new Error('The Form ref has not been set! This should not happen and is likely a bug.');
        }

        this.formRef.submit();
    };

    handleFormOverlayClose = () => {
        this.destroyFormOverlay();
    };

    handleFormSubmit = () => {
        if (!this.formStore) {
            throw new Error('The FormStore has not been initialized! This should not happen and is likely a bug.');
        }

        this.formStore.save()
            .then(() => {
                this.destroyFormOverlay();
                if (this.listRef) {
                    this.listRef.listStore.sendRequest();
                }
            })
            .catch(action((error) => {
                this.formErrors.push(error);
            }));
    };

    @action handleErrorSnackbarClose = () => {
        this.formErrors.pop();
    };

    @action createFormOverlay = (itemId: ?string | number) => {
        const {
            router: {
                attributes,
                route: {
                    options: {
                        apiOptions = {},
                        formKey,
                        resourceKey,
                        routerAttributesToFormStore = {},
                    },
                },
            },
        } = this.props;

        if (this.formStore) {
            this.formStore.destroy();
        }

        const observableOptions = {};
        if (this.listRef && this.listRef.locale && this.listRef.locale.get()) {
            observableOptions.locale = this.listRef.locale;
        }

        const formStoreOptions = this.buildFormStoreOptions(apiOptions, attributes, routerAttributesToFormStore);
        const resourceStore = new ResourceStore(resourceKey, itemId, observableOptions, formStoreOptions);
        this.formStore = new ResourceFormStore(resourceStore, formKey, formStoreOptions);
    };

    @action destroyFormOverlay = () => {
        this.formErrors = [];

        if (this.formStore) {
            this.formStore.destroy();
            this.formStore = undefined;
        }
    };

    buildFormStoreOptions(
        apiOptions: Object,
        attributes: Object,
        routerAttributesToFormStore: {[string | number]: string}
    ) {
        const formStoreOptions = apiOptions ? apiOptions : {};

        routerAttributesToFormStore = toJS(routerAttributesToFormStore);
        Object.keys(routerAttributesToFormStore).forEach((key) => {
            const attributeName = routerAttributesToFormStore[key];
            const formOptionKey = isNaN(key) ? key : routerAttributesToFormStore[key];

            formStoreOptions[formOptionKey] = attributes[attributeName];
        });

        return formStoreOptions;
    }

    setFormRef = (formRef: ?ElementRef<typeof Form>) => {
        this.formRef = formRef;
    };

    setListRef = (listRef: ?ElementRef<typeof List>) => {
        this.listRef = listRef;
    };

    componentWillUnmount() {
        this.destroyFormOverlay();
    }

    render() {
        const {
            router: {
                route: {
                    options: {
                        addOverlayTitle,
                        editOverlayTitle,
                        formKey,
                    },
                },
            },
        } = this.props;

        const overlayTitle = this.formStore && this.formStore.id
            ? translate(editOverlayTitle || 'sulu_admin.edit')
            : translate(addOverlayTitle || 'sulu_admin.create');

        return (
            <Fragment>
                <List
                    {...this.props}
                    onItemAdd={formKey && this.handleItemAdd}
                    onItemClick={formKey && this.handleItemClick}
                    ref={this.setListRef}
                />
                {!!this.formStore &&
                    <Overlay
                        confirmDisabled={!this.formStore.dirty}
                        confirmLoading={this.formStore.saving}
                        confirmText={translate('sulu_admin.save')}
                        onClose={this.handleFormOverlayClose}
                        onConfirm={this.handleFormOverlayConfirm}
                        open={!!this.formStore}
                        size="small"
                        title={overlayTitle}
                    >
                        <div className={formOverlayListStyles.form}>
                            <ErrorSnackbar
                                onCloseClick={this.handleErrorSnackbarClose}
                                visible={!!this.formErrors.length}
                            />
                            <Form
                                onSubmit={this.handleFormSubmit}
                                ref={this.setFormRef}
                                store={this.formStore}
                            />
                        </div>
                    </Overlay>
                }
            </Fragment>
        );
    }
}