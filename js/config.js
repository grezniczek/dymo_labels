/* DYMO Label EM - Project Configuration */
// @ts-check
;(function() {

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

/**
 * Adds a new label.
 */
function addNewLabel() {
    checkAddState()
    if (addState.valid) {
        submitData('add-label', addState, addLabelDone, addLabelFailed)
    }
}

function addLabelDone(response) {
    addState.xml = ''
    addState.name = ''
    addState.desc = ''
    addState.filename = ''
    checkAddState()
    log('addLabelDone')
}

function addLabelFailed(error) {
    log('addLabelFailed')
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
    $('#dlem-addlabelbtn').on('click', function() {
        addNewLabel()
    })

}

})();