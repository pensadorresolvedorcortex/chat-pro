import UIKit
import Flutter
import FirebaseCore
import FirebaseMessaging
import UserNotifications

private final class RemoteNotificationStreamHandler: NSObject, FlutterStreamHandler {
  private var eventSink: FlutterEventSink?
  private var bufferedEvents: [[String: Any]] = []

  func onListen(withArguments arguments: Any?, eventSink: @escaping FlutterEventSink) -> FlutterError? {
    self.eventSink = eventSink

    if !bufferedEvents.isEmpty {
      for event in bufferedEvents {
        eventSink(event)
      }
      bufferedEvents.removeAll()
    }

    return nil
  }

  func onCancel(withArguments arguments: Any?) -> FlutterError? {
    eventSink = nil
    return nil
  }

  func send(event: [String: Any]) {
    guard let sink = eventSink else {
      bufferedEvents.append(event)
      return
    }

    sink(event)
  }
}

private final class APNSTokenStreamHandler: NSObject, FlutterStreamHandler {
  private var eventSink: FlutterEventSink?
  private var latestToken: String?

  func onListen(withArguments arguments: Any?, eventSink: @escaping FlutterEventSink) -> FlutterError? {
    self.eventSink = eventSink

    if let token = latestToken {
      eventSink(token)
    }

    return nil
  }

  func onCancel(withArguments arguments: Any?) -> FlutterError? {
    eventSink = nil
    return nil
  }

  func update(token: String) {
    latestToken = token
    eventSink?(token)
  }

  func reset() {
    latestToken = nil
  }

  var currentToken: String? {
    latestToken
  }
}

private final class FCMTokenStreamHandler: NSObject, FlutterStreamHandler {
  private var eventSink: FlutterEventSink?
  private var tokenObserver: NSObjectProtocol?

  override init() {
    super.init()
    tokenObserver = NotificationCenter.default.addObserver(
      forName: NotificationName.refreshedFCMToken,
      object: nil,
      queue: .main
    ) { [weak self] notification in
      guard let token = notification.userInfo?["token"] as? String else { return }
      self?.send(token: token)
    }
  }

  deinit {
    if let observer = tokenObserver {
      NotificationCenter.default.removeObserver(observer)
    }
  }

  func onListen(withArguments arguments: Any?, eventSink: @escaping FlutterEventSink) -> FlutterError? {
    self.eventSink = eventSink

    Messaging.messaging().token { [weak self] token, error in
      guard error == nil, let token else { return }
      self?.send(token: token)
    }

    return nil
  }

  func onCancel(withArguments arguments: Any?) -> FlutterError? {
    eventSink = nil
    return nil
  }

  func send(token: String) {
    eventSink?(token)
  }
}

private enum NotificationName {
  static let refreshedFCMToken = Notification.Name("FCMTokenRefreshed")
}

