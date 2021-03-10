/* DYMO Label EM - Project Configuration */
// @ts-check
;(function() {

//#region Variables

// @ts-ignore
var EM = window.ExternalModules
if (typeof EM == 'undefined') {
    /** @type {ExternalModules} */
    EM = {}
    // @ts-ignore
    window.ExternalModules = EM
}

/** @type {DYMOLabelConfig} */
var config;

/** @type {AddLabelState} */
var addState = {
    name: '', 
    desc: '', 
    filename: '', 
    xml: '',
    config: {},
    valid: false
}

/** @type {DataTables.Api} */
var labelsTable;

/** @type {DYMOLabelFramework_PrinterInfo[]} */
var printers = []

/** @type {boolean} Indicates whether a print process is currently ongoing */
var printing = false

//#endregion


//#region Add a label

/**
 * Adds a new label.
 */
function addNewLabel() {
    dialog('#modal-addNew', null, function($modal, verb) {
        return new Promise(function(resolve, reject) {
            checkAddState()
            if (verb == 'add' && addState.valid) {
                addState.config = generateInitialConfig()
                submitData('add-label', addState)
                .then(function(response) {
                    resolve(response)
                })
                .catch(function(err) {
                    reject(err)
                })
            }
            else {
                resolve(verb)
            }
        })
    })
    .then(function(response) {
        if (response['success']) {
            addLabelData(response)
        }
        clearAddState()
    })
    .catch(function(err) {
        dialog('#modal-error', {
            error: err
        })
    })
}

/**
 * Adds a label row.
 * @param {AddLabelResponse} response 
 */
function addLabelData(response) {
    // Add label
    /** @type LabelData */
    var label = {
        id: response.id,
        name: addState.name,
        desc: addState.desc,
        filename: addState.filename,
        xml: addState.xml,
        config: addState.config
    }
    config.labels[label.id] = label
    labelsTable.rows.add([label])
    labelsTable.draw()
}

function clearAddState() {
    // Reset dialog
    addState.xml = ''
    addState.name = ''
    addState.desc = ''
    addState.filename = ''
    addState.config = {}
    checkAddState()
    $('#modal-addNew [data-input-control]').val('')
    fileChanged()
}

/**
 * @returns {LabelConfig}
 */
function generateInitialConfig() {
    /** @type {Object<string, LabelObjectInfo>} */
    var objects = {}
    var label = DLF.openLabelXml(addState.xml)
    var list = label.getObjectNames()
    $(label._doc).find('Name').each(function() {
        var name = this.textContent
        if (list.includes(name)) {
            var isText = this.parentNode.nodeName == 'TextObject'
            objects[name] = {
                default: label.getObjectText(name),
                transform: isText ? 'T' : 'PNG',
                type: isText ? 'Text' : 'Graphic',
                readOnly: false,
                multiline: false,
            }
        }
    })
    return {
        public: false,
        objects: objects
    }
}

/**
 * Controls the "Add new label" dialog UI state.
 */
function checkAddState() {
    addState.name = $('#dlem-name').val().toString()
    addState.desc = $('#dlem-desc').val().toString()
    
    addState.valid = 
        addState.name.length > 0 &&
        addState.xml.length > 0;

    if (addState.xml.length > 0 && addState.name.length == 0) {
        $('#dlem-name').addClass('is-invalid').attr('title', config.strings.nameRequired)
    }
    else {
        $('#dlem-name').removeClass('is-invalid').attr('title','')
    }

    if (addState.filename.length > 0 && addState.xml.length == 0) {
        $('#dlem-labelfile').addClass('is-invalid')
        $('#dlem-labelfile-invalid').show()
        $('#dlem-labelfile-valid').hide()
    }
    else {
        $('#dlem-labelfile').removeClass('is-invalid')
        $('#dlem-labelfile-invalid').hide()
    }
    if (addState.filename.length == 0) {
        $('#dlem-labelfile-valid').hide()
        $('#dlem-labelfile').removeClass('is-invalid')
        $('#dlem-labelfile-invalid').hide()
    }
    if (addState.xml.length > 0) {
        $('#dlem-labelfile-valid').show()
    }
}

/**
 * Verifies a label file.
 * @param {string} xml 
 * @returns {boolean}
 */
function verifyLabel(xml) {
    try {
        // Verify this is a DYMO Label file
        var label = DLF.openLabelXml(xml)
        if (label.isValidLabel()) {
            log('Label verified:', label.getObjectNames())
            return true
        }
        else {
            log('Unable to verify label file')
        }
    }
    catch(e) {
        log('Unable to verify label file: Invalid XML.')
    }
    return false
}

/**
 * Gets called when a label file has been loaded
 * @param {string} name 
 * @param {string} xml 
 */
function labelFileLoaded(name, xml) {
    addState.filename = name
    log('Loaded file: ' + name, 'Length: ' + xml.length)
    // Check file
    addState.xml = verifyLabel(xml) ? xml : ''
    checkAddState()
}

/**
 * Gets called when the file in a file input changes.
 */
function fileChanged() {
    var $fileInput = $('#dlem-labelfile')
    /** @type File[] */
    var files = $fileInput.prop('files')
    var name = config.strings.chooseFile
    addState.filename = ''
    if (files.length > 0) {
        var file = files[0]
        name = file.name
        var reader = new FileReader();
        reader.addEventListener('load', function(e) {
            labelFileLoaded(name, e.target.result.toString())
        })
        reader.readAsText(file)
    }
    $fileInput.siblings().first().text(name)
    checkAddState()
}

//#endregion


//#region Configure a label

/**
 * Configure a label.
 * @param {LabelData} label 
 */
function configureLabel(label) {
    log('Configure label: ' + label.id)
    dialog('#modal-config', {
        name: label.name,
        id: label.id,
        desc: label.desc,
    })
    .then(function(verb) {
        log('Config: ' + verb)
    })
    .catch(function(err) {
        logError(err)
    })
}

//#endregion


//#region Download a label file

/**
 * Download a label file.
 * @param {LabelData} label 
 */
function downloadLabel(label) {
    log('Download label: ' + label.id)
    var blob = new Blob([label.xml], { type: 'application/xml' })
    var url = URL.createObjectURL(blob)
    var a = document.createElement('a')
    a.href = url
    a.download = label.filename || (label.id + '.xml')
    var clickHandler = function() {
      setTimeout(function() {
        URL.revokeObjectURL(url)
        this.removeEventListener('click', clickHandler)
      }, 150)
    }
    a.addEventListener('click', clickHandler, false);
    a.click();
}

//#endregion


//#region Delete a label

/**
 * Shows the delete label confirmation
 * @param {LabelData} label 
 */
function deleteLabel(label) {
    dialog('#modal-delete', {
        name: label.name,
        id: label.id
    }, function($modal, verb) {
        return new Promise(function(resolve, reject) {
            if (verb == 'confirm') {
                submitData('delete-label', label.id)
                .then(function() {
                    resolve(verb)
                })
                .catch(function(err) {
                    reject(err)
                })
            }
            else {
                resolve(verb)
            }
        })
    })
    .then(function(verb) {
        if (verb == 'confirm') {
            // Delete the label from the internal store and update the table
            delete config.labels[label.id]
            labelsTable.clear()
            labelsTable.rows.add(getTableData())
            labelsTable.draw()
        }
    })
    .catch(function(err) {
        // Show an error notification
        dialog('#modal-error', {
            error: err
        })
    })
}

//#endregion


//#region Print a label

/**
 * Print a label.
 * @param {LabelData} label 
 */
function printLabel(label) {
    dialog('#modal-error', { 
        error: 'Not implemented yet.' 
    })
}

//#endregion


//#region UI & Ajax Helper

/**
 * Logs an error to the console
 * @param {string} msg 
 */
function logError(msg) {
    var prompt = 'DYMO Label EM - '
    console.error(prompt + msg)
}

/**
 * Log to the console when in debug mode
 */
function log() {
    if (!config.debug) return
    var ln = '??'
    try {
        var line = (new Error).stack.split('\n')[2]
        var parts = line.split(':')
        ln = parts[parts.length - 2]
    }
    catch { }
    var prompt = 'DYMO Label EM [' + ln + ']'
    switch(arguments.length) {
        case 1: 
            console.log(prompt, arguments[0])
            break
        case 2: 
            console.log(prompt, arguments[0], arguments[1])
            break
        case 3: 
            console.log(prompt, arguments[0], arguments[1], arguments[2])
            break
        case 4: 
            console.log(prompt, arguments[0], arguments[1], arguments[2], arguments[3])
            break
        case 5: 
            console.log(prompt, arguments[0], arguments[1], arguments[2], arguments[3], arguments[4])
            break
        default: 
            console.log(prompt, arguments)
            break
    }
}

/**
 * 
 * @param {string} selector 
 * @param {object} content 
 * @param {function(JQuery, string):Promise} action 
 */
function dialog(selector, content, action = null) {
    return new Promise(function(resolve, reject) {
        // Prepare dialog
        var $modal = $(selector)
        $modal.find('[data-modal-content]').each(function(index, element) {
            var item = element.getAttribute('data-modal-content')
            element.innerHTML = content[item]
        })
        var enable = function(enabled = true) {
            // Enable or disable all action buttons
            // and show/hide busy content
            $modal.find('[data-modal-action]').each(function() {
                var $btn = $(this)
                $btn.prop('disabled', !enabled)
                if (enabled) {
                    $btn.find('.when-disabled').hide()
                    $btn.find('.when-enabled').show()
                }
                else {
                    $btn.find('.when-disabled').show()
                    $btn.find('.when-enabled').hide()
                }
            })
        }
        var hide = function() {
            $modal.modal('hide')
            $modal.off('click', clickHandler)
            enable()
        }
        var done = function(result) {
            hide()
            resolve(result)
        }
        var error = function(err) {
            hide()
            reject(err)
        }
        /** @type {JQuery.EventHandlerBase<HTMLElement, JQuery.ClickEvent<HTMLElement, undefined, HTMLElement, HTMLElement>>} */
        var clickHandler = function(e) {
            var $btn = e.target.hasAttribute('data-modal-action') ? 
                $(e.target) : $(e.target).parents('[data-modal-action]').first()
            var verb = $btn.attr('data-modal-action')
            if (verb != undefined) {
                try {
                    if (action) {
                        // Disable all buttons and set spinner
                        enable(false)
                        // Perform action
                        action($modal, verb)
                        .then(function(result) {
                            done(result)
                        })
                        .catch(function(err) {
                            error(err)
                        })
                    }
                    else {
                        done(verb)
                    }
                }
                catch (err) {
                    error(err)
                } 
            }
        }
        enable()
        $modal.on('click', clickHandler)
        $modal.modal('show')
    })
}

/**
 * Sends an ajax request to the server
 * @param {string} action
 * @param {object} payload
 * @returns {Promise}
 */
function submitData(action, payload) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            method: 'POST', 
            url: config.ajax.endpoint,
            data: {
                verification: config.ajax.verification,
                action: action, 
                payload: JSON.stringify(payload),
            },
            dataType: "json",
            success: function(response) {
                if (response['success']) {
                    log('Successful server request:', response)
                    config.ajax.verification = response['verification']
                    resolve(response)
                }
                else {
                    log('Unsuccessful server request:', response)
                    reject(response.error)
                }
            },
            error: function(jqXHR, error) {
                log('Ajax error:', error, jqXHR)
                reject(error)
            }
        })
    })
}

