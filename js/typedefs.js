/**
 * @typedef ExternalModules
 * @type {{
 *  DYMOLabelConfig_init?: function(DYMOLabelConfig):void
 *  DYMOLabelPrint_init?: function(DYMOLabelConfig):void
 * }}
 */

/**
 * @typedef DYMOLabelConfig
 * @type {{
 *  debug: boolean
 *  canDownload: boolean
 *  ajax: DYMOLabelAjax
 *  strings: DYMOLabelStrings
 *  print: DYMOLabelPrintData
 *  labels: object { string: LabelData }
 * }}
 */

/**
 * @typedef DYMOLabelStrings
 * @type {{
 *  chooseFile: string
 *  nameRequired: string
 *  actionConfigure: string
 *  actionDownload: string
 *  actionPrint: string
 *  actionDelete: string
 *  noPrinters: string
 *  noLabels: string
 * }}
 */

/**
 * @typedef DYMOLabelPrintData
 * @type {{
 *  template: string
 *  auto: boolean
 *  labels: DYMOLabelItem[][]
 *  errors: string[]
 * }}
 */

/**
 * @typedef DYMOLabelItem
 * @type {{
 *  name: string
 *  type: string
 *  value: string
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


/**
 * @typedef DYMOLabelFramework
 * @type {{
 *  getPrinters: function():DYMOLabelFramework_PrinterInfo[]
 * }}
 */


/**
 * @typedef DYMOLabelFramework_PrinterInfo
 * @type {{
 *  name: string
 *  modelName: string
 *  isConnected: boolean
 *  isLocal: boolean
 *  isTwinTurbo: boolean
 *  originalPrinterName: string
 *  printerType: string
 *  printerUri: string
 * }}
 */