@UIApplicationMain
@objc class AppDelegate: FlutterAppDelegate, UNUserNotificationCenterDelegate, MessagingDelegate {
  private let fcmTokenStreamHandler = FCMTokenStreamHandler()
  private let fcmEventChannelName = "academy.flutter/fcm_token/events"
  private let fcmMethodChannelName = "academy.flutter/fcm_token/methods"
  private let apnsTokenStreamHandler = APNSTokenStreamHandler()
  private let apnsEventChannelName = "academy.flutter/apns_token/events"
  private let apnsMethodChannelName = "academy.flutter/apns_token/methods"
  private let notificationEventStreamHandler = RemoteNotificationStreamHandler()
  private let notificationEventChannelName = "academy.flutter/notifications/events"
  private let notificationsMethodChannelName = "academy.flutter/notifications/methods"
  private let configMethodChannelName = "academy.flutter/config/methods"
  private let operationsMethodChannelName = "academy.flutter/operations/methods"
  private let notificationCenter = UNUserNotificationCenter.current()
  private lazy var iso8601DateFormatter: ISO8601DateFormatter = {
    let formatter = ISO8601DateFormatter()
    formatter.formatOptions = [.withInternetDateTime, .withFractionalSeconds]
    return formatter
  }()
  private var latestOperationsSnapshot: [String: Any]?
  private var initialNotificationEvent: [String: Any]?
  private var foregroundPresentationOptions: UNNotificationPresentationOptions = []
  private var hasCustomForegroundPresentationOptions = false

  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]? = nil
  ) -> Bool {
    if FirebaseApp.app() == nil {
      FirebaseApp.configure()
    }

    notificationCenter.delegate = self
    notificationCenter.requestAuthorization(options: [.alert, .badge, .sound]) { _, _ in }
    foregroundPresentationOptions = defaultForegroundPresentationOptions()
    hasCustomForegroundPresentationOptions = false

    Messaging.messaging().delegate = self

    application.registerForRemoteNotifications()
    if let controller = window?.rootViewController as? FlutterViewController {
      configureFcmChannels(with: controller)
      configureApnsChannels(with: controller)
      configureNotificationChannel(with: controller)
      configureConfigChannel(with: controller)
      configureOperationsChannel(with: controller)
    }

    if let launchOptions,
       let remoteNotification = launchOptions[.remoteNotification] as? [AnyHashable: Any] {
      if let event = emitNotificationEvent(
        userInfo: remoteNotification,
        content: nil,
        trigger: "launch",
        wasTapped: true,
        actionIdentifier: nil,
        presentationOptions: nil,
        categoryIdentifier: nil,
        userText: nil
      ) {
        initialNotificationEvent = event
      }
    }
    GeneratedPluginRegistrant.register(with: self)
    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  override func application(
    _ application: UIApplication,
    didRegisterForRemoteNotificationsWithDeviceToken deviceToken: Data
  ) {
    Messaging.messaging().apnsToken = deviceToken
    updateApnsToken(with: deviceToken)
    super.application(application, didRegisterForRemoteNotificationsWithDeviceToken: deviceToken)
  }

  override func application(
    _ application: UIApplication,
    didFailToRegisterForRemoteNotificationsWithError error: Error
  ) {
    apnsTokenStreamHandler.reset()
    super.application(application, didFailToRegisterForRemoteNotificationsWithError: error)
  }

  func messaging(_ messaging: Messaging, didReceiveRegistrationToken fcmToken: String?) {
    guard let token = fcmToken else { return }
    NotificationCenter.default.post(
      name: NotificationName.refreshedFCMToken,
      object: nil,
      userInfo: ["token": token]
    )
    fcmTokenStreamHandler.send(token: token)
  }

  func userNotificationCenter(
    _ center: UNUserNotificationCenter,
    willPresent notification: UNNotification,
    withCompletionHandler completionHandler: @escaping (UNNotificationPresentationOptions) -> Void
  ) {
    let presentationOptions: UNNotificationPresentationOptions
    if hasCustomForegroundPresentationOptions {
      presentationOptions = foregroundPresentationOptions
    } else {
      presentationOptions = defaultForegroundPresentationOptions()
    }

    emitNotificationEvent(
      userInfo: notification.request.content.userInfo,
      content: notification.request.content,
      trigger: "willPresent",
      wasTapped: false,
      actionIdentifier: nil,
      presentationOptions: presentationOptions,
      categoryIdentifier: notification.request.content.categoryIdentifier,
      userText: nil
    )

    completionHandler(presentationOptions)
  }

  func userNotificationCenter(
    _ center: UNUserNotificationCenter,
    didReceive response: UNNotificationResponse,
    withCompletionHandler completionHandler: @escaping () -> Void
  ) {
    let textResponse = response as? UNTextInputNotificationResponse

    let event = emitNotificationEvent(
      userInfo: response.notification.request.content.userInfo,
      content: response.notification.request.content,
      trigger: "didReceive",
      wasTapped: true,
      actionIdentifier: response.actionIdentifier,
      presentationOptions: nil,
      categoryIdentifier: response.notification.request.content.categoryIdentifier,
      userText: textResponse?.userText
    )

    if UIApplication.shared.applicationState != .active {
      initialNotificationEvent = event
    }

    completionHandler()
  }

  override func application(
    _ application: UIApplication,
    didReceiveRemoteNotification userInfo: [AnyHashable: Any],
    fetchCompletionHandler completionHandler: @escaping (UIBackgroundFetchResult) -> Void
  ) -> Bool {
    _ = emitNotificationEvent(
      userInfo: userInfo,
      content: nil,
      trigger: "remote",
      wasTapped: false,
      actionIdentifier: nil,
      presentationOptions: nil,
      categoryIdentifier: nil,
      userText: nil
    )

    let handled = super.application(
      application,
      didReceiveRemoteNotification: userInfo,
      fetchCompletionHandler: completionHandler
    )

    if !handled {
      completionHandler(.newData)
      return true
    }

    return handled
  }

  private func configureFcmChannels(with controller: FlutterViewController) {
    let eventChannel = FlutterEventChannel(
      name: fcmEventChannelName,
      binaryMessenger: controller.binaryMessenger
    )
    eventChannel.setStreamHandler(fcmTokenStreamHandler)

    let methodChannel = FlutterMethodChannel(
      name: fcmMethodChannelName,
      binaryMessenger: controller.binaryMessenger
    )
    methodChannel.setMethodCallHandler { call, result in
      switch call.method {
      case "getToken":
        Messaging.messaging().token { token, error in
          if let error {
            DispatchQueue.main.async {
              result(FlutterError(
                code: "token_unavailable",
                message: error.localizedDescription,
                details: nil
              ))
            }
            return
          }

          DispatchQueue.main.async {
            result(token)
          }
        }
      default:
        result(FlutterMethodNotImplemented)
      }
    }
  }

  private func configureApnsChannels(with controller: FlutterViewController) {
    let eventChannel = FlutterEventChannel(
      name: apnsEventChannelName,
      binaryMessenger: controller.binaryMessenger
    )
    eventChannel.setStreamHandler(apnsTokenStreamHandler)

    let methodChannel = FlutterMethodChannel(
      name: apnsMethodChannelName,
      binaryMessenger: controller.binaryMessenger
    )

    methodChannel.setMethodCallHandler { [weak self] call, result in
      guard let self else {
        result(FlutterError(
          code: "apns_unavailable",
          message: "APNS token unavailable",
          details: nil
        ))
        return
      }

      switch call.method {
      case "getToken":
        result(self.apnsTokenStreamHandler.currentToken)
      default:
        result(FlutterMethodNotImplemented)
      }
    }

    if let existingToken = Messaging.messaging().apnsToken {
      updateApnsToken(with: existingToken)
    }
  }

  private func configureNotificationChannel(with controller: FlutterViewController) {
    let eventChannel = FlutterEventChannel(
      name: notificationEventChannelName,
      binaryMessenger: controller.binaryMessenger
    )
    eventChannel.setStreamHandler(notificationEventStreamHandler)

    let methodChannel = FlutterMethodChannel(
      name: notificationsMethodChannelName,
      binaryMessenger: controller.binaryMessenger
    )

    methodChannel.setMethodCallHandler { [weak self] call, result in
      guard let self else {
        result(FlutterError(
          code: "notifications_unavailable",
          message: "Notification center unavailable",
          details: nil
        ))
        return
      }

      switch call.method {
      case "getAuthorizationStatus":
        self.notificationCenter.getNotificationSettings { settings in
          let status = self.authorizationStatusString(from: settings.authorizationStatus)
          DispatchQueue.main.async {
            result(status)
          }
        }
      case "requestAuthorization":
        var options: UNAuthorizationOptions = []
        if let arguments = call.arguments as? [String: Any] {
          let shouldAlert = (arguments["alert"] as? Bool) ?? true
          let shouldBadge = (arguments["badge"] as? Bool) ?? true
          let shouldSound = (arguments["sound"] as? Bool) ?? true
          if shouldAlert { options.insert(.alert) }
          if shouldBadge { options.insert(.badge) }
          if shouldSound { options.insert(.sound) }
        } else {
          options = [.alert, .badge, .sound]
        }

        self.notificationCenter.requestAuthorization(options: options) { _, error in
          if let error {
            DispatchQueue.main.async {
              result(FlutterError(
                code: "authorization_failed",
                message: error.localizedDescription,
                details: nil
              ))
            }
            return
          }

          self.notificationCenter.getNotificationSettings { settings in
            let status = self.authorizationStatusString(from: settings.authorizationStatus)
            DispatchQueue.main.async {
              result(status)
            }
          }
        }
      case "openSettings":
        DispatchQueue.main.async {
          guard let url = URL(string: UIApplication.openSettingsURLString),
                UIApplication.shared.canOpenURL(url) else {
            result(false)
            return
          }

          UIApplication.shared.open(url, options: [:]) { success in
            result(success)
          }
        }
      case "setCategories":
        self.handleSetNotificationCategories(arguments: call.arguments, result: result)
      case "setForegroundPresentationOptions":
        self.handleSetForegroundPresentationOptions(arguments: call.arguments, result: result)
      case "getForegroundPresentationOptions":
        result(self.presentationOptionNames(from: self.foregroundPresentationOptions))
      case "resetForegroundPresentationOptions":
        self.foregroundPresentationOptions = self.defaultForegroundPresentationOptions()
        self.hasCustomForegroundPresentationOptions = false
        result(self.presentationOptionNames(from: self.foregroundPresentationOptions))
      case "listDeliveredNotifications":
        self.notificationCenter.getDeliveredNotifications { notifications in
          let serialized = notifications.compactMap { self.serializeDeliveredNotification($0) }
          DispatchQueue.main.async {
            result(serialized)
          }
        }
      case "removeDeliveredNotifications":
        let identifiers = self.extractIdentifiers(arguments: call.arguments)
        DispatchQueue.main.async {
          self.notificationCenter.removeDeliveredNotifications(withIdentifiers: identifiers)
          result(identifiers)
        }
      case "removeAllDeliveredNotifications":
        DispatchQueue.main.async {
          self.notificationCenter.removeAllDeliveredNotifications()
          result(nil)
        }
      case "listPendingNotificationRequests":
        self.notificationCenter.getPendingNotificationRequests { requests in
          let serialized = requests.compactMap { self.serializePendingNotificationRequest($0) }
          DispatchQueue.main.async {
            result(serialized)
          }
        }
      case "removePendingNotificationRequests":
        let identifiers = self.extractIdentifiers(arguments: call.arguments)
        DispatchQueue.main.async {
          self.notificationCenter.removePendingNotificationRequests(withIdentifiers: identifiers)
          result(identifiers)
        }
      case "getBadgeCount":
        DispatchQueue.main.async {
          result(UIApplication.shared.applicationIconBadgeNumber)
        }
      case "setBadgeCount":
        let badge = self.extractBadgeValue(from: call.arguments) ?? 0
        DispatchQueue.main.async {
          UIApplication.shared.applicationIconBadgeNumber = max(0, badge)
          result(UIApplication.shared.applicationIconBadgeNumber)
        }
      case "incrementBadgeCount":
        let delta = self.extractBadgeValue(from: call.arguments) ?? 1
        DispatchQueue.main.async {
          let current = UIApplication.shared.applicationIconBadgeNumber
          let updated = max(0, current + delta)
          UIApplication.shared.applicationIconBadgeNumber = updated
          result(updated)
        }
      case "clearBadgeCount":
        DispatchQueue.main.async {
          UIApplication.shared.applicationIconBadgeNumber = 0
          result(0)
        }
      case "consumeInitialNotification":
        DispatchQueue.main.async {
          let event = self.initialNotificationEvent
          self.initialNotificationEvent = nil
          result(event)
        }
      default:
        result(FlutterMethodNotImplemented)
      }
    }
  }

  private func authorizationStatusString(from status: UNAuthorizationStatus) -> String {
    switch status {
    case .notDetermined:
      return "notDetermined"
    case .denied:
      return "denied"
    case .authorized:
      return "authorized"
    case .provisional:
      return "provisional"
    case .ephemeral:
      return "ephemeral"
    @unknown default:
      return "notDetermined"
    }
  }

  private func configureOperationsChannel(with controller: FlutterViewController) {
    let methodChannel = FlutterMethodChannel(
      name: operationsMethodChannelName,
      binaryMessenger: controller.binaryMessenger
    )

    methodChannel.setMethodCallHandler { [weak self] call, result in
      guard let self else {
        result(FlutterError(
          code: "operations_unavailable",
          message: "Operations snapshot unavailable",
          details: nil
        ))
        return
      }

      switch call.method {
      case "fetchStatus":
        let payload = self.sanitizedOperationsPayload(self.latestOperationsSnapshot ?? self.loadBundledOperationsStatus())
        result(payload)
      case "refreshStatus":
        self.fetchRemoteOperationsStatus { payload, error in
          DispatchQueue.main.async {
            if let payload {
              result(self.sanitizedOperationsPayload(payload))
              return
            }

            if let error {
              NSLog("[Operations] refresh failed: %@", error.localizedDescription)
            }

            let fallbackPayload = self.sanitizedOperationsPayload(
              self.latestOperationsSnapshot ?? self.loadBundledOperationsStatus()
            )
            result(fallbackPayload)
          }
        }
      default:
        result(FlutterMethodNotImplemented)
      }
    }
  }

  private func configureConfigChannel(with controller: FlutterViewController) {
    let methodChannel = FlutterMethodChannel(
      name: configMethodChannelName,
      binaryMessenger: controller.binaryMessenger
    )

    methodChannel.setMethodCallHandler { [weak self] call, result in
      guard let self else {
        result(FlutterError(
          code: "config_unavailable",
          message: "App configuration unavailable",
          details: nil
        ))
        return
      }

      switch call.method {
      case "getConfig":
        let config = self.loadNativeConfig()
        result(config)
      default:
        result(FlutterMethodNotImplemented)
      }
    }
  }

  private func loadNativeConfig() -> [String: Any] {
    var config: [String: Any] = [:]

    if let apiBaseUrl = sanitizedInfoValue(for: "AcademiaApiBaseUrl") {
      config["apiBaseUrl"] = apiBaseUrl
    }

    if let environment = sanitizedInfoValue(for: "AcademiaEnvironment") {
      config["environment"] = environment
    }

    return config
  }

  private func sanitizedInfoValue(for key: String) -> String? {
    guard let value = Bundle.main.object(forInfoDictionaryKey: key) as? String else {
      return nil
    }

    let trimmed = value.trimmingCharacters(in: .whitespacesAndNewlines)
    return trimmed.isEmpty ? nil : trimmed
  }

  private func loadBundledOperationsStatus() -> [String: Any] {
    guard let dictionary = Bundle.main.object(forInfoDictionaryKey: "AcademiaOperationsStatus") as? [String: Any] else {
      return [:]
    }
    return dictionary
  }

  private func sanitizedOperationsPayload(_ dictionary: [String: Any]) -> [String: Any] {
    var payload = dictionary
    if payload["timestamp"] == nil {
      payload["timestamp"] = iso8601DateFormatter.string(from: Date())
    }
    if payload["baselineTimestamp"] == nil, let bundledTimestamp = dictionary["timestamp"] as? String {
      payload["baselineTimestamp"] = bundledTimestamp
    }
    return payload
  }

  private func fetchRemoteOperationsStatus(
    completion: @escaping ([String: Any]?, Error?) -> Void
  ) {
    guard let baseUrlString = sanitizedInfoValue(for: "AcademiaApiBaseUrl"),
          let baseUrl = URL(string: baseUrlString) else {
      completion(nil, NSError(
        domain: "AcademiaOperations",
        code: -1,
        userInfo: [NSLocalizedDescriptionKey: "API base URL indisponível"],
      ))
      return
    }

    let url = baseUrl.appendingPathComponent("operations/readiness")
    var request = URLRequest(url: url)
    request.httpMethod = "GET"
    request.timeoutInterval = 12

    let task = URLSession.shared.dataTask(with: request) { [weak self] data, _, error in
      if let error {
        completion(nil, error)
        return
      }

      guard
        let data,
        let jsonObject = try? JSONSerialization.jsonObject(with: data) as? [String: Any]
      else {
        completion(nil, NSError(
          domain: "AcademiaOperations",
          code: -2,
          userInfo: [NSLocalizedDescriptionKey: "Resposta inválida"],
        ))
        return
      }

      self?.latestOperationsSnapshot = jsonObject
      completion(jsonObject, nil)
    }

    task.resume()
  }

  private func updateApnsToken(with data: Data) {
    let token = data.map { String(format: "%02.2hhx", $0) }.joined()
    if Thread.isMainThread {
      apnsTokenStreamHandler.update(token: token)
    } else {
      DispatchQueue.main.async { [weak self] in
        self?.apnsTokenStreamHandler.update(token: token)
      }
    }
  }

  private func handleSetNotificationCategories(
    arguments: Any?,
    result: @escaping FlutterResult
  ) {
    let rawCategories: [[String: Any]]
    if let payload = arguments as? [String: Any] {
      if let provided = payload["categories"] as? [[String: Any]] {
        rawCategories = provided
      } else if payload["categories"] == nil {
        rawCategories = []
      } else {
        result(FlutterError(
          code: "invalid_categories_payload",
          message: "Expected 'categories' to be a list",
          details: nil
        ))
        return
      }
    } else if arguments == nil {
      rawCategories = []
    } else {
      result(FlutterError(
        code: "invalid_categories_payload",
        message: "Expected argument dictionary with 'categories'",
        details: nil
      ))
      return
    }

    let categories = rawCategories.compactMap { buildNotificationCategory(from: $0) }
    let identifiers = categories.map { $0.identifier }

    DispatchQueue.main.async {
      let categorySet = Set(categories)
      self.notificationCenter.setNotificationCategories(categorySet)
      result(identifiers)
    }
  }

  private func handleSetForegroundPresentationOptions(
    arguments: Any?,
    result: @escaping FlutterResult
  ) {
    let names: [String]
    if let dictionary = arguments as? [String: Any] {
      if let raw = dictionary["options"] {
        names = extractStringArray(from: raw)
      } else if dictionary["options"] == nil {
        names = []
      } else {
        result(FlutterError(
          code: "invalid_presentation_options",
          message: "Expected 'options' to be a list of strings",
          details: nil
        ))
        return
      }
    } else if arguments == nil {
      names = []
    } else {
      names = extractStringArray(from: arguments)
    }

    let options = presentationOptions(from: names)

    DispatchQueue.main.async {
      self.foregroundPresentationOptions = options
      self.hasCustomForegroundPresentationOptions = true
      result(self.presentationOptionNames(from: options))
    }
  }

  private func buildNotificationCategory(
    from dictionary: [String: Any]
  ) -> UNNotificationCategory? {
    guard let rawIdentifier = dictionary["identifier"] as? String else { return nil }
    let identifier = rawIdentifier.trimmingCharacters(in: .whitespacesAndNewlines)
    guard !identifier.isEmpty else { return nil }

    let actionsPayload = dictionary["actions"] as? [[String: Any]] ?? []
    let actions = actionsPayload.compactMap { buildNotificationAction(from: $0) }
    let intentIdentifiers = dictionary["intentIdentifiers"] as? [String] ?? []
    let options = notificationCategoryOptions(from: dictionary["options"] as? [String] ?? [])
    let placeholder = dictionary["hiddenPreviewsBodyPlaceholder"] as? String
    let summaryFormat = dictionary["categorySummaryFormat"] as? String

    if #available(iOS 12.0, *) {
      if placeholder != nil || summaryFormat != nil {
        return UNNotificationCategory(
          identifier: identifier,
          actions: actions,
          intentIdentifiers: intentIdentifiers,
          hiddenPreviewsBodyPlaceholder: placeholder,
          categorySummaryFormat: summaryFormat,
          options: options
        )
      }
    }

    if #available(iOS 11.0, *), let placeholder {
      return UNNotificationCategory(
        identifier: identifier,
        actions: actions,
        intentIdentifiers: intentIdentifiers,
        hiddenPreviewsBodyPlaceholder: placeholder,
        options: options
      )
    }

    return UNNotificationCategory(
      identifier: identifier,
      actions: actions,
      intentIdentifiers: intentIdentifiers,
      options: options
    )
  }

  private func extractStringArray(from value: Any) -> [String] {
    if let strings = value as? [String] {
      return strings
    }

    if let anyArray = value as? [Any] {
      return anyArray.compactMap { element -> String? in
        guard let string = element as? String else { return nil }
        let trimmed = string.trimmingCharacters(in: .whitespacesAndNewlines)
        return trimmed.isEmpty ? nil : trimmed
      }
    }

    return []
  }

  private func extractIdentifiers(arguments: Any?) -> [String] {
    if let dictionary = arguments as? [String: Any], let payload = dictionary["identifiers"] {
      return extractStringArray(from: payload)
    }

    if let arguments {
      return extractStringArray(from: arguments)
    }

    return []
  }

  private func extractBadgeValue(from arguments: Any?) -> Int? {
    if let dictionary = arguments as? [String: Any], let value = dictionary["badge"] {
      return extractInteger(from: value)
    }

    if let arguments {
      return extractInteger(from: arguments)
    }

    return nil
  }

  private func extractInteger(from value: Any) -> Int? {
    switch value {
    case let number as NSNumber:
      return number.intValue
    case let string as String:
      return Int(string.trimmingCharacters(in: .whitespacesAndNewlines))
    case let doubleValue as Double:
      return Int(doubleValue)
    case let intValue as Int:
      return intValue
    default:
      return nil
    }
  }

  private func buildNotificationAction(
    from dictionary: [String: Any]
  ) -> UNNotificationAction? {
    guard let rawIdentifier = dictionary["identifier"] as? String else { return nil }
    let identifier = rawIdentifier.trimmingCharacters(in: .whitespacesAndNewlines)
    guard !identifier.isEmpty else { return nil }

    guard let title = dictionary["title"] as? String else { return nil }

    let options = notificationActionOptions(from: dictionary["options"] as? [String] ?? [])

    if let textInput = dictionary["textInput"] as? [String: Any] {
      let buttonTitle = (textInput["buttonTitle"] as? String) ?? title
      let placeholder = textInput["placeholder"] as? String ?? ""
      return UNTextInputNotificationAction(
        identifier: identifier,
        title: title,
        options: options,
        textInputButtonTitle: buttonTitle,
        textInputPlaceholder: placeholder
      )
    }

    return UNNotificationAction(
      identifier: identifier,
      title: title,
      options: options
    )
  }

  private func notificationActionOptions(from names: [String]) -> UNNotificationActionOptions {
    var options: UNNotificationActionOptions = []
    for name in names {
      switch name {
      case "foreground":
        options.insert(.foreground)
      case "destructive":
        options.insert(.destructive)
      case "authenticationRequired":
        options.insert(.authenticationRequired)
      default:
        continue
      }
    }
    return options
  }

  private func notificationCategoryOptions(from names: [String]) -> UNNotificationCategoryOptions {
    var options: UNNotificationCategoryOptions = []
    for name in names {
      switch name {
      case "customDismissAction":
        options.insert(.customDismissAction)
      case "allowInCarPlay":
        options.insert(.allowInCarPlay)
      case "hiddenPreviewsShowTitle":
        if #available(iOS 11.0, *) {
          options.insert(.hiddenPreviewsShowTitle)
        }
      case "hiddenPreviewsShowSubtitle":
        if #available(iOS 11.0, *) {
          options.insert(.hiddenPreviewsShowSubtitle)
        }
      case "allowAnnouncement":
        if #available(iOS 13.0, *) {
          options.insert(.allowAnnouncement)
        }
      default:
        continue
      }
    }
    return options
  }

  @discardableResult
  private func emitNotificationEvent(
    userInfo: [AnyHashable: Any],
    content: UNNotificationContent?,
    trigger: String,
    wasTapped: Bool,
    actionIdentifier: String?,
    presentationOptions: UNNotificationPresentationOptions?,
    categoryIdentifier: String?,
    userText: String?
  ) -> [String: Any]? {
    guard let event = buildNotificationEvent(
      userInfo: userInfo,
      content: content,
      trigger: trigger,
      wasTapped: wasTapped,
      actionIdentifier: actionIdentifier,
      presentationOptions: presentationOptions,
      categoryIdentifier: categoryIdentifier,
      userText: userText
    ) else {
      return nil
    }

    notificationEventStreamHandler.send(event: event)
    return event
  }

  private func serializeDeliveredNotification(_ notification: UNNotification) -> [String: Any]? {
    var result: [String: Any] = [
      "identifier": notification.request.identifier,
      "trigger": triggerName(for: notification.request.trigger),
      "deliveredAt": iso8601DateFormatter.string(from: notification.date),
    ]

    if let sanitizedContent = sanitize(content: notification.request.content) {
      result["content"] = sanitizedContent
    }

    if let sanitizedUserInfo = sanitize(userInfo: notification.request.content.userInfo) {
      result["userInfo"] = sanitizedUserInfo
    }

    if let category = trimmedString(notification.request.content.categoryIdentifier) {
      result["categoryIdentifier"] = category
    }

    result["source"] = notificationSource(for: notification.request.content.userInfo)

    return result
  }

  private func serializePendingNotificationRequest(_ request: UNNotificationRequest) -> [String: Any]? {
    var result: [String: Any] = [
      "identifier": request.identifier,
      "trigger": triggerName(for: request.trigger),
      "repeats": request.trigger?.repeats ?? false,
    ]

    if let nextTriggerDate = nextTriggerDateString(for: request.trigger) {
      result["nextTriggerDate"] = nextTriggerDate
    }

    if let sanitizedContent = sanitize(content: request.content) {
      result["content"] = sanitizedContent
    }

    if let sanitizedUserInfo = sanitize(userInfo: request.content.userInfo) {
      result["userInfo"] = sanitizedUserInfo
    }

    if let category = trimmedString(request.content.categoryIdentifier) {
      result["categoryIdentifier"] = category
    }

    result["source"] = notificationSource(for: request.content.userInfo)

    return result
  }

  private func buildNotificationEvent(
    userInfo: [AnyHashable: Any],
    content: UNNotificationContent?,
    trigger: String,
    wasTapped: Bool,
    actionIdentifier: String?,
    presentationOptions: UNNotificationPresentationOptions?,
    categoryIdentifier: String?,
    userText: String?
  ) -> [String: Any]? {
    guard let sanitizedUserInfo = sanitize(userInfo: userInfo) else { return nil }

    var event: [String: Any] = [
      "userInfo": sanitizedUserInfo,
      "trigger": trigger,
      "wasTapped": wasTapped,
      "source": notificationSource(for: userInfo),
    ]

    event["receivedAt"] = iso8601DateFormatter.string(from: Date())
    event["applicationState"] = applicationStateName()

    if let actionIdentifier, actionIdentifier != UNNotificationDefaultActionIdentifier {
      event["actionIdentifier"] = actionIdentifier
    }

    if let categoryIdentifier, !categoryIdentifier.isEmpty {
      event["categoryIdentifier"] = categoryIdentifier
    }

    if let options = presentationOptions, !options.isEmpty {
      event["presentationOptions"] = presentationOptionNames(from: options)
    }

    if let sanitizedContent = content.flatMap({ sanitize(content: $0) }) {
      event["content"] = sanitizedContent
    }

    if let sanitizedText = sanitizedUserText(from: userText) {
      event["userText"] = sanitizedText
    }

    return event
  }

  private func triggerName(for trigger: UNNotificationTrigger?) -> String {
    guard let trigger else { return "unknown" }

    switch trigger {
    case is UNPushNotificationTrigger:
      return "push"
    case is UNTimeIntervalNotificationTrigger:
      return "timeInterval"
    case is UNCalendarNotificationTrigger:
      return "calendar"
    case is UNLocationNotificationTrigger:
      return "location"
    default:
      return "unknown"
    }
  }

  private func nextTriggerDateString(for trigger: UNNotificationTrigger?) -> String? {
    guard let trigger, let date = trigger.nextTriggerDate() else { return nil }
    return iso8601DateFormatter.string(from: date)
  }

  private func sanitizedUserText(from text: String?) -> String? {
    guard let text = text?.trimmingCharacters(in: .whitespacesAndNewlines), !text.isEmpty else {
      return nil
    }
    return text
  }

  private func sanitize(userInfo: [AnyHashable: Any]) -> [String: Any]? {
    let sanitized = sanitizeDictionary(userInfo)
    return sanitized.isEmpty ? nil : sanitized
  }

  private func sanitize(content: UNNotificationContent) -> [String: Any]? {
    var result: [String: Any] = [:]

    if let title = trimmedString(content.title) {
      result["title"] = title
    }

    if let subtitle = trimmedString(content.subtitle) {
      result["subtitle"] = subtitle
    }

    if let body = trimmedString(content.body) {
      result["body"] = body
    }

    if let launchImage = trimmedString(content.launchImageName) {
      result["launchImageName"] = launchImage
    }

    if let threadIdentifier = trimmedString(content.threadIdentifier) {
      result["threadIdentifier"] = threadIdentifier
    }

    if let badgeNumber = content.badge?.intValue {
      result["badge"] = badgeNumber
    }

    if #available(iOS 12.0, *) {
      if let summaryArgument = trimmedString(content.summaryArgument) {
        result["summaryArgument"] = summaryArgument
      }

      if content.summaryArgumentCount != 0 {
        result["summaryArgumentCount"] = content.summaryArgumentCount
      }
    }

    if #available(iOS 15.0, *), let targetIdentifier = trimmedString(content.targetContentIdentifier) {
      result["targetContentIdentifier"] = targetIdentifier
    }

    let attachments = content.attachments.compactMap { sanitize(attachment: $0) }
    if !attachments.isEmpty {
      result["attachments"] = attachments
    }

    return result.isEmpty ? nil : result
  }

  private func sanitize(attachment: UNNotificationAttachment) -> [String: Any]? {
    var result: [String: Any] = ["identifier": attachment.identifier]

    let urlString = attachment.url.absoluteString
    if !urlString.isEmpty {
      result["url"] = urlString
    }

    if !attachment.type.isEmpty {
      result["type"] = attachment.type
    }

    let fileName = attachment.url.lastPathComponent
    if !fileName.isEmpty {
      result["name"] = fileName
    }

    return result
  }

  private func sanitizeDictionary(_ dictionary: [AnyHashable: Any]) -> [String: Any] {
    var result: [String: Any] = [:]

    for (key, value) in dictionary {
      guard let keyString = key as? String else { continue }
      if let converted = sanitizeValue(value) {
        result[keyString] = converted
      }
    }

    return result
  }

  private func sanitizeValue(_ value: Any) -> Any? {
    switch value {
    case let string as String:
      return string
    case let number as NSNumber:
      return number
    case let dict as [AnyHashable: Any]:
      let nested = sanitizeDictionary(dict)
      return nested.isEmpty ? nil : nested
    case let array as [Any]:
      return array.compactMap { sanitizeValue($0) }
    case let date as Date:
      return iso8601DateFormatter.string(from: date)
    case let data as Data:
      return data.base64EncodedString()
    case _ as NSNull:
      return NSNull()
    default:
      return String(describing: value)
    }
  }

  private func trimmedString(_ value: String?) -> String? {
    guard let value = value?.trimmingCharacters(in: .whitespacesAndNewlines), !value.isEmpty else {
      return nil
    }
    return value
  }

  private func notificationSource(for userInfo: [AnyHashable: Any]) -> String {
    if userInfo.isEmpty {
      return "local"
    }

    if userInfo["gcm.message_id"] != nil || userInfo["google.c.a.c_id"] != nil {
      return "fcm"
    }
    return "apns"
  }

  private func presentationOptionNames(
    from options: UNNotificationPresentationOptions
  ) -> [String] {
    var names: [String] = []

    if options.contains(.alert) {
      names.append("alert")
    }

    if options.contains(.sound) {
      names.append("sound")
    }

    if options.contains(.badge) {
      names.append("badge")
    }

    if #available(iOS 14.0, *) {
      if options.contains(.banner) {
        names.append("banner")
      }

      if options.contains(.list) {
        names.append("list")
      }
    }

    if #available(iOS 15.0, *) {
      if options.contains(.announcement) {
        names.append("announcement")
      }
    }

    if #available(iOS 16.0, *) {
      if options.contains(.timeSensitive) {
        names.append("timeSensitive")
      }

      if options.contains(.criticalAlert) {
        names.append("criticalAlert")
      }
    }

    return names
  }

  private func presentationOptions(from names: [String]) -> UNNotificationPresentationOptions {
    if names.isEmpty {
      return []
    }

    var options: UNNotificationPresentationOptions = []

    for name in names {
      switch name {
      case "alert":
        options.insert(.alert)
      case "sound":
        options.insert(.sound)
      case "badge":
        options.insert(.badge)
      case "banner":
        if #available(iOS 14.0, *) {
          options.insert(.banner)
        }
      case "list":
        if #available(iOS 14.0, *) {
          options.insert(.list)
        }
      case "announcement":
        if #available(iOS 15.0, *) {
          options.insert(.announcement)
        }
      case "timeSensitive":
        if #available(iOS 16.0, *) {
          options.insert(.timeSensitive)
        }
      case "criticalAlert":
        if #available(iOS 16.0, *) {
          options.insert(.criticalAlert)
        }
      default:
        continue
      }
    }

    return options
  }

  private func defaultForegroundPresentationOptions() -> UNNotificationPresentationOptions {
    if #available(iOS 14.0, *) {
      return [.banner, .list, .sound, .badge]
    }

    return [.alert, .sound, .badge]
  }

  private func applicationStateName() -> String {
    switch UIApplication.shared.applicationState {
    case .active:
      return "active"
    case .background:
      return "background"
    case .inactive:
      return "inactive"
    @unknown default:
      return "unknown"
    }
  }
}
