/* eslint-disable flowtype/require-valid-file-annotation */
import {mount} from 'enzyme';
import React from 'react';
import pretty from 'pretty';
import Select from '../Select';
import Option from '../Option';

const Divider = Select.Divider;

afterEach(() => document.body.innerHTML = '');

test('The component should render with the popover closed', () => {
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const onSelect = jest.fn();
    const select = mount(
        <Select
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(document.body.innerHTML).toBe('');
});

test('The component should render with a dark skin', () => {
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const onSelect = jest.fn();
    const select = mount(
        <Select
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
            skin="dark"
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(document.body.innerHTML).toBe('');
});

test('The component should render with an icon', () => {
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const onSelect = jest.fn();
    const select = mount(
        <Select
            displayValue="My text"
            icon="su-plus"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(document.body.innerHTML).toBe('');
});

test('The component should render when disabled', () => {
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const onSelect = jest.fn();
    const select = mount(
        <Select
            disabled={true}
            displayValue="My text"
            icon="su-plus"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    expect(select.render()).toMatchSnapshot();
    expect(document.body.innerHTML).toBe('');
});

test('The component should open the popover when the display value is clicked', () => {
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const onSelect = jest.fn();
    const select = mount(
        <Select
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.instance().displayValueRef = {
        getBoundingClientRect: jest.fn().mockReturnValue({
            width: 200,
        }),
    };
    select.find('.displayValue').simulate('click');

    expect(select.render()).toMatchSnapshot();
    expect(pretty(document.body.innerHTML)).toMatchSnapshot();
});

test('The component should not open the popover on display-value-click when disabled', () => {
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const onSelect = jest.fn();

    const select = mount(
        <Select
            disabled={true}
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.find('.displayValue').simulate('click');

    expect(select.render()).toMatchSnapshot();
    expect(pretty(document.body.innerHTML)).toMatchSnapshot();
});

test('The component should trigger the select callback and close the popover when an option is clicked', () => {
    const onSelect = jest.fn();
    const isOptionSelected = jest.fn().mockReturnValue(false);
    const select = mount(
        <Select
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.instance().handleDisplayValueClick();
    document.body.getElementsByTagName('button')[2].click();
    expect(onSelect).toHaveBeenCalledWith('option-3');
    expect(document.body.innerHTML).toBe('');
});

test('The component should pass the centered child node to the popover', () => {
    const onSelect = jest.fn();
    const isOptionSelected = jest.fn((child) => child.props.value === 'option-3');
    const selectedOption = (<Option value="option-3">Option 3</Option>);
    const select = mount(
        <Select
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            {selectedOption}
        </Select>
    );

    const popover = select.find('Popover');
    expect(popover.props().centerChildNode).toBe(mount(selectedOption).get(0).innerHTML);
});

test('The component should pass the selected property to the options', () => {
    const isOptionSelected = jest.fn().mockReturnValue(true);
    const onSelect = jest.fn();
    const select = mount(
        <Select
            displayValue="My text"
            isOptionSelected={isOptionSelected}
            onSelect={onSelect}
        >
            <Option value="option-1">Option 1</Option>
            <Option value="option-2">Option 2</Option>
            <Divider />
            <Option value="option-3">Option 3</Option>
        </Select>
    );
    select.instance().handleDisplayValueClick();
    expect(document.body.querySelectorAll('.selected').length).toBe(3);
});