//#endregion


//#region Setup

EM.DYMOLabelConfig_init = function(data) {
    
    config = data
    log('Config initialized', config)
    // @ts-ignore
    DLF = dymo.label.framework
    DLF.init()

    checkAddState()
    $('#dlem-labelfile').on('change', function() {
        fileChanged()
    })
    $('#dlem-name').on('change', function() {
        checkAddState()
    })
    $('[data-command="add-new-label"]').on('click', addNewLabel)

    // Setup the labels table.
    labelsTable = $('#dlem-labels').DataTable({
        columns: [
            {
                data: 'name'
            },
            {
                data: 'desc'
            },
            {
                data: 'id',
                render: function(data, type) {
                    if (type == 'display') {
                        return renderLabelActions(data)
                    }
                    return data
                }
            }
        ],
        
        createdRow: function(row, data, index) {
            var $buttons = $('[data-dlem-action]', row)
            $buttons.on('click', function(e) {
                var action = e.currentTarget.getAttribute('data-dlem-action')
                handleLabelActions(action, data['id'])
            })
        },
        data: getTableData()
    })


    // Setup some usability enhancements.
    // @ts-ignore
    $('textarea.autosize').textareaAutoSize()
}

EM.DYMOLabelPrint_init = function(data) {
    
    config = data
    log('Print page initializing:', config)
    // @ts-ignore
    DLF = dymo.label.framework
    DLF.init()

    setTimeout(function() {
        var status = DLF.checkEnvironment()
        log('DYMO Framework Status:', status)
    
        if (status.isBrowserSupported && 
            status.isFrameworkInstalled && 
            status.isWebServicePresent) {
            
            setupPrinters()
            .then(function() {
                setupLabels()
    
                $('[data-command=refresh]').on('click', setupPrinters)
                $('[data-command=print]').on('click', printLabels)
                $('[data-command=calibrate]').on('click', calibrate)
                $('[data-command="print-single"]').on('click', function(e) {
                    printSingleLabel(Number.parseInt(e.currentTarget.getAttribute('data-label')))
                })
                $('input[name=printer]').on('change', selectPrinter)

    
                setUIState()
    
                $('.initialized').show(200)
                $('.initializing').hide(200)
                if (config.print.auto) {
                    printLabels()
                }
            })
            config.print.errors.forEach(function(err) {
                $('#error').append('<p>' + err + '</p>')
            })
        }
        else {
            $('#error').html(status.errorDetails)
            $('.initializing').hide(200)
        }
    }, 100);
}

