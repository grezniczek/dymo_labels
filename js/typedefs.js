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
 *  labels: object { string: LabelData }
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
 * @typedef LabelData
 * @type {{
 *  id: string
 *  name: string
 *  desc: string
 *  filename: string
 *  xml: string
 * }}
 */

/**
 * @typedef DYMOLabelAjax
 * @type {{
 *  endpoint: string
 *  verification: string
 * }}
 */

 
/**
 * @typedef AddLabelResponse
 * @type {{
 *  success: boolean
 *  id: string
 * }}
 */

/**
 * @typedef DeleteLabelResponse
 * @type {{
 *  success: boolean
 *  id: string
 * }}
 */

/**
 * @typedef GetLabelsResponse
 * @type {{
 *  success: boolean
 *  count: integer
 *  labels: object { string: LabelData }
 * }}
 */


