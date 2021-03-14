# DYMO Labels

An external module that provides action tags and plugin pages to enable printing of labels using DYMO LabelWriter printers.

## Requirements

- REDCAP 9.5.0 or newer (tested with REDCap 9.7.2).

## Installation

- Clone this repo into `<redcap-root>/modules/dymo_labels_v<version-number>`.
- Go to _Control Center > Technical / Developer Tools > External Modules_ and enable DYMO Labels.

## Configuration

Via "DYMO Labels" link in the left-side menu.

- Add
- Remove
- Configure
- View / Ad-hoc Print
- Duplicate

Congifure =

- transformation actions (DM, QR, upper/lower case, prefix/suffix, show/hide)
- default number of copies
- widget style (text, image, both)

## Action Tag: @DYMO-LABEL

Usage:

```JS
@DYMO-LABEL={
    'id': 'xxxx-xxxx-xxxx',
    'button': 'Button Label',
    'target': 'css-selector', // optional; default = appended to the label portion of the current field
    'data': {
        'LABELOBJECT1': 'Name: [firstname] [lastname]',
        'LABELOBJECT2': '[dob]',
        ...
    }
}
```

- `id`: The label ID as displayed in the label info or other various dialogs.
- `button`: The button label of the DYMO Labels widget.
- `target`: A CSS selector. The print widget will be displayed in the first matching element. This is optional. When not specified, the widget is added to the label portion of the field with the action tag.
- `data`: The data used to fill the label objects, given as key-value pairs. Keys are the names of the label object as shown/defined in the label configuration. Values are strings; piping is supported. Values will be transformed as configured for the label.

The action tag parameter must be valid JSON. The plugin page provides templates for labels that can be copy/pasted.

## Changelog

Version | Description
------- | -----------------------
v0.0.1  | Still in development.