/**
 * Renders the action buttons for the labels table.
 * @param {string} id 
 */
function renderLabelActions(id) {
    var buttons = 
        '<button class="btn btn-xs btn-primary" data-dlem-action="configure"><i class="fas fa-wrench"></i> ' + config.strings.actionConfigure + '</button> ' +
        '<button class="btn btn-xs btn-secondary" data-dlem-action="print"><i class="fas fa-print"></i> ' + config.strings.actionPrint + '</button> '
    if (config.canDownload) {
        buttons += 
        '<button class="btn btn-xs btn-secondary" data-dlem-action="download" title="' + config.strings.actionDownload + '"><i class="fas fa-file-download"></i></button> '
    }
    buttons += 
        '| <button class="btn btn-xs btn-danger" data-dlem-action="delete" title="' + config.strings.actionDelete + '"><i class="far fa-trash-alt"></i></button>'
    return buttons
}

//#endregion

//#region Setup Helpers

/**
 * Handles the click of a labels table action buttons.
 * @param {string} action 
 * @param {string} id 
 */
function handleLabelActions(action, id) {
    switch(action) {
        case "configure":
            configureLabel(config.labels[id])
            break
        case 'download':
            downloadLabel(config.labels[id])
            break
        case 'print': 
            printLabel(config.labels[id])
            break
        case 'delete':
            deleteLabel(config.labels[id])
            break
        default:
            log('Invalid action: \'' + action + '\' on id \'' + id + '\'.')
            break
    }
}

