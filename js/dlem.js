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

/** @type DYMOLabelConfig */
var config;

/** @type AddLabelState */
var addState = {
    name: '', 
    desc: '', 
    filename: '', 
    xml: '',
    valid: false
}

/** @type DataTables.Api */
var labelsTable;

//#endregion


//#region Add a label

/**
 * Adds a new label.
 */
function addNewLabel() {
    checkAddState()
    if (addState.valid) {
        submitData('add-label', addState, addLabelDone, addLabelFailed)
    }
}

/**
 * Adds a label row.
 * @param {AddLabelResponse} response 
 */
function addLabelDone(response) {
    // Add label
    /** @type LabelData */
    var label = {
        id: response.id,
        name: addState.name,
        desc: addState.desc,
        filename: addState.filename,
        xml: addState.xml
    }
    config.labels[label.id] = label
    labelsTable.rows.add([label])
    labelsTable.draw()
    // Reset dialog
    addState.xml = ''
    addState.name = ''
    addState.desc = ''
    addState.filename = ''
    checkAddState()
}

/**
 * Shows an error message after failing to add a label.
 * @param {string} error 
 */
function addLabelFailed(error) {
    showError(error)
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
    
    $('#dlem-addlabelbtn').prop('disabled', !addState.valid)
}

/**
 * Verifies a label file.
 * @param {string} xml 
 * @returns {boolean}
 */
function verifyLabel(xml) {
    try {
        var doc = $.parseXML(xml)
        // Verify this is a DYMO Label file
        if ($(doc).find('DieCutLabel').length == 1) {
            log('Verified label file: ', doc)
            return true
        }
        else {
            log('Unable to verify label file: Missing <DieCutLabel> root element.', doc)
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
    showError("Not implemented yet.")
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
 * Shows the delete label confirmation.
 * @param {LabelData} label 
 */
function deleteLabel(label) {
    var $modal = $('#modal-delete')
    $modal.find('[data-dlem-content]').each(function() {
        var $this = $(this)
        var item = $this.attr('data-dlem-content')
        $this.text(label[item])
    })
    $modal.find('[data-dlem-action="confirm-delete-label"]').attr('data-dlem-id', label.id)
    $modal.modal('show')
}

/**
 * Deletes a label.
 * @param {string} id 
 */
function deleteLabelConfirmed(id) {
    log('Delete label: ' + id)
    submitData('delete-label', id, deleteLabelDone, deleteLabelFailed)
    deleteLabelDone({success:true,id: id})
}

/**
 * Removes the label row.
 * @param {DeleteLabelResponse} response 
 */
function deleteLabelDone(response) {
    delete config.labels[response.id]
    labelsTable.clear()
    labelsTable.rows.add(getTableData())
    labelsTable.draw()
}

/**
 * Shows an error message after failed deletion.
 * @param {string} error 
 */
function deleteLabelFailed(error) {
    showError(error)
}

//#endregion


//#region Print a label

/**
 * Print a label.
 * @param {LabelData} label 
 */
function printLabel(label) {
    log('Print label: ' + label.id)
    showError('Not implemented yet.')
}

//#endregion


//#region UI & Ajax Helper

/**
 * Log to the console when in debug mode.
 */
function log() {
    if (!config.debug) return
    switch(arguments.length) {
        case 1: 
            console.log(arguments[0])
            break
        case 2: 
            console.log(arguments[0], arguments[1])
            break
        case 3: 
            console.log(arguments[0], arguments[1], arguments[2])
            break
        case 4: 
            console.log(arguments[0], arguments[1], arguments[2], arguments[3])
            break
        case 5: 
            console.log(arguments[0], arguments[1], arguments[2], arguments[3], arguments[4])
            break
        default: 
            console.log(arguments)
            break
    }
}

/**
 * Displays an error message.
 * @param {string} msg 
 */
function showError(msg) {
    var $modal = $('#modal-error')
    $modal.find('[data-dlem-content=error]').text(msg)
    $modal.modal('show')
}



/**
 * Handles dialog button clicks.
 * @param {JQuery.ClickEvent} e 
 */
function handleDialogButton(e) {
    var $btn = $(e.target)
    var action = $btn.attr('data-dlem-action')
    switch (action) {
        case 'add-label':
            addNewLabel()
            break
        case 'confirm-delete-label':
            var id = $btn.attr('data-dlem-id')
            deleteLabelConfirmed(id)
            break
        default:
            log('DYMO Label EM: Unknown action', e)
            break
    }
}

/** 
 * Callback on success. 
 * @callback onSuccessCallback 
 * @param {object} response
 */
/**
 * Callback on error.
 * @callback onErrorCallback
 * @param {string} errorMessage
 */
/**
 * Sends an request to the server. When done, calls the callback.
 * @param {string} action
 * @param {object} payload
 * @param {onSuccessCallback} onSuccess 
 * @param {onErrorCallback} onError 
 */
function submitData(action, payload, onSuccess, onError) {
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
            if (response.success) {
                log('Successful server request:', response)
                config.ajax.verification = response.verification
                onSuccess(response)
            }
            else {
                log('Unsuccessful server request:', response)
                onError(response.error)
            }
        },
        error: function(jqXHR, error) {
            log('Ajax error:', error, jqXHR)
            onError(error)
        }
    })
}

//#endregion


//#region Setup

EM.DYMOLabelConfig_init = function(/** @type DYMOLabelConfig */ data) {
    
    config = data
    log('DYMO Label EM - Config initialized', config)

    checkAddState()
    $('#dlem-labelfile').on('change', function() {
        fileChanged()
    })
    $('#dlem-name').on('change', function() {
        checkAddState()
    })

    // Setup dialog buttons
    $('button[data-dlem-action]').on('click', handleDialogButton)

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
        '<button class="btn btn-xs btn-secondary" data-dlem-action="download" title=""><i class="fas fa-file-download"></i></button> '
    }
    buttons += 
        '| <button class="btn btn-xs btn-danger" data-dlem-action="delete" title="' + config.strings.actionDownload + '"><i class="far fa-trash-alt"></i></button>'
    log (buttons)
    return buttons
}

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

})();