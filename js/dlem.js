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
    dialog('#modal-addNew', null, function($modal, verb) {
        return new Promise(function(resolve, reject) {
            checkAddState()
            if (verb == 'add' && addState.valid) {
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
        xml: addState.xml
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
    checkAddState()
    $('#modal-addNew [data-input-control]').val('')
    fileChanged()
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
    dialog('#modal-error', {
        error: 'Not implemented yet.'
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
 * Log to the console when in debug mode
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