/**
 * Prepares the data for the labels table.
 */
function getTableData() {
    var labels = []
    Object.keys(config.labels).forEach(function(id) {
        var label = config.labels[id]
        var row = {
            id: label.id,
            name: label.name,
            desc: label.desc
        }
         labels.push(row)
    })
    return labels
}

//#endregion

//#region Print Preview & Print

/** @type {DYMOLabelFramework_PrinterInfo} */
var selectedPrinter = null

/** @type DYMOLabelFramework */
var DLF = null

/**
 * Sets the UI state (highlights, messages, enable/disable buttons)
 */
function setUIState() {
    if (selectedPrinter == null && printers.length < 1) {
        $('tr.no-printer').show()
        $('.printers-card').addClass('border-danger')
    }
    else {
        $('tr.no-printer').hide()
        $('.printers-card').removeClass('border-danger')
    }
    if (config.print.labels.length == 0) {
        $('tr.no-labels').show()
        $('.labels-card').addClass('border-danger')
    }
    else {
        $('tr.no-labels').hide()
        $('.labels-card').removeClass('border-danger')
    }
    $('[data-command=calibrate').prop('disabled', 
        selectedPrinter == null || 
        config.labels[config.print.template] == undefined)

    // Count labels
    var n = 0
    $('input[data-label-include]').each(function() {
        if ($(this).prop('checked')) n++
    })
    if (n == 0) {
        $('[data-command="select-all"]').prop('checked', false)
    }
    $('[data-command=print]').prop('disabled', 
        printing ||
        selectedPrinter == null || 
        config.print.labels.length == 0 || 
        config.labels[config.print.template] == undefined ||
        n == 0)
    $('[data-command=preview]').prop('disabled', 
        selectedPrinter == null || 
        config.print.labels.length == 0 || 
        config.labels[config.print.template] == undefined)
    $('[data-command="print-single"]').prop('disabled', 
        selectedPrinter == null || 
        config.print.labels.length == 0 || 
        config.labels[config.print.template] == undefined)
}
/**
 * Selects a printer
 * @param {JQuery.ChangeEvent} e 
 */
