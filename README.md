# DYMO Labels

A REDCap external module that provides action tags and plugin pages to enable printing of labels on DYMO LabelWriter printers.

## Requirements

- REDCAP 10.0.1 or newer (tested with REDCap 10.9.3).

## Installation

- Automatic:
  - Find this module in the REDCap External Modules Repository and click the download button.

- Manual:
  - Clone this repo into `<redcap-root>/modules/dymo_labels_v<version-number>`.
  - Go to _Control Center > Technical / Developer Tools > External Modules_ and enable DYMO Labels.

## Configuration

Module behavior is controlled via External Module settings and labels are set up and managed on the **DYMO Labels** plugin page.

### System-level settingss

- _Block anonymous access to public plugin pages_ - When enabled, access to the plugin page that allows printing of labels set to be accessible in this manner on the  DYMO Labels setup page (see below) is blocked.
- _Disable POST endpoints (see docs)_ - When enabled, all access to the POST endpoint is blocked. More information about the POST endpoint can be found at the end of this README.

### Project-level settings

- _Output debug information to the browser console_ - When enabled, information useful for troubleshooting is output to the browser console. This option should be disabled in production projects.
- _Simulate printing (i.e. do not actually print)_ - When enabled, no data is sent to the printer. This is useful for setting up / testing labels.
- _Allow anonymous access to the plugin page_ - When enabled, individual labels can be made accessible via a public GET endpoint. See the in-page documentation on the DYMO Labels plugin page for more information.
- _Enable the POST endpoint (see docs)_ - Enables the POST endpoint. More information about the POST endpoint can be found at the end of this README.
- _Show the plugin-page link_ - When enabled, the **DYMO Labels** link is shown in the _External Modules_ section of the project main menu.
- _Allow users to download label files_ - When enabled, users with access to the DYMO Labels setup page can download label files.
- _Allow the auto print flag for public labels_ - When enabled, automatic printing can be used with the public GET endpoint.

### DYMO Labels plugin page


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
    "id": "xxxx-xxxx-xxxx",
    "button": "Button Label",
    "style": "bottom-margin:0.5rem;", // optional style that is added to <button>
    "class": "class1 class2", // optional classes that are added to <button>
    "target": "css-selector", // optional; default = appended to the label portion of the current field
    "range": "COL:A-C,ROW:1-2", // optional range definitions
    "data": {
        "NAME": "[lastname], [firstname]",
        "DOB": "[dob]",
        "STORE": "Location: {COL}{ROW}",
        ...
    },
    "auto": true | false
}
```

- `id`: The label ID as displayed in the label info or other various dialogs.
- `button`: The button label of the DYMO Labels widget (optional; default: 'Print label').
- `style`: CSS Style to be added directly to the `<button>` element (optional; default: 'bottom-margin:0.5rem;').
- `class`: CSS classes to be added to the `<button>` element (optional).
- `target`: A CSS selector. The print widget will be displayed in the first matching element. This is optional. When not specified, the widget is added to the label portion of the field with the action tag.
- `range`: Range definition(s). A comma-separated list of ranges. Ranges are defined with a label, a colon, and a numeric or alphabetic range. E.g. `COPY:1-3` defines a range named "COPY" that runs from 1 to 3 inclusive; `LETTER:A-B` represents the range of the letters A thru B. By inserting `{COPY}` or `{LETTER}` into the value for a label object, this will be replaced. When both these ranges were used together, they would expand to A1, A2, A3, B1, B2, B3 when written as `{LETTER}{COPY}`, resulting in 6 labels total.
- `data`: The data used to fill the label objects, given as key-value pairs. Keys are the names of the label object as shown/defined in the label configuration. Values are strings; piping is supported. Values will be transformed as configured for the label.
- `auto`: Determines, whether printing starts automatically. Default: `false`.

The action tag parameter must be valid JSON. The plugin page provides templates for labels that can be copy/pasted.

## Changelog

Version | Description
------- | -----------------------
v0.0.1  | Still in development.
