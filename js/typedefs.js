/**
 * @typedef ExternalModules
 * @type {{
 *  DYMOLabelConfig_init?: function(DYMOLabelConfig):void
 * }}
 */

/**
 * @typedef DYMOLabelConfig
 * @type {{
 *  debug: boolean
 *  ajax: DYMOLabelAjax
 *  strings: DYMOLabelStrings
 * }}
 */

/**
 * @typedef DYMOLabelStrings
 * @type {{
 *  chooseFile: string
 *  nameRequired: string
 * }}
 */

/**
 * @typedef AddLabelState
 * @type {{
 *  name: string
 *  desc: string
 *  filename: string
 *  xml: string
 *  valid: boolean
 * }}
 */

/**
 * @typedef DYMOLabelAjax
 * @type {{
 *  endpoint: string
 *  verification: string
 * }}
 */