function selectPrinter(e) {
    var idx = $(e.target).val().toString()
    log('Selected printer #' + idx)
    selectedPrinter = printers[idx]
    setUIState()
}

/**
 * Sets up the printer selection UI (initially and after refresh)
 */
function setupPrinters() {
    return new Promise(function(resolve, reject) {
        printers = DLF.getPrinters()
        selectedPrinter = null
        
        // Clone template and clear table
        var $table = $('table.printers')
        $('tr.no-printer').show()
        $table.find('tr.printer').remove()
        if (printers.length) {
            for (var i = 0; i < printers.length; i++) {
                var printer = printers[i]
                printer.listIndex = i.toString()
                var $row = $table.find('tr.printer-template').clone()
                $row.removeClass('printer-template').addClass('printer')
                $row.css('display', 'none')
                var active = selectedPrinter == null && printer.isConnected
                if (active) {
                    selectedPrinter = printers[i]
                }
                $row.find('input[name=printer]')
                    .prop('id', 'printer-' + i)
                    .prop('checked', active)
                    .val(i)
                $row.find('label.printer-name')
                    .attr('for', 'printer-' + i)
                    .text(printer.name)
                if (printer.isTwinTurbo) {
                    $row.find('input.printer-roll-left')
                        .attr('name', 'printer-roll-' + i)
                        .attr('id', 'printer-roll-left-' + i)
                        .prop('checked', true)
                    $row.find('label.printer-roll-left').attr('for', 'printer-roll-left-' + i)
                    $row.find('input.printer-roll-right')
                        .attr('name', 'printer-roll-' + i)
                        .attr('id', 'printer-roll-right-' + i)
                    $row.find('label.printer-roll-right').attr('for', 'printer-roll-right-' + i)
                }
                else {
                    $row.find('span.twinturbo').hide()
                }
                if (printer.isConnected) {
                    $row.find('span.printer-offline').hide()
                }
                $table.append($row)
            }
        }
        var done = function() {
            $table.find('tr.printer').show()
            setUIState()
            log('Printers found:', printers)
            resolve()
        }
        getPrinterCalibration().then(done, done)
    })
}

/**
 * Renders the labels table
 */
