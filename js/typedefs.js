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
 *  labels: Object<string, LabelData>
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
 *  removed: string
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
 *  png: string
 * }}
 */

/**
 * @typedef DYMOLabelCalibration
 * @type {{
 *  dx: Number
 *  dy: Number
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
 *  init: function()
 *  checkEnvironment: function():DYMOLabelFramework_Status
 * }}
 */

/**
 * @typedef DYMOLabelFramework_Status
 * @type {{
 *  errorDetails: string
 *  isBrowserSupported: boolean
 *  isFrameworkInstalled: boolean
 *  isWebServicePresent: boolean
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
 *  calData: DYMOLabelCalibration
 * }}
 */


