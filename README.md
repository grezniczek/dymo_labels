# DYMO Labels

An external module integrating DYMO label printers with REDCap.

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

## Action Tags

@DYMO-LABEL="id"
Adds a widget to the label portion that, when clicked, initiates printing of a label (shows a preview).

## Effect

Provides action tags and plugin pages to facilitate printing of labels using DYMO LabelWriter printers.