function setupLabels() {

    if (config.print.labels.length < 1) return

    /** @type {DYMOLabelItem[]} */
    var cols = []
    /** @type {DYMOLabelItem[][]} */
    var rows = []
    for (var i = 0; i < config.print.labels.length; i++) {
        rows[i] = []
        var items = config.print.labels[i]
        for (var j = 0; j < items.length; j++) {
            var label = items[j]
            switch (label.type) {
                case 'QR':
                    label.png = generateBarcode(label.value, 'qrcode')
                    break
                case 'DM':
                    label.png = generateBarcode(label.value, 'datamatrix')
                    break
                default:
                    label.value = label.value.replace('\\n', '\n')
                    break
            }
            if (i == 0) {
                cols[j] = label
            }
            rows[i][j] = label
        }
    }

    // Render table
    // Header row
    var $row = $('<tr></tr>')
    $row.append('<th><input type="checkbox" data-command="select-all" checked="checked"></th>')
    $row.append('<th><i class="far fa-eye"></i></th>')
    for (var col = 0; col < cols.length; col++) {
        $row.append('<th scope="col">' + cols[col].name + '</th>')
    }
    $('.labels-header').append($row)
    $('[data-command="select-all"]').on('change', function() {
        var all = $('input[data-command="select-all"]').prop('checked')
        $('input[data-label-include]').prop('checked', all)
        setUIState()
    })
    // Label rows
    for (var row = 0; row < rows.length; row++) {
        $row = $('<tr class="label" data-label="' + row + '"></tr>')
        $row.append('<th scope="row"><input type="checkbox" data-label-include="' + row + '" checked="checked"></th>')
        $row.append('<td><button data-command="preview" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></button></td>')
        for (var col = 0; col < cols.length; col++) {
            var $cell = $('<td></td>')
            switch(cols[col].type) {
                case 'T':
                    var $pre = $('<pre></pre>')
                    $pre.text(rows[row][col].value)
                    $cell.append($pre)
                    break
                case 'R':
                    var $span = $('<span class="removed"></span')
                    $span.html(config.strings.removed)
                    $cell.append($span)
                    break
                case 'DM':
                case 'QR':
                    var $img = $('<img src="data:image/png;base64, ' + rows[row][col].png + '" />')
                    $cell.append($img)
                    break
            }
            $row.append($cell)
        }
        $row.on('click', function(e) {
            if (e.target.hasAttribute('data-label-include')) {
                // Checkbox
            }
            else if (e.target.hasAttribute('data-command') || $(e.target).parents('[data-command]').length) {
                // Preview button
                previewLabel(Number.parseInt(e.currentTarget.getAttribute('data-label')))
            }
            else {
                // Somewhere in row
                var $chk = $(e.currentTarget).find('[data-label-include]')
                $chk.prop('checked', !$chk.prop('checked'))
            }
            setUIState()
        })
        $('.labels-body').append($row)
    }
}

/**
 * Renders a QRcode or DataMatrix 
 * @param {string} val 
 * @param {string} type 
 * @param {string} rot 
 * @returns {string}
 */
function generateBarcode(val, type, rot = 'N') {
    if (!val) return '';
    if (!type) type = 'datamatrix';
    if (!(type == 'datamatrix' || type == 'qrcode'))
    {
        throw 'Unsupported barcode type.'
    }
    switch (rot) {
        case "N":
        case "L":
        case "R":
        case "I":
            break;
        default:
            rot = "N";
            break;
    }

    var options = {
        scale: 2,
        rotate: rot,
        padding: 0,
        backgroundcolor: 'FFFFFF',
        bcid: type,
        text: val,
        includetext: false,
    }

    var canvas = document.createElement('canvas')
    try {
        // @ts-ignore
        bwipjs.toCanvas(canvas, options)
        var dataUrl = canvas.toDataURL('image/png')
        // Remove 'data:image/png;base64,'
        var png = dataUrl.substr(dataUrl.indexOf(',') + 1).trim()
        return png
    }
    catch (e) {
        log ('Failed to generate barcode:', e)
    }
    return val
}

/**
 * Generates an empty PNG
 */
function emptyPng() {
    /** @type {HTMLCanvasElement} */
    var canvas = document.createElement('canvas')
    var png = canvas.toDataURL('image/png')
    png = png.substr(png.indexOf(',') + 1).trim()
    return png;
}


function printLabels() {
    // Disable button
    $('[data-command=print]').prop('disabled', true)
    // Get calibration data
    var calData = selectedPrinter.calData
    /** @type {DYMOLabelFramework_PrintParams} */
    var printParams = {
        copies: 1,
        printQuality: DLF.LabelWriterPrintQuality.BarcodeAndGraphics,
        flowDirection: DLF.FlowDirection.LeftToRight
    }
    if (selectedPrinter.isTwinTurbo) {
        var left = $('#printer-roll-left-' + selectedPrinter.listIndex).prop('checked')
        printParams.twinTurboRoll = left ? DLF.TwinTurboRoll.Left : DLF.TwinTurboRoll.Right
    }
    // Print all checked labels
    var $labels = $('input[data-label-include]')
    for (var i = 0; i < $labels.length; i++) {
        var $chk = $($labels[i])
        if ($chk.prop('checked')) {
            var labelNo = Number.parseInt($chk.attr('data-label-include'))
            var labelData = config.print.labels[labelNo]
            printParams.jobTitle = 'Label #' + (labelNo + 1)
            try {
                var labelXml = prepareLabelXml(labelData, calData)
                var printParamsXml = DLF.createLabelWriterPrintParamsXml(printParams)
                if (!config.print.skipPrinting) {
                    log('Printing \'' + printParams.jobTitle + '\':', labelData)
                    DLF.printLabel(selectedPrinter.name, printParamsXml, labelXml, null)
                }
                else {
                    log('Printing (simulated) \'' + printParams.jobTitle + '\':', labelData)
                }
                $chk.prop('checked', false)
            }
            catch (err) {
                logError(err)
            }
        }
    }
    printing = false
    setUIState()
}

