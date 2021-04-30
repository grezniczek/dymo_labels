/* DYMO Label External Module */
// @ts-check
;(function() {

//#region Variables

/** @type {ExternalModules} */
// @ts-ignore
var EM = window.ExternalModules
if (typeof EM == 'undefined') {
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

/** @type {DYMOLabelFramework_PrinterInfo} */
var selectedPrinter = null

/** @type {DYMOLabelFramework_PrinterInfo[]} */
var printers = []

/** @type {boolean} Indicates whether a print process is currently ongoing */
var printing = false

/** @type {DYMOLabelFramework} */
var DLF = null

/** @type {boolean} Keeps track whether the DYMO Label Framework has been initialized yet */
var DLF_initialized = false;

//#endregion

//#region Add a label

/**
 * Adds a new label.
 */
function addNewLabel() {
    clearAddState()
    dialog('#modal-addNew', function(addDH) {
        checkAddState()
    }, function(addDH, verb) {
        return new Promise(function(resolve, reject) {
            checkAddState()
            if (verb == 'add' && addState.valid) {
                addDH.enable(false, true)
                addState.config = generateInitialConfig()
                submitData('add-label', addState)
                .then(function(response) {
                    resolve(response)
                })
                .catch(function(err) {
                    addDH.enable(true, true)
                    dialog('#modal-error', {
                        error: err
                    })
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
        logError(err)
    })
}

/**
 * Adds a label row.
 * @param {AddLabelResponse} response 
 */
function addLabelData(response) {
    // Add label
    var label = response.label
    config.labels[label.id] = label
    labelsTable.rows.add([label])
    labelsTable.draw()
    successToast(config.strings.toastLabelAdded)
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
 * Sets the initial configuration with default values
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
                name: name,
                desc: '',
                default: label.getObjectText(name),
                transform: isText ? 'T' : 'PNG',
                type: isText ? 'Text' : 'Graphic',
                readOnly: false,
                multiline: false,
                allowEmpty: false,
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
    $('[data-dlem-action="add-label"]').prop('disabled', !addState.valid)
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

//#region Show label info

/**
 * Show label info.
 * @param {LabelData} label 
 */
function showInfo(label) {
    // Construct action tag template
    var tagInfo = '@DYMO-LABEL={'
    tagInfo += '\n  "id": "' + label.id + '",'
    tagInfo += '\n  "button": "' + config.strings.widgetLabel + '",'
    tagInfo += '\n  "target": "",'
    tagInfo += '\n  "range": "COPY:1-1",'
    tagInfo += '\n  "data": {'
    Object.keys(label.config.objects).forEach(function(key) {
        var loi = label.config.objects[key]
        if (loi.transform != 'R' && !loi.readOnly) {
            tagInfo += '\n    "' + loi.name + '": "' + config.strings.actionTagReplace + '",'
        }
    })
    if (tagInfo[tagInfo.length - 1] == ',') {
        tagInfo = tagInfo.substring(0, tagInfo.length - 1)
    }
    tagInfo += '\n  }'
    tagInfo += '\n}'
    // Construct link template
    var linkInfo = ''
    if (label.config.public) {
        linkInfo = config.linkBase + '&template=' + label.id
        Object.keys(label.config.objects).forEach(function(key) {
            var loi = label.config.objects[key]
            linkInfo += '&' + loi.transform + '_' + loi.name
            if (loi.transform != 'R') {
                linkInfo += '=' + config.strings.infoValue
            }
        })
        linkInfo += '&range=COPIES:1-1'
    }
    dialog('#modal-info', function(infoDH) {
        infoDH.set({
            id: label.id,
            name: label.name,
            desc: label.desc,
            tag: tagInfo,
            link: linkInfo   
        })
        // Show GET info only when enabled.
        if (label.config.public) infoDH.$modal.find('[data-public-endpoint-active]').removeClass('d-none')
    }, function(infoDH, verb) {
        return new Promise(function (resolve, reject) {
            // Copy to clipboard
            if (verb.substr(0, 5) == 'copy-') {
                try {
                    /** @type {HTMLInputElement} */ // @ts-ignore
                    var el = verb == 'copy-tag' ?
                        infoDH.$modal.find('textarea[data-modal-content-html="tag"]')[0] :
                        infoDH.$modal.find('input[data-modal-value="link"]')[0]
                    el.focus()
                    el.select()
                    el.setSelectionRange(0,9999999)
                    if (document.execCommand('copy')) {
                        successToast(verb == 'copy-tag' ? 
                            config.strings.infoCopiedTag : config.strings.infoCopiedLink)
                    }
                    else {
                        throw new Error()
                    }
                    el.setSelectionRange(0,0)
                }
                catch {
                    dialog('#modal-error', {
                        error: config.strings.clipboardError
                    })
                }
                infoDH.enable(true, true)
            }
            else {
                resolve(verb)
            }
        })
    })
}
//#endregion

//#region Rename a label

/**
 * Rename a label.
 * @param {LabelData} label 
 */
function renameLabel(label) {
    dialog('#modal-renamelabel', function(renameDH) {
        // Setup
        renameDH.set({
            id: label.id
        })
        var $modal = renameDH.$modal
        $modal.find('[data-input-control="name"]').val(label.name).removeClass('is-invalid').attr('title', '')
        $modal.find('[data-input-control="desc"]').val(label.desc)
    }, function(renameDH, verb) {
        var $modal = renameDH.$modal
        return new Promise(function(resolve, reject) {
            if (verb == 'save') {
                // Gather data
                var newName = $modal.find('[data-input-control="name"]').val().toString().trim()
                var newDesc = $modal.find('[data-input-control="desc"]').val().toString().trim()
                // Check
                if (newName == '') {
                    $modal.find('[data-input-control="name"]').addClass('is-invalid').attr('title', config.strings.nameRequired)
                    $modal.find('[data-modal-action]').prop('disabled', false)
                }
                // Store
                else {
                    var payload = {
                        id: label.id,
                        name: newName,
                        desc: newDesc,
                    }
                    submitData('rename-label', payload)
                    .then(function(response) {
                        var updatedLabel = response.label
                        config.labels[label.id] = updatedLabel
                        resolve(verb)
                    })
                    .catch(function(err) {
                        renameDH.enable(true, true)
                        // Show an error notification
                        $modal.fadeTo(200, 0.8)
                        dialog('#modal-error', {
                            error: err
                        })
                        .then(function() {
                            $modal.find('[data-modal-action]').prop('disabled', false)
                            $modal.fadeTo(50, 1)
                        })
                    })
                }
            }
            else {
                resolve(verb)
            }
        })
    }).then(function(verb) {
        if (verb == 'save') {
            labelsTable.clear()
            labelsTable.rows.add(getTableData())
            labelsTable.draw()
            successToast(config.strings.toastLabelRenamed)
        }
    }).catch(function(err) {
        logError(err)
    })
}
//#endregion


//#region Configure a label

/**
 * Configure a label.
 * @param {LabelData} label 
 */
function configureLabel(label) {
    log('Configure label: ' + label.id)
    dialog('#modal-config', function(configDH) {
        log('Configuring:', label)
        var $modal = configDH.$modal
        // Clear
        $modal.find('[data-object-name]').remove()
        $modal.find('hr').remove()
        // Add static
        $modal.find('[data-modal-content="id"]').text(label.id)
        $modal.find('[data-modal-content="name"]').text(label.name)
        $modal.find('[data-modal-content="desc"]').text(label.desc)
        // Add dynamic
        $modal.find('[data-modal-content="public"]').prop('checked', label.config.public)
        var counter = 0
        Object.keys(label.config.objects).forEach(function(key) {
            var loi = label.config.objects[key]
            var $row = $modal.find(loi.type == 'Text' ? 'div[data-template=text-object]' : 'div[data-template=image-object]').clone(false)
            $row.removeClass('d-none').removeAttr('data-template')
            if (counter > 0) {
                $modal.find('.modal-body').append('<hr>')
            }
            $modal.find('.modal-body').append($row)
            $row.attr('data-object-name', loi.name)
            $row.find('[data-content="name"]').text(loi.name).attr('title', loi.desc).tooltip()
            $row.find('[data-content="readonly"]').prop('checked', loi.readOnly).attr('id', 'readonly-' + counter).siblings('label').attr('for', 'readonly-' + counter)
            $row.find('[data-content="allowempty"]').prop('checked', loi.allowEmpty).attr('id', 'allowempty-' + counter).siblings('label').attr('for', 'allowempty-' + counter)
            $row.find('[data-content="transform"]').val(loi.transform)
            $row.find('[data-content="default"]').val(loi.default).attr('id', 'textarea-' + counter)
            $row.find('[data-content="multiline"]').prop('checked', loi.multiline).attr('id', 'multiline-' + counter).on('change', function() {
                var ml = $(this).prop('checked')
                $row.find('textarea').prop('rows', ml ? 3 : 1)
            }).siblings('label').attr('for', 'multiline-' + counter)
            $row.find('textarea').prop('rows', loi.multiline ? 3 : 1)
            $row.find('[data-edit-action]').on('click', function(e) {
                $modal.fadeTo(200, 0.8)
                dialog('#modal-editlabelobject', function(editDH) {
                    var $edit = editDH.$modal
                    $edit.find('[data-modal-content=objectname]').text(loi.name)
                    $edit.find('[data-input-control=objectname]').val(loi.name)
                    $edit.find('[data-input-control=objectdesc]').val(loi.desc)
                    $edit.find('[data-input-control=objectname]').removeClass('is-invalid')
                }, function(editDH, editVerb) {
                    var $edit = editDH.$modal
                    return new Promise(function(resolve, reject) {
                        if (editVerb == 'edit-labelobject') {
                            var newName = $edit.find('[data-input-control=objectname]').val().toString().trim()

                            if (/^([A-Za-z0-9]{1,})$/.test(newName)) {
                                loi.name = newName
                                loi.desc = $edit.find('[data-input-control=objectdesc]').val().toString().trim()
                                resolve(editVerb)
                            }
                            else {
                                $edit.find('[data-input-control=objectname]').addClass('is-invalid').attr('title', 'The name must not be empty and consist of letters and numbers only!')
                                $edit.find('[data-modal-action]').prop('disabled', false)
                            }
                        }
                        else if (editVerb == 'cancel') {
                            resolve()
                        }
                    })
                })
                .then(function(editVerb) {
                    if (editVerb == 'edit-labelobject') {
                        // Store
                        $row.find('[data-content="name"]').text(loi.name).attr('title', loi.desc).tooltip()
                    }
                    $modal.fadeTo(50, 1)
                })
            })
            counter++
        })
        // Remove 'no labels'
        if (Object.keys(label.config.objects).length) {
            $modal.find('.dlem-no-labelobjects').hide()
        }
        else {
            $modal.find('.dlem-no-labelobjects').show()
        }
    }, function(configDH, verb) {
        var $modal = configDH.$modal
        return new Promise(function(resolve, reject) {
            if (verb == 'save') {
                // Save configuration - read from modal and replace old config
                /** @type {LabelConfig} */
                var updatedConfig = {}
                var updatedXml = label.xml
                updatedConfig.public = $modal.find('[data-modal-content="public"]').prop('checked')
                updatedConfig.objects = {}
                $modal.find('[data-object-name]').each(function() {
                    var $obj = $(this)
                    var oldName = $obj.attr('data-object-name')
                    /** @type {LabelObjectInfo} */
                    var loi = {
                        name: $obj.find('[data-content="name"]').text().trim(),
                        desc: $obj.find('[data-content="name"]').attr('title').trim(),
                        default: $obj.find('[data-content="default"]').val().toString(),
                        multiline: $obj.find('[data-content="multiline"]').prop('checked'),
                        readOnly: $obj.find('[data-content="readonly"]').prop('checked'),
                        allowEmpty: $obj.find('[data-content="allowempty"]').prop('checked'),
                        // @ts-ignore
                        transform: $obj.find('[data-content="transform"]').val().toString(),
                        // @ts-ignore
                        type: label.config.objects[oldName].type,
                    }
                    updatedConfig.objects[loi.name] = loi
                    if (loi.name != oldName) {
                        // Need to update label XML
                        var search = '<Name>' + oldName + '</Name>'
                        var replace = '<Name>' + loi.name + '</Name>'
                        updatedXml = updatedXml.replace(search, replace)
                    }
                })
                /** @type {LabelData} */
                var updatedLabel = {
                    id: label.id,
                    name: label.name,
                    desc: label.desc,
                    filename: label.filename,
                    config: updatedConfig,
                    xml: updatedXml,
                }
                submitData('update-label', updatedLabel)
                .then(function(response) {
                    updatedLabel = response.label
                    config.labels[label.id] = updatedLabel
                    log('Label updated:', updatedLabel)
                    resolve(verb)
                })
                .catch(function(err) {
                    // Show an error notification
                    $modal.fadeTo(200, 0.8)
                    configDH.enable(true, true)
                    dialog('#modal-error', {
                        error: err
                    })
                    .then(function() {
                        $modal.find('[data-modal-action]').prop('disabled', false)
                        $modal.fadeTo(50, 1)
                    })
                })
            }
            else {
                resolve(verb)
            }
        })
    })
    .then(function(verb) {
        if (verb == 'save') {
            successToast(config.strings.toastLabelUpdated)
        }
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
            successToast(config.strings.toastLabelDeleted)
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
    dialog('#modal-print', function(printDH) { 
        printDH.set({
            id: label.id,
            name: label.name,
            desc: label.desc
        })
        var $modal = printDH.$modal
        // Cleanup
        $modal.find('[data-item-remove]').remove()
        $modal.find('[data-item-template]').hide()
        // Construct dialog
        var index = 0
        Object.keys(label.config.objects).forEach(function(key) {
            var loi = label.config.objects[key]
            if (loi.transform == 'R') return // Skip removed items
            index++
            var $item = $modal.find('[data-item-template]').clone(false)
            $item.removeAttr('data-item-template').appendTo($modal.find('[data-label-objects]')).attr('data-item-remove', index).show()
            $item.find('[data-labelobject-name]').text(loi.name).attr('for', 'print-item-' + index)
            $item.find('[data-labelobject-type]').hide()
            $item.find('[data-labelobject-type="' + loi.type + '"]').show()
            $item.find('[data-input-control]').val(loi.default).attr('data-input-control', loi.name).attr('id', 'print-item-' + index).prop('rows', loi.multiline ? 3 : 1).prop('disabled', loi.readOnly)
            $item.find('[data-labelobject-transform]').text(config.strings['transform' + loi.transform])
        })
    }, function(printDH, verb) {
        return new Promise(function(resolve, reject) {
            if (verb == 'setup-print') {
                log('Setting up label', label)
                // Prepare print data
                var print = {
                    template: label.id,
                    errors: [],
                    range: printDH.$modal.find('[data-print-range]').val().toString(),
                    auto: false,
                    labels: [],
                }
                // Gather data
                var labelTpl = []
                Object.keys(label.config.objects).forEach(function(key) {
                    var loi = label.config.objects[key]
                    var labelItem = {
                        name: key,
                        type: loi.transform,
                        value: loi.default
                    }
                    if (loi.transform == 'R') {
                        labelItem.value = ''
                    }
                    else if (!loi.readOnly) {
                        var val = printDH.$modal.find('[data-input-control="' + key + '"]').val().toString()
                        if (val.length == 0 && !loi.allowEmpty) {
                            val = loi.default
                        }
                        labelItem.value = val
                    }
                    labelTpl.push(labelItem)
                })
                var expanded = expandRange(print.range, labelTpl)
                expanded.errors.forEach(function(error) {
                    print.errors.push(error)
                })
                print.labels = expanded.labels
                // Show print dialog
                var z = printDH.$modal.css('z-index')
                printDH.$modal.css('z-index', 0)
                dialog('#dlem-widget-modal-print', function(widgetDH) {
                    widgetDH.set({
                        name: label.name,
                        desc: label.desc,
                    })
                    config.print = print
                    printDH.enable(false, true)
                    setTimeout(() => {
                        initPrinting(widgetDH.$modal)
                    }, 10);
                })
                .then(function() {
                    printDH.enable(true, true)
                    printDH.$modal.css('z-index', z)
                })
                log('Print', print)
            }
            else {
                resolve(verb)
            }
        })
    })
}

//#endregion


//#region UI & Ajax Helper


function successToast(msg) {
    var $toast = $('#dlem-successToast')
    $toast.find('[data-content=toast]').html(msg)
    $toast.toast('show')
}

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
 * @param {Object<string, string> | function(DialogHelper):void} contentOrSetup 
 * @param {function(DialogHelper, string):Promise} action 
 */
function dialog(selector, contentOrSetup = null, action = null) {
    return new Promise(function(resolve, reject) {
        // Prepare dialog
        var $modal = $(selector)
        var enable = function(enabled = true, swap = false) {
            // Enable or disable all action buttons
            // and show/hide busy content
            $modal.find('[data-modal-action]').each(function() {
                var $btn = $(this)
                $btn.prop('disabled', !enabled)
                if (swap) {
                    if (enabled) {
                        $btn.find('.when-disabled').hide()
                        $btn.find('.when-enabled').show()
                    }
                    else {
                        $btn.find('.when-disabled').show()
                        $btn.find('.when-enabled').hide()
                    }
                }
            })
        }
        var hide = function() {
            $modal.modal('hide')
            $modal.off('click', clickHandler)
            enable(true, true)
        }
        var done = function(result) {
            hide()
            resolve(result)
        }
        var error = function(err) {
            hide()
            reject(err)
        }
        var setContent = function(content) {
            $modal.find('[data-modal-content]').each(function(index, element) {
                var item = element.getAttribute('data-modal-content')
                element.innerText = content[item]
            })
            $modal.find('[data-modal-content-html]').each(function(index, element) {
                var item = element.getAttribute('data-modal-content-html')
                element.innerHTML = content[item]
            })
            $modal.find('[data-modal-value]').each(function(index, element) {
                var item = element.getAttribute('data-modal-value')
                $(element).val(content[item])
            })
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
                        enable(false, true)
                        // Perform action
                        action({ $modal: $modal, set: setContent, enable: enable }, verb)
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
        enable(true, true)
        $modal.on('click', clickHandler)
        if (typeof contentOrSetup == 'function') {
            contentOrSetup({ $modal: $modal, set: setContent, enable: enable })
        }
        else if (typeof contentOrSetup == 'object') {
            setContent(contentOrSetup)
        }
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
    DLF_initialized = true

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
                data: 'name',
                render: $.fn.dataTable.render.text()
            },
            {
                data: 'desc',
                render: $.fn.dataTable.render.text()
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
        data: getTableData(),
        language: JSON.parse(config.strings.dataTablesLanguageJSON)
    })

    setupPrintEvents($('.dlem-widget-modal'))

    // Setup some usability enhancements.
    // @ts-ignore
    $('textarea.autosize').textareaAutoSize()
}


EM.DYMOLabelWidget_init = function(data) {
    config = data
    log('Initializing widgets...', data)
    setupPrintEvents($('.dlem-widget-modal'))

    $('[data-dlem-print-widget]').each(function() {
        var $widget = $(this)
        var id = $widget.attr('data-dlem-label')
        var field = $widget.attr('data-dlem-field')
        var target = $widget.attr('data-dlem-target')
        var eventid = $widget.attr('data-dlem-eventid')
        var classStart = 'piperec-' + eventid + '-'
        var $target = target == '' ? $('tr[sq_id="' + field + '"] td.labelrc') : $(target)
        if ($target.length) {
            $target.append($widget)
            var $btn = $widget.find('button')
            $btn.on('click', function(e) {
                e.preventDefault()
                $btn.prop('disabled', true)
                $btn.find('.when-disabled').prop('hidden', false)
                $widget.find('.piping_receiver').each(function() {
                    var span = this
                    span.classList.forEach(function(className) {
                        if (className.startsWith(classStart)) {
                            var srcField = className.split('-')[2]
                            var val = $('input[name="' + srcField + '"]').val()
                            // @ts-ignore
                            updatePipeReceivers(srcField, eventid, val)
                        }
                    })
                })
                /** @type {DYMOLabelPrintData} */
                var print = {
                    template: id,
                    errors: [],
                    range: $widget.find('[data-dlem-range]').text(),
                    auto: $widget.attr('data-dlem-auto') == '1',
                    labels: [],
                }
                var labelTpl = []
                Object.keys(config.labels[id].config.objects).forEach(function(key) {
                    var loi = config.labels[id].config.objects[key]
                    var label = {
                        name: key,
                        type: loi.transform,
                        value: loi.default
                    }
                    if (loi.transform == 'R') {
                        label.value = ''
                    }
                    else if (!loi.readOnly) {
                        // Get value from elements
                        var $el = $widget.find('[data-dlem-object="' + key + '"]')
                        if ($el.length) {
                            var $obj = $el.clone(false)
                            $obj.find('br').before('\\n').remove()
                            var val = $obj.text().trim()
                            if (val.length == 0 && !loi.allowEmpty) {
                                val = loi.default
                            }
                            label.value = val
                        }
                    }
                    // Add 
                    labelTpl.push(label)
                })

                // Expand ranges
                var expanded = expandRange(print.range, labelTpl)
                expanded.errors.forEach(function(error) {
                    print.errors.push(error)
                })
                print.labels = expanded.labels

                dialog('#dlem-widget-modal-print', function(printDH) {
                    printDH.set({
                        name: config.labels[id].name,
                        desc: config.labels[id].desc,
                    })
                    config.print = print
                    setTimeout(() => {
                        initPrinting(printDH.$modal)
                    }, 10);
                }).then(function() {
                    $btn.find('.when-disabled').prop('hidden', true)
                    $btn.prop('disabled', false)
                })
                log('Print', print)
                return false
            })
        }
    })
}

/**
 * 
 * @param {string} rangeDef 
 * @param {DYMOLabelItem[]} labelTpl
 * @returns {DYMOLabelRangeExpansionResult}
 */
function expandRange(rangeDef, labelTpl) {
    /** @type {DYMOLabelRange[]} */
    var ranges = []
    var rangeRegex = new RegExp('([A-Z0-9]+):([A-Z]|[a-z]|[0-9]+)-([A-Z]|[a-z]|[0-9]+)', 'i')
    var alphaMaps = {
        'Upper': 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'Lower': 'abcdefghijklmnopqrstuvwxyz',
        'Numeric': ''
    }
    /** @type {DYMOLabelRangeExpansionResult} */
    var rv = {
        range: rangeDef,
        labels: [],
        errors: []
    } 
    rangeDef.split(',').forEach(function(rpart) {
        var m = rangeRegex.exec(rpart)
        if (m) {
            /** @type {DYMOLabelRange} */
            var range = {
                id: m[1],
                start: m[2],
                end: m[3],
            }
            if ((alphaMaps.Upper.includes(range.start) && alphaMaps.Upper.includes(range.end))) {
                range.type = 'Upper'
                ranges.push(range)
            } 
            else if (alphaMaps.Lower.includes(range.start) && alphaMaps.Lower.includes(range.end)) {
                range.type = 'Lower'
                ranges.push(range)
            }
            else if (!alphaMaps.Upper.includes(range.start.toUpperCase()) && !alphaMaps.Upper.includes(range.end.toUpperCase())) {
                range.type = 'Numeric'
                ranges.push(range)
            }
            else {
                rv.errors.push(config.strings.invalidRange + rpart)
            }
        }
    })
    /** @type {DYMOLabelItem[][]} */
    var labels = []
    labels.push(labelTpl)
    ranges.forEach(function(range) {
        var alphaMap = alphaMaps[range.type]
        var start = range.type == 'Numeric' ? Number.parseInt(range.start) : alphaMap.search(range.start)
        var end = range.type == 'Numeric' ? Number.parseInt(range.end) : alphaMap.search(range.end)
        var delta = start > end ? -1 : 1
        // Copy over existing labels and initalize array to hold the new ones
        var unexpanded = labels
        labels = []
        var i = start
        var stop = end + delta
        while (i != stop) {
            var replaceWith = range.type == 'Numeric' ? i.toString() : alphaMap.substring(i, i + 1)
            var search = '{' + range.id + '}'
            unexpanded.forEach(function(uxLabel) {
                var label = createLabel(uxLabel, search, replaceWith)
                labels.push(label)
            })
            i = i + delta
        }
    })
    rv.labels = labels
    return rv
}


/**
 * 
 * @param {DYMOLabelItem[]} template 
 * @param {string} search 
 * @param {string} replace 
 * @returns {DYMOLabelItem[]}
 */
function createLabel(template, search, replace) {
    /** @type {DYMOLabelItem[]} */
    var label = []
    template.forEach(function(item) {
        label.push({
            name: item.name,
            type: item.type,
            value: item.value.replace(search, replace),
        })
    })
    return label
}

EM.DYMOLabelPrint_init = function(data) {
    
    config = data
    log('Print page initializing:', config)

    setupPrintEvents($('body'))
    setTimeout(() => {
        initPrinting($('body'))
    }, 10);
}

/**
 * 
 * @param {JQuery} $container 
 */
function setupPrintEvents($container) {
    $container.on('click', function(e) {
        var $btn = e.target.hasAttribute('data-command') ? $(e.target) : $(e.target).parents('[data-command]')
        if ($btn.length && $btn.is('button') ) {
            var cmd = $btn.attr('data-command')
            switch (cmd) {
                case 'refresh': setupPrinters($container, true); break;
                case 'print': printLabels($container); break;
                case 'calibrate': calibrate(); break;
                case 'print-single': 
                var single = Number.parseInt($btn.attr('data-label'))
                printSingleLabel(single, $container)
                break;
            }
            e.preventDefault()
            return false
        }
    })
}

var dlfStatus = null
/**
 * 
 * @param {JQuery} $container 
 */
function initPrinting($container) {
    // @ts-ignore
    DLF = dymo.label.framework
    if (!DLF_initialized) {
        DLF.init()
        DLF_initialized = true
    }
    if (dlfStatus == null) {
        dlfStatus = DLF.checkEnvironment()
        log('DYMO Framework Status:', dlfStatus)
    }

    $container.find('.initialized').hide()
    $container.find('.initializing').show()
    $container.find('thead.labels-header').empty()
    $container.find('tbody.labels-body tr.label').remove()

    setTimeout(function() {
        
        setupLabels($container)

        if (dlfStatus.isBrowserSupported && 
            dlfStatus.isFrameworkInstalled && 
            dlfStatus.isWebServicePresent) {

            setupPrinters($container, false)
            .then(function() {
                $container.find('.initialized').show(200)
                $container.find('.initializing').hide()
                if (config.print.auto && selectedPrinter) {
                    printLabels($container)
                }
            })
            config.print.errors.forEach(function(err) {
                $container.find('[data-dlem-error]').append('<p>' + err + '</p>')
            })
        }
        else {
            $container.find('[data-dlem-error]').html(dlfStatus.errorDetails)
            $container.find('.initializing').hide()
            $container.find('.initialized').show(200)
        }
        setUIState($container)
    }, 100);
}

/**
 * Renders the action buttons for the labels table.
 * @param {string} id 
 */
function renderLabelActions(id) {
    var buttons = 
        '<button class="btn btn-xs btn-info" data-dlem-action="info"><i class="fas fa-info"></i> ' + config.strings.actionInfo + '</button> ' +
        '<button class="btn btn-xs btn-primary" data-dlem-action="rename"><i class="fas fa-i-cursor"></i> ' + config.strings.actionRename + '</button> ' +
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
        case 'info':
            showInfo(config.labels[id])
            break
        case 'rename':
            renameLabel(config.labels[id])
            break
        case 'configure':
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

/**
 * Sets the UI state (highlights, messages, enable/disable buttons)
 * @param {JQuery} $container
 */
function setUIState($container) {
    if (selectedPrinter == null && printers.length < 1) {
        $container.find('tr.no-printer').show()
        $container.find('.printers-card').addClass('border-danger')
    }
    else {
        $container.find('tr.no-printer').hide()
        $container.find('.printers-card').removeClass('border-danger')
    }
    if (config.print.labels.length == 0) {
        $container.find('tr.no-labels').show()
        $container.find('.labels-card').addClass('border-danger')
    }
    else {
        $container.find('tr.no-labels').hide()
        $container.find('.labels-card').removeClass('border-danger')
    }
    $container.find('[data-command=calibrate').prop('disabled', 
        selectedPrinter == null || 
        config.labels[config.print.template] == undefined)

    // Count labels
    var n = 0
    $container.find('input[data-label-include]').each(function() {
        if ($(this).prop('checked')) n++
    })
    if (n == 0) {
        $container.find('[data-command="select-all"]').prop('checked', false)
    }
    $container.find('[data-command=print]').prop('disabled', 
        printing ||
        selectedPrinter == null || 
        config.print.labels.length == 0 || 
        config.labels[config.print.template] == undefined ||
        n == 0)
    $container.find('[data-command=preview]').prop('disabled', 
        selectedPrinter == null || 
        config.print.labels.length == 0 || 
        config.labels[config.print.template] == undefined)
    $container.find('[data-command="print-single"]').prop('disabled', 
        selectedPrinter == null || 
        config.print.labels.length == 0 || 
        config.labels[config.print.template] == undefined)
}

/**
 * Selects a printer
 * @param {JQuery.ChangeEvent} e 
 * @param {JQuery} $container
 */
function selectPrinter(e, $container) {
    var idx = $(e.target).val().toString()
    log('Selected printer #' + idx)
    selectedPrinter = printers[idx]
    setUIState($container)
}

/**
 * Sets up the printer selection UI (initially and after refresh)
 * @param {JQuery} $container
 * @param {boolean} force
 */
function setupPrinters($container, force) {
    return new Promise(function(resolve, reject) {
        if (printers.length == 0 || force) {
            printers = DLF.getPrinters()
        }
        selectedPrinter = null
        var hasCalData = force ? false : true

        // Clone template and clear table
        var $table = $container.find('table.printers')
        $container.find('tr.no-printer').show()
        $table.find('tr.printer').remove()
        if (printers.length) {
            for (var i = 0; i < printers.length; i++) {
                var printer = printers[i]
                hasCalData = hasCalData && (printer.calData != undefined)
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
                        .attr('id', 'dlem-printer-roll-left-' + i)
                        .prop('checked', true)
                    $row.find('label.printer-roll-left').attr('for', 'dlem-printer-roll-left-' + i)
                    $row.find('input.printer-roll-right')
                        .attr('name', 'printer-roll-' + i)
                        .attr('id', 'dlem-printer-roll-right-' + i)
                    $row.find('label.printer-roll-right').attr('for', 'dlem-printer-roll-right-' + i)
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
        $container.find('input[name=printer]').on('change', function(e) {
            selectPrinter(e, $container)
        })
        var done = function() {
            $table.find('tr.printer').show()
            setUIState($container)
            log('Printers found:', printers)
            resolve()
        }
        if (hasCalData) {
            done()
        }
        else {
            getPrinterCalibration().then(done, done)
        }
    })
}

/**
 * Renders the labels table
 * @param {JQuery} $container
 */
function setupLabels($container) {

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
    $container.find('.labels-header').append($row)
    var $selectAll = $container.find('[data-command="select-all"]')
    $selectAll.on('change', function() {
        var all = $container.find('input[data-command="select-all"]').prop('checked')
        $container.find('input[data-label-include]').prop('checked', all)
        setUIState($container)
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
                case 'PNG':
                    var $icon = $('<i class="far fa-image fa-2x"></i>')
                    $cell.append($icon)
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
            setUIState($container)
        })
        $container.find('.labels-body').append($row)
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


/**
 * 
 * @param {JQuery} $container 
 */
function printLabels($container) {
    // Disable button
    $container.find('[data-command=print]').prop('disabled', true)
    // Get calibration data
    var calData = selectedPrinter.calData
    /** @type {DYMOLabelFramework_PrintParams} */
    var printParams = {
        copies: 1,
        printQuality: DLF.LabelWriterPrintQuality.BarcodeAndGraphics,
        flowDirection: DLF.FlowDirection.LeftToRight
    }
    if (selectedPrinter.isTwinTurbo) {
        var left = $container.find('#dlem-printer-roll-left-' + selectedPrinter.listIndex).prop('checked')
        printParams.twinTurboRoll = left ? DLF.TwinTurboRoll.Left : DLF.TwinTurboRoll.Right
    }
    // Print all checked labels
    var $labels = $container.find('input[data-label-include]')
    for (var i = 0; i < $labels.length; i++) {
        var $chk = $($labels[i])
        if ($chk.prop('checked')) {
            var labelNo = Number.parseInt($chk.attr('data-label-include'))
            var labelData = config.print.labels[labelNo]
            printParams.jobTitle = 'Label #' + (labelNo + 1)
            try {
                var labelXml = prepareLabelXml(labelData, calData)
                var printParamsXml = DLF.createLabelWriterPrintParamsXml(printParams)
                if (!config.skipPrinting) {
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
    setUIState($container)
}

/**
 * Prints a sinlge label (from the preview modal)
 * @param {Number} labelNo 
 * @param {JQuery} $container
 */
function printSingleLabel(labelNo, $container) {
    // Get calibration data
    var calData = selectedPrinter.calData
    /** @type {DYMOLabelFramework_PrintParams} */
    var printParams = {
        copies: 1,
        printQuality: DLF.LabelWriterPrintQuality.BarcodeAndGraphics,
        flowDirection: DLF.FlowDirection.LeftToRight
    }
    if (selectedPrinter.isTwinTurbo) {
        var left = $container.find('#dlem-printer-roll-left-' + selectedPrinter.listIndex).prop('checked')
        printParams.twinTurboRoll = left ? DLF.TwinTurboRoll.Left : DLF.TwinTurboRoll.Right
    }
    var labelData = config.print.labels[labelNo]
    printParams.jobTitle = 'Label #' + (labelNo + 1)
    try {
        var labelXml = prepareLabelXml(labelData, calData)
        var printParamsXml = DLF.createLabelWriterPrintParamsXml(printParams)
        if (!config.skipPrinting) {
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
    $container.find('input[data-label-include="' + labelNo + '"]').prop('checked', false)
    setUIState($container)
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
    var z = $('#dlem-widget-modal-print').css('z-index')
    $('#dlem-widget-modal-print').css('z-index', 0)
    dialog('#dlem-modal-preview')
    .then(function() {
        $('#dlem-widget-modal-print').css('z-index', z)
    })

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
            logError('Failed to get calibration data: ' + err)
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
    var z = $('#dlem-widget-modal-print').css('z-index')
    $('#dlem-widget-modal-print').css('z-index', 0)

    dialog('#dlem-modal-calibrate', {}, function($modal, verb) {
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
    .then(function() {
        $('#dlem-widget-modal-print').css('z-index', z)
    })
}

//#endregion

})();