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
 *  skipPrinting: boolean
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
 *  actionInfo: string
 *  actionRename: string
 *  actionConfigure: string
 *  actionDownload: string
 *  actionPrint: string
 *  actionDelete: string
 *  noPrinters: string
 *  noLabels: string
 *  removed: string
 *  toastLabelAdded: string
 *  toastLabelRenamed: string
 *  toastLabelUpdated: string
 *  toastLabelDeleted: string
 *  actionTagReplace: string
 * }}
 */

/**
 * @typedef DYMOLabelPrintData
 * @type {{
 *  template: string
 *  auto: boolean
 *  labels: DYMOLabelItem[][]
 *  errors: string[]
 *  skipPrinting: boolean
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
 *  config: LabelConfig
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
 *  config: LabelConfig
 * }}
 */

/**
 * @typedef LabelConfig
 * @type {{
 *  public?: boolean
 *  objects?: Object<string, LabelObjectInfo>
 * }}
 */

/**
 * @typedef LabelObjectInfo
 * @type {{
 *  name: string
 *  desc: string
 *  type: 'Text' | 'Graphic' 
 *  transform: 'T' | 'PNG' | 'QR' | 'DM' | 'R'
 *  default: string
 *  readOnly: boolean
 *  multiline: boolean
 *  allowEmpty: boolean
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
 *  label: LabelData
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
 *  openLabelXml: function(string):DYMOLabelFramework_Label
 *  printLabel: function(string, string, string, string)
 *  renderLabel: function(string, string, string)
 *  createLabelRenderParamsXml: function(DYMOLabelFramework_RenderParams):string
 *  createLabelWriterPrintParamsXml: function(DYMOLabelFramework_PrintParams):string
 *  FlowDirection: { LeftToRight: string, RightToLeft: string }
 *  LabelWriterPrintQuality: { Text: string, BarcodeAndGraphics: string, Auto: string }
 *  TwinTurboRoll: { Left: string, Right: string, Auto: string }
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
 * @typedef DYMOLabelFramework_PrintParams
 * @type {{
 *  copies?: Number 
 *  jobTitle?: string
 *  flowDirection?: string
 *  printQuality?: string
 *  twinTurboRoll?: string
 * }}
 */

/**
 * @typedef DYMOLabelFramework_Color
 * @type {{
 *  alpha: Number 
 *  red: Number 
 *  green: Number 
 *  blue: Number 
 * }}
 */

/**
 * @typedef DYMOLabelFramework_RenderParams
 * @type {{
 *  labelColor?: DYMOLabelFramework_Color
 *  shadowColor?: DYMOLabelFramework_Color
 *  shadowDepth?: Number
 *  flowDirection?: string
 *  pngUseDisplayResolution?: boolean
 * }}
 */

/**
 * @typedef DYMOLabelFramework_Label
 * @type {{
 *  isValidLabel: function():boolean
 *  isDCDLabel: function():boolean
 *  isDLSLabel: function():boolean
 *  print: function(string,string,string):void
 *  render: function(string,string):string
 *  setObjectText: function(string,string):void
 *  getObjectText: function(string):string
 *  getObjectNames: function():string[]
 *  getLabelXml: function():string
 *  _doc: HTMLDocument
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
 *  listIndex: string
 * }}
 */

/**
 * @typedef DialogHelper
 * @type {{
 *  modal: JQuery<HTMLElement>
 *  enable: function(boolean?,boolean?):void
 * }}
 */