/**
 * Prints a sinlge label (from the preview modal)
 * @param {Number} labelNo 
 */
function printSingleLabel(labelNo) {
    // Get calibration data
    var calData = selectedPrinter.calData
    /** @type {DYMOLabelFramework_PrintParams} */
    var printParams = {
        copies: 1,
        printQuality: DLF.LabelWriterPrintQuality.BarcodeAndGraphics,
        flowDirection: DLF.FlowDirection.LeftToRight
    }
    if (selectedPrinter.isTwinTurbo) {
        var left = $('#printer-roll-left-' + selectedPrinter.listIndex).prop('checked')
        printParams.twinTurboRoll = left ? DLF.TwinTurboRoll.Left : DLF.TwinTurboRoll.Right
    }
    var labelData = config.print.labels[labelNo]
    printParams.jobTitle = 'Label #' + (labelNo + 1)
    try {
        var labelXml = prepareLabelXml(labelData, calData)
        var printParamsXml = DLF.createLabelWriterPrintParamsXml(printParams)
        if (!config.print.skipPrinting) {
            log('Printing \'' + printParams.jobTitle + '\':', labelData)
            DLF.printLabel(selectedPrinter.name, printParamsXml, labelXml, null)
        }
        else {
            log('Printing (simulated) \'' + printParams.jobTitle + '\':', labelData)
        }
    }
    catch (err) {
        logError(err)
    }
    // Uncheck in list
    $('input[data-label-include="' + labelNo + '"]').prop('checked', false)
    setUIState()
}



/**
 * 
 * @param {Number} labelNo 
 */
function previewLabel(labelNo) {
    var labelData = config.print.labels[labelNo]
    log('Preview label:', labelData)
    var labelXml = prepareLabelXml(labelData, null)
    var renderParamsXml = DLF.createLabelRenderParamsXml({
        labelColor: { 
            alpha: 255,
            red: 230,
            green: 230,
            blue: 230
        },
        shadowDepth: 0,
        flowDirection: DLF.FlowDirection.LeftToRight,
        pngUseDisplayResolution: false
    })
    var png = DLF.renderLabel(labelXml, renderParamsXml, selectedPrinter.name)
    $('img.label-preview').attr('src', 'data:image/png;base64,' + png)
    $('[data-command="print-single"]').attr('data-label', labelNo)
    $('#modal-preview').modal('show')
}

/**
 * 
 * @param {DYMOLabelFramework_Label} label 
 * @param {DYMOLabelItem[]} labelData
 * @returns {string[]}
 */
function setLabelObjects(label, labelData) {
    var errors = []
    for (var i = 0; i < labelData.length; i++) {
        var data = labelData[i]
        try {
            switch(data.type) {
                case 'T':
                case 'PNG':
                    label.setObjectText(data.name, data.value)
                    break
                case 'DM':
                case 'QR':
                    label.setObjectText(data.name, data.png)
                    break
            }
        }
        catch { 
            errors.push(data.name)
        }
    }
    return errors
}

/**
 * 
 * @param {DYMOLabelItem[]} labelData
 * @param {DYMOLabelCalibration} calData
 * @returns {string}
 */
