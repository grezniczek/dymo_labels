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

/** @type JQuery */
var $labels;

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
    addState.xml = ''
    addState.name = ''
    addState.desc = ''
    addState.filename = ''
    checkAddState()
    log('addLabelDone')
}

/**
 * Shows an error message after failing to add a label.
 * @param {string} error 
 */
function addLabelFailed(error) {
    log('addLabelFailed')
}

/**
 * Adds a row to the labels table.
 * @param {LabelData} label 
 */
function addLabelRow(label) {
    log('Adding label row: ', label)
    var row = '<tr data-dlem-label-id="' + label.id + '">' + 
        '<th scope="row">' + label.name + '</th>' +
        '<td class="dlem-labeldesc">' + label.desc + '</td>' + 
        '<td class="dlem-labelactions">' + 
          '<button class="btn btn-xs btn-link" data-action="configure">Configure</button> | ' +
          '<button class="btn btn-xs btn-secondary" data-action="print">Print</button> | ' +
          '<button class="btn btn-xs btn-danger" data-action="delete">Delete</button>' +
        '</td>' +
        '</tr>'
    var $row = $(row)
    $row.find('button[data-action=configure]').on('click', function() {
        configureLabel(label)
    })
    $row.find('button[data-action=print]').on('click', function() {
        printLabel(label)
    })
    $row.find('button[data-action=delete]').on('click', function() {
        deleteLabel(label)
    })
    $labels.find('tbody').append($row)
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
    //submitData('delete-label', id, deleteLabelDone, deleteLabelFailed)
    deleteLabelDone({success:true,id: id})
}

/**
 * Removes the label row.
 * @param {DeleteLabelResponse} response 
 */
function deleteLabelDone(response) {
    var $row = $('tr[data-dlem-label-id=' + response.id + ']')
    $row.remove()
    $labels.DataTable()
}

/**
 * Shows an error message after failed deletion.
 * @param {string} error 
 */
function deleteLabelFailed(error) {
    log('deleteLabelFailed')
}

//#endregion


//#region Print a label

/**
 * Print a label.
 * @param {LabelData} label 
 */
function printLabel(label) {
    log('Not implemented - Print label: ' + label.id)
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
    $labels = $('#dlem-labels')
    Object.keys(config.labels).forEach(function(id) {
        addLabelRow(config.labels[id])
    })
    
    labelsTable = $labels.DataTable()
    

    // Setup some usability enhancements.
    // @ts-ignore
    $('textarea.autosize').textareaAutoSize()
}

//#endregion

})();