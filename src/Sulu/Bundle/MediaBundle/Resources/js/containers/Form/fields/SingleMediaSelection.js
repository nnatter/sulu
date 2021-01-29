// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import userStore from 'sulu-admin-bundle/stores/userStore';
import {computed, observable} from 'mobx';
import {
    convertDisplayOptionsFromParams,
    convertMediaTypesFromParams,
    validateDisplayOption,
} from '../../../utils/MediaSelectionHelper';
import SingleMediaSelectionComponent from '../../SingleMediaSelection';
import type {Media} from '../../../types';
import type {Value} from '../../SingleMediaSelection';

@observer
class SingleMediaSelection extends React.Component<FieldTypeProps<Value>> {
    constructor(props: FieldTypeProps<Value>) {
        super(props);

        const {onChange, schemaOptions} = this.props;

        const {
            defaultDisplayOption: {
                value: defaultDisplayOption,
            } = {},
        } = schemaOptions;

        if (!defaultDisplayOption) {
            return;
        }

        if (typeof defaultDisplayOption !== 'string' || !validateDisplayOption(defaultDisplayOption)) {
            throw new Error(
                'The children of "defaultDisplayOption" contains the invalid value "'
                + (defaultDisplayOption.toString() + '') + '".'
            );
        }

        if (this.value === undefined) {
            onChange({id: undefined, displayOption: defaultDisplayOption});
        }
    }

    @computed get value(): ?Value {
        const {value} = this.props;

        if (value && typeof value !== 'object') {
            throw new Error(
                'The "SingleMediaSelection" field expects an object with an "id" property and '
                + 'an optional "displayOption" property as value.'
            );
        }

        return value;
    }

    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    handleItemClick = (itemId: ?number, item: ?Media) => {
        const {router} = this.props;

        if (!router || !item) {
            return;
        }

        const {id, locale} = item;

        router.navigate('sulu_media.form', {id, locale});
    };

    render() {
        const {disabled, error, formInspector, schemaOptions} = this.props;
        const {
            displayOptions: {
                value: displayOptions,
            } = {},
            types: {
                value: mediaTypes,
            } = {},
        } = schemaOptions;
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        if (displayOptions !== undefined && displayOptions !== null && !Array.isArray(displayOptions)) {
            throw new Error('The "displayOptions" option has to be an Array if set.');
        }

        const displayOptionValues = convertDisplayOptionsFromParams(displayOptions);

        if (mediaTypes !== undefined && mediaTypes !== null && typeof mediaTypes !== 'string') {
            throw new Error('The "types" option has to be a string if set.');
        }

        const mediaTypeValues = convertMediaTypesFromParams(mediaTypes);

        return (
            <SingleMediaSelectionComponent
                disabled={!!disabled}
                displayOptions={displayOptionValues}
                locale={locale}
                onChange={this.handleChange}
                onItemClick={this.handleItemClick}
                types={mediaTypeValues}
                valid={!error}
                value={this.value ? this.value : undefined}
            />
        );
    }
}

export default SingleMediaSelection;