function prepareLabelXml(labelData, calData) {
    // Adjust object bounds
    calData = calData || { dx: 0, dy: 0 }
    var xml = config.labels[config.print.template].xml
    var label = DLF.openLabelXml(xml)
    if (label.isDLSLabel()) {
        // Conversion of 1/10mm to twips = 1440/254
        var dx = 1440 * calData.dx / 254
        var dy = 1440 * calData.dy / 254;
        $(label._doc).find('Bounds').each(function (i, el) {
            var x = Number(el.getAttribute('X'))
            var y = Number(el.getAttribute('Y'))
            x = x + dx;
            y = y + dy;
            el.setAttribute('X', x.toString())
            el.setAttribute('Y', y.toString())
        })
        // Remove label objects marked 'R'
        for (var i = 0; i < labelData.length; i++) {
            if (labelData[i].type == 'R') {
                $(label._doc).find('Name').each(function(idx, el) {
                    if (el.textContent == labelData[i].name) {
                        $(el.parentNode.parentNode).remove()
                    }
                })
            }
        }
    }
    else {
        // Conversion of 1/10mm to inches
        var dx = calData.dx / 254
        var dy = calData.dy / 254;
        $(label._doc).find('ObjectLayout DYMOPoint X').each(function (i, el) {
            var x = Number.parseFloat(el.textContent)
            x = x + dx
            el.textContent = x.toString()
        })
        $(label._doc).find('ObjectLayout DYMOPoint Y').each(function (i, el) {
            var y = Number.parseFloat(el.textContent)
            y = y + dy
            el.textContent = y.toString()
        })
        // Remove label objects marked 'R'
        for (var i = 0; i < labelData.length; i++) {
            if (labelData[i].type == 'R') {
                $(label._doc).find('Name').each(function(idx, el) {
                    if (el.textContent == labelData[i].name) {
                        $(el.parentNode).remove()
                    }
                })
            }
        }
    }
    setLabelObjects(label, labelData)
    xml = label.getLabelXml()

    // Fix XML, otherwise the Webservice will throw
    // Color, Columns, Rows must have open/close tags
    if (label.isDCDLabel) {
        var elements = [ 'Color', 'Columns', 'Rows' ]
        elements.forEach(function(element) {
            var regex = new RegExp('<' + element + '.*?\\s{0,1}\\/>', 'sgm')
            var m
            while ((m = regex.exec(xml)) !== null) {
                if (m.index === regex.lastIndex) {
                    regex.lastIndex++;
                }
                m.forEach(function(match, groupIndex) {
                    var replace = match.substr(0, match.length - 2) + '></' + element + '>'
                    xml = xml.replace(match, replace)
                })
            }
        })
    }
    return xml
}



/**
 * Gets printer calibration data
 */
function getPrinterCalibration() {
    return new Promise(function(resolve, reject) {
        // Get data from AJAX based on available printers and the current label
        var payload = {
            printers: [],
            id: config.print.template
        }
        for (var i = 0; i < printers.length; i++) {
            payload.printers.push(printers[i].name)
        }
        submitData('get-calibration', payload)
        .then(function(response) {
            for (var i = 0; i < printers.length; i++) {
                var name = printers[i].name
                var cal = response.calData[name] || { dx: 0, dy: 0 }
                printers[i].calData = cal
            }
            resolve()
        })
        .catch(function(err) {
            log('Failed to get calibration data: ' + err)
            for (var i = 0; i < printers.length; i++) {
                printers[i].calData = { dx: 0, dy: 0 }
            }
            resolve()
        })
    })
}

function calibrate() {
    // Update values
    $('#offset-dx').val(selectedPrinter.calData.dx)
    $('#offset-dy').val(selectedPrinter.calData.dy)
    dialog('#modal-calibrate', {}, function($modal, verb) {
        return new Promise(function(resolve, reject) {
            if (verb == 'apply') {
                var dx = Number.parseInt($('#offset-dx').val().toString())
                var dy = Number.parseInt($('#offset-dy').val().toString())
                selectedPrinter.calData = { dx: dx, dy: dy }
                var payload = {
                    printer: selectedPrinter.name,
                    id: config.print.template,
                    cal: {
                        dx: dx,
                        dy: dy
                    }
                }
                log('Set calibration:', payload)
                // Store on server
                submitData('store-calibration', payload)
                .catch(function(err) {
                    logError(err)
                })
            }
            resolve(verb)
        })
    })
}

//#endregion

})();